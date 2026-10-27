<?php

declare(strict_types=1);

namespace Ephraitech\Forms;

use Ephraitech\Forms\Config\Forms;
use Ephraitech\Forms\Contracts\FieldInterface;
use Ephraitech\Forms\Contracts\FileFieldInterface;
use Ephraitech\Forms\Fields\Checkbox;
use Ephraitech\Forms\Fields\Checkboxes;
use Ephraitech\Forms\Fields\InputField;
use Ephraitech\Forms\Fields\Radio;
use Ephraitech\Forms\Fields\Select;
use Ephraitech\Forms\Layout\Container;
use Ephraitech\Forms\Layout\Fieldset;
use Ephraitech\Forms\Layout\Row;
use Stringable;

/**
 * Turns forms and fields into HTML using the active theme's views.
 *
 * All logic (values, classes, attributes, escaping) happens here, so theme
 * views stay simple templates. Every field view receives:
 *
 * - $field, $form           The field and form objects
 * - $name, $id, $label      Field name, element id and label text (unescaped)
 * - $inputName              Name for the HTML name attribute: "tags[]" for
 *                           multi-value fields, otherwise the same as $name
 * - $value                  Resolved value as a string (unescaped); '' for lists
 * - $values                 Resolved value as a list of strings: every selected
 *                           or ticked option for multi-value fields, [$value] otherwise
 * - $multiple               Whether the field holds a list
 * - $requiredMarker         Raw HTML marker for required fields, or ''
 * - $error, $errorId        Error message (unescaped) or null, and its id
 * - $help, $helpId          Help text (unescaped) or null, and its id
 * - $attributes             Extra attributes, already escaped, with a leading space
 * - $bare                   True when only the control is wanted (no label/wrapper)
 * - $e                      Escaper: $e($text) for text and double-quoted attributes
 * - $class                  $class('group') returns ' class="..."' or ''
 *
 * plus, by field type: $inputType, $placeholder, $options, $optionIds,
 * $checked, $checkedValue, $inline, and for file fields $reselect (the
 * "choose the file again" hint or null) and $reselectId.
 */
class Renderer
{
    /**
     * Theme class key for each field type's main control.
     */
    private const CONTROL_CLASS = [
        'textarea' => 'textarea',
        'select'   => 'select',
        'checkbox'   => 'check_input',
        'checkboxes' => 'check_input',
        'radio'      => 'check_input',
        'submit'   => 'submit',
        'hidden'   => '',
    ];

    /**
     * Resolved view names, keyed by theme and template.
     *
     * @var array<string, string>
     */
    private array $resolvedViews = [];

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
     * The named fields in the given order, without layout. With no names,
     * every field in its layout: rows, fieldsets and all.
     */
    public function fields(Form $form, string ...$names): string
    {
        if ($names === []) {
            return $this->nodes($form, $form->getLayout());
        }

        $html = '';

        foreach ($names as $name) {
            $html .= $this->field($form, $name);
        }

        return $html;
    }

    /**
     * @param list<Container|string> $nodes
     */
    protected function nodes(Form $form, array $nodes): string
    {
        $html = '';

        foreach ($nodes as $node) {
            $html .= $this->node($form, $node);
        }

        return $html;
    }

    protected function node(Form $form, Container|string $node): string
    {
        return match (true) {
            is_string($node)          => $form->has($node) ? $this->field($form, $node) : '',
            $node->isEmpty()          => '',
            $node instanceof Row      => $this->row($form, $node),
            $node instanceof Fieldset => $this->fieldsetGroup($form, $node),
            default                   => $this->nodes($form, $node->getChildren()),
        };
    }

    /**
     * A row: each child in a column sized by its width. Hidden fields are
     * placed without a column, since they take no space.
     */
    protected function row(Form $form, Row $row): string
    {
        $columns = [];

        foreach ($row->getChildren() as $child) {
            $html = $this->node($form, $child);

            if ($html === '') {
                continue;
            }

            if (is_string($child) && $form->getField($child)->getType() === 'hidden') {
                $columns[] = ['class' => null, 'html' => $html];

                continue;
            }

            $width = is_string($child) ? $form->getField($child)->getWidth() : $child->getWidth();

            $columns[] = [
                'class' => $width === null
                    ? $this->config->classFor('column_auto')
                    : str_replace('{width}', (string) $width, $this->config->classFor('column')),
                'html' => $html,
            ];
        }

        $classes = $this->joinClasses($this->config->classFor('row'), ...$row->getClasses());

        return $this->view('layout_row', [
            'form'    => $form,
            'row'     => $row,
            'columns' => $columns,
            'e'       => self::escape(...),
            'class'   => fn (string $key): string => $this->classAttribute($key === 'row' ? $classes : $this->config->classFor($key)),
        ]);
    }

    /**
     * A titled section around its children.
     */
    protected function fieldsetGroup(Form $form, Fieldset $fieldset): string
    {
        $classes = $this->joinClasses($this->config->classFor('section'), ...$fieldset->getClasses());

        return $this->view('layout_fieldset', [
            'form'        => $form,
            'fieldset'    => $fieldset,
            'legend'      => $fieldset->getLegend(),
            'description' => $fieldset->getDescription(),
            'id'          => $fieldset->getId(),
            'content'     => $this->nodes($form, $fieldset->getChildren()),
            'e'           => self::escape(...),
            'class'       => fn (string $key): string => $this->classAttribute($key === 'section' ? $classes : $this->config->classFor($key)),
        ]);
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

        $reselect   = $this->reselectHint($form, $field, $error);
        $reselectId = $id . '_reselect';

        $describedBy = array_filter([
            $error !== null && ! $bare ? $errorId : null,
            $reselect !== null && ! $bare ? $reselectId : null,
            $help !== null && ! $bare ? $helpId : null,
        ]);

        $multiple = $field->isMultiple();
        $value    = $form->getValue($field->getName());

        $attributes = array_merge(
            $field->ruleAttributes($this->config),
            $field->getAttributes(),
            $type === 'select' && $multiple ? ['multiple' => true] : [],
            [
                'placeholder'      => in_array($type, ['select', 'radio', 'checkbox', 'checkboxes', 'hidden', 'submit', 'file'], true) ? null : $field->getPlaceholder(),
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
            'inputName'      => $multiple ? $field->getName() . '[]' : $field->getName(),
            'value'          => $multiple ? '' : self::stringify($value),
            'values'         => $multiple ? self::stringList($value) : [self::stringify($value)],
            'multiple'       => $multiple,
            'requiredMarker' => $field->isRequired() ? $this->config->activeTheme()['required_marker'] : '',
            'error'          => $error,
            'errorId'        => $errorId,
            'help'           => $help,
            'helpId'         => $helpId,
            'reselect'       => $reselect,
            'reselectId'     => $reselectId,
            'attributes'     => self::attributes($attributes),
            'bare'           => $bare,
            'e'              => self::escape(...),
            'class'          => fn (string $key): string => $this->classAttribute($classes[$key] ?? ''),
            'placeholder'    => $multiple ? null : $field->getPlaceholder(),
            'inputType'      => $field instanceof InputField ? $field->getInputType() : 'text',
            'options'        => [],
            'optionIds'      => [],
            'checked'        => false,
            'checkedValue'   => '1',
            'inline'         => false,
        ];

        if ($field instanceof Select || $field instanceof Radio || $field instanceof Checkboxes) {
            $data['options'] = $field->getOptions();
        }

        if ($field instanceof Radio || $field instanceof Checkboxes) {
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
        $isCheck    = in_array($type, ['checkbox', 'checkboxes', 'radio'], true);
        $isGroup    = in_array($type, ['radio', 'checkboxes'], true);
        $inline     = ($field instanceof Checkbox || $field instanceof Checkboxes || $field instanceof Radio) && $field->isInline();

        return [
            'group'       => $this->config->classFor($isGroup ? 'fieldset' : 'group'),
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
            'error'       => $this->config->classFor($isGroup ? 'error_block' : 'error'),
            'help'        => $this->config->classFor('help'),
            'reselect'    => $this->config->classFor('reselect'),
        ];
    }

    /**
     * The "choose the file again" hint for a file field: shown when the form
     * came back with errors (directly or after a redirect) and the field has
     * no error of its own, since browsers clear file inputs.
     */
    protected function reselectHint(Form $form, FieldInterface $field, ?string $error): ?string
    {
        if (! $field instanceof FileFieldInterface || $error !== null || $this->config->fileReselectMessage === null) {
            return null;
        }

        $returned = $form->hasSubmission() || $form->getOldInput() !== null;

        return $returned && $form->hasErrors() ? $this->config->fileReselectMessage : null;
    }

    /**
     * Renders a theme view without leaking data between renders.
     *
     * @param array<string, mixed> $data
     */
    protected function view(string $template, array $data): string
    {
        return view($this->resolveView($template), $data, ['saveData' => false]);
    }

    /**
     * The view to load for a template: the active theme's own file, or the
     * package's bundled copy of that theme when the theme's views folder
     * lacks it.
     *
     * This keeps published (customised) views working after an upgrade that
     * adds templates for new field types: the new templates come from the
     * package until they are published too (php spark forms:publish adds
     * missing files without touching existing ones).
     */
    protected function resolveView(string $template): string
    {
        $key = $this->config->theme . '|' . $template;

        if (isset($this->resolvedViews[$key])) {
            return $this->resolvedViews[$key];
        }

        $view = $this->config->viewPath($template);

        if (! $this->viewExists($view)) {
            $theme   = $this->config->theme;
            $bundled = __DIR__ . '/Views/' . $theme . '/' . ltrim($template, '/\\') . '.php';

            if (is_file($bundled)) {
                $view = 'Ephraitech\\Forms\\Views\\' . $theme . '/' . ltrim($template, '/\\');
            }
        }

        return $this->resolvedViews[$key] = $view;
    }

    /**
     * Whether CodeIgniter can find a view, either in the app's view folder
     * or through a namespace.
     */
    protected function viewExists(string $view): bool
    {
        $directory = rtrim((new \Config\Paths())->viewDirectory, '/\\');

        if (is_file($directory . '/' . $view . '.php')) {
            return true;
        }

        return str_contains($view, '\\') && service('locator')->locateFile($view, 'Views') !== false;
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

    /**
     * A resolved value as a list of strings, for multi-value fields. Filled
     * data may hold a list or a single value.
     *
     * @return list<string>
     */
    private static function stringList(mixed $value): array
    {
        $items = is_array($value) ? $value : ($value === null || $value === '' ? [] : [$value]);
        $list  = [];

        foreach ($items as $item) {
            if (is_scalar($item) || $item instanceof Stringable) {
                $list[] = self::stringify($item);
            }
        }

        return $list;
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
