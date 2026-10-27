<?php

declare(strict_types=1);

namespace Ephraitech\Forms\Tests\Feature;

use CodeIgniter\Config\Services;
use CodeIgniter\Test\CIUnitTestCase;
use Ephraitech\Forms\Config\Forms;
use Ephraitech\Forms\Exceptions\FormException;
use Ephraitech\Forms\Fields\Checkboxes;
use Ephraitech\Forms\Fields\DatetimeLocal;
use Ephraitech\Forms\Fields\Submit;
use Ephraitech\Forms\Form;
use Ephraitech\Forms\Support\FieldTypes;
use Ephraitech\Forms\Tests\Support\Fields\MoneyField;
use PHPUnit\Framework\Attributes\DataProvider;
use stdClass;

/**
 * Registering custom field types and creating fields by type key.
 *
 * @internal
 */
final class FieldTypeRegistryTest extends CIUnitTestCase
{
    private Forms $config;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config             = new Forms();
        $this->config->fieldTypes = ['money' => MoneyField::class];

        service('autoloader')->addNamespace('FormsTestViews', dirname(__DIR__) . '/Support/Views');
        Services::resetSingle('validation');
    }

    private function make(): Form
    {
        return Form::make('order', $this->config)->setOldInput(null)->setOldErrors(null);
    }

    private function strip(string $html): string
    {
        return trim((string) preg_replace('/>\s+</', '><', (string) preg_replace('/<!-- DEBUG-VIEW (START|ENDED) [^>]*-->\n?/', '', $html)));
    }

    // -- Registered types -----------------------------------------------------

    public function testRegisteredTypeBecomesABuilderMethod(): void
    {
        $form  = $this->make();
        $field = $form->money('price', 'Price');

        $this->assertInstanceOf(MoneyField::class, $field);
        $this->assertSame('Price', $field->getLabel());
        $this->assertSame($form, $field->end());
        $this->assertTrue($form->hasFieldType('money'));
    }

    public function testRegisteredTypesChainLikeBuiltIns(): void
    {
        $form = $this->make()
            ->text('item')->rules('required')
            ->money('price', 'Price')->currency('USD')->rules('required|numeric')
            ->submit('Save')
            ->end();

        $this->assertSame(['item', 'price', 'submit'], array_keys($form->getFields()));
        $this->assertSame('USD', $form->getField('price')->getCurrency());
    }

    public function testRegisteredTypesWorkInsideGroupsAndFormClasses(): void
    {
        $form = new class (null, $this->config) extends Form {
            protected function define(): void
            {
                $this->row(function (): void {
                    $this->money('price')->width(6);
                    $this->money('deposit')->width(6);
                });
            }
        };

        $this->assertSame(['price', 'deposit'], $form->getLayout()[0]->getChildren());
    }

    public function testCustomFieldValidatesAndNormalises(): void
    {
        $form = $this->make();
        $form->money('price', 'Price')->rules('required|numeric');

        $this->assertTrue($form->validate(['price' => ' 1,250,000 ']), json_encode($form->getErrors()));
        $this->assertSame(['price' => '1250000'], $form->validated());

        $this->assertFalse($form->validate(['price' => 'twelve']));
        $this->assertTrue($form->hasError('price'));
    }

    public function testCustomFieldUsesItsOwnView(): void
    {
        $form = $this->make()->fill(['price' => '450000']);
        $form->money('price', 'Price')->currency('UGX');

        $this->assertSame(
            '<div class="mb-3"><label for="order_price" class="form-label">Price</label><div class="input-group"><span class="input-group-text">UGX</span><input type="text" name="price" id="order_price" value="450000" class="form-control" inputmode="decimal"></div></div>',
            $this->strip($form->field('price')),
        );
    }

    public function testCustomFieldErrorUsesItsView(): void
    {
        $form = $this->make();
        $form->money('price', 'Price')->rules('required');
        $form->validate([]);

        $this->assertStringContainsString('class="invalid-feedback d-block"', $form->field('price'));
    }

    public function testBuilderArgumentsAreChecked(): void
    {
        $this->expectException(FormException::class);
        $this->expectExceptionMessage('money() expects a field name and an optional label.');

        $this->make()->money();
    }

    public function testUnknownMethodsStillFail(): void
    {
        $this->expectException(FormException::class);
        $this->expectExceptionMessage('no built-in method or registered field type');

        $this->make()->currencyPicker('x');
    }

    // -- addField() -------------------------------------------------------------

    public function testAddFieldByTypeKey(): void
    {
        $form = $this->make();

        $this->assertInstanceOf(DatetimeLocal::class, $form->addField('datetime_local', 'starts_at', 'Starts at'));
        $this->assertInstanceOf(Checkboxes::class, $form->addField('checkboxes', 'extras'));
        $this->assertInstanceOf(MoneyField::class, $form->addField('money', 'price'));

        $submit = $form->addField('submit', 'save', 'Save changes');

        $this->assertInstanceOf(Submit::class, $submit);
        $this->assertSame('save', $submit->getName());
        $this->assertSame('Save changes', $submit->getLabel());
    }

    public function testEveryBuiltInTypeCanBeCreatedByKey(): void
    {
        $form = $this->make();

        foreach (FieldTypes::BUILT_IN as $type => $class) {
            $this->assertInstanceOf($class, $form->addField($type, 'field_' . $type));
        }
    }

    public function testAddFieldWithUnknownTypeThrows(): void
    {
        $this->expectException(FormException::class);
        $this->expectExceptionMessage('Unknown field type "slider"');

        $this->make()->addField('slider', 'volume');
    }

    // -- Registration checks --------------------------------------------------

    /**
     * @return array<string, array{string, mixed, string}>
     */
    public static function invalidRegistrations(): array
    {
        return [
            'not camelCase'         => ['Money', MoneyField::class, 'camelCase'],
            'built-in name'         => ['text', MoneyField::class, 'already used'],
            'form method name'      => ['render', MoneyField::class, 'already used'],
            'missing class'         => ['money', 'App\\Fields\\Missing', 'does not exist'],
            'not a field'           => ['money', stdClass::class, 'must implement'],
            'not a class name'      => ['money', 42, 'does not exist'],
        ];
    }

    #[DataProvider('invalidRegistrations')]
    public function testInvalidRegistrationsAreRejected(string $type, mixed $class, string $message): void
    {
        $this->config->fieldTypes = [$type => $class];

        $this->expectException(FormException::class);
        $this->expectExceptionMessage($message);

        FieldTypes::registered($this->config);
    }

    public function testNoRegisteredTypesByDefault(): void
    {
        $this->assertSame([], (new Forms())->fieldTypes);
        $this->assertFalse(Form::make('x', new Forms())->hasFieldType('money'));
        $this->assertTrue(Form::make('x', new Forms())->hasFieldType('text'));
    }
}
