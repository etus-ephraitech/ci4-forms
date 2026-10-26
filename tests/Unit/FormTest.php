<?php

declare(strict_types=1);

namespace Ephraitech\Forms\Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use Ephraitech\Forms\Config\Forms;
use Ephraitech\Forms\Exceptions\FormException;
use Ephraitech\Forms\Fields\Email;
use Ephraitech\Forms\Fields\Submit;
use Ephraitech\Forms\Fields\Text;
use Ephraitech\Forms\Form;
use Ephraitech\Forms\Tests\Support\BookingForm;

/**
 * @internal
 */
final class FormTest extends CIUnitTestCase
{
    private Forms $config;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = new Forms();
    }

    private function booking(): BookingForm
    {
        return (new BookingForm(null, $this->config))->setOldInput(null);
    }

    // -- Naming and construction ----------------------------------------------

    public function testNameIsDerivedFromClassName(): void
    {
        $this->assertSame('booking', $this->booking()->getName());
        $this->assertSame('form', (new Form(null, $this->config))->getName());
    }

    public function testExplicitNameWins(): void
    {
        $this->assertSame('quick_booking', (new BookingForm('quick_booking', $this->config))->getName());
        $this->assertSame('newsletter', Form::make('newsletter', $this->config)->getName());
    }

    public function testInvalidNameThrows(): void
    {
        $this->expectException(FormException::class);

        Form::make('my form', $this->config);
    }

    public function testDefineRunsOnConstruction(): void
    {
        $form = $this->booking();

        $this->assertSame(
            ['full_name', 'email', 'service_id', 'appointment_date', 'notes', 'is_returning', 'pin', 'submit'],
            array_keys($form->getFields()),
        );
        $this->assertSame('/bookings', $form->getAction());
    }

    // -- Settings -------------------------------------------------------------

    public function testMethodDefaultsToConfig(): void
    {
        $this->assertSame('post', Form::make('x', $this->config)->getMethod());

        $this->config->defaultMethod = 'GET';

        $this->assertSame('get', Form::make('x', $this->config)->getMethod());
    }

    public function testMethodSpoofingAndHtmlMethod(): void
    {
        $form = Form::make('x', $this->config)->method('PATCH');

        $this->assertSame('patch', $form->getMethod());
        $this->assertSame('post', $form->getHtmlMethod());
        $this->assertSame('PATCH', $form->getSpoofedMethod());

        $form->method('get');

        $this->assertSame('get', $form->getHtmlMethod());
        $this->assertNull($form->getSpoofedMethod());

        $this->config->spoofMethods = false;

        $this->assertNull($form->method('delete')->getSpoofedMethod());
    }

    public function testInvalidMethodThrows(): void
    {
        $this->expectException(FormException::class);

        Form::make('x', $this->config)->method('options');
    }

    public function testNeedsCsrf(): void
    {
        $form = Form::make('x', $this->config);

        $this->assertTrue($form->needsCsrf());
        $this->assertFalse($form->method('get')->needsCsrf());

        $this->config->csrf = false;

        $this->assertFalse($form->method('post')->needsCsrf());
    }

    public function testFormAttributesIdAndClasses(): void
    {
        $form = Form::make('booking', $this->config)
            ->attributes(['data-turbo' => 'false', 'id' => 'booking-form', 'class' => 'card p-3'])
            ->attribute('autocomplete', 'off')
            ->addClass('p-3', 'shadow');

        $this->assertSame(['data-turbo' => 'false', 'autocomplete' => 'off'], $form->getAttributes());
        $this->assertSame('booking-form', $form->getId());
        $this->assertSame(['card', 'p-3', 'shadow'], $form->getClasses());

        $form->attribute('autocomplete', null)->id(null);

        $this->assertSame(['data-turbo' => 'false'], $form->getAttributes());
        $this->assertSame('booking', $form->getId());
    }

    public function testReservedFormAttributeThrows(): void
    {
        $this->expectException(FormException::class);
        $this->expectExceptionMessage('the "method" attribute is managed by the package');

        Form::make('x', $this->config)->attribute('Method', 'put');
    }

    public function testActionAndMultipart(): void
    {
        $form = Form::make('x', $this->config)->action('/upload')->multipart();

        $this->assertSame('/upload', $form->getAction());
        $this->assertTrue($form->isMultipart());
        $this->assertNull($form->action('  ')->getAction());
    }

    public function testIdPrefixFollowsConfig(): void
    {
        $form = $this->booking();

        $this->assertSame('booking', $form->getIdPrefix());
        $this->assertSame('booking_email', $form->getField('email')->getId($form->getIdPrefix()));

        $this->config->prefixIdsWithFormName = false;

        $this->assertSame('', $form->getIdPrefix());
    }

    // -- Fields ---------------------------------------------------------------

    public function testBuildersReturnTypedFields(): void
    {
        $form = Form::make('x', $this->config);

        $this->assertInstanceOf(Text::class, $form->text('name'));
        $this->assertInstanceOf(Email::class, $form->email('email'));
        $this->assertInstanceOf(Submit::class, $form->submit('Save'));
        $this->assertSame('Save', $form->getField('submit')->getLabel());
    }

    public function testHiddenBuilderSetsDefault(): void
    {
        $form = Form::make('x', $this->config)->setOldInput(null);

        $form->hidden('booking_id', 42);

        $this->assertSame(42, $form->getValue('booking_id'));
    }

    public function testInlineChainingAndEnd(): void
    {
        $form = Form::make('newsletter', $this->config)
            ->text('name')->rules('required')
            ->email('email')->rules('required|valid_email')
            ->submit('Subscribe')
            ->end();

        $this->assertInstanceOf(Form::class, $form);
        $this->assertSame(['name', 'email', 'submit'], array_keys($form->getFields()));
        $this->assertSame(['required', 'valid_email'], $form->getField('email')->getRules());
    }

    public function testDuplicateFieldThrows(): void
    {
        $form = Form::make('x', $this->config);
        $form->text('name');

        $this->expectException(FormException::class);
        $this->expectExceptionMessage('already has a field named "name"');

        $form->email('name');
    }

    public function testReservedFieldNameThrows(): void
    {
        $this->expectException(FormException::class);

        Form::make('x', $this->config)->hidden('_method');
    }

    public function testCustomFieldInstanceCanBeAdded(): void
    {
        $form  = Form::make('x', $this->config);
        $field = $form->add(new Text('custom'));

        $this->assertSame($form, $field->end());
        $this->assertTrue($form->has('custom'));
    }

    public function testUnknownFieldThrows(): void
    {
        $this->expectException(FormException::class);
        $this->expectExceptionMessage('has no field named "missing"');

        $this->booking()->getField('missing');
    }

    public function testRemoveFields(): void
    {
        $form = $this->booking()->remove('pin', 'notes', 'not_there');

        $this->assertFalse($form->has('pin'));
        $this->assertFalse($form->has('notes'));
        $this->assertTrue($form->has('email'));
    }

    public function testSubmittableFieldsExcludeButtonsAndDisabled(): void
    {
        $form = $this->booking();
        $form->getField('notes')->disabled();

        $this->assertSame(
            ['full_name', 'email', 'service_id', 'appointment_date', 'is_returning', 'pin'],
            array_keys($form->getSubmittableFields()),
        );
    }

    // -- Value resolution -----------------------------------------------------

    public function testDefaultsAreUsedWhenNothingElseIsAvailable(): void
    {
        $form = $this->booking();
        $form->getField('notes')->default('None');

        $this->assertSame('None', $form->getValue('notes'));
        $this->assertNull($form->getValue('email'));
    }

    public function testFilledDataBeatsDefaults(): void
    {
        $form = $this->booking()->fill(['email' => 'jane@example.com', 'notes' => 'Filled']);
        $form->getField('notes')->default('None');

        $this->assertSame('jane@example.com', $form->getValue('email'));
        $this->assertSame('Filled', $form->getValue('notes'));
    }

    public function testFillAcceptsObjectsWithToArrayAndPlainObjects(): void
    {
        $entity = new class () {
            public function toArray(): array
            {
                return ['full_name' => 'From entity'];
            }
        };

        $this->assertSame('From entity', $this->booking()->fill($entity)->getValue('full_name'));

        $row            = new \stdClass();
        $row->full_name = 'From row';

        $this->assertSame('From row', $this->booking()->fill($row)->getValue('full_name'));
    }

    public function testOldInputBeatsFilledData(): void
    {
        $form = (new BookingForm(null, $this->config))
            ->fill(['email' => 'saved@example.com', 'is_returning' => '1'])
            ->setOldInput([Form::FORM_KEY => 'booking', 'email' => 'typed@example.com']);

        $this->assertSame('typed@example.com', $form->getValue('email'));
        $this->assertSame(0, $form->getValue('is_returning'));
    }

    public function testOldInputFromAnotherFormIsIgnored(): void
    {
        $form = (new BookingForm(null, $this->config))
            ->fill(['email' => 'saved@example.com'])
            ->setOldInput([Form::FORM_KEY => 'newsletter', 'email' => 'other@example.com']);

        $this->assertNull($form->getOldInput());
        $this->assertSame('saved@example.com', $form->getValue('email'));
    }

    public function testOldInputWithoutFormKeyNeedsAMatchingField(): void
    {
        $form = (new BookingForm(null, $this->config))->fill(['email' => 'saved@example.com']);

        $form->setOldInput(['search' => 'suits']);
        $this->assertSame('saved@example.com', $form->getValue('email'));

        $form->setOldInput(['email' => 'typed@example.com']);
        $this->assertSame('typed@example.com', $form->getValue('email'));
    }

    public function testOldInputCanBeDisabled(): void
    {
        $this->config->useOldInput = false;

        $form = (new BookingForm(null, $this->config))
            ->fill(['email' => 'saved@example.com'])
            ->setOldInput([Form::FORM_KEY => 'booking', 'email' => 'typed@example.com']);

        $this->assertSame('saved@example.com', $form->getValue('email'));
    }

    public function testSubmittedDataBeatsEverything(): void
    {
        $form = (new BookingForm(null, $this->config))
            ->fill(['email' => 'saved@example.com'])
            ->setOldInput([Form::FORM_KEY => 'booking', 'email' => 'old@example.com'])
            ->setSubmittedData(['email' => 'submitted@example.com']);

        $this->assertTrue($form->hasSubmission());
        $this->assertSame('submitted@example.com', $form->getValue('email'));
    }

    public function testUntickedCheckboxInSubmissionIsUncheckedEvenWhenFilled(): void
    {
        $form = $this->booking()
            ->fill(['is_returning' => '1', 'notes' => 'Saved'])
            ->setSubmittedData(['email' => 'jane@example.com']);

        $this->assertSame(0, $form->getValue('is_returning'));
        $this->assertNull($form->getValue('notes'));
    }

    public function testClearSubmittedDataFallsBackToFilled(): void
    {
        $form = $this->booking()
            ->fill(['email' => 'saved@example.com'])
            ->setSubmittedData(['email' => 'submitted@example.com'])
            ->clearSubmittedData();

        $this->assertFalse($form->hasSubmission());
        $this->assertSame('saved@example.com', $form->getValue('email'));
    }

    public function testPasswordIsNeverResolved(): void
    {
        $form = $this->booking()
            ->fill(['pin' => '1234'])
            ->setSubmittedData(['pin' => '9999']);

        $this->assertNull($form->getValue('pin'));
    }

    public function testGetValuesReturnsEveryField(): void
    {
        $values = $this->booking()->fill(['full_name' => 'Jane'])->getValues();

        $this->assertSame('Jane', $values['full_name']);
        $this->assertArrayHasKey('submit', $values);
        $this->assertCount(8, $values);
    }

    public function testOwnsInput(): void
    {
        $form = $this->booking();

        $this->assertTrue($form->ownsInput([Form::FORM_KEY => 'booking']));
        $this->assertFalse($form->ownsInput([Form::FORM_KEY => 'other', 'email' => 'x']));
        $this->assertFalse($form->ownsInput([Form::FORM_KEY => ['booking']]));
        $this->assertTrue($form->ownsInput(['email' => 'x']));
        $this->assertFalse($form->ownsInput(['submit' => 'Book appointment']));
    }

    public function testToArray(): void
    {
        $data = $this->booking()->toArray();

        $this->assertSame('booking', $data['name']);
        $this->assertSame('post', $data['method']);
        $this->assertSame('/bookings', $data['action']);
        $this->assertCount(8, $data['fields']);
        $this->assertSame('select', $data['fields'][2]['type']);
        $this->assertSame(['1' => 'Suit fitting', '2' => 'Alterations'], $data['fields'][2]['options']);
    }
}
