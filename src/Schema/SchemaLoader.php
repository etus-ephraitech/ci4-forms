<?php

declare(strict_types=1);

namespace Ephraitech\Forms\Schema;

use Ephraitech\Forms\Config\Forms;
use Ephraitech\Forms\Contracts\FieldInterface;
use Ephraitech\Forms\Contracts\SchemaConfigurableInterface;
use Ephraitech\Forms\Exceptions\FormException;
use Ephraitech\Forms\Fields\Field;
use Ephraitech\Forms\Form;
use Ephraitech\Forms\Support\FieldTypes;
use JsonException;
use TypeError;

/**
 * Builds forms from schemas: plain arrays, usually decoded from JSON.
 *
 * Loading is all or nothing. The schema is first built on a scratch form,
 * collecting every error with its location; only a schema without errors
 * is then built on the real form. Validation and building run the same
 * code, so they cannot disagree.
 *
 * Schema format (version 1):
 *
 *     {
 *       "version": 1,
 *       "name": "enquiry",
 *       "method": "post",
 *       "fields": [
 *         { "type": "row", "children": [
 *           { "type": "text", "name": "full_name", "rules": "required", "width": 6 },
 *           { "type": "tel",  "name": "phone", "width": 6 }
 *         ]},
 *         { "type": "fieldset", "legend": "Order", "children": [ ... ] },
 *         { "type": "submit", "label": "Send" }
 *       ]
 *     }
 */
final class SchemaLoader
{
    public const VERSION = 1;

    private const FORM_KEYS = ['version', 'name', 'method', 'action', 'id', 'classes', 'attributes', 'fields'];

    private const BASE = ['type', 'name', 'label', 'id', 'classes', 'attributes', 'width'];

    private const VALUE = [...self::BASE, 'rules', 'messages', 'help', 'default', 'disabled', 'readonly', 'autofocus'];

    private const TEXT_LIKE = [...self::VALUE, 'placeholder', 'autocomplete'];

    private const CHOICE = ['options', 'strict_options'];

    /**
     * Pointers for keys people often try from HTML habits.
     */
    private const KEY_HINTS = [
        'maxlength'   => 'set length limits with "rules", for example "max_length[100]"',
        'minlength'   => 'set length limits with "rules", for example "min_length[3]"',
        'required'    => 'make it required with "rules": "required"',
        'value'       => 'use "default" for the initial value',
        'class'       => 'use "classes"',
        'checked'     => 'use "default" (1 for ticked)',
        'selected'    => 'use "default" with the option value',
        'description' => 'use "help" for text under a field',
    ];

    /**
     * Keys each built-in type accepts.
     */
    private const TYPE_KEYS = [
        'text'           => self::TEXT_LIKE,
        'email'          => self::TEXT_LIKE,
        'password'       => self::TEXT_LIKE,
        'tel'            => self::TEXT_LIKE,
        'url'            => self::TEXT_LIKE,
        'search'         => self::TEXT_LIKE,
        'number'         => [...self::TEXT_LIKE, 'step'],
        'date'           => [...self::VALUE, 'autocomplete', 'min', 'max', 'step'],
        'time'           => [...self::VALUE, 'autocomplete', 'min', 'max', 'step'],
        'datetime_local' => [...self::VALUE, 'autocomplete', 'min', 'max', 'step'],
        'color'          => self::VALUE,
        'textarea'       => [...self::TEXT_LIKE, 'rows'],
        'select'         => [...self::VALUE, ...self::CHOICE, 'placeholder', 'multiple', 'size', 'each_rules'],
        'radio'          => [...self::VALUE, ...self::CHOICE, 'inline'],
        'checkboxes'     => [...self::VALUE, ...self::CHOICE, 'inline', 'each_rules'],
        'checkbox'       => [...self::VALUE, 'strict_options', 'checked_value', 'inline'],
        'file'           => [...self::BASE, 'rules', 'messages', 'help', 'disabled', 'autofocus', 'required', 'multiple', 'max_size', 'extensions', 'mimes', 'image', 'max_dimensions'],
        'hidden'         => ['type', 'name', 'id', 'attributes', 'default', 'rules', 'messages'],
        'submit'         => ['type', 'name', 'label', 'id', 'classes', 'attributes', 'width', 'disabled'],
    ];

    private const GROUP_KEYS = [
        'row'      => ['type', 'children', 'classes', 'width'],
        'fieldset' => ['type', 'legend', 'description', 'id', 'classes', 'width', 'children'],
    ];

    /**
     * Setter for each schema key. Keys are applied in the order they appear
     * in the schema, so errors are listed in reading order.
     */
    private const SETTERS = [
        'id'             => 'id',
        'help'           => 'help',
        'placeholder'    => 'placeholder',
        'autocomplete'   => 'autocomplete',
        'classes'        => 'addClass',
        'attributes'     => 'attributes',
        'width'          => 'width',
        'options'        => 'options',
        'multiple'       => 'multiple',
        'size'           => 'size',
        'inline'         => 'inline',
        'checked_value'  => 'checkedValue',
        'rows'           => 'rows',
        'step'           => 'step',
        'min'            => 'min',
        'max'            => 'max',
        'required'       => 'required',
        'max_size'       => 'maxSize',
        'extensions'     => 'extensions',
        'mimes'          => 'mimes',
        'image'          => 'image',
        'max_dimensions' => 'maxDimensions',
        'rules'          => 'rules',
        'each_rules'     => 'eachRules',
        'messages'       => 'messages',
        'strict_options' => 'strictOptions',
        'default'        => 'default',
        'disabled'       => 'disabled',
        'readonly'       => 'readonly',
        'autofocus'      => 'autofocus',
    ];

    /**
     * Collected while building.
     *
     * @var list<array{path: string, message: string}>
     */
    private array $errors = [];

    /**
     * Field names already used: in the form before loading, or earlier in
     * the schema.
     *
     * @var array<string, true>
     */
    private array $names = [];

    public function __construct(private readonly Forms $config)
    {
    }

    /**
     * Decodes a JSON schema.
     *
     * @return array<array-key, mixed>
     *
     * @throws SchemaException When the JSON is invalid or not an object.
     */
    public static function decodeJson(string $json): array
    {
        try {
            // Decoded as objects first: as arrays, "[]" and "{}" look the same.
            $top = json_decode($json, false, 64, JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING);
        } catch (JsonException $e) {
            throw SchemaException::forInvalidJson($e->getMessage());
        }

        if (! is_object($top)) {
            throw SchemaException::forErrors([['path' => '', 'message' => 'the schema must be a JSON object with a "fields" list.']]);
        }

        /** @var array<array-key, mixed> */
        return json_decode($json, true, 64, JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING);
    }

    /**
     * Every error in the schema, without changing anything. Pass the form
     * the schema will be loaded into to also catch clashes with fields it
     * already has.
     *
     * @param array<array-key, mixed> $schema
     *
     * @return list<array{path: string, message: string}>
     */
    public function errors(array $schema, ?Form $into = null): array
    {
        $scratch = Form::make('schema_check', $this->config);

        $this->errors = [];
        $this->names  = [];

        foreach (array_keys($into?->getFields() ?? []) as $name) {
            $this->names[$name] = true;
        }

        $this->build($schema, $scratch, $into);

        return $this->errors;
    }

    /**
     * Loads a schema into a form: validates it completely, then builds it.
     *
     * @param array<array-key, mixed> $schema
     *
     * @throws SchemaException When the schema has errors; the form is left unchanged.
     */
    public function load(array $schema, Form $form): void
    {
        $errors = $this->errors($schema, $form);

        if ($errors !== []) {
            throw SchemaException::forErrors($errors);
        }

        $this->errors = [];
        $this->names  = [];

        foreach (array_keys($form->getFields()) as $name) {
            $this->names[$name] = true;
        }

        $this->build($schema, $form, $form);

        if ($this->errors !== []) {
            // Not reachable when the dry run passed; kept as a guard so a
            // half-built form is never returned silently.
            throw SchemaException::forErrors($this->errors);
        }
    }

    // -------------------------------------------------------------------------
    // Building
    // -------------------------------------------------------------------------

    /**
     * @param array<array-key, mixed> $schema
     */
    private function build(array $schema, Form $form, ?Form $into): void
    {
        if ($schema !== [] && array_is_list($schema)) {
            $this->error('', 'the schema must be an object with a "fields" list, not a list.');

            return;
        }

        $this->unknownKeys($schema, self::FORM_KEYS, '', 'the form');

        if (array_key_exists('version', $schema) && $schema['version'] !== self::VERSION) {
            $this->error('version', is_int($schema['version'])
                ? sprintf('schema version %d is not supported; this version of ci4-forms reads version %d.', $schema['version'], self::VERSION)
                : 'must be the number ' . self::VERSION . '.');
        }

        if (array_key_exists('name', $schema)) {
            $name = $schema['name'];

            if (! is_string($name) || preg_match('/^[A-Za-z_][A-Za-z0-9_\-]*$/', $name) !== 1) {
                $this->error('name', 'must start with a letter or underscore and contain only letters, digits, underscores and hyphens.');
            } elseif ($into !== null && $name !== $into->getName()) {
                $this->error('name', sprintf('is "%s" but the form is named "%s"; a loaded form keeps its name.', $name, $into->getName()));
            }
        }

        $this->formSetting($schema, 'method', $form, static function (Form $form, mixed $value): void {
            if (! is_string($value)) {
                throw new FormException('must be one of: ' . implode(', ', Forms::METHODS) . '.');
            }

            $form->method($value);
        });

        $this->formSetting($schema, 'action', $form, static function (Form $form, mixed $value): void {
            if (! is_string($value)) {
                throw new FormException('must be a string.');
            }

            $form->action($value);
        });

        $this->formSetting($schema, 'id', $form, static function (Form $form, mixed $value): void {
            if ($value !== null && ! is_string($value)) {
                throw new FormException('must be a string.');
            }

            $form->id($value);
        });

        $this->formSetting($schema, 'classes', $form, function (Form $form, mixed $value): void {
            $form->addClass(...$this->classList($value));
        });

        $this->formSetting($schema, 'attributes', $form, function (Form $form, mixed $value): void {
            $form->attributes($this->attributeMap($value));
        });

        if (! array_key_exists('fields', $schema)) {
            $this->error('fields', 'is required: a list of fields and groups.');

            return;
        }

        if (! is_array($schema['fields']) || ($schema['fields'] !== [] && ! array_is_list($schema['fields']))) {
            $this->error('fields', 'must be a list of fields and groups.');

            return;
        }

        $this->nodes($form, $schema['fields'], 'fields');
    }

    /**
     * @param list<mixed> $nodes
     */
    private function nodes(Form $form, array $nodes, string $path): void
    {
        foreach ($nodes as $index => $node) {
            $this->node($form, $node, $path . '[' . $index . ']');
        }
    }

    private function node(Form $form, mixed $node, string $path): void
    {
        if (! is_array($node) || ($node !== [] && array_is_list($node))) {
            $this->error($path, 'must be an object with a "type".');

            return;
        }

        $type = $node['type'] ?? null;

        if (! is_string($type) || $type === '') {
            $this->error($path . '.type', 'is required.');

            return;
        }

        if (isset(self::GROUP_KEYS[$type])) {
            $this->group($form, $type, $node, $path);

            return;
        }

        $this->field($form, $type, $node, $path);
    }

    /**
     * @param array<array-key, mixed> $node
     */
    private function group(Form $form, string $type, array $node, string $path): void
    {
        $this->unknownKeys($node, self::GROUP_KEYS[$type], $path, 'a ' . $type);

        $children = $node['children'] ?? null;

        if (! is_array($children) || ($children !== [] && ! array_is_list($children))) {
            $this->error($path . '.children', 'is required: a list of fields and groups.');
            $children = [];
        }

        $legend = '';

        if ($type === 'fieldset') {
            if (! array_key_exists('legend', $node)) {
                $this->error($path . '.legend', 'is required (use "" for a fieldset without a title).');
            } elseif (! is_string($node['legend'])) {
                $this->error($path . '.legend', 'must be a string.');
            } else {
                $legend = $node['legend'];
            }
        }

        $define    = fn (Form $form) => $this->nodes($form, $children, $path . '.children');
        $container = $type === 'row' ? $form->row($define) : $form->fieldset($legend, $define);

        $this->groupSetting($node, $path, 'classes', function () use ($container, $node): void {
            $container->addClass(...$this->classList($node['classes']));
        });

        $this->groupSetting($node, $path, 'width', static function () use ($container, $node): void {
            if ($node['width'] !== null && ! is_int($node['width'])) {
                throw new FormException('must be a whole number from 1 to 12.');
            }

            $container->width($node['width']);
        });

        if ($type === 'fieldset') {
            /** @var \Ephraitech\Forms\Layout\Fieldset $container */
            $this->groupSetting($node, $path, 'description', static function () use ($container, $node): void {
                if ($node['description'] !== null && ! is_string($node['description'])) {
                    throw new FormException('must be a string.');
                }

                $container->description($node['description']);
            });

            $this->groupSetting($node, $path, 'id', static function () use ($container, $node): void {
                if ($node['id'] !== null && ! is_string($node['id'])) {
                    throw new FormException('must be a string.');
                }

                $container->id($node['id']);
            });
        }
    }

    /**
     * @param array<array-key, mixed> $node
     */
    private function field(Form $form, string $type, array $node, string $path): void
    {
        $class = FieldTypes::resolve($type, $this->config);

        if ($class === null) {
            $this->error($path . '.type', sprintf('unknown field type "%s"%s.', $type, $this->suggest($type, $this->knownTypes())));

            return;
        }

        $extras  = is_subclass_of($class, SchemaConfigurableInterface::class) ? $class::schemaProperties() : [];
        $allowed = self::TYPE_KEYS[$type] ?? [...(is_subclass_of($class, Field::class) ? self::TEXT_LIKE : ['type', 'name', 'label']), ...array_keys($extras)];

        $this->unknownKeys($node, $allowed, $path, 'a "' . $type . '" field');

        $name = $node['name'] ?? ($type === 'submit' ? 'submit' : null);

        if (! is_string($name) || $name === '') {
            $this->error($path . '.name', 'is required.');

            return;
        }

        if (isset($this->names[$name])) {
            $this->error($path . '.name', sprintf('"%s" is already used by another field.', $name));

            return;
        }

        $label = $node['label'] ?? null;

        if ($label !== null && ! is_string($label)) {
            $this->error($path . '.label', 'must be a string.');
            $label = null;
        }

        try {
            $field = $form->addField($type, $name, $label);
        } catch (FormException $e) {
            $this->error($path . '.name', $this->reason($e));

            return;
        }

        $this->names[$name] = true;

        if (in_array($type, ['select', 'radio', 'checkboxes'], true) && ! array_key_exists('options', $node)) {
            $this->error($path . '.options', 'is required for a "' . $type . '" field.');
        }

        if ($type === 'select' && array_key_exists('each_rules', $node) && ($node['multiple'] ?? false) !== true) {
            $this->error($path . '.each_rules', 'only applies to a select with "multiple": true.');
        }

        foreach ($node as $key => $value) {
            $key = (string) $key;

            if (isset(self::SETTERS[$key]) && in_array($key, $allowed, true)) {
                $this->apply($field, $key, self::SETTERS[$key], $value, $path . '.' . $key, $node);
            } elseif (isset($extras[$key])) {
                $this->apply($field, $key, $extras[$key], $value, $path . '.' . $key, $node);
            }
        }
    }

    /**
     * Checks a value's shape, then calls the field's setter. Setters do the
     * detailed checks (ranges, formats) and their messages are reported at
     * the key's path.
     *
     * @param array<array-key, mixed> $node
     */
    private function apply(FieldInterface $field, string $key, string $method, mixed $value, string $path, array $node): void
    {
        try {
            $arguments = match ($key) {
                'classes'        => $this->classList($value),
                'attributes'     => [$this->attributeMap($value)],
                'rules', 'each_rules' => [$this->ruleList($value)],
                'messages'       => [$this->stringMap($value)],
                'options'        => [$this->optionMap($value, $key === 'options' && ($node['type'] ?? '') === 'select')],
                'default'        => [$this->defaultValue($value, $field, $node)],
                'max_dimensions' => $this->dimensions($value),
                'extensions', 'mimes' => [$this->stringList($value)],
                'step'           => [$this->step($value, (string) ($node['type'] ?? ''))],
                'width'          => [$this->optionalInt($value, 'a whole number from 1 to 12')],
                'size', 'rows', 'max_size' => [$this->int($value)],
                'checked_value'  => [$this->checkedValue($value)],
                'id', 'help', 'placeholder' => [$this->optionalString($value)],
                'autocomplete', 'min', 'max' => [$this->string($value)],
                'multiple', 'inline', 'image', 'required', 'strict_options', 'disabled', 'readonly', 'autofocus' => [$this->bool($value)],
                default          => [$value],
            };

            if (! method_exists($field, $method)) {
                throw new FormException('is not supported by this field type.');
            }

            $field->{$method}(...$arguments);
        } catch (FormException $e) {
            $this->error($path, $this->reason($e));
        } catch (TypeError) {
            $this->error($path, 'has the wrong type of value.');
        }
    }

    /**
     * @param array<array-key, mixed>       $schema
     * @param \Closure(Form, mixed): void   $apply
     */
    private function formSetting(array $schema, string $key, Form $form, \Closure $apply): void
    {
        if (! array_key_exists($key, $schema)) {
            return;
        }

        try {
            $apply($form, $schema[$key]);
        } catch (FormException $e) {
            $this->error($key, $this->reason($e));
        } catch (TypeError) {
            $this->error($key, 'has the wrong type of value.');
        }
    }

    /**
     * @param array<array-key, mixed> $node
     * @param \Closure(): void        $apply
     */
    private function groupSetting(array $node, string $path, string $key, \Closure $apply): void
    {
        if (! array_key_exists($key, $node)) {
            return;
        }

        try {
            $apply();
        } catch (FormException $e) {
            $this->error($path . '.' . $key, $this->reason($e));
        } catch (TypeError) {
            $this->error($path . '.' . $key, 'has the wrong type of value.');
        }
    }

    // -------------------------------------------------------------------------
    // Value checks
    // -------------------------------------------------------------------------

    private function string(mixed $value): string
    {
        if (! is_string($value)) {
            throw new FormException('must be a string.');
        }

        return $value;
    }

    private function optionalString(mixed $value): ?string
    {
        return $value === null ? null : $this->string($value);
    }

    private function bool(mixed $value): bool
    {
        if (! is_bool($value)) {
            throw new FormException('must be true or false.');
        }

        return $value;
    }

    private function int(mixed $value): int
    {
        if (! is_int($value)) {
            throw new FormException('must be a whole number.');
        }

        return $value;
    }

    private function optionalInt(mixed $value, string $description): ?int
    {
        if ($value !== null && ! is_int($value)) {
            throw new FormException('must be ' . $description . '.');
        }

        return $value;
    }

    /**
     * @return list<string>
     */
    private function classList(mixed $value): array
    {
        if (is_string($value)) {
            return [$value];
        }

        if (is_array($value) && array_is_list($value) && array_filter($value, 'is_string') === $value) {
            return $value;
        }

        throw new FormException('must be a string or a list of strings.');
    }

    /**
     * @return list<string>
     */
    private function stringList(mixed $value): array
    {
        if (is_string($value)) {
            return [$value];
        }

        if (is_array($value) && array_is_list($value) && $value !== [] && array_filter($value, 'is_string') === $value) {
            return $value;
        }

        throw new FormException('must be a string or a non-empty list of strings.');
    }

    /**
     * @return list<string>|string
     */
    private function ruleList(mixed $value): array|string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_array($value) && array_is_list($value)) {
            foreach ($value as $rule) {
                if (! is_string($rule) || trim($rule) === '') {
                    throw new FormException('must be a rule string such as "required|max_length[100]" or a list of rule strings.');
                }
            }

            return $value;
        }

        throw new FormException('must be a rule string such as "required|max_length[100]" or a list of rule strings.');
    }

    /**
     * @return array<string, string>
     */
    private function stringMap(mixed $value): array
    {
        if (! is_array($value) || ($value !== [] && array_is_list($value))) {
            throw new FormException('must be an object of rule name to message.');
        }

        foreach ($value as $message) {
            if (! is_string($message)) {
                throw new FormException('every message must be a string.');
            }
        }

        return $value;
    }

    /**
     * @return array<string, bool|float|int|string|null>
     */
    private function attributeMap(mixed $value): array
    {
        if (! is_array($value) || ($value !== [] && array_is_list($value))) {
            throw new FormException('must be an object of attribute name to value.');
        }

        foreach ($value as $attribute) {
            if ($attribute !== null && ! is_scalar($attribute)) {
                throw new FormException('every attribute value must be a string, number, true, false or null.');
            }
        }

        return $value;
    }

    /**
     * @return array<array-key, mixed>
     */
    private function optionMap(mixed $value, bool $allowGroups): array
    {
        if (! is_array($value)) {
            throw new FormException('must be an object of value to label.');
        }

        if ($value !== [] && array_is_list($value)) {
            throw new FormException('must be an object of value to label, such as {"fitting": "Suit fitting"}, not a list.');
        }

        foreach ($value as $label) {
            if (is_array($label)) {
                if (! $allowGroups) {
                    throw new FormException('option groups are only supported by select fields.');
                }

                if ($label !== [] && array_is_list($label)) {
                    throw new FormException('each option group must be an object of value to label.');
                }

                foreach ($label as $groupLabel) {
                    if (! is_scalar($groupLabel)) {
                        throw new FormException('every option label must be a string or number.');
                    }
                }

                continue;
            }

            if (! is_scalar($label)) {
                throw new FormException('every option label must be a string or number.');
            }
        }

        return $value;
    }

    /**
     * @param array<array-key, mixed> $node
     */
    private function defaultValue(mixed $value, FieldInterface $field, array $node): mixed
    {
        $multiple = $field->isMultiple() || (($node['type'] ?? '') === 'select' && ($node['multiple'] ?? false) === true);

        if ($multiple) {
            if (! is_array($value) || ! array_is_list($value) || array_filter($value, 'is_scalar') !== $value) {
                throw new FormException('must be a list of values for a field that holds several values.');
            }

            return $value;
        }

        if ($value !== null && ! is_scalar($value)) {
            throw new FormException('must be a single value (string, number, true or false) or null.');
        }

        return $value;
    }

    /**
     * @return array{int, int}
     */
    private function dimensions(mixed $value): array
    {
        if (is_array($value) && isset($value['width'], $value['height']) && count($value) === 2) {
            $value = [$value['width'], $value['height']];
        }

        if (! is_array($value) || ! array_is_list($value) || count($value) !== 2 || ! is_int($value[0]) || ! is_int($value[1])) {
            throw new FormException('must be [width, height] or {"width": ..., "height": ...} in whole pixels.');
        }

        return [$value[0], $value[1]];
    }

    private function step(mixed $value, string $type): float|int|string
    {
        if ($value === 'any') {
            return $value;
        }

        $wholeOnly = in_array($type, ['date', 'time', 'datetime_local'], true);

        if (is_int($value) && $value > 0) {
            return $value;
        }

        if (! $wholeOnly && is_float($value) && $value > 0) {
            return $value;
        }

        throw new FormException($wholeOnly ? 'must be a positive whole number or "any".' : 'must be a positive number or "any".');
    }

    private function checkedValue(mixed $value): int|string
    {
        if (! is_string($value) && ! is_int($value)) {
            throw new FormException('must be a string or whole number.');
        }

        return $value;
    }

    // -------------------------------------------------------------------------
    // Errors
    // -------------------------------------------------------------------------

    /**
     * @param array<array-key, mixed> $node
     * @param list<string>            $allowed
     */
    private function unknownKeys(array $node, array $allowed, string $path, string $subject): void
    {
        foreach (array_keys($node) as $key) {
            $key = (string) $key;

            if (in_array($key, $allowed, true)) {
                continue;
            }

            $where = $path === '' ? $key : $path . '.' . $key;
            $hint  = isset(self::KEY_HINTS[strtolower($key)]) && ! in_array(strtolower($key), $allowed, true)
                ? '; ' . self::KEY_HINTS[strtolower($key)]
                : $this->suggest($key, $allowed);

            $this->error($where, sprintf('is not a valid key for %s%s.', $subject, $hint));
        }
    }

    /**
     * A ' (did you mean "x"?)' hint for close misspellings.
     *
     * @param list<string> $candidates
     */
    private function suggest(string $given, array $candidates): string
    {
        $best     = null;
        $distance = PHP_INT_MAX;
        $given    = strtolower($given);

        foreach ($candidates as $candidate) {
            $d = levenshtein($given, strtolower($candidate));

            if ($d < $distance) {
                [$best, $distance] = [$candidate, $d];
            }

            if (str_replace(['_', '-'], '', $given) === str_replace(['_', '-'], '', strtolower($candidate))) {
                [$best, $distance] = [$candidate, 0];

                break;
            }
        }

        return $best !== null && $distance <= max(2, intdiv(strlen($given), 4)) ? sprintf(' (did you mean "%s"?)', $best) : '';
    }

    /**
     * @return list<string>
     */
    private function knownTypes(): array
    {
        return [...array_keys(FieldTypes::BUILT_IN), ...array_keys(FieldTypes::registered($this->config)), ...array_keys(self::GROUP_KEYS)];
    }

    /**
     * A FormException message rephrased for a schema path: setters name the
     * field, the path already does.
     */
    private function reason(FormException $e): string
    {
        $message = $e->getMessage();
        $message = (string) preg_replace('/^(File field|Field|Form) "[^"]*"(:| has invalid options:)?\s*/', '', $message);
        $message = (string) preg_replace('/^Width of [^ ]+( "[^"]*")? must/', 'must', $message);
        $message = strtr($message, [
            'maxSize() '       => '',
            'maxDimensions() ' => '',
        ]);

        $message = rtrim($message, '.');

        // Lower-case the first letter, but keep acronyms such as "HTTP".
        if (strlen($message) > 1 && ctype_upper($message[0]) && ! ctype_upper($message[1])) {
            $message = lcfirst($message);
        }

        return $message . '.';
    }

    private function error(string $path, string $message): void
    {
        $this->errors[] = ['path' => $path, 'message' => $message];
    }
}
