<?php

declare(strict_types=1);

namespace Ephraitech\Forms\Tests\Feature\Schema;

use CodeIgniter\Config\Services;
use CodeIgniter\Test\CIUnitTestCase;
use Ephraitech\Forms\Config\Forms;
use Ephraitech\Forms\Exceptions\FormException;
use Ephraitech\Forms\Fields\Select;
use Ephraitech\Forms\Form;
use Ephraitech\Forms\Schema\SchemaException;
use Ephraitech\Forms\Schema\SchemaLoader;
use Ephraitech\Forms\Schema\SchemaPolicy;
use Ephraitech\Forms\Support\OptionProviders;
use Ephraitech\Forms\Tests\Support\Providers\BranchModel;
use Ephraitech\Forms\Tests\Support\Providers\ServiceOptions;

/**
 * Named option providers in code and in schemas.
 *
 * @internal
 */
final class OptionProvidersTest extends CIUnitTestCase
{
    private Forms $config;

    protected function setUp(): void
    {
        parent::setUp();

        ServiceOptions::$calls = 0;
        Services::resetSingle('validation');

        $this->config                  = new Forms();
        $this->config->optionProviders = [
            'services'  => [ServiceOptions::class, 'all'],
            'styles'    => ServiceOptions::class . '::grouped',
            'branches'  => [BranchModel::class, 'dropdown'],
            'sizes'     => static fn (): array => ['s' => 'Small', 'm' => 'Medium'],
            'broken'    => [ServiceOptions::class, 'broken'],
        ];
    }

    /**
     * @param array<array-key, mixed> $schema
     */
    private function load(array $schema, ?SchemaPolicy $policy = null): Form
    {
        return Form::fromSchema($schema, $this->config, $policy)->setOldInput(null)->setOldErrors(null);
    }

    /**
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

    // -- Kinds of provider ----------------------------------------------------

    public function testStaticMethodStringClosureAndModelProviders(): void
    {
        $this->assertSame(['fitting' => 'Suit fitting', 'alterations' => 'Alterations'], OptionProviders::call($this->config, 'services'));
        $this->assertSame(['formal' => 'Formal'], OptionProviders::call($this->config, 'styles')['Shirts'], 'instance method via "Class::method"');
        $this->assertSame(['kampala' => 'Kampala', 'entebbe' => 'Entebbe'], OptionProviders::call($this->config, 'branches'), 'CodeIgniter model via model()');
        $this->assertSame(['s' => 'Small', 'm' => 'Medium'], OptionProviders::call($this->config, 'sizes'));
        $this->assertSame(['services', 'styles', 'branches', 'sizes', 'broken'], OptionProviders::names($this->config));
    }

    public function testProviderMustReturnAnArray(): void
    {
        $this->expectException(FormException::class);
        $this->expectExceptionMessage('Option provider "broken" is invalid: it returned string instead of an array of value => label.');

        OptionProviders::call($this->config, 'broken');
    }

    /**
     * @return array<string, array{mixed, string}>
     */
    public static function invalidRegistrations(): array
    {
        return [
            'bad name'          => ['Services', 'names use lowercase letters'],
            'missing class'     => [['App\\Missing', 'all'], 'class "App\\Missing" does not exist'],
            'missing method'    => [[ServiceOptions::class, 'nope'], 'has no method "nope"'],
            'private method'    => [[ServiceOptions::class, 'hidden'], 'must be public'],
            'needs an argument' => [[ServiceOptions::class, 'needsArgument'], 'must not require arguments'],
            'wrong shape'       => [42, 'register [ClassName::class'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('invalidRegistrations')]
    public function testInvalidRegistrations(mixed $provider, string $message): void
    {
        $name                          = is_string($provider) ? $provider : 'services';
        $this->config->optionProviders = is_string($provider) ? [$provider => [ServiceOptions::class, 'all']] : ['services' => $provider];

        $this->expectException(FormException::class);
        $this->expectExceptionMessage($message);

        OptionProviders::check($this->config);
        OptionProviders::call($this->config, $name);
    }

    // -- In code --------------------------------------------------------------

    public function testOptionsFromInCode(): void
    {
        $form = Form::make('booking', $this->config)->setOldInput(null);

        /** @var Select $field */
        $field = $form->select('service', 'Service')->optionsFrom('services', $this->config)->rules('required');

        $this->assertSame('services', $field->getOptionProvider());
        $this->assertSame(0, ServiceOptions::$calls, 'not called until needed');

        $this->assertTrue($form->validate(['service' => 'alterations']));
        $this->assertFalse($form->validate(['service' => 'free_suit']), 'strict option checking uses the provider\'s options');

        $form->field('service');

        $this->assertSame(1, ServiceOptions::$calls, 'called once per field');
    }

    public function testExplicitOptionsReplaceAProvider(): void
    {
        $field = (new Select('x'))->optionsFrom('sizes', $this->config)->options(['a' => 'A']);

        $this->assertNull($field->getOptionProvider());
        $this->assertSame(['a' => 'A'], $field->getOptions());
    }

    public function testUnknownProviderInCode(): void
    {
        $this->expectException(FormException::class);
        $this->expectExceptionMessage('Option provider "staff" is not registered. Registered providers: services, styles, branches, sizes, broken.');

        (new Select('x'))->optionsFrom('staff', $this->config);
    }

    // -- In schemas -----------------------------------------------------------

    public function testSchemaOptionsFrom(): void
    {
        $form = $this->load(['name' => 'booking', 'fields' => [
            ['type' => 'select', 'name' => 'service', 'label' => 'Service', 'options_from' => 'services', 'placeholder' => 'Choose', 'rules' => 'required'],
            ['type' => 'select', 'name' => 'style', 'options_from' => 'styles'],
            ['type' => 'radio', 'name' => 'branch', 'options_from' => 'branches'],
            ['type' => 'checkboxes', 'name' => 'sizes', 'options_from' => 'sizes'],
        ]]);

        $this->assertSame(0, ServiceOptions::$calls, 'loading a schema never calls a provider');

        $html = $form->render();

        $this->assertStringContainsString('<option value="alterations">Alterations</option>', $html);
        $this->assertStringContainsString('<optgroup label="Shirts">', $html);
        $this->assertStringContainsString('value="entebbe"', $html);
        $this->assertStringContainsString('name="sizes[]"', $html);

        $this->assertTrue($form->validate(['service' => 'fitting', 'style' => 'formal', 'branch' => 'kampala', 'sizes' => ['m']]));
        $this->assertFalse($form->validate(['service' => 'fitting', 'branch' => 'gulu']));
        $this->assertTrue($form->hasError('branch'));
    }

    public function testCheckingASchemaNeverCallsProviders(): void
    {
        $loader = new SchemaLoader($this->config);

        $this->assertSame([], $loader->errors(['fields' => [['type' => 'select', 'name' => 's', 'options_from' => 'services']]]));
        $this->assertSame(0, ServiceOptions::$calls);
    }

    public function testSchemaProviderErrors(): void
    {
        $this->assertSame(
            [
                'fields[0].options_from: "service" is not a registered option provider (did you mean "services"?). Registered: services, styles, branches, sizes, broken.',
                'fields[1].options_from: cannot be combined with "options"; use one or the other.',
                'fields[2].options_from: must be the name of a registered option provider.',
            ],
            $this->errors(['fields' => [
                ['type' => 'select', 'name' => 'a', 'options_from' => 'service'],
                ['type' => 'select', 'name' => 'b', 'options_from' => 'services', 'options' => ['x' => 'X']],
                ['type' => 'radio', 'name' => 'c', 'options_from' => ['services']],
            ]]),
        );
    }

    public function testNoProvidersRegistered(): void
    {
        $this->config->optionProviders = [];

        $this->assertSame(
            ['fields[0].options_from: "services" is not a registered option provider (none are registered).'],
            $this->errors(['fields' => [['type' => 'select', 'name' => 'a', 'options_from' => 'services']]]),
        );
    }

    public function testOptionsFromOnlyOnChoiceFields(): void
    {
        $this->assertSame(
            ['fields[0].options_from: is not a valid key for a "text" field.'],
            $this->errors(['fields' => [['type' => 'text', 'name' => 'a', 'options_from' => 'services']]]),
        );
    }

    // -- Policy ---------------------------------------------------------------

    public function testAdminSchemasMayUseEveryRegisteredProviderByDefault(): void
    {
        $this->assertSame([], $this->errors(['fields' => [['type' => 'select', 'name' => 'a', 'options_from' => 'branches']]]));
    }

    public function testPolicyCanNarrowProviders(): void
    {
        $schema = ['fields' => [
            ['type' => 'select', 'name' => 'a', 'options_from' => 'services'],
            ['type' => 'select', 'name' => 'b', 'options_from' => 'branches'],
        ]];

        $this->assertSame(
            ['fields[1].options_from: the "branches" option provider is not allowed in this schema.'],
            $this->errors($schema, SchemaPolicy::restricted()->onlyProviders('services')),
        );
        $this->assertSame(
            ['fields[0].options_from: the "services" option provider is not allowed in this schema.'],
            $this->errors($schema, SchemaPolicy::restricted()->disallowProviders('services')),
        );
        $this->assertTrue(SchemaPolicy::trusted()->allowsProvider('anything'));
    }
}
