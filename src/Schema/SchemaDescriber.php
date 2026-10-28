<?php

declare(strict_types=1);

namespace Ephraitech\Forms\Schema;

use Ephraitech\Forms\Config\Forms;
use Ephraitech\Forms\Support\FieldTypes;
use Ephraitech\Forms\Support\OptionProviders;

/**
 * Describes what a schema may contain under a policy, as plain data: every
 * allowed field type with the keys it accepts and the kind of value each
 * takes, plus the allowed rules, attributes, option providers, upload types
 * and limits.
 *
 * Per type: "requires" lists keys a node must have (each entry gives
 * alternatives, one of which is needed) and "exclusive" lists keys that
 * cannot be used together.
 *
 * The key lists come from SchemaLoader itself, so the description and the
 * loader cannot disagree. A visual builder can build its property panels
 * from it; an admin help page can list what is available.
 */
final class SchemaDescriber
{
    /**
     * Kind of value each key takes. Kinds: name, text, optional_text, bool,
     * int, positive_int, width, rules, messages, options, provider,
     * attributes, classes, default, step, checked_value, strings,
     * dimensions, children, method.
     */
    private const KEY_KINDS = [
        'type'           => 'type',
        'name'           => 'name',
        'label'          => 'text',
        'legend'         => 'text',
        'description'    => 'optional_text',
        'id'             => 'optional_text',
        'help'           => 'optional_text',
        'placeholder'    => 'optional_text',
        'autocomplete'   => 'text',
        'min'            => 'text',
        'max'            => 'text',
        'classes'        => 'classes',
        'attributes'     => 'attributes',
        'width'          => 'width',
        'rules'          => 'rules',
        'each_rules'     => 'rules',
        'messages'       => 'messages',
        'default'        => 'default',
        'options'        => 'options',
        'options_from'   => 'provider',
        'strict_options' => 'bool',
        'multiple'       => 'bool',
        'inline'         => 'bool',
        'disabled'       => 'bool',
        'readonly'       => 'bool',
        'autofocus'      => 'bool',
        'required'       => 'bool',
        'image'          => 'bool',
        'size'           => 'positive_int',
        'rows'           => 'positive_int',
        'max_size'       => 'positive_int',
        'step'           => 'step',
        'checked_value'  => 'checked_value',
        'extensions'     => 'strings',
        'mimes'          => 'strings',
        'max_dimensions' => 'dimensions',
        'children'       => 'children',
    ];

    /**
     * One-line explanations, for tooltips and help pages.
     */
    private const KEY_HELP = [
        'type'           => 'The kind of field or group.',
        'name'           => 'Unique name used when the form is submitted; letters, digits, underscores and hyphens.',
        'label'          => 'Text shown with the field.',
        'legend'         => 'Title of the section; use "" for none.',
        'description'    => 'Short explanation shown under the section title.',
        'id'             => 'HTML id; generated when left out.',
        'help'           => 'Hint shown under the field.',
        'placeholder'    => 'Example text shown inside an empty field.',
        'autocomplete'   => 'Browser autofill hint, such as "name", "email" or "tel".',
        'min'            => 'Earliest selectable value.',
        'max'            => 'Latest selectable value.',
        'classes'        => 'Extra CSS classes.',
        'attributes'     => 'Extra HTML attributes.',
        'width'          => 'Column width inside a row, from 1 to 12.',
        'rules'          => 'Validation rules, such as "required|max_length[100]".',
        'each_rules'     => 'Rules for every chosen item.',
        'messages'       => 'Custom error messages by rule name; "options" for invalid choices.',
        'default'        => 'Initial value.',
        'options'        => 'Choices as value => label.',
        'options_from'   => 'Name of an option provider that supplies the choices.',
        'strict_options' => 'Reject submitted values that are not among the choices.',
        'multiple'       => 'Allow several choices or files.',
        'inline'         => 'Show the choices on one line.',
        'disabled'       => 'Shown but not editable, and never submitted.',
        'readonly'       => 'Shown but not editable; still submitted.',
        'autofocus'      => 'Put the cursor here when the page opens.',
        'required'       => 'A file must be uploaded.',
        'image'          => 'Only images.',
        'size'           => 'Visible rows of a multiple choice list.',
        'rows'           => 'Visible lines of text.',
        'max_size'       => 'Largest file size in kilobytes.',
        'step'           => 'Interval between allowed values, or "any".',
        'checked_value'  => 'Value sent when the box is ticked.',
        'extensions'     => 'Allowed file name extensions, such as "pdf".',
        'mimes'          => 'Allowed file types, such as "image/png".',
        'max_dimensions' => 'Largest image size as [width, height] in pixels.',
        'children'       => 'Fields and groups inside this group.',
    ];

    private const TYPE_LABELS = [
        'text'           => 'Text',
        'email'          => 'Email',
        'password'       => 'Password',
        'tel'            => 'Phone number',
        'url'            => 'Web address',
        'search'         => 'Search box',
        'number'         => 'Number',
        'date'           => 'Date',
        'time'           => 'Time',
        'datetime_local' => 'Date and time',
        'color'          => 'Colour',
        'textarea'       => 'Long text',
        'select'         => 'Dropdown',
        'radio'          => 'Single choice',
        'checkboxes'     => 'Multiple choice',
        'checkbox'       => 'Tick box',
        'file'           => 'File upload',
        'hidden'         => 'Hidden value',
        'submit'         => 'Button',
        'row'            => 'Row',
        'fieldset'       => 'Section',
    ];

    public function __construct(private readonly Forms $config, private readonly SchemaPolicy $policy)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function describe(): array
    {
        $policy    = $this->policy;
        $providers = array_values(array_filter(
            OptionProviders::names($this->config),
            static fn (string $name): bool => $policy->allowsProvider($name),
        ));

        $formKeys = ['version', 'name', 'method', 'id', 'classes', 'attributes', 'fields'];

        if ($policy->allowsAction()) {
            $formKeys[] = 'action';
        }

        return [
            'version' => SchemaLoader::VERSION,
            'policy'  => $policy->isTrusted() ? 'trusted' : 'restricted',
            'form'    => [
                'keys'    => $formKeys,
                'methods' => Forms::METHODS,
            ],
            'types'           => $this->types($providers),
            'rules'           => $policy->getRules(),
            'attributes'      => $policy->getAttributes(),
            'providers'       => $providers,
            'mimes'           => $policy->isTrusted() ? null : $policy->getMimes(),
            'max_upload_kb'   => $policy->isTrusted() ? null : $policy->getMaxUploadSize(),
            'forbidden_names' => $policy->getForbiddenNames(),
            'limits'          => $policy->getLimits(),
        ];
    }

    /**
     * @param list<string> $providers
     *
     * @return array<string, array<string, mixed>>
     */
    private function types(array $providers): array
    {
        $types = [];

        $classes = FieldTypes::BUILT_IN + FieldTypes::registered($this->config);

        foreach ($classes as $type => $class) {
            if (! $this->policy->allowsType($type)) {
                continue;
            }

            $keys = SchemaLoader::keysFor($type, $class);

            if ($providers === [] && ($index = array_search('options_from', $keys, true)) !== false) {
                unset($keys[$index]);
                $keys = array_values($keys);
            }

            $types[$type] = [
                'label'    => self::TYPE_LABELS[$type] ?? ucfirst(str_replace('_', ' ', $type)),
                'group'    => false,
                'custom'   => ! isset(FieldTypes::BUILT_IN[$type]),
                'multiple' => in_array($type, ['checkboxes'], true) ? true : (in_array('multiple', $keys, true) ? 'optional' : false),
                'requires'  => $this->requirements($type, $keys),
                'exclusive' => in_array('options_from', $keys, true) ? [['options', 'options_from']] : [],
                'keys'      => $this->keys($keys, $type),
            ];
        }

        foreach (SchemaLoader::GROUP_KEYS as $type => $keys) {
            $types[$type] = [
                'label'    => self::TYPE_LABELS[$type],
                'group'    => true,
                'custom'   => false,
                'multiple' => false,
                'requires'  => $type === 'fieldset' ? [['children'], ['legend']] : [['children']],
                'exclusive' => [],
                'keys'      => $this->keys($keys, $type),
            ];
        }

        return $types;
    }

    /**
     * Keys a node of this type must have; each entry lists alternatives, at
     * least one of which is required.
     *
     * @param list<string> $keys
     *
     * @return list<list<string>>
     */
    private function requirements(string $type, array $keys): array
    {
        $requires = [['type']];

        if ($type !== 'submit') {
            $requires[] = ['name'];
        }

        if (in_array($type, ['select', 'radio', 'checkboxes'], true)) {
            $requires[] = in_array('options_from', $keys, true) ? ['options', 'options_from'] : ['options'];
        }

        if ($type === 'file' && ! $this->policy->isTrusted()) {
            $requires[] = ['max_size'];
            $requires[] = ['mimes'];
        }

        return $requires;
    }

    /**
     * @param list<string> $keys
     *
     * @return array<string, array{kind: string, help: string}>
     */
    private function keys(array $keys, string $type): array
    {
        $described = [];

        foreach ($keys as $key) {
            $kind = self::KEY_KINDS[$key] ?? 'mixed';

            if ($key === 'step' && in_array($type, ['date', 'time', 'datetime_local'], true)) {
                $kind = 'whole_step';
            }

            if ($key === 'options' && $type === 'select') {
                $kind = 'options_with_groups';
            }

            if ($key === 'label' && $type === 'submit') {
                $described[$key] = ['kind' => 'text', 'help' => 'Button text.'];

                continue;
            }

            $described[$key] = ['kind' => $kind, 'help' => self::KEY_HELP[$key] ?? 'Custom setting of this field type.'];
        }

        return $described;
    }
}
