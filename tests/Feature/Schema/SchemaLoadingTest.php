<?php

declare(strict_types=1);

namespace Ephraitech\Forms\Tests\Feature\Schema;

use CodeIgniter\Config\Services;
use CodeIgniter\Test\CIUnitTestCase;
use Ephraitech\Forms\Config\Forms;
use Ephraitech\Forms\Fields\Checkbox;
use Ephraitech\Forms\Fields\Checkboxes;
use Ephraitech\Forms\Fields\File;
use Ephraitech\Forms\Fields\Select;
use Ephraitech\Forms\Form;
use Ephraitech\Forms\Layout\Fieldset;
use Ephraitech\Forms\Layout\Row;
use Ephraitech\Forms\Schema\SchemaException;
use Ephraitech\Forms\Tests\Support\Fields\MoneyField;

/**
 * Building forms from valid schemas.
 *
 * @internal
 */
final class SchemaLoadingTest extends CIUnitTestCase
{
    private Forms $config;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = new Forms();
        Services::resetSingle('validation');
    }

    /**
     * @param array<string, mixed> $schema
     */
    private function load(array $schema): Form
    {
        return Form::fromSchema($schema, $this->config)->setOldInput(null)->setOldErrors(null);
    }

    public function testMinimalSchema(): void
    {
        $form = $this->load(['fields' => [['type' => 'text', 'name' => 'full_name']]]);

        $this->assertSame('form', $form->getName());
        $this->assertSame(['full_name'], array_keys($form->getFields()));
        $this->assertSame('Full name', $form->getField('full_name')->getLabel());
    }

    public function testFormSettings(): void
    {
        $form = $this->load([
            'version'    => 1,
            'name'       => 'enquiry',
            'method'     => 'patch',
            'action'     => '/enquiries/5',
            'id'         => 'enquiry-form',
            'classes'    => 'card p-3',
            'attributes' => ['data-turbo' => 'false'],
            'fields'     => [],
        ]);

        $this->assertSame('enquiry', $form->getName());
        $this->assertSame('patch', $form->getMethod());
        $this->assertSame('/enquiries/5', $form->getAction());
        $this->assertSame('enquiry-form', $form->getId());
        $this->assertSame(['card', 'p-3'], $form->getClasses());
        $this->assertSame(['data-turbo' => 'false'], $form->getAttributes());
    }

    public function testCommonFieldProperties(): void
    {
        $form  = $this->load(['fields' => [[
            'type'         => 'text',
            'name'         => 'full_name',
            'label'        => 'Full name',
            'rules'        => 'required|max_length[100]',
            'messages'     => ['required' => 'Tell us your name.'],
            'help'         => 'As on your ID.',
            'placeholder'  => 'Jane Namuli',
            'default'      => 'Jane',
            'classes'      => ['form-control-lg'],
            'attributes'   => ['data-role' => 'name', 'spellcheck' => 'false'],
            'id'           => 'name-input',
            'autocomplete' => 'name',
            'autofocus'    => true,
            'readonly'     => false,
        ]]]);
        $field = $form->getField('full_name');

        $this->assertSame(['required', 'max_length[100]'], $field->getRules());
        $this->assertSame(['required' => 'Tell us your name.'], $field->getMessages());
        $this->assertSame('As on your ID.', $field->getHelp());
        $this->assertSame('Jane Namuli', $field->getPlaceholder());
        $this->assertSame('Jane', $field->getDefault());
        $this->assertSame(['form-control-lg'], $field->getClasses());
        $this->assertSame('name-input', $field->getId('x'));
        $this->assertSame(['data-role' => 'name', 'spellcheck' => 'false', 'autocomplete' => 'name', 'autofocus' => true], $field->getAttributes());
    }

    public function testRulesAsList(): void
    {
        $form = $this->load(['fields' => [['type' => 'text', 'name' => 'code', 'rules' => ['required', 'regex_match[/^(A|B)\d+$/]']]]]);

        $this->assertSame(['required', 'regex_match[/^(A|B)\d+$/]'], $form->getField('code')->getRules());
    }

    public function testEveryBuiltInTypeWithItsOptions(): void
    {
        $form = $this->load(['fields' => [
            ['type' => 'email', 'name' => 'email'],
            ['type' => 'password', 'name' => 'password'],
            ['type' => 'tel', 'name' => 'phone'],
            ['type' => 'url', 'name' => 'website'],
            ['type' => 'search', 'name' => 'q'],
            ['type' => 'number', 'name' => 'quantity', 'step' => 0.5],
            ['type' => 'date', 'name' => 'day', 'min' => '2026-01-01', 'max' => '2026-12-31'],
            ['type' => 'time', 'name' => 'starts', 'min' => '09:00', 'step' => 900],
            ['type' => 'datetime_local', 'name' => 'opens_at', 'step' => 'any'],
            ['type' => 'color', 'name' => 'accent', 'default' => '#1f2a44'],
            ['type' => 'textarea', 'name' => 'notes', 'rows' => 4],
            ['type' => 'select', 'name' => 'service', 'options' => ['fitting' => 'Suit fitting'], 'placeholder' => 'Choose', 'strict_options' => false],
            ['type' => 'select', 'name' => 'styles', 'options' => ['slim' => 'Slim'], 'multiple' => true, 'size' => 3, 'each_rules' => 'max_length[10]', 'default' => ['slim']],
            ['type' => 'select', 'name' => 'style', 'options' => ['Suits' => ['1' => 'Two-piece']]],
            ['type' => 'radio', 'name' => 'payment', 'options' => ['cash' => 'Cash'], 'inline' => true],
            ['type' => 'checkboxes', 'name' => 'extras', 'options' => ['monogram' => 'Monogram'], 'inline' => true, 'each_rules' => ['max_length[20]']],
            ['type' => 'checkbox', 'name' => 'agree', 'checked_value' => 'yes', 'inline' => true],
            ['type' => 'file', 'name' => 'photos', 'required' => true, 'multiple' => true, 'max_size' => 2048, 'extensions' => ['jpg', 'png'], 'mimes' => ['image/jpeg', 'image/png'], 'image' => true, 'max_dimensions' => ['width' => 4000, 'height' => 3000]],
            ['type' => 'hidden', 'name' => 'source', 'default' => 'web'],
            ['type' => 'submit', 'label' => 'Send'],
        ]]);

        $this->assertSame(['step' => 0.5], $form->getField('quantity')->getAttributes());
        $this->assertSame(['min' => '2026-01-01', 'max' => '2026-12-31'], $form->getField('day')->getAttributes());
        $this->assertSame(['min' => '09:00', 'step' => 900], $form->getField('starts')->getAttributes());
        $this->assertSame(['step' => 'any'], $form->getField('opens_at')->getAttributes());
        $this->assertSame(['rows' => 4], $form->getField('notes')->getAttributes());

        /** @var Select $service */
        $service = $form->getField('service');
        $this->assertSame(['fitting' => 'Suit fitting'], $service->getOptions());
        $this->assertFalse($service->getStrictOptions());

        /** @var Select $styles */
        $styles = $form->getField('styles');
        $this->assertTrue($styles->isMultiple());
        $this->assertSame(['max_length[10]'], $styles->getEachRules());
        $this->assertSame(['slim'], $styles->getDefault());
        $this->assertSame(['1'], $form->getField('style')->getOptionValues());

        $this->assertTrue($form->getField('payment')->isInline());

        /** @var Checkboxes $extras */
        $extras = $form->getField('extras');
        $this->assertSame(['max_length[20]'], $extras->getEachRules());

        /** @var Checkbox $agree */
        $agree = $form->getField('agree');
        $this->assertSame('yes', $agree->getCheckedValue());

        /** @var File $photos */
        $photos = $form->getField('photos');
        $this->assertSame(
            ['uploaded[photos]', 'max_size[photos,2048]', 'ext_in[photos,jpg,png]', 'mime_in[photos,image/jpeg,image/png]', 'is_image[photos]', 'max_dims[photos,4000,3000]'],
            $photos->getRules(),
        );
        $this->assertTrue($photos->isMultiple());
        $this->assertTrue($form->isMultipart());

        $this->assertSame('web', $form->getField('source')->getDefault());
        $this->assertSame('Send', $form->getField('submit')->getLabel());
    }

    public function testMaxDimensionsAsList(): void
    {
        $form = $this->load(['fields' => [['type' => 'file', 'name' => 'logo', 'max_dimensions' => [500, 200]]]]);

        $this->assertSame(['max_dims[logo,500,200]'], $form->getField('logo')->getRules());
    }

    public function testLayout(): void
    {
        $form = $this->load(['fields' => [
            ['type' => 'row', 'classes' => 'g-3', 'children' => [
                ['type' => 'text', 'name' => 'first_name', 'width' => 6],
                ['type' => 'text', 'name' => 'last_name', 'width' => 6],
            ]],
            ['type' => 'fieldset', 'legend' => 'Address', 'description' => 'Where to deliver.', 'id' => 'address', 'children' => [
                ['type' => 'text', 'name' => 'street'],
                ['type' => 'row', 'children' => [
                    ['type' => 'text', 'name' => 'city', 'width' => 8],
                    ['type' => 'fieldset', 'legend' => '', 'width' => 4, 'children' => [['type' => 'text', 'name' => 'postcode']]],
                ]],
            ]],
        ]]);

        [$row, $fieldset] = $form->getLayout();

        $this->assertInstanceOf(Row::class, $row);
        $this->assertSame(['first_name', 'last_name'], $row->getChildren());
        $this->assertSame(['g-3'], $row->getClasses());

        $this->assertInstanceOf(Fieldset::class, $fieldset);
        $this->assertSame('Address', $fieldset->getLegend());
        $this->assertSame('Where to deliver.', $fieldset->getDescription());
        $this->assertSame('address', $fieldset->getId());
        $this->assertSame(4, $fieldset->getChildren()[1]->getChildren()[1]->getWidth());
        $this->assertSame(['first_name', 'last_name', 'street', 'city', 'postcode'], array_keys($form->getFields()));
    }

    public function testLoadedFormValidatesAndRenders(): void
    {
        $form = $this->load(['name' => 'enquiry', 'fields' => [
            ['type' => 'text', 'name' => 'full_name', 'label' => 'Full name', 'rules' => 'required'],
            ['type' => 'checkboxes', 'name' => 'extras', 'options' => ['monogram' => 'Monogram', 'express' => 'Express']],
            ['type' => 'submit', 'label' => 'Send'],
        ]]);

        $this->assertFalse($form->validate(['full_name' => '', 'extras' => ['free_shoes']]));
        $this->assertTrue($form->hasError('full_name'));
        $this->assertTrue($form->hasError('extras'), 'strict option checking applies to loaded forms');

        $this->assertTrue($form->validate(['full_name' => 'Jane', 'extras' => ['express']]));
        $this->assertSame(['full_name' => 'Jane', 'extras' => ['express']], $form->validated());

        $html = $form->render();

        $this->assertStringContainsString('name="_form" value="enquiry"', $html);
        $this->assertStringContainsString('name="extras[]"', $html);
        $this->assertStringContainsString('>Send</button>', $html);
    }

    public function testFromJson(): void
    {
        $form = Form::fromJson('{"name":"x","fields":[{"type":"select","name":"service_id","options":{"1":"Fitting","2":"Alterations"}}]}', $this->config);

        $this->assertSame(['1', '2'], $form->getField('service_id')->getOptionValues());
    }

    public function testRegisteredCustomTypeWithSchemaProperties(): void
    {
        $this->config->fieldTypes = ['money' => MoneyField::class];
        service('autoloader')->addNamespace('FormsTestViews', dirname(__DIR__, 2) . '/Support/Views');

        $form  = $this->load(['fields' => [['type' => 'money', 'name' => 'budget', 'label' => 'Budget', 'currency' => 'USD', 'rules' => 'permit_empty|numeric', 'width' => 6]]]);
        $field = $form->getField('budget');

        $this->assertInstanceOf(MoneyField::class, $field);
        $this->assertSame('USD', $field->getCurrency());
        $this->assertTrue($form->validate(['budget' => '1,500']));
        $this->assertSame(['budget' => '1500'], $form->validated());
    }

    public function testLoadSchemaCombinesCodeAndData(): void
    {
        $form = new class (null, $this->config) extends Form {
            protected function define(): void
            {
                $this->text('full_name')->rules('required');

                $this->loadSchema(['fields' => [
                    ['type' => 'textarea', 'name' => 'notes'],
                ]]);

                $this->submit('Send');
            }
        };

        $this->assertSame(['full_name', 'notes', 'submit'], array_keys($form->getFields()));
    }

    public function testFromSchemaOnASubclassKeepsItsOwnFields(): void
    {
        $class = new class (null, $this->config) extends Form {
            protected function define(): void
            {
                $this->hidden('customer_id', 7);
            }
        };

        $form = $class::fromSchema(['fields' => [['type' => 'text', 'name' => 'note']]], $this->config);

        $this->assertSame(['customer_id', 'note'], array_keys($form->getFields()));
    }

    public function testLoadingIsAllOrNothing(): void
    {
        $form = Form::make('x', $this->config);
        $form->text('existing');

        try {
            $form->loadSchema(['fields' => [
                ['type' => 'text', 'name' => 'good_one'],
                ['type' => 'text', 'name' => 'existing'],
            ]]);
            $this->fail('expected a SchemaException');
        } catch (SchemaException $e) {
            $this->assertSame('fields[1].name: "existing" is already used by another field.', $e->getMessages()[0]);
        }

        $this->assertSame(['existing'], array_keys($form->getFields()), 'nothing from the failed schema was added');
    }
}
