<?php

declare(strict_types=1);

namespace Ephraitech\Forms\Tests\Unit\Fields;

use CodeIgniter\Test\CIUnitTestCase;
use Ephraitech\Forms\Config\Forms;
use Ephraitech\Forms\Exceptions\FormException;
use Ephraitech\Forms\Fields\Email;
use Ephraitech\Forms\Fields\Text;

/**
 * Behaviour shared by every field, exercised through Text.
 *
 * @internal
 */
final class FieldTest extends CIUnitTestCase
{
    private Forms $config;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = new Forms();
    }

    public function testLabelIsHumanisedFromName(): void
    {
        $this->assertSame('Appointment date', (new Text('appointment_date'))->getLabel());
        $this->assertSame('Full name', (new Text('full-name'))->getLabel());
        $this->assertSame('Custom', (new Text('x', 'Custom'))->getLabel());
        $this->assertSame('Changed', (new Text('x'))->label('Changed')->getLabel());
    }

    public function testInvalidNameThrows(): void
    {
        $this->expectException(FormException::class);

        new Text('1st name');
    }

    public function testNameWithBracketsIsRejectedForNow(): void
    {
        $this->expectException(FormException::class);

        new Text('items[]');
    }

    public function testTypeViewAndInputType(): void
    {
        $field = new Email('email');

        $this->assertSame('email', $field->getType());
        $this->assertSame('field_input', $field->getView());
        $this->assertSame('email', $field->getInputType());
    }

    public function testRulesAndRequiredDetection(): void
    {
        $field = (new Text('name'))->rules('required|max_length[100]');

        $this->assertSame(['required', 'max_length[100]'], $field->getRules());
        $this->assertTrue($field->isRequired());
        $this->assertFalse((new Text('name'))->rules('permit_empty|max_length[5]')->isRequired());
        $this->assertFalse((new Text('name'))->rules('required_with[email]')->isRequired());
    }

    public function testInvalidRulesThrow(): void
    {
        $this->expectException(FormException::class);

        (new Text('name'))->rules(['required', 123]);
    }

    public function testMessages(): void
    {
        $field = (new Text('name'))->messages(['required' => 'Tell us your name.']);

        $this->assertSame(['required' => 'Tell us your name.'], $field->getMessages());
    }

    public function testRuleAttributesFromLengthRules(): void
    {
        $field = (new Text('name'))->rules('required|min_length[3]|max_length[100]');

        $this->assertSame(
            ['required' => true, 'maxlength' => 100, 'minlength' => 3],
            $field->ruleAttributes($this->config),
        );
    }

    public function testExactLengthSetsBothLengths(): void
    {
        $field = (new Text('code'))->rules('exact_length[6]');

        $this->assertSame(['minlength' => 6, 'maxlength' => 6], $field->ruleAttributes($this->config));
    }

    public function testRuleAttributesCanBeDisabled(): void
    {
        $this->config->ruleAttributes = false;

        $field = (new Text('name'))->rules('required|max_length[100]');

        $this->assertSame([], $field->ruleAttributes($this->config));
    }

    public function testAttributesAndBooleanHandling(): void
    {
        $field = (new Text('name'))
            ->attributes(['data-role' => 'name', 'spellcheck' => 'false'])
            ->readonly()
            ->autofocus()
            ->autocomplete('name');

        $this->assertSame(
            ['data-role' => 'name', 'spellcheck' => 'false', 'readonly' => true, 'autofocus' => true, 'autocomplete' => 'name'],
            $field->getAttributes(),
        );

        $field->readonly(false)->attribute('data-role', null);

        $this->assertSame(['spellcheck' => 'false', 'autofocus' => true, 'autocomplete' => 'name'], $field->getAttributes());
    }

    public function testSpecialAttributesAreRouted(): void
    {
        $field = (new Text('name'))->attributes([
            'id'          => 'custom-id',
            'class'       => 'one two',
            'placeholder' => 'Your name',
            'disabled'    => true,
        ]);

        $this->assertSame('custom-id', $field->getId('booking'));
        $this->assertSame(['one', 'two'], $field->getClasses());
        $this->assertSame('Your name', $field->getPlaceholder());
        $this->assertTrue($field->isDisabled());
        $this->assertSame([], $field->getAttributes());
    }

    public function testReservedAttributesThrow(): void
    {
        $this->expectException(FormException::class);
        $this->expectExceptionMessage('the "value" attribute is managed by the package');

        (new Text('name'))->attribute('VALUE', 'x');
    }

    public function testInvalidAttributeNameThrows(): void
    {
        $this->expectException(FormException::class);

        (new Text('name'))->attribute('onclick="x"', 'y');
    }

    public function testClassesAreDeduplicated(): void
    {
        $field = (new Text('name'))->addClass('a b', 'b', ' c ');

        $this->assertSame(['a', 'b', 'c'], $field->getClasses());
    }

    public function testIdGeneration(): void
    {
        $field = new Text('email');

        $this->assertSame('email', $field->getId());
        $this->assertSame('booking_email', $field->getId('booking'));
        $this->assertSame('my_form_email', $field->getId('my form'));
        $this->assertSame('fixed', $field->id('fixed')->getId('booking'));
        $this->assertSame('booking_email', $field->id(null)->getId('booking'));
    }

    public function testHelpDefaultAndPlaceholder(): void
    {
        $field = (new Text('name'))->help('As on your ID.')->default('Jane')->placeholder('Name');

        $this->assertSame('As on your ID.', $field->getHelp());
        $this->assertSame('Jane', $field->getDefault());
        $this->assertSame('Name', $field->getPlaceholder());
        $this->assertNull($field->help('  ')->getHelp());
    }

    public function testDisabledFieldIsNotSubmittable(): void
    {
        $field = new Text('name');

        $this->assertTrue($field->isSubmittable());
        $this->assertFalse($field->disabled()->isSubmittable());
        $this->assertTrue($field->shouldRepopulate());
    }

    public function testNormaliseValueTrimsAndConvertsEmptyToNull(): void
    {
        $field = new Text('name');

        $this->assertSame('Jane', $field->normaliseValue('  Jane ', $this->config));
        $this->assertNull($field->normaliseValue('   ', $this->config));
        $this->assertSame(5, $field->normaliseValue(5, $this->config));
        $this->assertNull($field->valueWhenMissing($this->config));
    }

    public function testNormaliseValueRespectsConfig(): void
    {
        $this->config->trimStrings        = false;
        $this->config->emptyStringsToNull = false;

        $field = new Text('name');

        $this->assertSame('  Jane ', $field->normaliseValue('  Jane ', $this->config));
        $this->assertSame('', $field->normaliseValue('', $this->config));
    }

    public function testUnknownMethodWithoutParentThrows(): void
    {
        $this->expectException(FormException::class);
        $this->expectExceptionMessage('Call to undefined method');

        (new Text('name'))->email('email');
    }

    public function testUnknownMethodIsForwardedToParent(): void
    {
        $parent = new class () {
            public function email(string $name): string
            {
                return 'forwarded:' . $name;
            }
        };

        $field = (new Text('name'))->setParent($parent);

        $this->assertSame('forwarded:email', $field->email('email'));
    }

    public function testToArray(): void
    {
        $data = (new Text('name', 'Name'))->rules('required')->help('Help')->toArray();

        $this->assertSame('text', $data['type']);
        $this->assertSame('text', $data['input_type']);
        $this->assertSame('name', $data['name']);
        $this->assertSame('Name', $data['label']);
        $this->assertSame(['required'], $data['rules']);
        $this->assertSame('Help', $data['help']);
    }
}
