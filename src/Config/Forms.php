<?php

declare(strict_types=1);

namespace Ephraitech\Forms\Config;

use CodeIgniter\Config\BaseConfig;
use Ephraitech\Forms\Exceptions\FormException;

/**
 * Configuration for ephraitech/ci4-forms.
 *
 * To customise, create app/Config/Forms.php in the host application:
 *
 *     namespace Config;
 *
 *     use Ephraitech\Forms\Config\Forms as BaseForms;
 *
 *     class Forms extends BaseForms
 *     {
 *         public bool $showErrorSummary = true;
 *
 *         public function __construct()
 *         {
 *             parent::__construct();
 *
 *             // Change one class without redefining the whole theme:
 *             $this->themes['bootstrap5']['classes']['submit'] = 'btn btn-success';
 *         }
 *     }
 *
 * Scalar settings can also be overridden from .env, for example:
 *
 *     forms.theme = bootstrap5
 *     forms.showErrorSummary = true
 */
class Forms extends BaseConfig
{
    /**
     * HTTP methods a form may declare. Anything other than GET and POST is
     * sent as POST with a hidden _method field (see $spoofMethods).
     */
    public const METHODS = ['get', 'post', 'put', 'patch', 'delete'];

    /**
     * Key of the active theme in $themes.
     */
    public string $theme = 'bootstrap5';

    /**
     * Registered themes.
     *
     * - views:           View path prefix the renderer loads templates from.
     *                    Either a namespaced path (as below) or an app view
     *                    folder such as 'forms/bootstrap5' after publishing.
     * - required_marker: Raw HTML appended to labels of required fields.
     *                    It is developer-controlled and NOT escaped.
     * - classes:         CSS classes the renderer applies, keyed by element.
     *                    In "column", {width} becomes the field's width.
     *
     * @var array<string, array{views: string, required_marker?: string, classes?: array<string, string>}>
     */
    public array $themes = [
        'bootstrap5' => [
            'views'           => 'Ephraitech\Forms\Views\bootstrap5',
            'required_marker' => ' <span class="text-danger" aria-hidden="true">*</span>',
            'classes'         => [
                'form'             => '',
                'group'            => 'mb-3',
                'label'            => 'form-label',
                'input'            => 'form-control',
                'textarea'         => 'form-control',
                'select'           => 'form-select',
                'check_wrapper'    => 'form-check',
                'check_inline'     => 'form-check-inline',
                'check_input'      => 'form-check-input',
                'check_label'      => 'form-check-label',
                'fieldset'         => 'mb-3',
                'legend'           => 'col-form-label pt-0',
                'invalid'          => 'is-invalid',
                'error'            => 'invalid-feedback',
                'error_block'      => 'invalid-feedback d-block',
                'help'             => 'form-text',
                'reselect'         => 'form-text text-warning-emphasis',
                'submit'           => 'btn btn-primary',
                'error_summary'    => 'alert alert-danger',
                'row'                 => 'row',
                'column'              => 'col-md-{width}',
                'column_auto'         => 'col-md',
                'section'             => 'mb-4',
                'section_legend'      => 'h6 mb-3',
                'section_description' => 'text-body-secondary small mb-3',
            ],
        ],
    ];

    /**
     * Method used when a form does not declare one.
     */
    public string $defaultMethod = 'post';

    /**
     * Add CodeIgniter's CSRF field automatically to every non-GET form.
     */
    public bool $csrf = true;

    /**
     * Send PUT, PATCH and DELETE forms as POST with a hidden _method field,
     * which CodeIgniter's router understands when $supportedHTTPMethods allows it.
     */
    public bool $spoofMethods = true;

    /**
     * Add the novalidate attribute so the browser does not block submission.
     * Server-side validation then always runs and its messages are shown
     * consistently, instead of mixing browser and server error styles.
     */
    public bool $novalidate = true;

    /**
     * Emit HTML attributes derived from validation rules, such as required,
     * maxlength and minlength. These aid accessibility and mobile keyboards;
     * they never replace server-side validation.
     */
    public bool $ruleAttributes = true;

    /**
     * Show a summary of all errors above the fields when a form is rendered
     * as a whole. Per-field errors are always shown.
     */
    public bool $showErrorSummary = false;

    /**
     * Repopulate fields from session old input after redirect()->withInput().
     */
    public bool $useOldInput = true;

    /**
     * Prefix generated element ids with the form name ("booking_email"
     * instead of "email") so two forms on one page never share an id.
     */
    public bool $prefixIdsWithFormName = true;

    /**
     * Trim leading and trailing whitespace from string values in validated().
     */
    public bool $trimStrings = true;

    /**
     * Convert empty strings to null in validated(), so optional fields are
     * stored as NULL rather than ''.
     */
    public bool $emptyStringsToNull = true;

    /**
     * Reject submitted values that are not among a field's options, for
     * selects, multi-selects, radios, checkbox groups and single checkboxes.
     * Browsers only offer the listed options, but any value can be posted,
     * so this closes a common tampering hole without writing in_list rules.
     * Fields can opt out with ->strictOptions(false).
     */
    public bool $strictOptions = true;

    /**
     * Message for a value outside the options. {field} becomes the field
     * label. Override per field with ->messages(['options' => '...']).
     */
    public string $strictOptionsMessage = 'Please choose a valid option for {field}.';

    /**
     * Hint shown under file fields after a failed submission, because
     * browsers cannot refill file inputs. Set to null to show nothing.
     */
    public ?string $fileReselectMessage = 'Please choose the file again.';

    /**
     * Custom field types, as name => class. Each class implements
     * Ephraitech\Forms\Contracts\FieldInterface (usually by extending
     * Ephraitech\Forms\Fields\Field or InputField) and takes the
     * constructor arguments (string $name, ?string $label = null).
     *
     * A registered name becomes a form builder method and a type key:
     *
     *     public array $fieldTypes = ['money' => \App\Forms\Fields\MoneyField::class];
     *
     *     $this->money('price', 'Price');          // in a form class
     *     $form->addField('money', 'price');       // by type key
     *
     * Names are camelCase and must not clash with built-in types or Form
     * methods.
     *
     * @var array<string, class-string>
     */
    public array $fieldTypes = [];

    /**
     * Value validated() returns for a checkbox that was not ticked. Browsers
     * send nothing for unticked checkboxes, so without this the column would
     * simply be missing from the data.
     */
    public int|string $checkboxUncheckedValue = 0;

    /**
     * Returns the active theme definition, normalised.
     *
     * @return array{views: string, required_marker: string, classes: array<string, string>}
     */
    public function activeTheme(): array
    {
        return $this->themeDefinition($this->theme);
    }

    /**
     * Returns a registered theme definition, normalised.
     *
     * @return array{views: string, required_marker: string, classes: array<string, string>}
     *
     * @throws FormException When the theme is missing or malformed.
     */
    public function themeDefinition(string $name): array
    {
        if (! array_key_exists($name, $this->themes)) {
            throw FormException::forUnknownTheme($name, array_keys($this->themes));
        }

        $theme = $this->themes[$name];

        if (! is_array($theme)) {
            throw FormException::forInvalidTheme($name, 'the definition must be an array.');
        }

        if (! isset($theme['views']) || ! is_string($theme['views']) || trim($theme['views']) === '') {
            throw FormException::forInvalidTheme($name, 'a non-empty "views" path is required.');
        }

        $marker = $theme['required_marker'] ?? '';

        if (! is_string($marker)) {
            throw FormException::forInvalidTheme($name, '"required_marker" must be a string.');
        }

        $classes = $theme['classes'] ?? [];

        if (! is_array($classes)) {
            throw FormException::forInvalidTheme($name, '"classes" must be an array.');
        }

        foreach ($classes as $element => $value) {
            if (! is_string($element) || ! is_string($value)) {
                throw FormException::forInvalidTheme($name, '"classes" must map element names to class strings.');
            }
        }

        return [
            'views'           => rtrim(trim($theme['views']), '/\\'),
            'required_marker' => $marker,
            'classes'         => $classes,
        ];
    }

    /**
     * CSS classes for an element in the active theme, or $default when the
     * theme does not define that element.
     */
    public function classFor(string $element, string $default = ''): string
    {
        return $this->activeTheme()['classes'][$element] ?? $default;
    }

    /**
     * Full view name for a template in the active theme, ready for view().
     */
    public function viewPath(string $template): string
    {
        return $this->activeTheme()['views'] . '/' . ltrim($template, '/\\');
    }

    /**
     * Validates and lower-cases an HTTP method.
     *
     * @throws FormException When the method is not supported.
     */
    public function normaliseMethod(string $method): string
    {
        $method = strtolower(trim($method));

        if (! in_array($method, self::METHODS, true)) {
            throw FormException::forInvalidMethod($method, self::METHODS);
        }

        return $method;
    }

    /**
     * The configured default method, validated.
     */
    public function defaultMethod(): string
    {
        return $this->normaliseMethod($this->defaultMethod);
    }
}
