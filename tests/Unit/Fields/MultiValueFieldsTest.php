<?php

declare(strict_types=1);

namespace Ephraitech\Forms\Tests\Unit\Fields;

use CodeIgniter\Test\CIUnitTestCase;
use Ephraitech\Forms\Config\Forms;
use Ephraitech\Forms\Exceptions\FormException;
use Ephraitech\Forms\Fields\Checkbox;
use Ephraitech\Forms\Fields\Checkboxes;
use Ephraitech\Forms\Fields\Select;
use Ephraitech\Forms\Fields\Text;

/**
 * @internal
 */
final class MultiValueFieldsTest extends CIUnitTestCase
{
    private Forms $config;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = new Forms();
    }

    public function testSingleFieldsAreNotMultiple(): void
    {
        $this->assertFalse((new Text('name'))->isMultiple());
        $this->assertFalse((new Select('service'))->isMultiple());
        $this->assertFalse((new Checkbox('agree'))->isMultiple());
    }

    public function testSelectCanBeMultiple(): void
    {
        $field = (new Select('tags'))->multiple()->size(5);

        $this->assertTrue($field->isMultiple());
        $this->assertSame(['size' => 5], $field->getAttributes());
        $this->assertTrue($field->toArray()['multiple']);
        $this->assertFalse($field->multiple(false)->isMultiple());
    }

    public function testCheckboxesField(): void
    {
        $field = (new Checkboxes('interests', 'Interests'))
            ->options(['suits' => 'Suits', 'shirts' => 'Shirts'])
            ->rules('required')
            ->inline();

        $this->assertTrue($field->isMultiple());
        $this->assertSame('checkboxes', $field->getType());
        $this->assertSame('field_checkboxes', $field->getView());
        $this->assertTrue($field->isInline());
        $this->assertTrue($field->isRequired());
        $this->assertSame([], $field->ruleAttributes($this->config), 'required on every box would force all to be ticked');
        $this->assertSame('booking_interests_suits', $field->getOptionId('suits', 'booking'));
        $this->assertSame(['suits' => 'Suits', 'shirts' => 'Shirts'], $field->toArray()['options']);
    }

    public function testCheckboxesRejectOptionGroups(): void
    {
        $field = (new Checkboxes('x'))->options(['Group' => ['a' => 'A']]);

        $this->expectException(FormException::class);

        $field->getOptions();
    }

    public function testEachRules(): void
    {
        $field = (new Checkboxes('interests'))->eachRules('in_list[suits,shirts]|max_length[20]');

        $this->assertSame(['in_list[suits,shirts]', 'max_length[20]'], $field->getEachRules());
        $this->assertSame(['in_list[suits,shirts]', 'max_length[20]'], $field->toArray()['each_rules']);
        $this->assertSame([], (new Text('name'))->getEachRules());
    }

    public function testInvalidEachRulesThrow(): void
    {
        $this->expectException(FormException::class);

        (new Checkboxes('x'))->eachRules(['in_list[a]', 5]);
    }

    public function testMultiValueNormalisationAlwaysGivesAList(): void
    {
        $field = new Checkboxes('interests');

        $this->assertSame(['suits', 'shirts'], $field->normaliseValue([' suits ', 'shirts'], $this->config));
        $this->assertSame(['suits'], $field->normaliseValue('suits', $this->config));
        $this->assertSame([], $field->normaliseValue(null, $this->config));
        $this->assertSame([], $field->normaliseValue('', $this->config));
        $this->assertSame(['a', 'b'], $field->normaliseValue(['x' => 'a', '', '  ', ['nested'], 'b'], $this->config));
        $this->assertSame([], $field->valueWhenMissing($this->config));
    }

    public function testMultiValueNormalisationKeepsNumbers(): void
    {
        $this->assertSame([1, 2], (new Select('ids'))->multiple()->normaliseValue([1, 2], $this->config));
    }

    public function testSingleValueFieldsNeverReturnArrays(): void
    {
        $this->assertNull((new Text('email'))->normaliseValue(['x@example.com'], $this->config));
        $this->assertNull((new Select('service'))->normaliseValue(['a', 'b'], $this->config));
        $this->assertSame(0, (new Checkbox('agree'))->normaliseValue(['1'], $this->config));
    }
}
