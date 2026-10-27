<?php

declare(strict_types=1);

namespace Ephraitech\Forms;

use Ephraitech\Forms\Config\Forms;
use Ephraitech\Forms\Contracts\FieldInterface;
use Ephraitech\Forms\Fields\Checkbox;
use Ephraitech\Forms\Fields\InputField;
use Ephraitech\Forms\Fields\Radio;
use Ephraitech\Forms\Fields\Select;
use Stringable;

/**
 * Turns forms and fields into HTML using the active theme's views.
 *
 * All logic (values, classes, attributes, escaping) happens here, so theme
 * views stay simple templates. Every field view receives:
 *
 * - $field, $form           The field and form objects
 * - $name, $id, $label      Field name, element id and label text (unescaped)
 * - $value                  Resolved value as a string (unescaped)
 * - $requiredMarker         Raw HTML marker for required fields, or ''
 * - $error, $errorId        Error message (unescaped) or null, and its id
 * - $help, $helpId          Help text (unescaped) or null, and its id
 * - $attributes             Extra attributes, already escaped, with a leading space
 * - $bare                   True when only the control is wanted (no label/wrapper)
 * - $e                      Escaper: $e($text) for text and double-quoted attributes
 * - $class                  $class('group') returns ' class="..."' or ''
 *
 * plus, by field type: $inputType, $placeholder, $options, $optionIds,
 * $checked, $checkedValue and $inline.
 */
class Renderer
{
    /**
     * Theme class key for each field type's main control.
     */
    private const CONTROL_CLASS = [
        'textarea' => 'textarea',
        'select'   => 'select',
        'checkbox' => 'check_input',
        'radio'    => 'check_input',
        'submit'   => 'submit',
        'hidden'   => '',
    ];

    public function __construct(protected Forms $config)
    {
    }

    /**
     * Escapes text for HTML content and double-quoted attribute values,
     * producing the same entities as CodeIgniter's esc().
     */
    public static function escape(mixed $value): string
    {
        return htmlspecialchars(self::stringify($value), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Builds an escaped attribute string with a leading space. True renders
     * a bare attribute, false and null are skipped.
     *
     * @param array<string, bool|float|int|string|Stringable|null> $attributes
     */
    public static function attributes(array $attributes): string
    {
        $html = '';

        foreach ($attributes as $name => $value) {
            if ($value === null || $value === false) {
                continue;
            }

            $html .= $value === true
                ? ' ' . $name
                : ' ' . $name . '="' . self::escape($value) . '"';
        }

        return $html;
    }

    /**
     * Opening <form> tag plus the hidden form key, CSRF and _method fields.
     *
     * @param array<string, bool|float|int|string|Stringable|null> $attributes
     */
    public function open(Form $form, array $attributes = []): string
    {
        $classes = $this->joinClasses($this->config->classFor('form'), ...$form->getClasses());

        $tag = array_merge(
            [
                'action'     => $this->resolveAction($form->getAction()),
                'method'     => $form->getHtmlMethod(),
                'id'         => $form->getId(),
                'class'      => $classes === '' ? null : $classes,
                'enctype'    => $form->isMultipart() ? 'multipart/form-data' : null,
                'novalidate' => $this->config->novalidate,
            ],
            $form->getAttributes(),
            $attributes,
        );

        $html = '<form' . self::attributes($tag) . ">\n";
        $html .= '<input type="hidden" name="' . Form::FORM_KEY . '" value="' . self::escape($form->getName()) . "\">\n";

        if ($form->needsCsrf()) {
            $html .= csrf_field() . "\n";
        }

        $spoofed = $form->getSpoofedMethod();

        if ($spoofed !== null) {
            $html .= '<input type="hidden" name="_method" value="' . self::escape($spoofed) . "\">\n";
        }

        return $html;
    }

    public function close(Form $form): string
    {
        return "</form>\n";
    }

    /**
     * One field. With $bare only the control is rendered, without label,
     * wrapper, error or help text, for fully custom layouts.
     */
    public function field(Form $form, string $name, bool $bare = false): string
    {
        $field = $form->getField($name);

        return $this->view($field->getView(), $this->fieldData($form, $field, $bare));
    }

    /**
     * Several fields in order; all fields when no names are given.
     */
    public function fields(Form $form, string ...$names): string
    {
        $names = $names === [] ? array_keys($form->getFields()) : $names;
        $html  = '';

        foreach ($names as $name) {
            $html .= $this->field($form, $name);
        }

        return $html;
    }

    /**
     * A list of every current error, linking to the fields. Empty when the
     * form has no errors.
     */
    public function errorSummary(Form $form): string
    {
        $errors = $form->getErrors();

        if ($errors === []) {
            return '';
        }

        $items = [];

        foreach ($errors as $name => $message) {
            $items[] = [
                'message' => $message,
                'target'  => $form->has($name) ? $form->getField($name)->getId($form->getIdPrefix()) : null,
            ];
        }

        return $this->view('error_summary', [
            'form'  => $form,
            'items' => $items,
            'e'     => self::escape(...),
            'class' => fn (string $key): string => $this->classAttribute($this->config->classFor($key)),
        ]);
    }

    /**
     * The whole form: opening tag, optional error summary, every field and
     * the closing tag.
     */
    public function form(Form $form): string
    {
        return $this->view('form', [
            'form'        => $form,
            'showSummary' => $this->config->showErrorSummary,
        ]);
    }

    /**
     * Data passed to a field's view.
     *
     * @return array<string, mixed>
     */
    protected function fieldData(Form $form, FieldInterface $field, bool $bare): array
    {
        $id      = $field->getId($form->getIdPrefix());
        $error   = $form->getError($field->getName());
        $help    = $field->getHelp();
        $errorId = $id . '_error';
        $helpId  = $id . '_help';
        $type    = $field->getType();

        $describedBy = array_filter([
            $error !== null && ! $bare ? $errorId : null,
            $help !== null && ! $bare ? $helpId : null,
        ]);

        $attributes = array_merge(
            $field->ruleAttributes($this->config),
            $field->getAttributes(),
            [
                'placeholder'      => in_array($type, ['select', 'radio', 'checkbox', 'hidden', 'submit'], true) ? null : $field->getPlaceholder(),
                'disabled'         => $field->isDisabled(),
                'aria-invalid'     => $error !== null ? 'true' : null,
                'aria-describedby' => $describedBy === [] ? null : implode(' ', $describedBy),
            ],
        );

        $classes = $this->themeClasses($field, $error !== null);

        $data = [
            'form'           => $form,
            'field'          => $field,
            'name'           => $field->getName(),
            'id'             => $id,
            'label'          => $field->getLabel(),
            'value'          => self::stringify($form->getValue($field->getName())),
            'requiredMarker' => $field->isRequired() ? $this->config->activeTheme()['required_marker'] : '',
            'error'          => $error,
            'errorId'        => $errorId,
            'help'           => $help,
            'helpId'         => $helpId,
            'attributes'     => self::attributes($attributes),
            'bare'           => $bare,
            'e'              => self::escape(...),
            'class'          => fn (string $key): string => $this->classAttribute($classes[$key] ?? ''),
            'placeholder'    => $field->getPlaceholder(),
            'inputType'      => $field instanceof InputField ? $field->getInputType() : 'text',
            'options'        => [],
            'optionIds'      => [],
            'checked'        => false,
            'checkedValue'   => '1',
            'inline'         => false,
        ];

        if ($field instanceof Select || $field instanceof Radio) {
            $data['options'] = $field->getOptions();
        }

        if ($field instanceof Radio) {
            $data['inline'] = $field->isInline();

            foreach (array_keys($data['options']) as $value) {
                $data['optionIds'][(string) $value] = $field->getOptionId((string) $value, $form->getIdPrefix());
            }
        }

        if ($field instanceof Checkbox) {
            $data['checked']      = $field->isChecked($form->getValue($field->getName()));
            $data['checkedValue'] = $field->getCheckedValue();
            $data['inline']       = $field->isInline();
        }

        return $data;
    }

    /**
     * Class strings for every element of a field's markup.
     *
     * @return array<string, string>
     */
    protected function themeClasses(FieldInterface $field, bool $invalid): array
    {
        $type       = $field->getType();
        $controlKey = self::CONTROL_CLASS[$type] ?? 'input';
        $isCheck    = in_array($type, ['checkbox', 'radio'], true);
        $inline     = ($field instanceof Checkbox || $field instanceof Radio) && $field->isInline();

        return [
            'group'       => $this->config->classFor($type === 'radio' ? 'fieldset' : 'group'),
            'label'       => $this->config->classFor('label'),
            'legend'      => $this->config->classFor('legend'),
            'control'     => $this->joinClasses(
                $controlKey === '' ? '' : $this->config->classFor($controlKey),
                ...[...$field->getClasses(), $invalid ? $this->config->classFor('invalid') : ''],
            ),
            'wrapper'     => $isCheck
                ? $this->joinClasses($this->config->classFor('check_wrapper'), $inline ? $this->config->classFor('check_inline') : '')
                : '',
            'check_label' => $this->config->classFor('check_label'),
            'error'       => $this->config->classFor($type === 'radio' ? 'error_block' : 'error'),
            'help'        => $this->config->classFor('help'),
        ];
    }

    /**
     * Renders a theme view without leaking data between renders.
     *
     * @param array<string, mixed> $data
     */
    protected function view(string $template, array $data): string
    {
        return view($this->config->viewPath($template), $data, ['saveData' => false]);
    }

    protected function resolveAction(?string $action): ?string
    {
        if ($action === null) {
            return null;
        }

        if (preg_match('#^([a-z][a-z0-9+.\-]*:|//|\#|\?)#i', $action) === 1) {
            return $action;
        }

        helper('url');

        return site_url($action);
    }

    private function classAttribute(string $classes): string
    {
        return $classes === '' ? '' : ' class="' . self::escape($classes) . '"';
    }

    private function joinClasses(string ...$classes): string
    {
        $unique = [];

        foreach ($classes as $class) {
            foreach (preg_split('/\s+/', trim($class)) ?: [] as $single) {
                if ($single !== '' && ! in_array($single, $unique, true)) {
                    $unique[] = $single;
                }
            }
        }

        return implode(' ', $unique);
    }

    private static function stringify(mixed $value): string
    {
        return match (true) {
            $value === null           => '',
            is_bool($value)           => $value ? '1' : '',
            is_scalar($value)         => (string) $value,
            $value instanceof Stringable => (string) $value,
            default                   => '',
        };
    }
}
