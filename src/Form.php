<?php

declare(strict_types=1);

namespace Ephraitech\Forms;

use CodeIgniter\HTTP\Files\UploadedFile;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\Validation\ValidationInterface;
use Ephraitech\Forms\Config\Forms;
use Ephraitech\Forms\Contracts\ChoiceFieldInterface;
use Ephraitech\Forms\Contracts\FieldInterface;
use Ephraitech\Forms\Contracts\FileFieldInterface;
use Ephraitech\Forms\Contracts\PresentableFieldInterface;
use Ephraitech\Forms\Exceptions\FormException;
use Ephraitech\Forms\Fields\Checkbox;
use Ephraitech\Forms\Fields\Checkboxes;
use Ephraitech\Forms\Fields\Color;
use Ephraitech\Forms\Fields\Date;
use Ephraitech\Forms\Fields\DatetimeLocal;
use Ephraitech\Forms\Fields\Email;
use Ephraitech\Forms\Fields\Field;
use Ephraitech\Forms\Fields\File;
use Ephraitech\Forms\Fields\Hidden;
use Ephraitech\Forms\Fields\Number;
use Ephraitech\Forms\Fields\Password;
use Ephraitech\Forms\Fields\Radio;
use Ephraitech\Forms\Fields\Search;
use Ephraitech\Forms\Fields\Select;
use Ephraitech\Forms\Fields\Submit;
use Ephraitech\Forms\Fields\Tel;
use Ephraitech\Forms\Fields\Text;
use Ephraitech\Forms\Fields\Textarea;
use Ephraitech\Forms\Fields\Time;
use Ephraitech\Forms\Fields\Url;
use Ephraitech\Forms\Layout\Container;
use Ephraitech\Forms\Layout\Fieldset;
use Ephraitech\Forms\Layout\Row;
use Ephraitech\Forms\Schema\SchemaException;
use Ephraitech\Forms\Schema\SchemaExporter;
use Ephraitech\Forms\Schema\SchemaLoader;
use Ephraitech\Forms\Schema\SchemaPolicy;
use Ephraitech\Forms\Support\FieldTypes;
use Ephraitech\Forms\Support\RuleParser;
use Stringable;
use Traversable;

/**
 * A form definition plus the data it is working with.
 *
 * Define forms either as a class:
 *
 *     class BookingForm extends Form
 *     {
 *         protected function define(): void
 *         {
 *             $this->text('full_name')->rules('required|max_length[100]');
 *             $this->email('email')->rules('required|valid_email');
 *             $this->submit('Book now');
 *         }
 *     }
 *
 * or inline:
 *
 *     $form = Form::make('newsletter')
 *         ->email('email')->rules('required|valid_email')
 *         ->submit('Subscribe')
 *         ->end();
 *
 * (end() returns from the last field back to the form.)
 *
 * Values are resolved per field in this order: the submitted request data,
 * then session old input (after redirect()->withInput()), then data passed to
 * fill(), then the field's default.
 *
 * Validation runs through CodeIgniter's shared Validation service, so both
 * controller patterns work: re-render the view with the form directly, or
 * redirect()->back()->withInput() and the errors travel with the old input.
 *
 * In views, render the whole form with <?= $form ?>, or build a custom
 * layout from open(), field(), control(), errorSummary() and close().
 */
class Form implements Stringable
{
    /**
     * Name of the hidden field that identifies which form a submission came
     * from, so old input from another form on the same page is never applied.
     */
    public const FORM_KEY = '_form';

    /**
     * Field names the package uses itself. The CSRF token name is checked
     * separately because it comes from the app's Security config.
     */
    private const RESERVED_FIELD_NAMES = [self::FORM_KEY, '_method'];

    /**
     * Form attributes that have dedicated methods.
     */
    private const RESERVED_ATTRIBUTES = ['action', 'method', 'enctype', 'name'];

    protected Forms $config;

    protected string $name;

    protected ?string $action = null;

    protected string $method;

    protected ?string $id = null;

    protected bool $multipart = false;

    /**
     * @var array<string, bool|float|int|string|Stringable>
     */
    protected array $attributes = [];

    /**
     * @var list<string>
     */
    protected array $classes = [];

    /**
     * @var array<string, FieldInterface>
     */
    protected array $fields = [];

    /**
     * Display order: field names and layout groups at the top level.
     *
     * @var list<Container|string>
     */
    protected array $layout = [];

    /**
     * Groups currently being defined, innermost last.
     *
     * @var list<Container>
     */
    private array $openContainers = [];

    /**
     * @var array<string, mixed>
     */
    protected array $filled = [];

    /**
     * Request data of the current submission, or null when none was given.
     *
     * @var array<string, mixed>|null
     */
    protected ?array $submitted = null;

    /**
     * @var array<string, mixed>|null
     */
    private ?array $oldInput = null;

    private bool $oldInputLoaded = false;

    /**
     * Errors from validate() and setError(), keyed by field name.
     *
     * @var array<string, string>
     */
    protected array $errors = [];

    /**
     * Whether validate() has run on this instance.
     */
    protected bool $hasRunValidation = false;

    private ?ValidationInterface $validation = null;

    private ?Renderer $renderer = null;

    /**
     * @var array<string, string>|null
     */
    private ?array $oldErrors = null;

    private bool $oldErrorsLoaded = false;

    /**
     * @param string|null $name   Form name; derived from the class name when
     *                            omitted (BookingForm becomes "booking").
     * @param Forms|null  $config Config instance; resolved from the app when
     *                            omitted, preferring App\Config\Forms.
     */
    public function __construct(?string $name = null, ?Forms $config = null)
    {
        $this->config = $config ?? static::resolveConfig();
        $this->name   = $this->validateName($name ?? static::defaultName());
        $this->method = $this->config->defaultMethod();

        $this->define();
    }

    /**
     * Renders the whole form, so a view can simply echo it: <?= $form ?>
     */
    public function __toString(): string
    {
        return $this->render();
    }

    /**
     * Builder methods for field types registered in the $fieldTypes config
     * setting: with 'money' registered, $form->money('price', 'Price').
     *
     * @param list<mixed> $arguments
     */
    public function __call(string $method, array $arguments): mixed
    {
        if (array_key_exists($method, FieldTypes::registered($this->config))) {
            $name  = $arguments[0] ?? null;
            $label = $arguments[1] ?? null;

            if (! is_string($name) || ($label !== null && ! is_string($label))) {
                throw FormException::forInvalidBuilderArguments($method);
            }

            return $this->addField($method, $name, $label);
        }

        throw FormException::forUndefinedMethod(static::class, $method . ' (no built-in method or registered field type)');
    }

    /**
     * Creates a form fluently: Form::make('newsletter')->email('email')...
     */
    public static function make(?string $name = null, ?Forms $config = null): static
    {
        return new static($name, $config);
    }

    /**
     * Builds a form from a schema array (see Schema\SchemaLoader for the
     * format). The schema is checked completely first; on any error a
     * SchemaException lists every problem and no form is created.
     *
     * Schemas are treated as written by admins or clients and checked
     * against SchemaPolicy::restricted() unless another policy is passed.
     * For schemas written by developers and committed with the code:
     *
     *     Form::fromSchema($schema, policy: SchemaPolicy::trusted());
     *
     * @param array<array-key, mixed> $schema
     *
     * @throws SchemaException
     */
    public static function fromSchema(array $schema, ?Forms $config = null, ?SchemaPolicy $policy = null): static
    {
        $config ??= static::resolveConfig();
        $loader   = new SchemaLoader($config, $policy);
        $errors   = $loader->errors($schema);

        if ($errors !== []) {
            throw SchemaException::forErrors($errors);
        }

        $name = isset($schema['name']) && is_string($schema['name']) ? $schema['name'] : null;
        $form = new static($name, $config);

        $loader->load($schema, $form);

        return $form;
    }

    /**
     * Builds a form from a JSON schema.
     *
     * @throws SchemaException When the JSON or the schema is invalid.
     */
    public static function fromJson(string $json, ?Forms $config = null, ?SchemaPolicy $policy = null): static
    {
        return static::fromSchema(SchemaLoader::decodeJson($json), $config, $policy);
    }

    /**
     * Adds a schema's fields (and settings) to this form, for example inside
     * define() to combine code and data. All or nothing: on any error the
     * form is left unchanged. Checked against SchemaPolicy::restricted()
     * unless another policy is passed.
     *
     * @param array<array-key, mixed> $schema
     *
     * @throws SchemaException
     */
    public function loadSchema(array $schema, ?SchemaPolicy $policy = null): static
    {
        (new SchemaLoader($this->config, $policy))->load($schema, $this);

        return $this;
    }

    /**
     * Exports the form as a version 1 schema that fromSchema() builds back
     * into an identical form. Useful as a starting template for admins, or
     * to move a form from code into data.
     *
     * @return array<string, mixed>
     *
     * @throws SchemaException When part of the form cannot be written as data, such as a closure rule.
     */
    public function toSchema(): array
    {
        return (new SchemaExporter())->export($this);
    }

    /**
     * The schema as JSON, pretty-printed by default.
     *
     * @throws SchemaException
     */
    public function toJson(int $flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE): string
    {
        return (string) json_encode($this->toSchema(), $flags | JSON_THROW_ON_ERROR);
    }

    // -------------------------------------------------------------------------
    // Form settings
    // -------------------------------------------------------------------------

    public function action(?string $action): static
    {
        $this->action = $action === null || trim($action) === '' ? null : $action;

        return $this;
    }

    public function method(string $method): static
    {
        $this->method = $this->config->normaliseMethod($method);

        return $this;
    }

    public function id(?string $id): static
    {
        $this->id = $id === null || trim($id) === '' ? null : trim($id);

        return $this;
    }

    /**
     * Sends the form as multipart/form-data, required for file uploads.
     */
    public function multipart(bool $multipart = true): static
    {
        $this->multipart = $multipart;

        return $this;
    }

    /**
     * Sets several attributes on the <form> element.
     *
     * @param array<string, bool|float|int|string|Stringable|null> $attributes
     */
    public function attributes(array $attributes): static
    {
        foreach ($attributes as $attribute => $value) {
            $this->attribute((string) $attribute, $value);
        }

        return $this;
    }

    /**
     * Sets one attribute on the <form> element. True renders a bare
     * attribute, false or null removes it. "id" and "class" are routed to
     * id() and addClass().
     */
    public function attribute(string $attribute, bool|float|int|string|Stringable|null $value): static
    {
        if (preg_match('/^[A-Za-z_:][A-Za-z0-9_:.\-]*$/', $attribute) !== 1) {
            throw FormException::forInvalidFormAttributeName($this->name, $attribute);
        }

        $key = strtolower($attribute);

        if (in_array($key, self::RESERVED_ATTRIBUTES, true)) {
            throw FormException::forReservedFormAttribute($this->name, $key);
        }

        if ($key === 'id') {
            return $this->id($value === null || $value === false ? null : (string) $value);
        }

        if ($key === 'class') {
            return $value === null || $value === false ? $this : $this->addClass((string) $value);
        }

        if ($value === null || $value === false) {
            unset($this->attributes[$key]);

            return $this;
        }

        $this->attributes[$key] = $value;

        return $this;
    }

    /**
     * Adds CSS classes to the <form> element, on top of the theme's class.
     */
    public function addClass(string ...$classes): static
    {
        foreach ($classes as $class) {
            foreach (preg_split('/\s+/', trim($class)) ?: [] as $single) {
                if ($single !== '' && ! in_array($single, $this->classes, true)) {
                    $this->classes[] = $single;
                }
            }
        }

        return $this;
    }

    public function getConfig(): Forms
    {
        return $this->config;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }

    /**
     * The declared method: get, post, put, patch or delete.
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * The method attribute the browser can actually send: get or post.
     */
    public function getHtmlMethod(): string
    {
        return $this->method === 'get' ? 'get' : 'post';
    }

    /**
     * The method to send in a hidden _method field, or null when no spoofing
     * is needed (GET and POST forms, or spoofing disabled in config).
     */
    public function getSpoofedMethod(): ?string
    {
        if (! $this->config->spoofMethods || in_array($this->method, ['get', 'post'], true)) {
            return null;
        }

        return strtoupper($this->method);
    }

    /**
     * Whether the CSRF field should be added (all non-GET forms, when enabled).
     */
    public function needsCsrf(): bool
    {
        return $this->config->csrf && $this->method !== 'get';
    }

    /**
     * The id set with id(), or null when the form name is used.
     */
    public function getExplicitId(): ?string
    {
        return $this->id;
    }

    public function getId(): string
    {
        return $this->id ?? (string) preg_replace('/[^A-Za-z0-9_\-]/', '_', $this->name);
    }

    /**
     * Whether the form is sent as multipart/form-data: when set with
     * multipart(), and always when it has a file field.
     */
    public function isMultipart(): bool
    {
        if ($this->multipart) {
            return true;
        }

        foreach ($this->fields as $field) {
            if ($field instanceof FileFieldInterface) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, bool|float|int|string|Stringable>
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @return list<string>
     */
    public function getClasses(): array
    {
        return $this->classes;
    }

    /**
     * Prefix for generated field ids, according to the config.
     */
    public function getIdPrefix(): string
    {
        return $this->config->prefixIdsWithFormName ? $this->getId() : '';
    }

    // -------------------------------------------------------------------------
    // Field builders
    // -------------------------------------------------------------------------

    public function text(string $name, ?string $label = null): Text
    {
        return $this->add(new Text($name, $label));
    }

    public function email(string $name, ?string $label = null): Email
    {
        return $this->add(new Email($name, $label));
    }

    public function password(string $name, ?string $label = null): Password
    {
        return $this->add(new Password($name, $label));
    }

    public function number(string $name, ?string $label = null): Number
    {
        return $this->add(new Number($name, $label));
    }

    public function tel(string $name, ?string $label = null): Tel
    {
        return $this->add(new Tel($name, $label));
    }

    public function url(string $name, ?string $label = null): Url
    {
        return $this->add(new Url($name, $label));
    }

    public function date(string $name, ?string $label = null): Date
    {
        return $this->add(new Date($name, $label));
    }

    public function time(string $name, ?string $label = null): Time
    {
        return $this->add(new Time($name, $label));
    }

    public function datetimeLocal(string $name, ?string $label = null): DatetimeLocal
    {
        return $this->add(new DatetimeLocal($name, $label));
    }

    public function color(string $name, ?string $label = null): Color
    {
        return $this->add(new Color($name, $label));
    }

    public function search(string $name, ?string $label = null): Search
    {
        return $this->add(new Search($name, $label));
    }

    public function hidden(string $name, mixed $default = null): Hidden
    {
        return $this->add((new Hidden($name))->default($default));
    }

    public function textarea(string $name, ?string $label = null): Textarea
    {
        return $this->add(new Textarea($name, $label));
    }

    /**
     * @param array<array-key, mixed>|\Closure(): array<array-key, mixed>|null $options
     */
    public function select(string $name, ?string $label = null, array|\Closure|null $options = null): Select
    {
        $field = new Select($name, $label);

        if ($options !== null) {
            $field->options($options);
        }

        return $this->add($field);
    }

    /**
     * @param array<array-key, mixed>|\Closure(): array<array-key, mixed>|null $options
     */
    public function radio(string $name, ?string $label = null, array|\Closure|null $options = null): Radio
    {
        $field = new Radio($name, $label);

        if ($options !== null) {
            $field->options($options);
        }

        return $this->add($field);
    }

    public function checkbox(string $name, ?string $label = null): Checkbox
    {
        return $this->add(new Checkbox($name, $label));
    }

    /**
     * A group of checkboxes; the value is a list of the ticked options.
     *
     * @param array<array-key, mixed>|\Closure(): array<array-key, mixed>|null $options
     */
    public function checkboxes(string $name, ?string $label = null, array|\Closure|null $options = null): Checkboxes
    {
        $field = new Checkboxes($name, $label);

        if ($options !== null) {
            $field->options($options);
        }

        return $this->add($field);
    }

    /**
     * File upload field. The form becomes multipart automatically.
     */
    public function file(string $name, ?string $label = null): File
    {
        return $this->add(new File($name, $label));
    }

    public function submit(string $label = 'Submit', string $name = 'submit'): Submit
    {
        return $this->add(new Submit($label, $name));
    }

    /**
     * Creates and adds a field by type key: a built-in type ('text',
     * 'datetime_local', 'checkboxes'...) or one registered in $fieldTypes.
     * For 'submit', the label is the button text.
     */
    public function addField(string $type, string $name, ?string $label = null): FieldInterface
    {
        $class = FieldTypes::resolve($type, $this->config);

        if ($class === null) {
            throw FormException::forUnknownFieldType($type);
        }

        $field = $class === Submit::class
            ? new Submit($label ?? 'Submit', $name)
            : new $class($name, $label);

        return $this->add($field);
    }

    /**
     * Whether a type key is built in or registered.
     */
    public function hasFieldType(string $type): bool
    {
        return FieldTypes::resolve($type, $this->config) !== null;
    }

    /**
     * Adds any field instance, including custom field classes that
     * implement FieldInterface.
     *
     * @template T of FieldInterface
     *
     * @param T $field
     *
     * @return T
     */
    public function add(FieldInterface $field): FieldInterface
    {
        $name = $field->getName();

        if (in_array($name, self::RESERVED_FIELD_NAMES, true) || $name === $this->csrfTokenName()) {
            throw FormException::forReservedFieldName($this->name, $name);
        }

        if (array_key_exists($name, $this->fields)) {
            throw FormException::forDuplicateField($this->name, $name);
        }

        if ($field instanceof Field) {
            $field->setParent($this);
        }

        $this->fields[$name] = $field;
        $this->placeInLayout($name);

        return $field;
    }

    // -------------------------------------------------------------------------
    // Layout
    // -------------------------------------------------------------------------

    /**
     * Places the fields defined inside $define side by side:
     *
     *     $this->row(function () {
     *         $this->text('first_name')->width(6);
     *         $this->text('last_name')->width(6);
     *     });
     *
     * $define receives the form, which suits arrow functions and inline
     * forms. Rows and fieldsets can be nested.
     *
     * @param \Closure(static): mixed $define
     */
    public function row(\Closure $define): Row
    {
        return $this->group(new Row(), $define);
    }

    /**
     * Groups the fields defined inside $define under a titled section:
     *
     *     $this->fieldset('Delivery address', function () {
     *         $this->text('street');
     *         $this->text('city');
     *     })->description('Where should we send the finished suit?');
     *
     * @param \Closure(static): mixed $define
     */
    public function fieldset(string $legend, \Closure $define): Fieldset
    {
        return $this->group(new Fieldset($legend), $define);
    }

    /**
     * Top-level layout: field names and groups in display order. Every field
     * appears exactly once, either here or inside a group.
     *
     * @return list<Container|string>
     */
    public function getLayout(): array
    {
        return $this->layout;
    }

    /**
     * Removes a field, for example a password field on an edit form that
     * shares its definition with the create form.
     */
    public function remove(string ...$names): static
    {
        foreach ($names as $name) {
            unset($this->fields[$name]);

            $layout = [];

            foreach ($this->layout as $node) {
                if ($node === $name) {
                    continue;
                }

                if ($node instanceof Container) {
                    $node->removeField($name);
                }

                $layout[] = $node;
            }

            $this->layout = $layout;
        }

        return $this;
    }

    public function has(string $name): bool
    {
        return array_key_exists($name, $this->fields);
    }

    public function getField(string $name): FieldInterface
    {
        if (! array_key_exists($name, $this->fields)) {
            throw FormException::forUnknownField($this->name, $name);
        }

        return $this->fields[$name];
    }

    /**
     * All fields in definition order.
     *
     * @return array<string, FieldInterface>
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * Fields whose values belong in validated() data: everything except
     * buttons and disabled fields.
     *
     * @return array<string, FieldInterface>
     */
    public function getSubmittableFields(): array
    {
        return array_filter(
            $this->fields,
            static fn (FieldInterface $field): bool => $field->isSubmittable(),
        );
    }

    // -------------------------------------------------------------------------
    // Data
    // -------------------------------------------------------------------------

    /**
     * Fills the form with existing data, typically a database row or Entity
     * on an edit screen. Submitted data and old input still take priority.
     *
     * @param array<array-key, mixed>|object $data
     */
    public function fill(array|object $data): static
    {
        $this->filled = $this->toDataArray($data);

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getFilled(): array
    {
        return $this->filled;
    }

    /**
     * Sets the data of the current submission. validate() does this from the
     * request; calling it directly is useful in tests and APIs.
     *
     * @param array<array-key, mixed> $data
     */
    public function setSubmittedData(array $data): static
    {
        $submitted = [];

        foreach ($data as $key => $value) {
            $submitted[(string) $key] = $value;
        }

        $this->submitted = $submitted;

        return $this;
    }

    /**
     * Forgets the current submission, for example to show a fresh form after
     * a successful save without redirecting.
     */
    public function clearSubmittedData(): static
    {
        $this->submitted = null;

        return $this;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getSubmittedData(): ?array
    {
        return $this->submitted;
    }

    public function hasSubmission(): bool
    {
        return $this->submitted !== null;
    }

    /**
     * Replaces the session old input with explicit data, or with no old input
     * when null is passed. Mainly useful in tests.
     *
     * @param array<array-key, mixed>|null $input
     */
    public function setOldInput(?array $input): static
    {
        $this->oldInput       = $input === null ? null : $this->stringKeys($input);
        $this->oldInputLoaded = true;

        return $this;
    }

    /**
     * Whether a set of request data was produced by this form. Data carrying
     * the hidden form key must name this form; data without it counts as this
     * form's when it contains at least one of this form's field names.
     *
     * @param array<array-key, mixed> $input
     */
    public function ownsInput(array $input): bool
    {
        if (array_key_exists(self::FORM_KEY, $input)) {
            return is_scalar($input[self::FORM_KEY]) && (string) $input[self::FORM_KEY] === $this->name;
        }

        foreach (array_keys($this->getSubmittableFields()) as $name) {
            if (array_key_exists($name, $input)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Value to show in a field, resolved in priority order: submitted data,
     * old input, filled data, then the field default.
     *
     * Once a submission (or old input) belongs to this form, a key missing
     * from it is treated as empty rather than falling back to filled data.
     * That is what makes an unticked checkbox render unticked.
     */
    public function getValue(string $name): mixed
    {
        $field = $this->getField($name);

        if (! $field->shouldRepopulate()) {
            return null;
        }

        if ($this->submitted !== null) {
            return array_key_exists($name, $this->submitted)
                ? $this->submitted[$name]
                : $field->valueWhenMissing($this->config);
        }

        $old = $this->getOldInput();

        if ($old !== null) {
            return array_key_exists($name, $old)
                ? $old[$name]
                : $field->valueWhenMissing($this->config);
        }

        if (array_key_exists($name, $this->filled)) {
            return $this->filled[$name];
        }

        return $field->getDefault();
    }

    /**
     * Resolved values of every field, keyed by name.
     *
     * @return array<string, mixed>
     */
    public function getValues(): array
    {
        $values = [];

        foreach (array_keys($this->fields) as $name) {
            $values[$name] = $this->getValue($name);
        }

        return $values;
    }

    /**
     * Old input from the session that belongs to this form, or null.
     *
     * @return array<string, mixed>|null
     */
    public function getOldInput(): ?array
    {
        if (! $this->config->useOldInput) {
            return null;
        }

        if (! $this->oldInputLoaded) {
            $this->oldInput       = $this->loadOldInput();
            $this->oldInputLoaded = true;
        }

        if ($this->oldInput === null || ! $this->ownsInput($this->oldInput)) {
            return null;
        }

        return $this->oldInput;
    }

    // -------------------------------------------------------------------------
    // Validation
    // -------------------------------------------------------------------------

    /**
     * Validates submitted data against the fields' rules.
     *
     * With no argument the current request is used: POST data for POST, PUT,
     * PATCH and DELETE forms, query data for GET forms, and the decoded body
     * for JSON requests. An array can be passed instead, for APIs and tests.
     *
     * The data is stored as this form's submission, so a failed form can be
     * re-rendered with the user's input, and validated() can be called when
     * validation passes.
     *
     * @param array<array-key, mixed>|IncomingRequest|null $input
     */
    public function validate(array|IncomingRequest|null $input = null): bool
    {
        $data = $this->prepareForValidation($this->extractInput($input));

        $this->setSubmittedData($data);
        $this->errors           = [];
        $this->hasRunValidation = true;

        $validation = $this->getValidation();
        $validation->reset();

        $values = $this->validationData($this->submitted ?? []);
        $rules  = $this->getValidationRules();

        if ($rules !== []) {
            $validation->setRules($rules);

            if (! $validation->run($values)) {
                foreach ($validation->getErrors() as $key => $message) {
                    // Errors for list items arrive as "tags.1" or "tags.*";
                    // the first one is reported on the field itself.
                    $field = explode('.', (string) $key, 2)[0];

                    $this->errors[$field] ??= (string) $message;
                }
            }
        }

        $this->checkOptions($values);

        return $this->errors === [];
    }

    /**
     * Whether strict option checking applies to a field: its own setting,
     * otherwise the config.
     */
    public function usesStrictOptions(FieldInterface $field): bool
    {
        if (! $field instanceof ChoiceFieldInterface) {
            return false;
        }

        return $field->getStrictOptions() ?? $this->config->strictOptions;
    }

    /**
     * Whether validate() ran and no errors exist, including errors added
     * afterwards with setError().
     */
    public function isValid(): bool
    {
        return $this->hasRunValidation && $this->errors === [];
    }

    /**
     * Rules for every submittable field that has rules, in the format
     * CodeIgniter's Validation::setRules() expects. Item rules of
     * multi-value fields are keyed "name.*" and start with permit_empty,
     * because CodeIgniter otherwise runs them against an empty list as if it
     * held one empty item; "at least one" belongs in rules('required').
     * Also usable directly with a controller's $this->validate().
     *
     * @return array<string, array{label: string, rules: list<callable|string>, errors?: array<string, string>}>
     */
    public function getValidationRules(): array
    {
        $rules = [];

        foreach ($this->getSubmittableFields() as $name => $field) {
            // CodeIgniter's permit_empty looks at POST data, where files never
            // appear, so optional uploads are handled here instead: no file,
            // no rules. Once a file is uploaded every rule applies.
            if ($field instanceof FileFieldInterface && ! $field->isRequired() && ! $this->hasUploadedFile($name)) {
                continue;
            }

            $sets = [$name => $field->getRules()];

            if ($field->isMultiple()) {
                $each = $field->getEachRules();

                if ($each !== [] && ! RuleParser::has($each, 'permit_empty')) {
                    array_unshift($each, 'permit_empty');
                }

                $sets[$name . '.*'] = $each;
            }

            foreach ($sets as $key => $fieldRules) {
                if ($fieldRules === []) {
                    continue;
                }

                $rule = ['label' => $field->getLabel(), 'rules' => $fieldRules];

                if ($field->getMessages() !== []) {
                    $rule['errors'] = $field->getMessages();
                }

                $rules[$key] = $rule;
            }
        }

        return $rules;
    }

    /**
     * Clean data for every submittable field, ready to insert or update.
     *
     * File fields are never included: get uploads with getFile() and
     * getFiles().
     *
     * Only defined fields are returned, so extra request keys can never reach
     * the database. Strings are trimmed and empty strings become null
     * according to the config, and unticked checkboxes get the configured
     * unchecked value. Pass field names to leave out, such as a password
     * confirmation field:
     *
     *     $model->insert($form->validated('password_confirm'));
     *
     * @return array<string, mixed>
     *
     * @throws FormException When validate() has not run or did not pass.
     */
    public function validated(string ...$except): array
    {
        if (! $this->hasRunValidation) {
            throw FormException::forNotValidated($this->name);
        }

        if ($this->errors !== []) {
            throw FormException::forFailedValidation($this->name);
        }

        $submitted = $this->submitted ?? [];
        $data      = [];

        foreach ($this->getSubmittableFields() as $name => $field) {
            if (in_array($name, $except, true) || $field instanceof FileFieldInterface) {
                continue;
            }

            $data[$name] = array_key_exists($name, $submitted)
                ? $field->normaliseValue($submitted[$name], $this->config)
                : $field->valueWhenMissing($this->config);
        }

        return $data;
    }

    // -------------------------------------------------------------------------
    // Uploads
    // -------------------------------------------------------------------------

    /**
     * The uploaded file of a file field, or null when none was uploaded or
     * it is not a valid upload. For a multiple field, the first file.
     *
     * Files are read from the current request, as CodeIgniter's file rules
     * do. Store them after validate() passes:
     *
     *     $path = $form->getFile('photo')?->store('photos');
     */
    public function getFile(string $name): ?UploadedFile
    {
        return $this->getFiles($name)[0] ?? null;
    }

    /**
     * All valid uploaded files of a file field, in upload order. Files that
     * failed to upload or were already moved are left out.
     *
     * @return list<UploadedFile>
     */
    public function getFiles(string $name): array
    {
        return array_values(array_filter(
            $this->uploadsFor($name),
            static fn (UploadedFile $file): bool => $file->isValid() && ! $file->hasMoved(),
        ));
    }

    /**
     * Whether the request contains a file for this field, valid or not.
     * An empty file input (nothing chosen) does not count.
     */
    public function hasUploadedFile(string $name): bool
    {
        return $this->uploadsFor($name) !== [];
    }

    /**
     * Adds an error after validation, such as "This time slot is already
     * booked" from a database check. The form then counts as invalid, and the
     * error survives redirect()->withInput() like rule errors do.
     */
    public function setError(string $field, string $message): static
    {
        $this->errors[$field] = $message;

        $this->getValidation()->setError($field, $message);

        return $this;
    }

    /**
     * Current errors keyed by field name.
     *
     * Before validate() runs on this request, errors carried over by
     * redirect()->withInput() are included, limited to this form's fields and
     * only when the old input came from this form.
     *
     * @return array<string, string>
     */
    public function getErrors(): array
    {
        if ($this->hasRunValidation || $this->getOldInput() === null) {
            return $this->errors;
        }

        return array_merge($this->getOldErrors(), $this->errors);
    }

    public function getError(string $field): ?string
    {
        return $this->getErrors()[$field] ?? null;
    }

    public function hasError(string $field): bool
    {
        return $this->getError($field) !== null;
    }

    public function hasErrors(): bool
    {
        return $this->getErrors() !== [];
    }

    /**
     * Removes all errors, including any carried over from a redirect.
     */
    public function clearErrors(): static
    {
        $this->errors          = [];
        $this->oldErrors       = [];
        $this->oldErrorsLoaded = true;

        return $this;
    }

    /**
     * Uses a specific Validation instance instead of the shared service.
     * redirect()->withInput() only carries errors from the shared service,
     * so use this mainly in tests.
     */
    public function setValidation(ValidationInterface $validation): static
    {
        $this->validation = $validation;

        return $this;
    }

    public function getValidation(): ValidationInterface
    {
        return $this->validation ??= service('validation');
    }

    /**
     * Replaces the errors carried over by a redirect with explicit data, or
     * with none when null is passed. Mainly useful in tests.
     *
     * @param array<string, string>|null $errors
     */
    public function setOldErrors(?array $errors): static
    {
        $this->oldErrors       = $errors === null ? null : $this->filterFieldErrors($errors);
        $this->oldErrorsLoaded = true;

        return $this;
    }

    // -------------------------------------------------------------------------
    // Presenting stored data
    // -------------------------------------------------------------------------

    /**
     * Stored data (such as a saved submission) as rows people can read: the
     * field's label and its value as text, with option labels instead of
     * option values and Yes/No for checkboxes. Rows follow the form's order.
     * Passwords, buttons and uploads are never included; keys that are not
     * fields are ignored.
     *
     * Labels and values are plain, unescaped text: escape them (esc()) when
     * printing rows yourself. renderPresented() and forms_present() escape
     * everything.
     *
     * @param array<array-key, mixed>|object $data
     *
     * @return list<array{name: string, label: string, value: list<string>|string, text: string, empty: bool}>
     */
    public function present(array|object $data, bool $includeEmpty = true): array
    {
        $data = $this->toDataArray($data);
        $rows = [];

        foreach ($this->fields as $name => $field) {
            if ($field instanceof PresentableFieldInterface && ! $field->isPresentable()) {
                continue;
            }

            if (! $field instanceof PresentableFieldInterface && ! $field->isSubmittable()) {
                continue;
            }

            $raw   = $data[$name] ?? null;
            $value = $field instanceof PresentableFieldInterface
                ? $field->presentValue($raw, $this->config)
                : (is_scalar($raw) ? (string) $raw : '');

            $text  = is_array($value) ? implode(', ', $value) : $value;
            $empty = $text === '';

            if ($empty && ! $includeEmpty) {
                continue;
            }

            $rows[] = ['name' => $name, 'label' => $field->getLabel(), 'value' => $value, 'text' => $text, 'empty' => $empty];
        }

        return $rows;
    }

    /**
     * present() as HTML, using the theme's "presented" view.
     *
     * @param array<array-key, mixed>|object $data
     */
    public function renderPresented(array|object $data, bool $includeEmpty = true): string
    {
        return $this->getRenderer()->presented($this, $this->present($data, $includeEmpty));
    }

    // -------------------------------------------------------------------------
    // Rendering
    // -------------------------------------------------------------------------

    /**
     * The whole form: opening tag, optional error summary, every field in
     * order and the closing tag.
     */
    public function render(): string
    {
        return $this->getRenderer()->form($this);
    }

    /**
     * Opening <form> tag with the hidden form key, the CSRF field (non-GET
     * forms) and the _method field (PUT, PATCH and DELETE forms).
     *
     * @param array<string, bool|float|int|string|Stringable|null> $attributes Extra attributes for this render only
     */
    public function open(array $attributes = []): string
    {
        return $this->getRenderer()->open($this, $attributes);
    }

    public function close(): string
    {
        return $this->getRenderer()->close($this);
    }

    /**
     * One field with its label, error and help text.
     */
    public function field(string $name): string
    {
        return $this->getRenderer()->field($this, $name);
    }

    /**
     * Several fields in order; every field when no names are given.
     */
    public function fields(string ...$names): string
    {
        return $this->getRenderer()->fields($this, ...$names);
    }

    /**
     * Only the field's control (input, select, textarea or button), without
     * label, wrapper, error or help text. Use getError() to place the error
     * yourself in a custom layout.
     */
    public function control(string $name): string
    {
        return $this->getRenderer()->field($this, $name, true);
    }

    /**
     * A list of all current errors linking to their fields, or '' when the
     * form has no errors.
     */
    public function errorSummary(): string
    {
        return $this->getRenderer()->errorSummary($this);
    }

    public function setRenderer(Renderer $renderer): static
    {
        $this->renderer = $renderer;

        return $this;
    }

    public function getRenderer(): Renderer
    {
        return $this->renderer ??= new Renderer($this->config);
    }

    /**
     * Plain array description of the form and its fields.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name'       => $this->name,
            'id'         => $this->id,
            'action'     => $this->action,
            'method'     => $this->method,
            'multipart'  => $this->multipart,
            'attributes' => array_map(
                static fn (mixed $value): mixed => $value instanceof Stringable ? (string) $value : $value,
                $this->attributes,
            ),
            'classes' => $this->classes,
            'fields'  => array_values(array_map(
                static fn (FieldInterface $field): array => $field->toArray(),
                $this->fields,
            )),
            'layout' => array_map(
                static fn (Container|string $node): array|string => $node instanceof Container ? $node->toArray() : $node,
                $this->layout,
            ),
        ];
    }

    // -------------------------------------------------------------------------
    // Extension points
    // -------------------------------------------------------------------------

    /**
     * Override in form classes to declare fields. Called by the constructor.
     */
    protected function define(): void
    {
    }

    /**
     * Override in form classes to adjust submitted data before validation,
     * for example to strip spaces from a phone number. The returned data is
     * what gets validated, repopulated and returned by validated().
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    protected function prepareForValidation(array $data): array
    {
        return $data;
    }

    /**
     * Name used when none is given: the class name without a "Form" suffix,
     * in snake_case. BookingForm becomes "booking", UserProfileForm becomes
     * "user_profile", and the base class becomes "form".
     */
    protected static function defaultName(): string
    {
        $short = substr((string) strrchr('\\' . static::class, '\\'), 1);

        if ($short !== 'Form' && str_ends_with($short, 'Form')) {
            $short = substr($short, 0, -4);
        }

        if ($short === '' || str_contains($short, '@anonymous')) {
            return 'form';
        }

        return strtolower((string) preg_replace('/(?<!^)[A-Z]/', '_$0', $short));
    }

    /**
     * Resolves the config, preferring the app's App\Config\Forms override.
     */
    protected static function resolveConfig(): Forms
    {
        $class = class_exists(\Config\Forms::class) && is_a(\Config\Forms::class, Forms::class, true)
            ? \Config\Forms::class
            : Forms::class;

        $config = config($class);

        return $config instanceof Forms ? $config : new Forms();
    }

    /**
     * Reads old input stored by redirect()->withInput(). POST values take
     * priority over GET values, as in CodeIgniter's old() helper.
     *
     * @return array<string, mixed>|null
     */
    protected function loadOldInput(): ?array
    {
        $old = service('session')->get('_ci_old_input');

        if (! is_array($old)) {
            return null;
        }

        $get  = is_array($old['get'] ?? null) ? $old['get'] : [];
        $post = is_array($old['post'] ?? null) ? $old['post'] : [];

        $merged = array_merge($get, $post);

        return $merged === [] ? null : $this->stringKeys($merged);
    }

    /**
     * Reads validation errors stored by redirect()->withInput().
     *
     * @return array<string, string>|null
     */
    protected function loadOldErrors(): ?array
    {
        $errors = service('session')->get('_ci_validation_errors');

        return is_array($errors) ? $errors : null;
    }

    // -------------------------------------------------------------------------
    // Internals
    // -------------------------------------------------------------------------

    /**
     * @param array<array-key, mixed>|IncomingRequest|null $input
     *
     * @return array<string, mixed>
     */
    private function extractInput(array|IncomingRequest|null $input): array
    {
        if (is_array($input)) {
            return $this->stringKeys($input);
        }

        $request = $input ?? service('request');

        if (! $request instanceof IncomingRequest) {
            return [];
        }

        if (str_contains(strtolower($request->getHeaderLine('Content-Type')), 'json')) {
            $json = $request->getJSON(true);

            return is_array($json) ? $this->stringKeys($json) : [];
        }

        $data = $this->getHtmlMethod() === 'get' ? $request->getGet() : $request->getPost();

        return is_array($data) ? $this->stringKeys($data) : [];
    }

    /**
     * Data handed to the validator: strings of known fields are trimmed (per
     * the config) but empty strings are kept, so "required" and
     * "permit_empty" see exactly what the user left blank. Missing keys stay
     * missing, so an unticked required checkbox fails as it should.
     *
     * Values are also forced into the right shape: multi-value fields always
     * get a list, single-value fields never get an array.
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    private function validationData(array $data): array
    {
        $config                     = clone $this->config;
        $config->emptyStringsToNull = false;

        foreach ($data as $name => $value) {
            if (isset($this->fields[$name]) && $this->fields[$name]->isSubmittable()) {
                $data[$name] = $this->fields[$name]->normaliseValue($value, $config);
            }
        }

        return $data;
    }

    /**
     * Files the request holds for a file field, excluding empty inputs.
     *
     * @return list<UploadedFile>
     */
    private function uploadsFor(string $name): array
    {
        if (! $this->getField($name) instanceof FileFieldInterface) {
            throw FormException::forNotAFileField($this->name, $name);
        }

        $request = service('request');

        if (! $request instanceof IncomingRequest) {
            return [];
        }

        $files = $request->getFileMultiple($name) ?? [$request->getFile($name)];

        return array_values(array_filter(
            $files,
            static fn (?UploadedFile $file): bool => $file instanceof UploadedFile && $file->getError() !== UPLOAD_ERR_NO_FILE,
        ));
    }

    /**
     * Rejects submitted values that are not among a choice field's options.
     * Runs after the rules; a field that already has an error is skipped so
     * each field reports one error at a time. Empty values are left to
     * "required".
     *
     * @param array<string, mixed> $values Shape-normalised validation data
     */
    private function checkOptions(array $values): void
    {
        foreach ($this->getSubmittableFields() as $name => $field) {
            if (isset($this->errors[$name]) || ! $this->usesStrictOptions($field) || ! array_key_exists($name, $values)) {
                continue;
            }

            /** @var ChoiceFieldInterface $field */
            $allowed = $field->getAllowedValues();
            $items   = is_array($values[$name]) ? $values[$name] : [$values[$name]];

            foreach ($items as $item) {
                if ($item === null || $item === '') {
                    continue;
                }

                if (! is_scalar($item) || ! in_array((string) $item, $allowed, true)) {
                    $message = $field->getMessages()['options'] ?? $this->config->strictOptionsMessage;

                    $this->setError($name, str_replace('{field}', $field->getLabel(), $message));

                    break;
                }
            }
        }
    }

    /**
     * @return array<string, string>
     */
    private function getOldErrors(): array
    {
        if (! $this->oldErrorsLoaded) {
            $loaded                = $this->loadOldErrors();
            $this->oldErrors       = $loaded === null ? null : $this->filterFieldErrors($loaded);
            $this->oldErrorsLoaded = true;
        }

        return $this->oldErrors ?? [];
    }

    /**
     * @param array<array-key, mixed> $errors
     *
     * @return array<string, string>
     */
    private function filterFieldErrors(array $errors): array
    {
        $filtered = [];

        foreach ($errors as $key => $message) {
            // Item errors of list fields are keyed "tags.1" or "tags.*".
            $field = explode('.', (string) $key, 2)[0];

            if (isset($this->fields[$field]) && is_scalar($message)) {
                $filtered[$field] ??= (string) $message;
            }
        }

        return $filtered;
    }

    /**
     * Adds a group where fields are currently being placed, runs $define
     * with the group open, then closes it even if $define throws.
     *
     * @template T of Container
     *
     * @param T $container
     *
     * @return T
     */
    private function group(Container $container, \Closure $define): Container
    {
        $container->setParent($this);

        $parent = end($this->openContainers);

        if ($parent instanceof Container) {
            $parent->addChild($container);
        } else {
            $this->layout[] = $container;
        }

        $this->openContainers[] = $container;

        try {
            $define($this);
        } finally {
            array_pop($this->openContainers);
        }

        return $container;
    }

    private function placeInLayout(string $name): void
    {
        $parent = end($this->openContainers);

        if ($parent instanceof Container) {
            $parent->addChild($name);

            return;
        }

        $this->layout[] = $name;
    }

    /**
     * The CSRF token name, read from the Security config. csrf_token() is
     * not used: it starts the Security service, which needs a web request,
     * so forms could not be built from the command line (spark commands,
     * queue workers, seeders).
     */
    private function csrfTokenName(): ?string
    {
        if (! class_exists(\Config\Security::class)) {
            return null;
        }

        $security = config(\Config\Security::class);

        return is_object($security) && isset($security->tokenName) && is_string($security->tokenName)
            ? $security->tokenName
            : null;
    }

    private function validateName(string $name): string
    {
        if (preg_match('/^[A-Za-z_][A-Za-z0-9_\-]*$/', $name) !== 1) {
            throw FormException::forInvalidFormName($name);
        }

        return $name;
    }

    /**
     * @param array<array-key, mixed>|object $data
     *
     * @return array<string, mixed>
     */
    private function toDataArray(array|object $data): array
    {
        if (is_array($data)) {
            return $this->stringKeys($data);
        }

        if (method_exists($data, 'toArray')) {
            $array = $data->toArray();

            if (is_array($array)) {
                return $this->stringKeys($array);
            }
        }

        if ($data instanceof Traversable) {
            return $this->stringKeys(iterator_to_array($data));
        }

        if ($data instanceof \stdClass || get_object_vars($data) !== []) {
            return $this->stringKeys(get_object_vars($data));
        }

        throw FormException::forInvalidFillData($this->name, get_debug_type($data));
    }

    /**
     * @param array<array-key, mixed> $data
     *
     * @return array<string, mixed>
     */
    private function stringKeys(array $data): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            $result[(string) $key] = $value;
        }

        return $result;
    }
}
