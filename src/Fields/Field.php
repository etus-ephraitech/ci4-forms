<?php

declare(strict_types=1);

namespace Ephraitech\Forms\Fields;

use Ephraitech\Forms\Config\Forms;
use Ephraitech\Forms\Contracts\FieldInterface;
use Ephraitech\Forms\Exceptions\FormException;
use Ephraitech\Forms\Support\RuleParser;
use Stringable;

/**
 * Base class for all fields.
 *
 * Setters are fluent and named after what they set (label(), rules(), help());
 * getters use the get/is prefix. Calls to methods a field does not have are
 * forwarded to the form that created it, so inline definitions can chain:
 *
 *     $form = Form::make('booking')
 *         ->text('full_name')->rules('required')
 *         ->email('email')->rules('required|valid_email')
 *         ->end();
 *
 * end() returns the form itself once the last field is defined.
 */
abstract class Field implements FieldInterface
{
    /**
     * Type key, overridden by every concrete field.
     */
    public const TYPE = '';

    /**
     * Template name, overridden by every concrete field.
     */
    public const VIEW = '';

    /**
     * Attributes the package controls itself.
     */
    private const RESERVED_ATTRIBUTES = ['name', 'type', 'value'];

    protected string $name;

    protected string $label;

    /**
     * @var list<callable|string>
     */
    protected array $rules = [];

    /**
     * @var list<callable|string>
     */
    protected array $eachRules = [];

    /**
     * @var array<string, string>
     */
    protected array $messages = [];

    /**
     * @var array<string, bool|float|int|string|Stringable|null>
     */
    protected array $attributes = [];

    /**
     * @var list<string>
     */
    protected array $classes = [];

    protected ?string $help = null;

    protected ?int $width = null;

    protected ?string $id = null;

    protected mixed $default = null;

    protected ?string $placeholder = null;

    protected bool $disabled = false;

    private ?object $parent = null;

    public function __construct(string $name, ?string $label = null)
    {
        if (preg_match('/^[A-Za-z_][A-Za-z0-9_\-]*$/', $name) !== 1) {
            throw FormException::forInvalidFieldName($name);
        }

        $this->name  = $name;
        $this->label = $label ?? self::humanise($name);
    }

    /**
     * Forwards unknown method calls to the owning form, which allows inline
     * chaining from one field definition to the next.
     *
     * @param list<mixed> $arguments
     */
    public function __call(string $method, array $arguments): mixed
    {
        if ($this->parent !== null && is_callable([$this->parent, $method])) {
            return $this->parent->{$method}(...$arguments);
        }

        throw FormException::forUndefinedMethod(static::class, $method);
    }

    /**
     * Links the field to the form that created it.
     *
     * @internal Called by the form; not part of the public API.
     */
    public function setParent(object $parent): static
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Returns the form this field belongs to, ending an inline chain.
     */
    public function end(): object
    {
        if ($this->parent === null) {
            throw FormException::forFieldWithoutForm($this->name);
        }

        return $this->parent;
    }

    public function label(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Sets the validation rules, as a CodeIgniter rule string
     * ("required|max_length[100]") or an array of rules and callables.
     *
     * @param array<array-key, mixed>|string $rules
     */
    public function rules(array|string $rules): static
    {
        $parsed = RuleParser::split($rules);

        if ($parsed === null) {
            throw FormException::forInvalidRule($this->name);
        }

        $this->rules = $parsed;

        return $this;
    }

    /**
     * Sets rules checked against every item of a multi-value field, such as
     * a multi-select or checkbox group. rules() still applies to the list as
     * a whole (for example "required" means at least one item).
     *
     * @param array<array-key, mixed>|string $rules
     */
    public function eachRules(array|string $rules): static
    {
        $parsed = RuleParser::split($rules);

        if ($parsed === null) {
            throw FormException::forInvalidRule($this->name);
        }

        $this->eachRules = $parsed;

        return $this;
    }

    /**
     * Custom validation messages keyed by rule name, for example
     * ['required' => 'Please tell us your name.'].
     *
     * @param array<string, string> $messages
     */
    public function messages(array $messages): static
    {
        foreach ($messages as $rule => $message) {
            $this->messages[(string) $rule] = (string) $message;
        }

        return $this;
    }

    /**
     * Sets several HTML attributes at once. "id", "class", "disabled" and
     * "placeholder" are routed to their dedicated setters.
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
     * Sets one HTML attribute. True renders a bare attribute (required),
     * false or null removes it.
     */
    public function attribute(string $attribute, bool|float|int|string|Stringable|null $value): static
    {
        if (preg_match('/^[A-Za-z_:][A-Za-z0-9_:.\-]*$/', $attribute) !== 1) {
            throw FormException::forInvalidAttributeName($this->name, $attribute);
        }

        $key = strtolower($attribute);

        if (in_array($key, self::RESERVED_ATTRIBUTES, true)) {
            throw FormException::forReservedAttribute($this->name, $key);
        }

        switch ($key) {
            case 'id':
                return $this->id($value === null || $value === false ? null : (string) $value);

            case 'class':
                return $value === null || $value === false ? $this : $this->addClass((string) $value);

            case 'disabled':
                return $this->disabled($value !== null && $value !== false);

            case 'placeholder':
                return $this->placeholder($value === null || $value === false ? null : (string) $value);
        }

        if ($value === null || $value === false) {
            unset($this->attributes[$key]);

            return $this;
        }

        $this->attributes[$key] = $value;

        return $this;
    }

    /**
     * Adds CSS classes on top of the theme's classes for this element.
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

    public function help(?string $help): static
    {
        $this->help = $help === null || trim($help) === '' ? null : $help;

        return $this;
    }

    /**
     * Column width (1 to 12) inside a row; ignored outside rows.
     */
    public function width(?int $columns): static
    {
        if ($columns !== null && ($columns < 1 || $columns > 12)) {
            throw FormException::forInvalidWidth('field "' . $this->name . '"', $columns);
        }

        $this->width = $columns;

        return $this;
    }

    public function id(?string $id): static
    {
        $this->id = $id === null || trim($id) === '' ? null : trim($id);

        return $this;
    }

    public function default(mixed $value): static
    {
        $this->default = $value;

        return $this;
    }

    public function placeholder(?string $placeholder): static
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    public function disabled(bool $disabled = true): static
    {
        $this->disabled = $disabled;

        return $this;
    }

    /**
     * Read-only fields are still submitted by the browser and can be altered
     * by a user, so keep validating them; use disabled() to exclude a value.
     */
    public function readonly(bool $readonly = true): static
    {
        return $this->attribute('readonly', $readonly);
    }

    public function autofocus(bool $autofocus = true): static
    {
        return $this->attribute('autofocus', $autofocus);
    }

    public function autocomplete(string $value): static
    {
        return $this->attribute('autocomplete', $value);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getType(): string
    {
        return static::TYPE;
    }

    public function getView(): string
    {
        return static::VIEW;
    }

    public function getRules(): array
    {
        return $this->rules;
    }

    public function getEachRules(): array
    {
        return $this->eachRules;
    }

    public function isMultiple(): bool
    {
        return false;
    }

    public function getMessages(): array
    {
        return $this->messages;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getClasses(): array
    {
        return $this->classes;
    }

    public function getHelp(): ?string
    {
        return $this->help;
    }

    public function getWidth(): ?int
    {
        return $this->width;
    }

    public function getId(string $prefix = ''): string
    {
        if ($this->id !== null) {
            return $this->id;
        }

        $id = $prefix === '' ? $this->name : $prefix . '_' . $this->name;

        return (string) preg_replace('/[^A-Za-z0-9_\-]/', '_', $id);
    }

    public function getDefault(): mixed
    {
        return $this->default;
    }

    public function getPlaceholder(): ?string
    {
        return $this->placeholder;
    }

    public function isRequired(): bool
    {
        return RuleParser::has($this->rules, 'required');
    }

    public function isDisabled(): bool
    {
        return $this->disabled;
    }

    public function isSubmittable(): bool
    {
        return ! $this->disabled;
    }

    public function shouldRepopulate(): bool
    {
        return true;
    }

    public function ruleAttributes(Forms $config): array
    {
        if (! $config->ruleAttributes) {
            return [];
        }

        $attributes = $this->isRequired() ? ['required' => true] : [];

        return array_merge($attributes, $this->extraRuleAttributes());
    }

    /**
     * Multi-value fields always get a list: a single submitted value is
     * wrapped, nested arrays and empty items are dropped. Single-value fields
     * never get an array: a submitted array (name[] sent to a plain field) is
     * treated as no value, so it can never reach the database.
     */
    public function normaliseValue(mixed $value, Forms $config): mixed
    {
        if ($this->isMultiple()) {
            $items = match (true) {
                is_array($value)               => $value,
                $value === null, $value === '' => [],
                default                        => [$value],
            };

            $list = [];

            foreach ($items as $item) {
                if (! is_scalar($item)) {
                    continue;
                }

                $item = $this->normaliseScalar($item, $config);

                if ($item !== null && $item !== '') {
                    $list[] = $item;
                }
            }

            return $list;
        }

        if (is_array($value)) {
            return null;
        }

        return $this->normaliseScalar($value, $config);
    }

    public function valueWhenMissing(Forms $config): mixed
    {
        return $this->isMultiple() ? [] : null;
    }

    public function toArray(): array
    {
        return [
            'type'        => $this->getType(),
            'name'        => $this->name,
            'label'       => $this->label,
            'rules'       => array_values(array_filter($this->rules, 'is_string')),
            'each_rules'  => array_values(array_filter($this->eachRules, 'is_string')),
            'multiple'    => $this->isMultiple(),
            'messages'    => $this->messages,
            'attributes'  => array_map(
                static fn (mixed $value): mixed => $value instanceof Stringable ? (string) $value : $value,
                $this->attributes,
            ),
            'classes'     => $this->classes,
            'help'        => $this->help,
            'width'       => $this->width,
            'id'          => $this->id,
            'default'     => $this->default,
            'placeholder' => $this->placeholder,
            'disabled'    => $this->disabled,
        ];
    }

    /**
     * Rule-derived attributes specific to a field type, beyond "required".
     *
     * @return array<string, bool|float|int|string>
     */
    protected function extraRuleAttributes(): array
    {
        return [];
    }

    /**
     * maxlength and minlength from max_length, min_length and exact_length.
     *
     * @return array<string, int>
     */
    protected function lengthRuleAttributes(): array
    {
        $attributes = [];

        $exact = RuleParser::numericParamOf($this->rules, 'exact_length');

        if (is_int($exact)) {
            return ['minlength' => $exact, 'maxlength' => $exact];
        }

        $max = RuleParser::numericParamOf($this->rules, 'max_length');
        $min = RuleParser::numericParamOf($this->rules, 'min_length');

        if (is_int($max)) {
            $attributes['maxlength'] = $max;
        }

        if (is_int($min)) {
            $attributes['minlength'] = $min;
        }

        return $attributes;
    }

    /**
     * Whether string values are trimmed when $trimStrings is on.
     */
    protected function trimsValue(): bool
    {
        return true;
    }

    protected function normaliseScalar(mixed $value, Forms $config): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        if ($config->trimStrings && $this->trimsValue()) {
            $value = trim($value);
        }

        if ($config->emptyStringsToNull && $value === '') {
            return null;
        }

        return $value;
    }

    /**
     * "appointment_date" becomes "Appointment date".
     */
    protected static function humanise(string $name): string
    {
        $label = trim((string) preg_replace('/\s+/', ' ', str_replace(['_', '-'], ' ', $name)));

        return ucfirst($label);
    }
}
