<?php

declare(strict_types=1);

namespace Ephraitech\Forms\Tests\Feature\Schema;

use CodeIgniter\Config\Services;
use CodeIgniter\Test\CIUnitTestCase;
use Ephraitech\Forms\Config\Forms;
use Ephraitech\Forms\Form;
use Ephraitech\Forms\Schema\FormSchema;
use Ephraitech\Forms\Schema\SchemaException;
use Ephraitech\Forms\Schema\SchemaPolicy;
use Ephraitech\Forms\Tests\Support\BookingForm;
use Ephraitech\Forms\Tests\Support\Fields\MoneyField;
use Ephraitech\Forms\Tests\Support\Providers\ServiceOptions;

/**
 * Exporting forms as schemas, and the round trip back.
 *
 * @internal
 */
final class SchemaExportTest extends CIUnitTestCase
{
    private Forms $config;

    protected function setUp(): void
    {
        parent::setUp();

        ServiceOptions::$calls = 0;
        Services::resetSingle('validation');
        service('autoloader')->addNamespace('FormsTestViews', dirname(__DIR__, 2) . '/Support/Views');

        $this->config                  = new Forms();
        $this->config->fieldTypes      = ['money' => MoneyField::class];
        $this->config->optionProviders = ['services' => [ServiceOptions::class, 'all']];
    }

    /**
     * A form using every field type, option, layout feature and setting.
     */
    private function richForm(): Form
    {
        $form = Form::make('order', $this->config)
            ->method('patch')
            ->action('/orders/5')
            ->id('order-form')
            ->addClass('card p-3')
            ->attribute('data-turbo', 'false');

        $form->fieldset('Customer', function (Form $form): void {
            $form->row(function (Form $form): void {
                $form->text('full_name', 'Full name')->rules('required|max_length[100]')->messages(['required' => 'Name, please.'])->autocomplete('name')->autofocus()->width(6);
                $form->tel('phone', 'Phone')->rules(['required', 'regex_match[/^(\+256|0)\d{9}$/]'])->placeholder('0772 123 456')->width(6);
            })->addClass('g-3');
            $form->email('email')->help('Optional.')->attribute('spellcheck', 'false');
        })->description('Who is ordering?')->id('customer');

        $form->row(function (Form $form): void {
            $form->select('service', 'Service')->optionsFrom('services', $this->config)->placeholder('Choose')->rules('required')->width(4);
            $form->select('styles', 'Styles', ['slim' => 'Slim', 'classic' => 'Classic'])->multiple()->size(3)->eachRules('max_length[10]')->default(['slim'])->width(4);
            $form->select('fabric', 'Fabric', ['Wool' => ['w1' => 'Merino'], 'Cotton' => ['c1' => 'Poplin']])->strictOptions(false)->width(4);
        });

        $form->radio('payment', 'Payment', ['cash' => 'Cash', 'momo' => 'Mobile money'])->inline()->rules('required');
        $form->checkboxes('extras', 'Extras', ['monogram' => 'Monogram', 'express' => 'Express'])->inline();
        $form->checkbox('is_rush', 'Rush order')->checkedValue('yes');
        $form->number('quantity')->rules('required|is_natural_no_zero')->step(1)->default(1);
        $form->date('needed_by')->min('2026-01-01')->max('2026-12-31');
        $form->time('fitting_time')->step(900);
        $form->datetimeLocal('opens_at');
        $form->color('accent')->default('#1f2a44');
        $form->search('q');
        $form->url('website');
        $form->password('pin')->rules('permit_empty|exact_length[4]');
        $form->textarea('notes')->rows(4)->rules('permit_empty|max_length[500]');
        $form->hidden('source', 'web');
        $form->money('budget', 'Budget')->currency('USD')->rules('permit_empty|numeric');
        $form->file('photos', 'Photos')->multiple()->required()->maxSize(2048)->image()->mimes(['image/jpeg', 'image/png'])->maxDimensions(4000, 3000)->extensions(['jpg', 'png']);
        $form->text('legacy')->disabled()->readonly();
        $form->submit('Save order', 'save');

        return $form;
    }

    private function strip(string $html): string
    {
        return (string) preg_replace('/<!-- DEBUG-VIEW (START|ENDED) [^>]*-->\n?/', '', $html);
    }

    // -- Round trip -----------------------------------------------------------

    public function testRoundTripReproducesTheSchema(): void
    {
        $schema = $this->richForm()->toSchema();
        $again  = Form::fromSchema($schema, $this->config, SchemaPolicy::trusted())->toSchema();

        $this->assertSame($schema, $again);
    }

    public function testRoundTripRendersIdenticalHtml(): void
    {
        $original = $this->richForm()->setOldInput(null)->setOldErrors(null);
        $reloaded = Form::fromSchema($original->toSchema(), $this->config, SchemaPolicy::trusted())->setOldInput(null)->setOldErrors(null);

        $this->assertSame($this->strip($original->render()), $this->strip($reloaded->render()));
    }

    public function testRoundTripValidatesIdentically(): void
    {
        $input = ['full_name' => '  Jane ', 'phone' => '0772123456', 'service' => 'fitting', 'styles' => ['classic'], 'fabric' => 'anything', 'payment' => 'momo', 'extras' => ['express'], 'is_rush' => 'yes', 'quantity' => '2', 'legacy' => 'ignored'];

        // Uploads need a real request and are covered by FileUploadTest.
        $original = $this->richForm()->setOldInput(null)->remove('photos');
        $reloaded = Form::fromSchema($original->toSchema(), $this->config, SchemaPolicy::trusted())->setOldInput(null);

        $this->assertTrue($original->validate($input), json_encode($original->getErrors()));
        $this->assertTrue($reloaded->validate($input), json_encode($reloaded->getErrors()));
        $this->assertSame($original->validated(), $reloaded->validated());

        $bad = ['phone' => '123', 'service' => 'free_suit', 'payment' => 'bitcoin'];

        $this->assertFalse($original->validate($bad));
        $this->assertFalse($reloaded->validate($bad));
        $this->assertSame($original->getErrors(), $reloaded->getErrors());
        // Rule errors first, then strict option errors (checked after rules).
        $this->assertSame(['full_name', 'phone', 'quantity', 'service', 'payment'], array_keys($reloaded->getErrors()));
    }

    public function testBookingFormFixtureRoundTrips(): void
    {
        $form   = new BookingForm(null, $this->config);
        $schema = $form->toSchema();

        $this->assertSame($schema, Form::fromSchema($schema, $this->config, SchemaPolicy::trusted())->toSchema());
    }

    // -- Output shape ---------------------------------------------------------

    public function testDefaultsAreLeftOut(): void
    {
        $form = Form::make('contact', $this->config);
        $form->text('full_name');
        $form->submit();

        $this->assertSame(
            ['version' => 1, 'name' => 'contact', 'fields' => [
                ['type' => 'text', 'name' => 'full_name', 'label' => 'Full name'],
                ['type' => 'submit', 'label' => 'Submit'],
            ]],
            $form->toSchema(),
        );
    }

    public function testAttributesWithTheirOwnKeyAreExportedUnderIt(): void
    {
        $fields = $this->richForm()->toSchema()['fields'];
        $name   = $fields[0]['children'][0]['children'][0];

        $this->assertSame('name', $name['autocomplete']);
        $this->assertTrue($name['autofocus']);
        $this->assertArrayNotHasKey('attributes', $name);
        $this->assertSame(['spellcheck' => 'false'], $fields[0]['children'][1]['attributes']);
    }

    public function testProviderOptionsAreExportedByName(): void
    {
        $service = $this->richForm()->toSchema()['fields'][1]['children'][0];

        $this->assertSame('services', $service['options_from']);
        $this->assertArrayNotHasKey('options', $service);
        $this->assertSame(0, ServiceOptions::$calls, 'exporting does not call the provider');
    }

    public function testClosureOptionsAreExportedAsValues(): void
    {
        $form = Form::make('x', $this->config);
        $form->select('size', 'Size', static fn (): array => ['s' => 'Small']);

        $this->assertSame(['s' => 'Small'], $form->toSchema()['fields'][0]['options']);
    }

    public function testCustomFieldValuesAreExported(): void
    {
        $fields = array_column($this->richForm()->toSchema()['fields'], null, 'name');

        $this->assertSame(['type' => 'money', 'name' => 'budget', 'label' => 'Budget', 'rules' => 'permit_empty|numeric', 'currency' => 'USD'], $fields['budget']);
    }

    public function testFileFieldExportsItsSettingsNotGeneratedRules(): void
    {
        $photos = array_column($this->richForm()->toSchema()['fields'], null, 'name')['photos'];

        $this->assertSame(
            ['type' => 'file', 'name' => 'photos', 'label' => 'Photos', 'required' => true, 'multiple' => true, 'max_size' => 2048, 'extensions' => ['jpg', 'png'], 'mimes' => ['image/jpeg', 'image/png'], 'image' => true, 'max_dimensions' => [4000, 3000]],
            $photos,
        );
    }

    public function testRulesContainingPipesAreExportedAsAList(): void
    {
        $phone = $this->richForm()->toSchema()['fields'][0]['children'][0]['children'][1];

        $this->assertSame(['required', 'regex_match[/^(\+256|0)\d{9}$/]'], $phone['rules']);
    }

    public function testToJson(): void
    {
        $json = $this->richForm()->toJson();

        $this->assertStringContainsString("\n    \"version\": 1,", $json);
        $this->assertStringContainsString('"action": "/orders/5"', $json, 'slashes are not escaped');
        $this->assertSame($this->richForm()->toSchema(), json_decode($json, true));
    }

    // -- What cannot be exported ---------------------------------------------

    public function testClosureRulesCannotBeExported(): void
    {
        $form = Form::make('x', $this->config);
        $form->text('code')->rules(['required', static fn ($value): bool => $value !== 'x']);

        $this->expectException(SchemaException::class);
        $this->expectExceptionMessage('fields[0].rules: a closure or callable rule cannot be written to a schema; use a named rule instead.');

        $form->toSchema();
    }

    public function testUnregisteredCustomFieldsCannotBeExported(): void
    {
        $this->config->fieldTypes = [];

        $form = Form::make('x', $this->config);
        $form->add(new MoneyField('budget'));

        $this->expectException(SchemaException::class);
        $this->expectExceptionMessage('is not a registered field type; register it in $fieldTypes to export it.');

        $form->toSchema();
    }

    // -- Templates for admins -------------------------------------------------

    public function testExportedDeveloperFormCanBecomeAnAdminTemplate(): void
    {
        $form = Form::make('enquiry', $this->config);
        $form->row(static function (Form $form): void {
            $form->text('full_name')->rules('required|max_length[100]')->autocomplete('name')->width(6);
            $form->tel('phone')->rules('required')->width(6);
        });
        $form->textarea('message')->rows(5)->rules('required|max_length[2000]');
        $form->submit('Send');

        $check = FormSchema::check($form->toJson(), null, $this->config);

        $this->assertTrue($check->isValid(), implode("\n", $check->getMessages()));
    }
}
