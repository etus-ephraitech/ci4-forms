<?php

declare(strict_types=1);

namespace Ephraitech\Forms\Tests\Feature;

use CodeIgniter\Config\Services;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\Test\CIUnitTestCase;
use Config\App;
use Ephraitech\Forms\Config\Forms;
use Ephraitech\Forms\Exceptions\FormException;
use Ephraitech\Forms\Form;
use Ephraitech\Forms\Tests\Support\BookingForm;

/**
 * Validation against CodeIgniter's real Validation service, requests and
 * session.
 *
 * @internal
 */
final class ValidationTest extends CIUnitTestCase
{
    private Forms $config;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = new Forms();
        Services::resetSingle('validation');
    }

    protected function tearDown(): void
    {
        $_POST = [];
        $_GET  = [];

        parent::tearDown();
    }

    private function booking(): BookingForm
    {
        return (new BookingForm(null, $this->config))->setOldInput(null)->setOldErrors(null);
    }

    /**
     * @return array<string, string>
     */
    private function validBooking(array $overrides = []): array
    {
        return array_merge([
            'full_name'        => 'Jane Namuli',
            'email'            => 'jane@example.com',
            'service_id'       => '1',
            'appointment_date' => '2026-11-20',
            'notes'            => '',
            'is_returning'     => '1',
            'pin'              => '',
            'submit'           => 'Book appointment',
            'csrf_test_name'   => 'token',
            Form::FORM_KEY     => 'booking',
        ], $overrides);
    }

    private function request(string $method, array $post = [], array $get = []): IncomingRequest
    {
        /** @var IncomingRequest $request */
        $request = Services::incomingrequest(config(App::class), false);
        $request->setMethod($method);
        $request->setGlobal('post', $post);
        $request->setGlobal('get', $get);

        return $request;
    }

    // -- Passing and failing --------------------------------------------------

    public function testValidDataPasses(): void
    {
        $form = $this->booking();

        $this->assertTrue($form->validate($this->validBooking()));
        $this->assertTrue($form->isValid());
        $this->assertFalse($form->hasErrors());
        $this->assertSame([], $form->getErrors());
    }

    public function testErrorsUseFieldLabels(): void
    {
        $form = $this->booking();

        $this->assertFalse($form->validate($this->validBooking(['full_name' => '', 'email' => 'not-an-email'])));
        $this->assertFalse($form->isValid());
        $this->assertTrue($form->hasError('full_name'));
        $this->assertStringContainsString('Full Name', (string) $form->getError('full_name'));
        $this->assertStringContainsString('Email Address', (string) $form->getError('email'));
        $this->assertNull($form->getError('service_id'));
        $this->assertSame(['full_name', 'email'], array_keys($form->getErrors()));
    }

    public function testCustomMessages(): void
    {
        $form = $this->booking();
        $form->getField('full_name')->messages(['required' => 'Please tell us your name.']);

        $form->validate($this->validBooking(['full_name' => '']));

        $this->assertSame('Please tell us your name.', $form->getError('full_name'));
    }

    public function testValuesAreTrimmedBeforeValidation(): void
    {
        $form = $this->booking();

        $this->assertTrue($form->validate($this->validBooking(['email' => '  jane@example.com  '])));

        $form = $this->booking();

        $this->assertFalse($form->validate($this->validBooking(['full_name' => '     '])));
        $this->assertTrue($form->hasError('full_name'));
    }

    public function testRegexRuleWithPipesWorks(): void
    {
        $form = Form::make('payment', $this->config)->setOldInput(null);
        $form->text('method')->rules('required|regex_match[/^(cash|mobile_money)$/]');

        $this->assertTrue($form->validate(['method' => 'mobile_money']));
        $this->assertFalse((clone $form)->validate(['method' => 'cheque']));
    }

    public function testUntickedRequiredCheckboxFails(): void
    {
        $form = Form::make('signup', $this->config)->setOldInput(null);
        $form->checkbox('terms', 'I accept the terms')->rules('required');

        $this->assertFalse($form->validate([]));
        $this->assertTrue($form->hasError('terms'));
        $this->assertTrue($form->validate(['terms' => '1']));
    }

    public function testDisabledFieldsAreNotValidated(): void
    {
        $form = $this->booking();
        $form->getField('email')->disabled();

        $this->assertTrue($form->validate($this->validBooking(['email' => 'not-an-email'])));
        $this->assertArrayNotHasKey('email', $form->validated());
    }

    public function testFormWithoutRulesPasses(): void
    {
        $form = Form::make('plain', $this->config)->setOldInput(null);
        $form->text('anything');

        $this->assertTrue($form->validate(['anything' => 'x']));
        $this->assertSame(['anything' => 'x'], $form->validated());
    }

    public function testRepeatedValidationStartsFresh(): void
    {
        $form = $this->booking();

        $this->assertFalse($form->validate($this->validBooking(['email' => 'bad'])));
        $this->assertTrue($form->validate($this->validBooking()));
        $this->assertSame([], $form->getErrors());
    }

    public function testFailedSubmissionIsRepopulated(): void
    {
        $form = $this->booking()->fill(['full_name' => 'Saved name']);

        $form->validate($this->validBooking(['full_name' => 'Typed name', 'email' => 'bad', 'is_returning' => null]));

        $this->assertSame('Typed name', $form->getValue('full_name'));
        $this->assertSame('bad', $form->getValue('email'));
        $this->assertNull($form->getValue('pin'));
    }

    // -- validated() ----------------------------------------------------------

    public function testValidatedReturnsOnlyCleanDefinedFields(): void
    {
        $form = $this->booking();
        $form->validate($this->validBooking([
            'full_name' => '  Jane Namuli ',
            'is_admin'  => '1',
        ]));

        $this->assertSame([
            'full_name'        => 'Jane Namuli',
            'email'            => 'jane@example.com',
            'service_id'       => '1',
            'appointment_date' => '2026-11-20',
            'notes'            => null,
            'is_returning'     => '1',
            'pin'              => null,
        ], $form->validated());
    }

    public function testValidatedFillsUncheckedCheckboxAndMissingFields(): void
    {
        $data = $this->validBooking();
        unset($data['is_returning'], $data['notes']);

        $form = $this->booking();
        $form->validate($data);

        $validated = $form->validated();

        $this->assertSame(0, $validated['is_returning']);
        $this->assertNull($validated['notes']);
    }

    public function testValidatedKeepsPasswordSpaces(): void
    {
        $form = $this->booking();
        $form->getField('pin')->rules('permit_empty|exact_length[6]');
        $form->validate($this->validBooking(['pin' => ' 1234 ']));

        $this->assertSame(' 1234 ', $form->validated()['pin']);
    }

    public function testValidatedCanExcludeFields(): void
    {
        $form = Form::make('register', $this->config)->setOldInput(null);
        $form->password('password')->rules('required|min_length[8]');
        $form->password('password_confirm', 'Confirm password')->rules('required|matches[password]');

        $this->assertFalse($form->validate(['password' => 'secret123', 'password_confirm' => 'different']));
        $this->assertTrue($form->hasError('password_confirm'));

        $this->assertTrue($form->validate(['password' => 'secret123', 'password_confirm' => 'secret123']));
        $this->assertSame(['password' => 'secret123'], $form->validated('password_confirm'));
    }

    public function testValidatedBeforeValidateThrows(): void
    {
        $this->expectException(FormException::class);
        $this->expectExceptionMessage('call validate() before validated()');

        $this->booking()->validated();
    }

    public function testValidatedAfterFailureThrows(): void
    {
        $form = $this->booking();
        $form->validate($this->validBooking(['email' => 'bad']));

        $this->expectException(FormException::class);
        $this->expectExceptionMessage('did not pass validation');

        $form->validated();
    }

    // -- Manual errors --------------------------------------------------------

    public function testSetErrorMakesFormInvalidAndReachesSharedService(): void
    {
        $form = $this->booking();

        $this->assertTrue($form->validate($this->validBooking()));

        $form->setError('appointment_date', 'That date is fully booked.');

        $this->assertFalse($form->isValid());
        $this->assertSame('That date is fully booked.', $form->getError('appointment_date'));
        $this->assertSame(
            'That date is fully booked.',
            service('validation')->getErrors()['appointment_date'] ?? null,
        );

        $form->clearErrors();

        $this->assertTrue($form->isValid());
    }

    // -- Requests -------------------------------------------------------------

    public function testValidatesPostRequest(): void
    {
        $form = $this->booking();

        $this->assertTrue($form->validate($this->request('POST', $this->validBooking())));
        $this->assertSame('Jane Namuli', $form->validated()['full_name']);
    }

    public function testGetFormReadsQueryString(): void
    {
        $form = Form::make('search', $this->config)->method('get')->setOldInput(null);
        $form->text('q', 'Search')->rules('required|min_length[2]');

        $request = $this->request('GET', ['q' => 'ignored post value'], ['q' => 'suits']);

        $this->assertTrue($form->validate($request));
        $this->assertSame(['q' => 'suits'], $form->validated());
    }

    public function testJsonRequest(): void
    {
        $form = $this->booking();

        $request = $this->request('POST');
        $request->setHeader('Content-Type', 'application/json');
        $request->setBody((string) json_encode($this->validBooking(['full_name' => 'From JSON'])));

        $this->assertTrue($form->validate($request));
        $this->assertSame('From JSON', $form->validated()['full_name']);
    }

    public function testDefaultsToSharedRequest(): void
    {
        Services::injectMock('request', $this->request('POST', $this->validBooking(['full_name' => 'Shared'])));

        $form = $this->booking();

        $this->assertTrue($form->validate());
        $this->assertSame('Shared', $form->validated()['full_name']);
    }

    // -- Hooks and rules export -----------------------------------------------

    public function testPrepareForValidationHook(): void
    {
        $form = new class (null, $this->config) extends Form {
            protected function define(): void
            {
                $this->tel('phone')->rules('required|regex_match[/^\+256\d{9}$/]');
            }

            protected function prepareForValidation(array $data): array
            {
                if (isset($data['phone']) && is_string($data['phone'])) {
                    $data['phone'] = str_replace([' ', '-'], '', $data['phone']);
                }

                return $data;
            }
        };
        $form->setOldInput(null);

        $this->assertTrue($form->validate(['phone' => '+256 772-123 456']));
        $this->assertSame(['phone' => '+256772123456'], $form->validated());
        $this->assertSame('+256772123456', $form->getValue('phone'));
    }

    public function testGetValidationRulesFormat(): void
    {
        $form = $this->booking();
        $form->getField('email')->messages(['valid_email' => 'Check the email.']);

        $rules = $form->getValidationRules();

        $this->assertSame(
            ['label' => 'Full Name', 'rules' => ['required', 'min_length[3]', 'max_length[100]']],
            $rules['full_name'],
        );
        $this->assertSame(['valid_email' => 'Check the email.'], $rules['email']['errors']);
        $this->assertArrayNotHasKey('is_returning', $rules);
        $this->assertArrayNotHasKey('submit', $rules);
    }

    // -- Redirect round trip --------------------------------------------------

    public function testErrorsAndInputSurviveRedirectWithInput(): void
    {
        $_POST = $this->validBooking(['full_name' => 'Ja', 'email' => 'bad']);
        Services::injectMock('request', $this->request('POST', $_POST));

        $first = new BookingForm(null, $this->config);
        $this->assertFalse($first->validate());

        (new RedirectResponse(config(App::class)))->withInput();

        // Next request: a fresh form reads old input and errors from the session.
        Services::resetSingle('validation');
        $second = new BookingForm(null, $this->config);

        $this->assertSame('Ja', $second->getValue('full_name'));
        $this->assertSame('bad', $second->getValue('email'));
        $this->assertTrue($second->hasError('email'));
        $this->assertTrue($second->hasError('full_name'));
        $this->assertFalse($second->hasError('service_id'));
    }

    public function testManualErrorSurvivesRedirectWithInput(): void
    {
        $_POST = $this->validBooking();
        Services::injectMock('request', $this->request('POST', $_POST));

        $first = new BookingForm(null, $this->config);
        $this->assertTrue($first->validate());
        $first->setError('appointment_date', 'That date is fully booked.');

        (new RedirectResponse(config(App::class)))->withInput();

        Services::resetSingle('validation');
        $second = new BookingForm(null, $this->config);

        $this->assertSame('That date is fully booked.', $second->getError('appointment_date'));
    }

    public function testOldErrorsFromAnotherFormAreIgnored(): void
    {
        $session = service('session');
        $session->setFlashdata('_ci_old_input', ['get' => [], 'post' => [Form::FORM_KEY => 'newsletter', 'email' => 'x']]);
        $session->setFlashdata('_ci_validation_errors', ['email' => 'The Email field must contain a valid email address.']);

        $form = new BookingForm(null, $this->config);

        $this->assertFalse($form->hasErrors());
    }

    public function testOldErrorsAreLimitedToThisFormsFields(): void
    {
        $form = (new BookingForm(null, $this->config))
            ->setOldInput([Form::FORM_KEY => 'booking', 'email' => 'bad'])
            ->setOldErrors(['email' => 'Bad email.', 'coupon' => 'Unknown coupon.']);

        $this->assertSame(['email' => 'Bad email.'], $form->getErrors());
    }

    public function testValidatingReplacesOldErrors(): void
    {
        $form = (new BookingForm(null, $this->config))
            ->setOldInput([Form::FORM_KEY => 'booking', 'email' => 'bad'])
            ->setOldErrors(['email' => 'Bad email.']);

        $this->assertTrue($form->hasError('email'));
        $this->assertTrue($form->validate($this->validBooking()));
        $this->assertFalse($form->hasErrors());
    }
}
