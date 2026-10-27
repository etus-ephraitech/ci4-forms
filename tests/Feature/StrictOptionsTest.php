<?php

declare(strict_types=1);

namespace Ephraitech\Forms\Tests\Feature;

use CodeIgniter\Config\Services;
use CodeIgniter\Test\CIUnitTestCase;
use Ephraitech\Forms\Config\Forms;
use Ephraitech\Forms\Fields\Checkbox;
use Ephraitech\Forms\Fields\Checkboxes;
use Ephraitech\Forms\Fields\Radio;
use Ephraitech\Forms\Fields\Select;
use Ephraitech\Forms\Fields\Text;
use Ephraitech\Forms\Form;

/**
 * Strict option checking: submitted values must be among a choice field's
 * options.
 *
 * @internal
 */
final class StrictOptionsTest extends CIUnitTestCase
{
    private Forms $config;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = new Forms();
        Services::resetSingle('validation');
    }

    private function form(): Form
    {
        $form = Form::make('order', $this->config)->setOldInput(null)->setOldErrors(null);

        $form->select('service', 'Service', ['fitting' => 'Suit fitting', 'alterations' => 'Alterations']);
        $form->radio('payment', 'Payment', ['cash' => 'Cash', 'momo' => 'Mobile money']);
        $form->checkboxes('extras', 'Extras', ['pressing' => 'Pressing', 'delivery' => 'Delivery']);
        $form->select('colours', 'Colours', ['navy' => 'Navy', 'grey' => 'Grey'])->multiple();
        $form->checkbox('is_rush', 'Rush order');
        $form->text('notes');

        return $form;
    }

    // -- Defaults -------------------------------------------------------------

    public function testOnByDefault(): void
    {
        $this->assertTrue((new Forms())->strictOptions);
        $this->assertSame('Please choose a valid option for {field}.', (new Forms())->strictOptionsMessage);
    }

    public function testValidValuesPass(): void
    {
        $form = $this->form();

        $this->assertTrue($form->validate([
            'service' => 'alterations',
            'payment' => 'momo',
            'extras'  => ['pressing', 'delivery'],
            'colours' => ['grey'],
            'is_rush' => '1',
            'notes'   => 'anything at all',
        ]));
    }

    public function testEmptyAndMissingValuesAreLeftToRequired(): void
    {
        $form = $this->form();

        $this->assertTrue($form->validate(['service' => '', 'extras' => []]));

        $form = $this->form();
        $form->getField('service')->rules('required');

        $this->assertFalse($form->validate(['service' => '']));
        $this->assertStringContainsString('required', (string) $form->getError('service'));
    }

    // -- Rejection ------------------------------------------------------------

    public function testTamperedSingleValuesAreRejected(): void
    {
        $form = $this->form();

        $this->assertFalse($form->validate(['service' => 'free_suit', 'payment' => 'bitcoin']));
        $this->assertSame('Please choose a valid option for Service.', $form->getError('service'));
        $this->assertSame('Please choose a valid option for Payment.', $form->getError('payment'));
    }

    public function testOneBadItemRejectsAList(): void
    {
        $form = $this->form();

        $this->assertFalse($form->validate([
            'extras'  => ['pressing', 'free_shoes'],
            'colours' => ['navy', 'gold'],
        ]));
        $this->assertSame('Please choose a valid option for Extras.', $form->getError('extras'));
        $this->assertSame('Please choose a valid option for Colours.', $form->getError('colours'));
    }

    public function testCheckboxAcceptsOnlyItsCheckedValue(): void
    {
        $form = $this->form();

        $this->assertFalse($form->validate(['is_rush' => 'hacked']));
        $this->assertSame('Please choose a valid option for Rush order.', $form->getError('is_rush'));

        $form = $this->form();
        $form->getField('is_rush')->checkedValue('yes');

        $this->assertTrue($form->validate(['is_rush' => 'yes']));
        $this->assertFalse($this->form()->validate(['is_rush' => 'yes']));
    }

    public function testTextFieldsAreNeverChecked(): void
    {
        $form = $this->form();

        $this->assertFalse($form->usesStrictOptions($form->getField('notes')));
        $this->assertTrue($form->usesStrictOptions($form->getField('service')));
    }

    // -- Matching rules -------------------------------------------------------

    public function testOptionGroupValuesAreAccepted(): void
    {
        $form = Form::make('x', $this->config)->setOldInput(null);
        $form->select('style', 'Style', ['Suits' => ['1' => 'Two-piece'], 'Shirts' => ['3' => 'Formal']]);

        $this->assertTrue($form->validate(['style' => '3']));
        $this->assertFalse((clone $form)->validate(['style' => 'Suits']), 'a group label is not a value');
    }

    public function testClosureOptionsWithIntegerKeys(): void
    {
        $form = Form::make('x', $this->config)->setOldInput(null);
        $form->select('service_id', 'Service', static fn (): array => [1 => 'A', 2 => 'B']);

        $this->assertTrue($form->validate(['service_id' => '2']));
        $this->assertTrue($form->validate(['service_id' => 2]));
        $this->assertTrue($form->validate(['service_id' => ' 2 ']), 'values are trimmed before checking');
        $this->assertFalse($form->validate(['service_id' => '3']));
    }

    public function testMatchingIsExact(): void
    {
        $form = $this->form();

        $this->assertFalse($form->validate(['service' => 'Fitting']));
    }

    public function testRuleErrorTakesPrecedence(): void
    {
        $form = $this->form();
        $form->getField('service')->rules('max_length[5]');

        $form->validate(['service' => 'way_too_long_and_invalid']);

        $this->assertStringContainsString('cannot exceed', (string) $form->getError('service'));
        $this->assertCount(1, $form->getErrors());
    }

    public function testDisabledFieldsAreSkipped(): void
    {
        $form = $this->form();
        $form->getField('service')->disabled();

        $this->assertTrue($form->validate(['service' => 'anything']));
    }

    // -- Messages and switches --------------------------------------------------

    public function testPerFieldMessage(): void
    {
        $form = $this->form();
        $form->getField('payment')->messages(['options' => 'We only accept cash or mobile money.']);

        $form->validate(['payment' => 'cheque']);

        $this->assertSame('We only accept cash or mobile money.', $form->getError('payment'));
    }

    public function testConfigMessage(): void
    {
        $this->config->strictOptionsMessage = '{field}: not an allowed choice.';

        $form = $this->form();
        $form->validate(['service' => 'x']);

        $this->assertSame('Service: not an allowed choice.', $form->getError('service'));
    }

    public function testCanBeTurnedOffInConfig(): void
    {
        $this->config->strictOptions = false;

        $form = $this->form();

        $this->assertTrue($form->validate(['service' => 'x', 'extras' => ['y'], 'is_rush' => 'z']));
    }

    public function testFieldSettingOverridesConfig(): void
    {
        $form = $this->form();
        $form->getField('service')->strictOptions(false);

        $this->assertTrue($form->validate(['service' => 'added_by_javascript']));

        $this->config->strictOptions = false;

        $form = $this->form();
        $form->getField('payment')->strictOptions();

        $this->assertFalse($form->validate(['service' => 'x', 'payment' => 'y']));
        $this->assertSame(['payment'], array_keys($form->getErrors()));
    }

    public function testErrorsReachTheSharedValidationService(): void
    {
        $form = $this->form();
        $form->validate(['service' => 'free_suit']);

        $this->assertSame(
            'Please choose a valid option for Service.',
            service('validation')->getErrors()['service'] ?? null,
            'needed for redirect()->withInput() to carry the error',
        );
    }

    // -- Field contract -------------------------------------------------------

    public function testAllowedValues(): void
    {
        $this->assertSame(['1'], (new Checkbox('a'))->getAllowedValues());
        $this->assertSame(['yes'], (new Checkbox('a'))->checkedValue('yes')->getAllowedValues());
        $this->assertSame(['a', 'b'], (new Radio('r'))->options(['a' => 'A', 'b' => 'B'])->getAllowedValues());
        $this->assertSame(['x'], (new Checkboxes('c'))->options(['x' => 'X'])->getAllowedValues());
        $this->assertSame(['1', '2'], (new Select('s'))->options(['G' => ['1' => 'One', '2' => 'Two']])->getAllowedValues());
        $this->assertNull((new Select('s'))->getStrictOptions());
        $this->assertFalse((new Select('s'))->strictOptions(false)->getStrictOptions());
        $this->assertFalse(Form::make('x', $this->config)->usesStrictOptions(new Text('t')));
    }
}
