<?php

declare(strict_types=1);

namespace Ephraitech\Forms\Tests\Support;

use Ephraitech\Forms\Config\Forms;
use Ephraitech\Forms\Schema\FormSchema;
use Ephraitech\Forms\Schema\SchemaPolicy;

/**
 * Generates docs/reference/schema-keys.md from FormSchema::describe(), so the
 * schema reference always matches the code. Run `composer docs:reference`
 * to rewrite the page; SchemaReferenceTest fails when it is out of date.
 */
final class SchemaReference
{
    private const KINDS = [
        'type'                => 'The type name',
        'name'                => 'A field name: letters, digits, underscores and hyphens, starting with a letter or underscore',
        'text'                => 'Text',
        'optional_text'       => 'Text, or `null`',
        'bool'                => '`true` or `false`',
        'positive_int'        => 'A whole number of at least 1',
        'width'               => 'A whole number from 1 to 12, or `null`',
        'rules'               => 'A rule string such as `"required|max_length[100]"`, or a list of rule strings',
        'messages'            => 'An object of rule name to message, such as `{"required": "Tell us your name."}`',
        'default'             => 'A value, or a list of values for fields that hold several',
        'options'             => 'An object of value to label, such as `{"suit": "Suit"}`',
        'options_with_groups' => 'An object of value to label; a label may be an object of its own to form a group',
        'provider'            => 'The name of a registered option provider',
        'attributes'          => 'An object of attribute name to value',
        'classes'             => 'A class string, or a list of them',
        'step'                => 'A positive number, or `"any"`',
        'whole_step'          => 'A positive whole number, or `"any"`',
        'checked_value'       => 'Text or a whole number',
        'strings'             => 'A string, or a list of strings',
        'dimensions'          => '`[width, height]` or `{"width": ..., "height": ...}`, in whole pixels',
        'children'            => 'A list of fields and groups',
        'mixed'               => 'Defined by the field type',
    ];

    private const PARAMETERS = [
        'none'                 => 'none',
        'int'                  => 'a whole number, such as `[100]`',
        'ints'                 => 'one or more whole numbers, such as `[6]` or `[6,8]`',
        'number'               => 'a number, such as `[0]`',
        'field'                => 'another field in the form, such as `[password]`',
        'fields'               => 'other fields in the form, such as `[phone,email]`',
        'list'                 => 'a list of up to 100 values, such as `[a,b,c]`',
        'optional_schemes'     => 'optional URL schemes, such as `[https]`',
        'optional_date_format' => 'an optional date format, such as `[Y-m-d]`',
    ];

    public static function markdown(): string
    {
        $config     = new Forms();
        $restricted = FormSchema::describe(SchemaPolicy::restricted(), $config);
        $trusted    = FormSchema::describe(SchemaPolicy::trusted(), $config);

        $out   = [];
        $out[] = '# Schema keys';
        $out[] = '';
        $out[] = '<!-- Generated from FormSchema::describe() by `composer docs:reference`. Do not edit by hand. -->';
        $out[] = '';
        $out[] = 'Every key a schema may contain, for each field type. This page is generated from';
        $out[] = '`FormSchema::describe()`, so it always matches the version it documents. For';
        $out[] = 'loading and checking schemas, see [Schemas](schemas.md).';
        $out[] = '';
        $out[] = 'Field types are listed as admins may use them under the restricted policy (the';
        $out[] = 'default). [Developer-only types](#developer-only-types) are allowed by the trusted';
        $out[] = 'policy only.';
        $out[] = '';
        $out[] = '## The form';
        $out[] = '';
        $out[] = '| Key | Value |';
        $out[] = '| --- | ----- |';
        $out[] = '| `version` | `' . $restricted['version'] . '`, the schema format version |';
        $out[] = '| `name` | ' . self::KINDS['name'] . '; becomes the form name |';
        $out[] = '| `method` | One of `' . implode('`, `', $restricted['form']['methods']) . '` |';
        $out[] = '| `id` | ' . self::KINDS['optional_text'] . ' |';
        $out[] = '| `classes` | ' . self::KINDS['classes'] . ' |';
        $out[] = '| `attributes` | ' . self::KINDS['attributes'] . ' |';
        $out[] = '| `fields` | ' . self::KINDS['children'] . ' (required) |';
        $out[] = '| `action` | Text. Trusted policy only, or a restricted policy with `allowAction()` |';
        $out[] = '';
        $out[] = '## Field types';
        $out[] = '';

        foreach ($restricted['types'] as $type => $info) {
            $out = [...$out, ...self::type($type, $info)];
        }

        $out[] = '## Developer-only types';
        $out[] = '';
        $out[] = 'These types are not allowed by the restricted policy: password fields invite';
        $out[] = 'phishing, and hidden fields carry data the visitor cannot see. Schemas loaded';
        $out[] = 'with `SchemaPolicy::trusted()` may use them.';
        $out[] = '';

        foreach ($trusted['types'] as $type => $info) {
            if (! isset($restricted['types'][$type])) {
                $out = [...$out, ...self::type($type, $info, 3)];
            }
        }

        $out[] = '## Value kinds';
        $out[] = '';
        $out[] = '| Kind | Accepts |';
        $out[] = '| ---- | ------- |';

        foreach (self::KINDS as $kind => $description) {
            $out[] = '| `' . $kind . '` | ' . $description . ' |';
        }

        $out[] = '';
        $out[] = '## Rules allowed by the restricted policy';
        $out[] = '';
        $out[] = 'Any other rule is refused, with the reason for well-known ones such as';
        $out[] = '`is_unique` (it reads the database) and `regex_match` (a badly written pattern';
        $out[] = 'can hang the server). The trusted policy allows every rule.';
        $out[] = '';
        $out[] = '| Rule | Parameter |';
        $out[] = '| ---- | --------- |';

        foreach ($restricted['rules'] as $rule => $kind) {
            $out[] = '| `' . $rule . '` | ' . self::PARAMETERS[$kind] . ' |';
        }

        $out[] = '';
        $out[] = '## Other limits of the restricted policy';
        $out[] = '';
        $out[] = '| Limit | Value |';
        $out[] = '| ----- | ----- |';
        $out[] = '| Attributes | `' . implode('`, `', $restricted['attributes']) . '` (`aria-*` means any attribute starting with `aria-`) |';
        $out[] = '| Upload types (`mimes`) | `' . implode('`, `', $restricted['mimes']) . '` |';
        $out[] = '| Largest `max_size` | ' . $restricted['max_upload_kb'] . ' kilobytes |';
        $out[] = '| Forbidden field names | `' . implode('`, `', $restricted['forbidden_names']) . '` |';

        foreach ($restricted['limits'] as $limit => $value) {
            $out[] = '| `' . $limit . '` | ' . $value . ' |';
        }

        $out[] = '';
        $out[] = 'Change any of these per app with the methods of `SchemaPolicy`; see';
        $out[] = '[Schemas](schemas.md#schemapolicy).';

        return implode("\n", $out) . "\n";
    }

    /**
     * @param array<string, mixed> $info
     *
     * @return list<string>
     */
    private static function type(string $type, array $info, int $level = 3): array
    {
        $out   = [];
        $out[] = str_repeat('#', $level) . ' ' . $info['label'] . ' (`' . $type . '`)';
        $out[] = '';

        $required = array_map(
            static fn (array $alternatives): string => '`' . implode('` or `', $alternatives) . '`',
            $info['requires'],
        );
        $out[] = 'Required: ' . implode(', ', $required) . '.';

        foreach ($info['exclusive'] as $group) {
            $out[] = 'Use only one of `' . implode('` and `', $group) . '`.';
        }

        if ($info['group']) {
            $out[] = 'A group: its `children` are fields and other groups.';
        }

        $out[] = '';
        $out[] = '| Key | Value | Description |';
        $out[] = '| --- | ----- | ----------- |';

        foreach ($info['keys'] as $key => $spec) {
            $out[] = '| `' . $key . '` | `' . $spec['kind'] . '` | ' . $spec['help'] . ' |';
        }

        $out[] = '';

        return $out;
    }
}
