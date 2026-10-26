<?php

declare(strict_types=1);

namespace Ephraitech\Forms\Tests\Unit\Fields;

use CodeIgniter\Test\CIUnitTestCase;
use Ephraitech\Forms\Config\Forms;
use Ephraitech\Forms\Exceptions\FormException;
use Ephraitech\Forms\Fields\Checkbox;
use Ephraitech\Forms\Fields\Date;
use Ephraitech\Forms\Fields\Hidden;
use Ephraitech\Forms\Fields\Number;
use Ephraitech\Forms\Fields\Password;
use Ephraitech\Forms\Fields\Radio;
use Ephraitech\Forms\Fields\Select;
use Ephraitech\Forms\Fields\Submit;
use Ephraitech\Forms\Fields\Tel;
use Ephraitech\Forms\Fields\Textarea;
use Ephraitech\Forms\Fields\Url;

/**
 * Behaviour specific to individual field types.
 *
 * @internal
 */
final class FieldTypesTest extends CIUnitTestCase
{
    private Forms $config;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = new Forms();
    }

    public function testSimpleInputTypes(): void
    {
        $this->assertSame('tel', (new Tel('phone'))->getInputType());
        $this->assertSame('url', (new Url('website'))->getInputType());
        $this->assertSame('date', (new Date('dob'))->getInputType());
        $this->assertSame('field_input', (new Tel('phone'))->getView());
    }

    public function testPasswordIsNeverRepopulatedOrTrimmed(): void
    {
        $field = new Password('password');

        $this->assertFalse($field->shouldRepopulate());
        $this->assertSame(' secret ', $field->normaliseValue(' secret ', $this->config));
        $this->assertNull($field->normaliseValue('', $this->config));
    }

    public function testNumberRuleAttributesAndStep(): void
    {
        $field = (new Number('quantity'))
            ->rules('required|integer|greater_than_equal_to[1]|less_than_equal_to[20]')
            ->step(1);

        $this->assertSame(['required' => true, 'min' => 1, 'max' => 20], $field->ruleAttributes($this->config));
        $this->assertSame(['step' => 1], $field->getAttributes());
        $this->assertSame('12.50', $field->normaliseValue('12.50', $this->config));
    }

    public function testDateMinMaxAndNoLengthAttributes(): void
    {
        $field = (new Date('appointment_date'))
            ->rules('required|valid_date[Y-m-d]|max_length[10]')
            ->min('2026-01-01')
            ->max('2026-12-31');

        $this->assertSame(['required' => true], $field->ruleAttributes($this->config));
        $this->assertSame(['min' => '2026-01-01', 'max' => '2026-12-31'], $field->getAttributes());
    }

    public function testHiddenHasNoRuleAttributes(): void
    {
        $field = (new Hidden('booking_id'))->rules('required|is_natural_no_zero');

        $this->assertSame('field_hidden', $field->getView());
        $this->assertSame([], $field->ruleAttributes($this->config));
        $this->assertTrue($field->isSubmittable());
    }

    public function testTextareaRowsAndLengthAttributes(): void
    {
        $field = (new Textarea('notes'))->rules('permit_empty|max_length[500]')->rows(0);

        $this->assertSame('field_textarea', $field->getView());
        $this->assertSame(['rows' => 1], $field->getAttributes());
        $this->assertSame(['maxlength' => 500], $field->ruleAttributes($this->config));
    }

    public function testSelectOptionsFromArray(): void
    {
        $field = (new Select('service_id'))->options([1 => 'Suit fitting', 2 => 'Alterations']);

        $this->assertSame(['1' => 'Suit fitting', '2' => 'Alterations'], $field->getOptions());
        $this->assertSame(['1', '2'], $field->getOptionValues());
        $this->assertTrue($field->hasOptionValue(2));
        $this->assertTrue($field->hasOptionValue('1'));
        $this->assertFalse($field->hasOptionValue('3'));
        $this->assertFalse($field->hasOptionValue(['1']));
    }

    public function testSelectOptionsClosureRunsOnce(): void
    {
        $calls = 0;

        $field = (new Select('service_id'))->options(static function () use (&$calls): array {
            $calls++;

            return ['a' => 'A'];
        });

        $this->assertSame(0, $calls);

        $field->getOptions();
        $field->getOptionValues();
        $field->toArray();

        $this->assertSame(1, $calls);
    }

    public function testSelectOptionGroups(): void
    {
        $field = (new Select('style'))->options([
            'Suits'  => ['1' => 'Two-piece', '2' => 'Three-piece'],
            'Shirts' => ['3' => 'Formal'],
        ]);

        $this->assertSame(['1', '2', '3'], $field->getOptionValues());
        $this->assertSame(['3' => 'Formal'], $field->getOptions()['Shirts']);
    }

    public function testClosureMustReturnArray(): void
    {
        $field = (new Select('x'))->options(static fn () => 'not an array');

        $this->expectException(FormException::class);
        $this->expectExceptionMessage('the options closure must return an array.');

        $field->getOptions();
    }

    public function testRadioRejectsOptionGroups(): void
    {
        $field = (new Radio('size'))->options(['Group' => ['s' => 'Small']]);

        $this->expectException(FormException::class);
        $this->expectExceptionMessage('option groups are not supported');

        $field->getOptions();
    }

    public function testRadioInlineAndOptionIds(): void
    {
        $field = (new Radio('payment'))->options(['cash' => 'Cash', 'mobile money' => 'Mobile money'])->inline();

        $this->assertTrue($field->isInline());
        $this->assertSame('field_radio', $field->getView());
        $this->assertSame('booking_payment_cash', $field->getOptionId('cash', 'booking'));
        $this->assertSame('payment_mobile_money', $field->getOptionId('mobile money'));
        $this->assertSame('payment_empty', $field->getOptionId(''));
    }

    public function testCheckboxValues(): void
    {
        $field = new Checkbox('is_active', 'Active');

        $this->assertSame('1', $field->getCheckedValue());
        $this->assertTrue($field->isChecked('1'));
        $this->assertTrue($field->isChecked(1));
        $this->assertTrue($field->isChecked(true));
        $this->assertFalse($field->isChecked('0'));
        $this->assertFalse($field->isChecked(null));
        $this->assertSame(0, $field->valueWhenMissing($this->config));

        $field->checkedValue('yes');

        $this->assertTrue($field->isChecked('yes'));
        $this->assertFalse($field->isChecked('1'));
    }

    public function testCheckboxUncheckedValueFollowsConfig(): void
    {
        $this->config->checkboxUncheckedValue = 'no';

        $this->assertSame('no', (new Checkbox('agree'))->valueWhenMissing($this->config));
    }

    public function testSubmitIsNeverSubmittedOrValidated(): void
    {
        $submit = new Submit('Book now');

        $this->assertSame('submit', $submit->getName());
        $this->assertSame('Book now', $submit->getLabel());
        $this->assertFalse($submit->isSubmittable());
        $this->assertFalse($submit->shouldRepopulate());
        $this->assertSame([], $submit->ruleAttributes($this->config));

        $this->expectException(FormException::class);

        $submit->rules('required');
    }
}
