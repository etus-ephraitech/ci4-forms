<?php

declare(strict_types=1);

namespace Ephraitech\Forms\Tests\Feature\Schema;

use CodeIgniter\Test\CIUnitTestCase;
use Ephraitech\Forms\Config\Forms;
use Ephraitech\Forms\Exceptions\FormException;
use Ephraitech\Forms\Form;
use Ephraitech\Forms\Schema\SchemaException;
use Ephraitech\Forms\Schema\SchemaPolicy;
use Ephraitech\Forms\Schema\SchemaLoader;

/**
 * Error reporting for invalid schemas: every error, with its location.
 * Structural errors are the same under every policy; these load with the
 * trusted policy so policy errors do not mix in.
 *
 * @internal
 */
final class SchemaErrorsTest extends CIUnitTestCase
{
    private Forms $config;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = new Forms();
    }

    /**
     * Every error message for a schema, as "path: message".
     *
     * @param array<array-key, mixed> $schema
     *
     * @return list<string>
     */
    private function errors(array $schema): array
    {
        try {
            Form::fromSchema($schema, $this->config, SchemaPolicy::trusted());
        } catch (SchemaException $e) {
            return $e->getMessages();
        }

        return [];
    }

    /**
     * @param array<string, mixed> $field
     *
     * @return list<string>
     */
    private function fieldErrors(array $field): array
    {
        return $this->errors(['fields' => [$field]]);
    }

    public function testAllErrorsAreReportedTogether(): void
    {
        $errors = $this->errors(['fields' => [
            ['type' => 'txt', 'name' => 'a'],
            ['type' => 'text'],
            ['type' => 'text', 'name' => 'b', 'width' => 13],
        ]]);

        $this->assertSame([
            'fields[0].type: unknown field type "txt" (did you mean "text"?).',
            'fields[1].name: is required.',
            'fields[2].width: must be between 1 and 12 columns, 13 given.',
        ], $errors);
    }

    public function testExceptionCarriesStructuredErrors(): void
    {
        try {
            Form::fromSchema(['fields' => [['type' => 'text']]], $this->config, SchemaPolicy::trusted());
            $this->fail('expected a SchemaException');
        } catch (SchemaException $e) {
            $this->assertInstanceOf(FormException::class, $e);
            $this->assertSame([['path' => 'fields[0].name', 'message' => 'is required.']], $e->getErrors());
            $this->assertSame("The form schema has 1 error:\n- fields[0].name: is required.", $e->getMessage());
        }
    }

    public function testErrorsWithoutLoading(): void
    {
        $loader = new SchemaLoader($this->config, SchemaPolicy::trusted());

        $this->assertSame([], $loader->errors(['fields' => [['type' => 'text', 'name' => 'a']]]));
        $this->assertSame([['path' => 'fields', 'message' => 'is required: a list of fields and groups.']], $loader->errors([]));
    }

    // -- Structure ------------------------------------------------------------

    public function testTopLevelStructure(): void
    {
        $this->assertSame(['Fields: is required: a list of fields and groups.'], array_map('ucfirst', $this->errors([])));
        $this->assertSame(['fields: must be a list of fields and groups.'], $this->errors(['fields' => ['a' => []]]));
        $this->assertSame(['The schema must be an object with a "fields" list, not a list.'], $this->errors([['type' => 'text']]));
        $this->assertSame(['fields[0]: must be an object with a "type".'], $this->errors(['fields' => ['text']]));
        $this->assertSame(['fields[0].type: is required.'], $this->errors(['fields' => [['name' => 'a']]]));
    }

    public function testFormKeys(): void
    {
        $this->assertSame(
            [
                'nmae: is not a valid key for the form (did you mean "name"?).',
                'version: schema version 2 is not supported; this version of ci4-forms reads version 1.',
                'method: HTTP method "options" is not supported for forms. Use one of: get, post, put, patch, delete.',
            ],
            $this->errors(['nmae' => 'x', 'version' => 2, 'method' => 'options', 'fields' => []]),
        );
        $this->assertSame(['version: must be the number 1.'], $this->errors(['version' => '1', 'fields' => []]));
        $this->assertSame(
            ['name: must start with a letter or underscore and contain only letters, digits, underscores and hyphens.'],
            $this->errors(['name' => 'my form', 'fields' => []]),
        );
    }

    public function testNameMustMatchWhenLoadingIntoAForm(): void
    {
        $this->expectException(SchemaException::class);
        $this->expectExceptionMessage('name: is "other" but the form is named "booking"; a loaded form keeps its name.');

        Form::make('booking', $this->config)->loadSchema(['name' => 'other', 'fields' => []]);
    }

    // -- Keys -----------------------------------------------------------------

    public function testUnknownKeysWithSuggestions(): void
    {
        $this->assertSame(
            ['fields[0].max_lenght: is not a valid key for a "text" field.'],
            $this->fieldErrors(['type' => 'text', 'name' => 'a', 'max_lenght' => 5]),
        );
        $this->assertSame(
            ['fields[0].maxsize: is not a valid key for a "file" field (did you mean "max_size"?).'],
            $this->fieldErrors(['type' => 'file', 'name' => 'a', 'maxsize' => 5]),
        );
        $this->assertSame(
            ['fields[0].lable: is not a valid key for a "text" field (did you mean "label"?).'],
            $this->fieldErrors(['type' => 'text', 'name' => 'a', 'lable' => 'A']),
        );
    }

    public function testHintsForHtmlHabits(): void
    {
        $this->assertSame(
            [
                'fields[0].maxlength: is not a valid key for a "text" field; set length limits with "rules", for example "max_length[100]".',
                'fields[0].required: is not a valid key for a "text" field; make it required with "rules": "required".',
                'fields[0].value: is not a valid key for a "text" field; use "default" for the initial value.',
                'fields[0].class: is not a valid key for a "text" field; use "classes".',
            ],
            $this->fieldErrors(['type' => 'text', 'name' => 'a', 'maxlength' => 5, 'required' => true, 'value' => 'x', 'class' => 'big']),
        );
    }

    public function testKeysOfOtherTypesAreRejected(): void
    {
        $this->assertSame(['fields[0].options: is not a valid key for a "text" field.'], $this->fieldErrors(['type' => 'text', 'name' => 'a', 'options' => ['x' => 'X']]));
        $this->assertSame(['fields[0].rules: is not a valid key for a "submit" field.'], $this->fieldErrors(['type' => 'submit', 'rules' => 'required']));
        $this->assertSame(['fields[0].placeholder: is not a valid key for a "hidden" field.'], $this->fieldErrors(['type' => 'hidden', 'name' => 'a', 'placeholder' => 'x']));
    }

    // -- Values ---------------------------------------------------------------

    public function testValueTypes(): void
    {
        $this->assertSame(
            [
                'fields[0].label: must be a string.',
                'fields[0].width: must be a whole number from 1 to 12.',
                'fields[0].rules: must be a rule string such as "required|max_length[100]" or a list of rule strings.',
                'fields[0].messages: must be an object of rule name to message.',
                'fields[0].autofocus: must be true or false.',
            ],
            $this->fieldErrors(['type' => 'text', 'name' => 'a', 'label' => 5, 'width' => '6', 'rules' => ['required', 5], 'messages' => ['x'], 'autofocus' => 'yes']),
        );
    }

    public function testAttributes(): void
    {
        $this->assertSame(
            ['fields[0].attributes: "onclick="x"" is not a valid HTML attribute name.'],
            $this->fieldErrors(['type' => 'text', 'name' => 'a', 'attributes' => ['onclick="x"' => 'y']]),
        );
        $this->assertSame(
            ['fields[0].attributes: the "value" attribute is managed by the package and cannot be set directly.'],
            $this->fieldErrors(['type' => 'text', 'name' => 'a', 'attributes' => ['value' => 'x']]),
        );
        $this->assertSame(
            ['fields[0].attributes: every attribute value must be a string, number, true, false or null.'],
            $this->fieldErrors(['type' => 'text', 'name' => 'a', 'attributes' => ['data-x' => ['nested']]]),
        );
    }

    public function testOptions(): void
    {
        $this->assertSame(['fields[0].options: is required for a "select" field (or use "options_from" with a registered provider).'], $this->fieldErrors(['type' => 'select', 'name' => 'a']));
        $this->assertSame(
            ['fields[0].options: must be an object of value to label, such as {"fitting": "Suit fitting"}, not a list.'],
            $this->fieldErrors(['type' => 'radio', 'name' => 'a', 'options' => ['Cash', 'Card']]),
        );
        $this->assertSame(
            ['fields[0].options: option groups are only supported by select fields.'],
            $this->fieldErrors(['type' => 'checkboxes', 'name' => 'a', 'options' => ['G' => ['x' => 'X']]]),
        );
        $this->assertSame(
            ['fields[0].each_rules: only applies to a select with "multiple": true.'],
            $this->fieldErrors(['type' => 'select', 'name' => 'a', 'options' => ['x' => 'X'], 'each_rules' => 'max_length[3]']),
        );
    }

    public function testDefaults(): void
    {
        $this->assertSame(
            ['fields[0].default: must be a list of values for a field that holds several values.'],
            $this->fieldErrors(['type' => 'checkboxes', 'name' => 'a', 'options' => ['x' => 'X'], 'default' => 'x']),
        );
        $this->assertSame(
            ['fields[0].default: must be a single value (string, number, true or false) or null.'],
            $this->fieldErrors(['type' => 'text', 'name' => 'a', 'default' => ['x']]),
        );
        $this->assertSame([], $this->fieldErrors(['type' => 'select', 'name' => 'a', 'options' => ['x' => 'X'], 'multiple' => true, 'default' => ['x']]));
    }

    public function testFileSettings(): void
    {
        $this->assertSame(
            [
                'fields[0].max_size: must be at least 1 kilobyte.',
                'fields[0].extensions: "p d f" is not a valid file extension.',
                'fields[0].mimes: must be a string or a non-empty list of strings.',
                'fields[0].max_dimensions: must be [width, height] or {"width": ..., "height": ...} in whole pixels.',
            ],
            $this->fieldErrors(['type' => 'file', 'name' => 'a', 'max_size' => 0, 'extensions' => ['p d f'], 'mimes' => [], 'max_dimensions' => [100]]),
        );
    }

    public function testSteps(): void
    {
        $this->assertSame(['fields[0].step: must be a positive whole number or "any".'], $this->fieldErrors(['type' => 'time', 'name' => 'a', 'step' => 0.5]));
        $this->assertSame(['fields[0].step: must be a positive number or "any".'], $this->fieldErrors(['type' => 'number', 'name' => 'a', 'step' => -1]));
    }

    public function testNames(): void
    {
        $this->assertSame(
            ['fields[1].name: "a" is already used by another field.'],
            $this->errors(['fields' => [['type' => 'text', 'name' => 'a'], ['type' => 'email', 'name' => 'a']]]),
        );
        $this->assertSame(
            ['fields[0].name: the field name "_method" is reserved by the package.'],
            $this->fieldErrors(['type' => 'hidden', 'name' => '_method']),
        );
        $this->assertSame(
            ['fields[0].name: field name "1st" is invalid. Use a letter or underscore first, then letters, digits, underscores or hyphens.'],
            $this->fieldErrors(['type' => 'text', 'name' => '1st']),
        );
    }

    // -- Groups ---------------------------------------------------------------

    public function testGroupErrors(): void
    {
        $this->assertSame(
            [
                'fields[0].children: is required: a list of fields and groups.',
                'fields[1].legend: is required (use "" for a fieldset without a title).',
                'fields[2].label: is not a valid key for a row.',
                'fields[3].children[0].children[0].type: unknown field type "slider".',
            ],
            $this->errors(['fields' => [
                ['type' => 'row'],
                ['type' => 'fieldset', 'children' => []],
                ['type' => 'row', 'label' => 'x', 'children' => []],
                ['type' => 'fieldset', 'legend' => 'A', 'children' => [['type' => 'row', 'children' => [['type' => 'slider', 'name' => 'v']]]]],
            ]]),
        );
    }

    public function testDuplicateNamesAcrossGroups(): void
    {
        $this->assertSame(
            ['fields[1].children[0].name: "email" is already used by another field.'],
            $this->errors(['fields' => [
                ['type' => 'email', 'name' => 'email'],
                ['type' => 'row', 'children' => [['type' => 'email', 'name' => 'email']]],
            ]]),
        );
    }

    // -- JSON -----------------------------------------------------------------

    public function testInvalidJson(): void
    {
        try {
            Form::fromJson('{"fields": [ {"type": "text",, } ]}', $this->config, SchemaPolicy::trusted());
            $this->fail('expected a SchemaException');
        } catch (SchemaException $e) {
            $this->assertSame(['The schema is not valid JSON (Syntax error).'], $e->getMessages());
        }
    }

    public function testJsonMustBeAnObject(): void
    {
        foreach (['[]', '[{"type":"text"}]', '"text"', '42', 'null'] as $json) {
            try {
                Form::fromJson($json, $this->config, SchemaPolicy::trusted());
                $this->fail($json . ' should be rejected');
            } catch (SchemaException $e) {
                $this->assertSame(['The schema must be a JSON object with a "fields" list.'], $e->getMessages(), $json);
            }
        }

        $this->assertSame(
            ['Fields: is required: a list of fields and groups.'],
            array_map('ucfirst', $this->errorsFromJson('{}')),
        );
    }

    /**
     * @return list<string>
     */
    private function errorsFromJson(string $json): array
    {
        try {
            Form::fromJson($json, $this->config, SchemaPolicy::trusted());
        } catch (SchemaException $e) {
            return $e->getMessages();
        }

        return [];
    }
}
