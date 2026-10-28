<?php

declare(strict_types=1);

namespace Ephraitech\Forms\Schema;

use Ephraitech\Forms\Contracts\ChoiceFieldInterface;
use Ephraitech\Forms\Contracts\FieldInterface;
use Ephraitech\Forms\Contracts\SchemaConfigurableInterface;
use Ephraitech\Forms\Fields\Checkbox;
use Ephraitech\Forms\Fields\Checkboxes;
use Ephraitech\Forms\Fields\Field;
use Ephraitech\Forms\Fields\File;
use Ephraitech\Forms\Fields\Radio;
use Ephraitech\Forms\Fields\Select;
use Ephraitech\Forms\Fields\Submit;
use Ephraitech\Forms\Form;
use Ephraitech\Forms\Layout\Container;
use Ephraitech\Forms\Layout\Fieldset;
use Ephraitech\Forms\Layout\Row;
use Ephraitech\Forms\Support\FieldTypes;
use Stringable;

/**
 * Turns a form into a version 1 schema that SchemaLoader builds back into an
 * identical form.
 *
 * Options that come from a provider are exported as "options_from"; other
 * options, including closures, are exported as their current values.
 * Anything that cannot be written as data (a closure used as a rule, an
 * unregistered custom field class) makes the export fail with the field's
 * location, instead of being dropped silently.
 */
final class SchemaExporter
{
    /**
     * Attributes that have their own schema key, exported under that key
     * when the field type accepts it.
     */
    private const ATTRIBUTE_KEYS = ['autocomplete', 'autofocus', 'readonly', 'rows', 'step', 'min', 'max', 'size'];

    /**
     * @var list<array{path: string, message: string}>
     */
    private array $errors = [];

    /**
     * @return array<string, mixed>
     *
     * @throws SchemaException When part of the form cannot be written as data.
     */
    public function export(Form $form): array
    {
        $this->errors = [];

        // Getters only: Form::toArray() describes every field, which would
        // resolve options and call option providers.
        $schema = ['version' => SchemaLoader::VERSION, 'name' => $form->getName()];

        if ($form->getMethod() !== $form->getConfig()->defaultMethod()) {
            $schema['method'] = $form->getMethod();
        }

        if ($form->getAction() !== null) {
            $schema['action'] = $form->getAction();
        }

        if ($form->getExplicitId() !== null) {
            $schema['id'] = $form->getExplicitId();
        }

        if ($form->getClasses() !== []) {
            $schema['classes'] = $form->getClasses();
        }

        $attributes = array_map(
            static fn (mixed $value): mixed => $value instanceof Stringable ? (string) $value : $value,
            $form->getAttributes(),
        );

        if ($attributes !== []) {
            $schema['attributes'] = $attributes;
        }

        $schema['fields'] = $this->nodes($form, $form->getLayout(), 'fields');

        if ($this->errors !== []) {
            throw SchemaException::forErrors($this->errors);
        }

        return $schema;
    }

    /**
     * @param list<Container|string> $nodes
     *
     * @return list<array<string, mixed>>
     */
    private function nodes(Form $form, array $nodes, string $path): array
    {
        $result = [];

        foreach ($nodes as $node) {
            $nodePath = $path . '[' . count($result) . ']';

            if (is_string($node)) {
                if ($form->has($node)) {
                    $result[] = $this->field($form, $form->getField($node), $nodePath);
                }

                continue;
            }

            if (! $node->isEmpty()) {
                $result[] = $this->group($form, $node, $nodePath);
            }
        }

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    private function group(Form $form, Container $group, string $path): array
    {
        $data = ['type' => $group->getType()];

        if ($group instanceof Fieldset) {
            $data['legend'] = $group->getLegend();

            if ($group->getDescription() !== null) {
                $data['description'] = $group->getDescription();
            }

            if ($group->getId() !== null) {
                $data['id'] = $group->getId();
            }
        }

        if ($group->getClasses() !== []) {
            $data['classes'] = $group->getClasses();
        }

        if ($group->getWidth() !== null) {
            $data['width'] = $group->getWidth();
        }

        $data['children'] = $this->nodes($form, $group->getChildren(), $path . '.children');

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function field(Form $form, FieldInterface $field, string $path): array
    {
        $type = $this->typeKey($form, $field, $path);

        if ($type === null) {
            return ['type' => $field->getType(), 'name' => $field->getName()];
        }

        $allowed = SchemaLoader::TYPE_KEYS[$type] ?? null;
        $data    = ['type' => $type];

        if (! $field instanceof Submit || $field->getName() !== 'submit') {
            $data['name'] = $field->getName();
        }

        if ($allowed === null || in_array('label', $allowed, true)) {
            $data['label'] = $field->getLabel();
        }

        // Read through getters, never toArray(): a choice field's toArray()
        // resolves its options, which would call an option provider.
        $attributes = array_map(
            static fn (mixed $value): mixed => $value instanceof Stringable ? (string) $value : $value,
            $field instanceof Field ? $field->getOwnAttributes() : $field->getAttributes(),
        );

        $values = [];

        foreach (self::ATTRIBUTE_KEYS as $key) {
            if (array_key_exists($key, $attributes) && ($allowed === null || in_array($key, $allowed, true))) {
                $values[$key] = $attributes[$key];
                unset($attributes[$key]);
            }
        }

        $values += [
            'id'          => $field instanceof Field ? $field->getExplicitId() : null,
            'classes'     => $field->getClasses(),
            'attributes'  => $attributes,
            'width'       => $field->getWidth(),
            'help'        => $field->getHelp(),
            'placeholder' => $field->getPlaceholder(),
            'default'     => $field->getDefault(),
            'disabled'    => $field->isDisabled(),
            'messages'    => $field->getMessages(),
        ];

        $rules = $field instanceof File ? $field->getExtraRules() : $field->getRules();

        $values['rules']      = $this->rules($rules, $path . '.rules');
        $values['each_rules'] = $this->rules($field->getEachRules(), $path . '.each_rules');

        if ($field instanceof Select || $field instanceof Radio || $field instanceof Checkboxes) {
            $provider = $field->getOptionProvider();

            if ($provider !== null) {
                $values['options_from'] = $provider;
            } else {
                $values['options'] = $field->getOptions();
            }
        }

        if ($field instanceof ChoiceFieldInterface) {
            $values['strict_options'] = $field->getStrictOptions();
        }

        if ($field instanceof Select) {
            $values['multiple'] = $field->isMultiple();
        }

        if ($field instanceof Radio || $field instanceof Checkboxes || $field instanceof Checkbox) {
            $values['inline'] = $field->isInline();
        }

        if ($field instanceof Checkbox && $field->getCheckedValue() !== '1') {
            $values['checked_value'] = $field->getCheckedValue();
        }

        if ($field instanceof File) {
            $raw = $field->toArray();

            $values += [
                'required'       => $field->isUploadRequired(),
                'multiple'       => $field->isMultiple(),
                'max_size'       => $raw['max_size'],
                'extensions'     => $raw['extensions'],
                'mimes'          => $raw['mimes'],
                'image'          => $raw['image'],
                'max_dimensions' => $raw['max_dimensions'],
            ];
        }

        foreach ($values as $key => $value) {
            if ($this->isEmpty($key, $value) || ($allowed !== null && ! in_array($key, $allowed, true))) {
                continue;
            }

            $data[$key] = $value;
        }

        if ($field instanceof SchemaConfigurableInterface) {
            foreach ($field->schemaValues() as $key => $value) {
                $data[$key] = $value;
            }
        }

        return $data;
    }

    /**
     * The schema type key: the built-in key, or the name the class is
     * registered under in $fieldTypes.
     */
    private function typeKey(Form $form, FieldInterface $field, string $path): ?string
    {
        if (isset(FieldTypes::BUILT_IN[$field->getType()]) && FieldTypes::BUILT_IN[$field->getType()] === $field::class) {
            return $field->getType();
        }

        foreach (FieldTypes::registered($form->getConfig()) as $key => $class) {
            if ($class === $field::class) {
                return $key;
            }
        }

        $this->errors[] = [
            'path'    => $path,
            'message' => sprintf('field "%s" is a %s, which is not a registered field type; register it in $fieldTypes to export it.', $field->getName(), $field::class),
        ];

        return null;
    }

    /**
     * Rules as one string, or as a list when a rule itself contains "|".
     *
     * @param list<callable|string> $rules
     *
     * @return list<string>|string|null
     */
    private function rules(array $rules, string $path): array|string|null
    {
        if ($rules === []) {
            return null;
        }

        foreach ($rules as $rule) {
            if (! is_string($rule)) {
                $this->errors[] = ['path' => $path, 'message' => 'a closure or callable rule cannot be written to a schema; use a named rule instead.'];

                return null;
            }
        }

        foreach ($rules as $rule) {
            if (str_contains($rule, '|')) {
                return $rules;
            }
        }

        return implode('|', $rules);
    }

    private function isEmpty(string $key, mixed $value): bool
    {
        return $value === null
            || $value === []
            || ($value === false && ! in_array($key, ['strict_options'], true));
    }
}
