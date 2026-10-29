<?php

declare(strict_types=1);

namespace Ephraitech\Forms\Tests\Docs;

use CodeIgniter\Config\Services;
use CodeIgniter\Test\CIUnitTestCase;
use Ephraitech\Forms\Config\Forms;
use Ephraitech\Forms\Exceptions\FormException;
use Ephraitech\Forms\Form;
use Ephraitech\Forms\Schema\SchemaException;

/**
 * Checks the statements made on the Concepts pages. Each test is named after
 * the page and section that make the claim; when one fails, that paragraph
 * no longer describes the package.
 *
 * @internal
 */
final class ConceptsClaimsTest extends CIUnitTestCase
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
        $form = Form::make('booking', $this->config);
        $form->text('full_name')->rules('required');
        $form->email('email')->rules('permit_empty|valid_email');
        $form->checkbox('newsletter');
        $form->checkboxes('interests', 'Interests', ['suits' => 'Suits', 'shirts' => 'Shirts']);
        $form->select('service', 'Service', ['fitting' => 'Fitting'])->rules('max_length[10]');
        $form->password('pin');
        $form->text('legacy')->disabled();

        return $form;
    }

    // -- concepts/values.md -----------------------------------------------------

    public function testValuesWhereAValueComesFrom(): void
    {
        $form = $this->form()->setOldInput(null);
        $form->getField('full_name')->default('From the default');

        $this->assertSame('From the default', $form->getValue('full_name'));

        $form->fill(['full_name' => 'From fill()']);
        $this->assertSame('From fill()', $form->getValue('full_name'));

        $form->setOldInput(['_form' => 'booking', 'full_name' => 'From old input']);
        $this->assertSame('From old input', $form->getValue('full_name'));

        $form->setSubmittedData(['full_name' => 'From the submission']);
        $this->assertSame('From the submission', $form->getValue('full_name'));
    }

    public function testValuesMissingMeansEmptyOnceSubmitted(): void
    {
        $form = $this->form()->setOldInput(null)->fill(['newsletter' => 1, 'interests' => ['suits'], 'email' => 'saved@example.com']);
        $form->setSubmittedData(['full_name' => 'Jane']);

        $this->assertSame(0, $form->getValue('newsletter'), 'an unticked box stays unticked');
        $this->assertSame([], $form->getValue('interests'));
        $this->assertNull($form->getValue('email'));
    }

    public function testValuesOldInputBelongsToOneForm(): void
    {
        $form = $this->form()->fill(['full_name' => 'Saved'])->setOldInput(['_form' => 'newsletter', 'full_name' => 'Other form']);

        $this->assertSame('Saved', $form->getValue('full_name'));
    }

    public function testValuesPasswordsAreNeverShown(): void
    {
        $form = $this->form()->setOldInput(null)->fill(['pin' => '1234']);
        $form->setSubmittedData(['pin' => '9999']);

        $this->assertNull($form->getValue('pin'));
        $this->assertStringContainsString('name="pin" id="booking_pin" value=""', $form->field('pin'));
    }

    public function testValuesWhatValidatedReturns(): void
    {
        $form = $this->form()->setOldInput(null);

        $this->assertTrue($form->validate([
            'full_name' => '  Jane ',
            'email'     => '',
            'pin'       => ' 12 34 ',
            'legacy'    => 'tampered',
            'is_admin'  => '1',
            'service'   => ['fitting'],
        ]));

        $this->assertSame([
            'full_name'  => 'Jane',
            'email'      => null,
            'newsletter' => 0,
            'interests'  => [],
            'service'    => null,
            'pin'        => ' 12 34 ',
        ], $form->validated());
    }

    // -- concepts/validation.md ---------------------------------------------------

    public function testValidationSeesTrimmedValuesButKeepsEmptyOnes(): void
    {
        $form = $this->form()->setOldInput(null);

        $this->assertTrue($form->validate(['full_name' => 'Jane', 'email' => '  jane@example.com  ']));
        $this->assertFalse($form->validate(['full_name' => '    ']));
        $this->assertTrue($form->hasError('full_name'));
    }

    public function testValidationMissingKeysStayMissing(): void
    {
        $form = Form::make('signup', $this->config)->setOldInput(null);
        $form->checkbox('terms')->rules('required');

        $this->assertFalse($form->validate([]), 'an unticked required box fails');
    }

    public function testValidationPrepareRunsFirst(): void
    {
        $form = new class (null, $this->config) extends Form {
            protected function define(): void
            {
                $this->text('code')->rules('required|exact_length[4]');
            }

            protected function prepareForValidation(array $data): array
            {
                $data['code'] = strtoupper(str_replace('-', '', (string) ($data['code'] ?? '')));

                return $data;
            }
        };
        $form->setOldInput(null);

        $this->assertTrue($form->validate(['code' => 'ab-cd']));
        $this->assertSame(['code' => 'ABCD'], $form->validated());
        $this->assertSame('ABCD', $form->getValue('code'));
    }

    public function testValidationRuleErrorsFirstOneErrorPerField(): void
    {
        $form = $this->form()->setOldInput(null);
        $form->select('branch', 'Branch', ['kampala' => 'Kampala']);

        $form->validate(['full_name' => '', 'service' => 'way_too_long_value', 'branch' => 'gulu']);

        $this->assertSame(['full_name', 'service', 'branch'], array_keys($form->getErrors()));
        $this->assertStringContainsString('cannot exceed', (string) $form->getError('service'), 'the rule error, not the option error');
        $this->assertSame('Please choose a valid option for Branch.', $form->getError('branch'));
    }

    public function testValidationFirstFailingRuleIsShown(): void
    {
        $form = Form::make('x', $this->config)->setOldInput(null);
        $form->text('code', 'Code')->rules('required|min_length[4]|alpha');

        $form->validate(['code' => '']);
        $this->assertSame('The Code field is required.', $form->getError('code'));

        $form->validate(['code' => '1']);
        $this->assertStringContainsString('at least 4 characters', (string) $form->getError('code'), 'min_length fails before alpha is reported');
    }

    public function testValidationItemErrorsAreReportedOnTheField(): void
    {
        $form = Form::make('x', $this->config)->setOldInput(null);
        $form->checkboxes('tags', 'Tags', ['a' => 'A', 'bb' => 'BB'])->eachRules('max_length[1]');

        $form->validate(['tags' => ['a', 'bb']]);

        $this->assertSame(['tags'], array_keys($form->getErrors()));
    }

    public function testValidationSetErrorTravelsWithRedirects(): void
    {
        $form = $this->form()->setOldInput(null);
        $form->validate(['full_name' => 'Jane']);
        $form->setError('full_name', 'Already booked.');

        $this->assertFalse($form->isValid());
        $this->assertSame('Already booked.', service('validation')->getErrors()['full_name'] ?? null);
    }

    public function testValidationValidatedIsGuarded(): void
    {
        $form = $this->form()->setOldInput(null);

        try {
            $form->validated();
            $this->fail('validated() before validate() must throw');
        } catch (FormException) {
            $this->addToAssertionCount(1);
        }

        $form->validate(['full_name' => '']);

        $this->expectException(FormException::class);
        $form->validated();
    }

    // -- concepts/security.md -----------------------------------------------------

    public function testSecurityOutputIsEscaped(): void
    {
        $form = $this->form()->setOldInput(null)->fill(['full_name' => '"><script>alert(1)</script>']);
        $form->getField('email')->label('<b>Email</b>')->help('<i>Optional</i>');

        $html = $form->render();

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('<b>Email</b>', $html);
        $this->assertStringContainsString('&lt;i&gt;Optional&lt;/i&gt;', $html);
    }

    public function testSecurityTamperedChoicesAreRejected(): void
    {
        $form = $this->form()->setOldInput(null);

        $this->assertFalse($form->validate(['full_name' => 'Jane', 'interests' => ['suits', 'free_shoes'], 'newsletter' => 'hacked']));
        $this->assertTrue($form->hasError('interests'));
        $this->assertTrue($form->hasError('newsletter'));
    }

    public function testSecuritySchemasAreRestrictedByDefault(): void
    {
        $this->expectException(SchemaException::class);
        $this->expectExceptionMessage('the "onfocus" attribute is not allowed in this schema');

        Form::fromSchema(['fields' => [['type' => 'text', 'name' => 'a', 'attributes' => ['onfocus' => 'x()']]]], $this->config);
    }

    // -- concepts/architecture.md -------------------------------------------------

    public function testArchitectureLayoutIsSeparateFromFields(): void
    {
        $form = Form::make('x', $this->config);
        $form->fieldset('Contact', static function (Form $form): void {
            $form->row(static function (Form $form): void {
                $form->text('first_name');
                $form->text('last_name');
            });
        });

        $this->assertSame(['first_name', 'last_name'], array_keys($form->getFields()));
        $this->assertSame('fieldset', $form->getLayout()[0]->getType());
    }
}
