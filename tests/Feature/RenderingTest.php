<?php

declare(strict_types=1);

namespace Ephraitech\Forms\Tests\Feature;

use CodeIgniter\Config\Services;
use CodeIgniter\Test\CIUnitTestCase;
use Ephraitech\Forms\Config\Forms;
use Ephraitech\Forms\Exceptions\FormException;
use Ephraitech\Forms\Form;
use Ephraitech\Forms\Renderer;
use Ephraitech\Forms\Tests\Support\BookingForm;

/**
 * Rendering through CodeIgniter's real view renderer and the Bootstrap 5
 * theme.
 *
 * @internal
 */
final class RenderingTest extends CIUnitTestCase
{
    private Forms $config;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = new Forms();
        Services::resetSingle('validation');
    }

    private function booking(): BookingForm
    {
        return (new BookingForm(null, $this->config))->setOldInput(null)->setOldErrors(null);
    }

    private function make(string $name = 'test'): Form
    {
        return Form::make($name, $this->config)->setOldInput(null)->setOldErrors(null);
    }

    // -- Helpers ----------------------------------------------------------------

    public function testEscapeAndAttributes(): void
    {
        $this->assertSame('&lt;b&gt; &quot;x&quot; &#039;y&#039; &amp;', Renderer::escape('<b> "x" \'y\' &'));
        $this->assertSame('', Renderer::escape(null));
        $this->assertSame('1', Renderer::escape(true));
        $this->assertSame(
            ' required data-x="a&quot;b" maxlength="5"',
            Renderer::attributes(['required' => true, 'hidden' => false, 'data-x' => 'a"b', 'title' => null, 'maxlength' => 5]),
        );
    }

    // -- Opening and closing ------------------------------------------------------

    public function testOpenTagWithHiddenFields(): void
    {
        $html = $this->booking()->open();

        $this->assertStringContainsString('<form action="' . site_url('bookings') . '" method="post" id="booking" novalidate>', $html);
        $this->assertStringContainsString('<input type="hidden" name="_form" value="booking">', $html);
        $this->assertStringContainsString('name="' . csrf_token() . '"', $html);
        $this->assertStringNotContainsString('_method', $html);
        $this->assertSame("</form>\n", $this->booking()->close());
    }

    public function testAbsoluteAndSpecialActionsAreKept(): void
    {
        $this->assertStringContainsString('action="https://pay.example.com/checkout"', $this->make()->action('https://pay.example.com/checkout')->open());
        $this->assertStringContainsString('action="#"', $this->make()->action('#')->open());
        $this->assertStringContainsString('action="?step=2"', $this->make()->action('?step=2')->open());
        $this->assertStringNotContainsString('action=', $this->make()->open());
    }

    public function testMethodSpoofingAndGetForms(): void
    {
        $patch = $this->make()->method('patch')->open();

        $this->assertStringContainsString('method="post"', $patch);
        $this->assertStringContainsString('<input type="hidden" name="_method" value="PATCH">', $patch);

        $get = $this->make()->method('get')->open();

        $this->assertStringContainsString('method="get"', $get);
        $this->assertStringNotContainsString(csrf_token(), $get);
    }

    public function testOpenRespectsConfigAndFormSettings(): void
    {
        $this->config->novalidate = false;
        $this->config->csrf       = false;
        $this->config->themes['bootstrap5']['classes']['form'] = 'needs-validation';

        $html = $this->make('upload')
            ->multipart()
            ->addClass('card p-3')
            ->attribute('data-turbo', 'false')
            ->open(['data-step' => 2]);

        $this->assertStringContainsString('class="needs-validation card p-3"', $html);
        $this->assertStringContainsString('enctype="multipart/form-data"', $html);
        $this->assertStringContainsString('data-turbo="false"', $html);
        $this->assertStringContainsString('data-step="2"', $html);
        $this->assertStringNotContainsString('novalidate', $html);
        $this->assertStringNotContainsString(csrf_token(), $html);
    }

    // -- Inputs -------------------------------------------------------------------

    public function testInputFieldMarkup(): void
    {
        $html = $this->booking()->field('full_name');

        $this->assertStringContainsString('<div class="mb-3">', $html);
        $this->assertStringContainsString('<label for="booking_full_name" class="form-label">Full Name <span class="text-danger" aria-hidden="true">*</span></label>', $html);
        $this->assertStringContainsString('<input type="text" name="full_name" id="booking_full_name" value="" class="form-control" required maxlength="100" minlength="3">', $html);
    }

    public function testValuesAreEscaped(): void
    {
        $form = $this->booking()->fill(['full_name' => '"><script>alert(1)</script>', 'notes' => '</textarea><script>x</script>']);

        $this->assertStringContainsString('value="&quot;&gt;&lt;script&gt;alert(1)&lt;/script&gt;"', $form->field('full_name'));
        $this->assertStringNotContainsString('<script>', $form->field('full_name'));
        $this->assertStringContainsString('&lt;/textarea&gt;&lt;script&gt;x&lt;/script&gt;</textarea>', $form->field('notes'));
    }

    public function testLabelsAndHelpAreEscaped(): void
    {
        $form = $this->make();
        $form->text('name', 'Name <em>')->help('Use <b>full</b> name');

        $html = $form->field('name');

        $this->assertStringContainsString('Name &lt;em&gt;', $html);
        $this->assertStringContainsString('Use &lt;b&gt;full&lt;/b&gt; name', $html);
    }

    public function testErrorStateMarkup(): void
    {
        $form = $this->booking();
        $form->getField('email')->help('We send the confirmation here.');
        $form->validate(['email' => 'not-an-email']);

        $html = $form->field('email');

        $this->assertStringContainsString('class="form-control is-invalid"', $html);
        $this->assertStringContainsString('aria-invalid="true"', $html);
        $this->assertStringContainsString('aria-describedby="booking_email_error booking_email_help"', $html);
        $this->assertStringContainsString('<div id="booking_email_error" class="invalid-feedback">The Email Address field must contain a valid email address.</div>', $html);
        $this->assertStringContainsString('<div id="booking_email_help" class="form-text">We send the confirmation here.</div>', $html);
        $this->assertStringContainsString('value="not-an-email"', $html);
    }

    public function testPasswordIsNeverRendered(): void
    {
        $form = $this->booking()->fill(['pin' => '1234']);
        $form->validate(['pin' => '9876']);

        $this->assertStringContainsString('type="password" name="pin" id="booking_pin" value=""', $form->field('pin'));
    }

    public function testPlaceholderAttributesAndDisabled(): void
    {
        $form = $this->make();
        $form->email('email')->placeholder('you@example.com')->addClass('form-control-lg')->autofocus()->disabled();

        $html = $form->field('email');

        $this->assertStringContainsString('class="form-control form-control-lg"', $html);
        $this->assertStringContainsString('autofocus placeholder="you@example.com" disabled', $html);
    }

    public function testRuleAttributesCanBeTurnedOff(): void
    {
        $this->config->ruleAttributes = false;

        $html = $this->booking()->field('full_name');

        $this->assertStringNotContainsString('required', $html);
        $this->assertStringNotContainsString('maxlength', $html);
    }

    public function testIdsWithoutFormPrefix(): void
    {
        $this->config->prefixIdsWithFormName = false;

        $this->assertStringContainsString('id="email"', $this->booking()->field('email'));
    }

    public function testHiddenFieldMarkup(): void
    {
        $form = $this->make();
        $form->hidden('booking_id', 42);

        $this->assertSame('<input type="hidden" name="booking_id" value="42">', trim($this->stripDebug($form->field('booking_id'))));
    }

    public function testTextareaMarkup(): void
    {
        $form = $this->make();
        $form->textarea('notes')->rows(4)->rules('max_length[500]')->placeholder('Anything else?');

        $this->assertStringContainsString(
            '<textarea name="notes" id="test_notes" class="form-control" maxlength="500" rows="4" placeholder="Anything else?"></textarea>',
            $form->field('notes'),
        );
    }

    // -- Choice fields ------------------------------------------------------------

    public function testSelectMarkupWithPlaceholderAndSelection(): void
    {
        $form = $this->booking()->fill(['service_id' => 2]);
        $form->getField('service_id')->placeholder('Choose a service');

        $html = $form->field('service_id');

        $this->assertStringContainsString('<select name="service_id" id="booking_service_id" class="form-select" required>', $html);
        $this->assertStringContainsString('<option value="">Choose a service</option>', $html);
        $this->assertStringContainsString('<option value="1">Suit fitting</option>', $html);
        $this->assertStringContainsString('<option value="2" selected>Alterations</option>', $html);
    }

    public function testSelectPlaceholderSelectedWhenEmpty(): void
    {
        $form = $this->booking();
        $form->getField('service_id')->placeholder('Choose');

        $this->assertStringContainsString('<option value="" selected>Choose</option>', $form->field('service_id'));
    }

    public function testSelectOptionGroups(): void
    {
        $form = $this->make()->fill(['style' => '3']);
        $form->select('style', 'Style', [
            'Suits'  => ['1' => 'Two-piece', '2' => 'Three-piece'],
            'Shirts' => ['3' => 'Formal & casual'],
        ]);

        $html = $form->field('style');

        $this->assertStringContainsString('<optgroup label="Suits">', $html);
        $this->assertStringContainsString('<option value="3" selected>Formal &amp; casual</option>', $html);
    }

    public function testCheckboxMarkup(): void
    {
        $form = $this->booking()->fill(['is_returning' => 1]);

        $html = $form->field('is_returning');

        $this->assertStringContainsString('<div class="form-check">', $html);
        $this->assertStringContainsString('<input type="checkbox" name="is_returning" id="booking_is_returning" value="1" checked class="form-check-input">', $html);
        $this->assertStringContainsString('<label for="booking_is_returning" class="form-check-label">I have booked before</label>', $html);
    }

    public function testUntickedCheckboxAfterSubmission(): void
    {
        $form = $this->booking()->fill(['is_returning' => 1]);
        $form->validate(['full_name' => 'Jane']);

        $this->assertStringNotContainsString(' checked', $form->field('is_returning'));
    }

    public function testRequiredCheckboxErrorIsInsideWrapper(): void
    {
        $form = $this->make('signup');
        $form->checkbox('terms', 'I accept the terms')->rules('required')->inline();
        $form->validate([]);

        $html = $form->field('terms');

        $this->assertStringContainsString('<div class="form-check form-check-inline">', $html);
        $this->assertStringContainsString('class="form-check-input is-invalid" required', $html);
        $this->assertMatchesRegularExpression('#form-check-label">I accept the terms <span[^>]*>\*</span></label>\s*<div id="signup_terms_error" class="invalid-feedback">#', $html);
    }

    public function testRadioMarkup(): void
    {
        $form = $this->make('booking')->fill(['payment' => 'momo']);
        $form->radio('payment', 'Payment method', ['cash' => 'Cash', 'momo' => 'Mobile money'])->rules('required')->inline();

        $html = $form->field('payment');

        $this->assertStringContainsString('<fieldset class="mb-3">', $html);
        $this->assertStringContainsString('<legend class="col-form-label pt-0">Payment method <span', $html);
        $this->assertStringContainsString('<div class="form-check form-check-inline">', $html);
        $this->assertStringContainsString('<input type="radio" name="payment" id="booking_payment_cash" value="cash" class="form-check-input" required>', $html);
        $this->assertStringContainsString('<input type="radio" name="payment" id="booking_payment_momo" value="momo" checked class="form-check-input" required>', $html);
        $this->assertStringContainsString('<label for="booking_payment_momo" class="form-check-label">Mobile money</label>', $html);
    }

    public function testRadioErrorUsesBlockFeedback(): void
    {
        $form = $this->make('booking');
        $form->radio('payment', 'Payment', ['cash' => 'Cash'])->rules('required');
        $form->validate([]);

        $this->assertStringContainsString('class="invalid-feedback d-block"', $form->field('payment'));
    }

    public function testSubmitMarkup(): void
    {
        $this->config->themes['bootstrap5']['classes']['submit'] = 'btn btn-success';

        $this->assertStringContainsString(
            '<button type="submit" name="submit" id="booking_submit" value="1" class="btn btn-success">Book appointment</button>',
            $this->booking()->field('submit'),
        );
    }

    // -- Custom layouts ---------------------------------------------------------

    public function testControlRendersOnlyTheInput(): void
    {
        $form = $this->booking();
        $form->getField('email')->help('Help text');
        $form->validate(['email' => 'bad']);

        $html = $this->stripDebug($form->control('email'));

        $this->assertStringContainsString('<input type="email" name="email" id="booking_email" value="bad" class="form-control is-invalid" required aria-invalid="true">', $html);
        $this->assertStringNotContainsString('<label', $html);
        $this->assertStringNotContainsString('invalid-feedback', $html);
        $this->assertStringNotContainsString('Help text', $html);
        $this->assertStringNotContainsString('mb-3', $html);
    }

    public function testFieldsRendersSelectedFieldsInOrder(): void
    {
        $html = $this->booking()->fields('email', 'full_name');

        $this->assertLessThan(strpos($html, 'name="full_name"'), strpos($html, 'name="email"'));
        $this->assertStringNotContainsString('name="notes"', $html);
    }

    public function testUnknownFieldThrows(): void
    {
        $this->expectException(FormException::class);

        $this->booking()->field('missing');
    }

    // -- Error summary and whole form -------------------------------------------

    public function testErrorSummary(): void
    {
        $form = $this->booking();

        $this->assertSame('', $form->errorSummary());

        $form->validate(['email' => 'bad']);
        $form->setError('general', 'Bookings are closed today.');

        $html = $form->errorSummary();

        $this->assertStringContainsString('<div class="alert alert-danger" role="alert">', $html);
        $this->assertStringContainsString('<a href="#booking_email" class="alert-link">The Email Address field must contain a valid email address.</a>', $html);
        $this->assertStringContainsString('<li>Bookings are closed today.</li>', $html);
    }

    public function testWholeFormRendering(): void
    {
        $form = $this->booking();
        $html = (string) $form;

        $this->assertSame($this->stripDebug($html), $this->stripDebug($form->render()));
        $this->assertStringStartsWith('<form', ltrim($this->stripDebug($html)));
        $this->assertStringEndsWith('</form>', rtrim($this->stripDebug($html)));

        foreach (array_keys($form->getFields()) as $name) {
            $this->assertStringContainsString('name="' . $name . '"', $html);
        }

        $this->assertStringNotContainsString('alert-danger', $html);
    }

    public function testWholeFormShowsSummaryWhenEnabled(): void
    {
        $this->config->showErrorSummary = true;

        $form = $this->booking();
        $form->validate(['email' => 'bad']);

        $this->assertStringContainsString('alert alert-danger', $form->render());
    }

    public function testCustomThemeViewsAreUsed(): void
    {
        // Theme views can live in any namespace a module registers.
        service('autoloader')->addNamespace('FormsTestViews', dirname(__DIR__) . '/Support/Views');

        $this->config->themes['minimal'] = [
            'views'   => 'FormsTestViews\minimal',
            'classes' => ['input' => 'input'],
        ];
        $this->config->theme = 'minimal';

        $form = $this->make();
        $form->text('name');

        $this->assertSame('<input class="input" name="name">', trim($this->stripDebug($form->field('name'))));
    }

    public function testMissingThemeTemplatesFallBackToTheBundledCopy(): void
    {
        // Simulates views published before an upgrade that added new
        // templates: only field_input exists in the theme's folder.
        service('autoloader')->addNamespace('FormsTestViews', dirname(__DIR__) . '/Support/Views');
        $this->config->themes['bootstrap5']['views'] = 'FormsTestViews\\partial';

        $form = $this->make();
        $form->text('name');
        $form->checkboxes('tags', 'Tags', ['a' => 'A']);

        $this->assertStringContainsString('data-theme="partial"', $form->field('name'), 'published template is used');
        $this->assertStringContainsString('<fieldset class="mb-3">', $form->field('tags'), 'missing template comes from the package');
    }

    public function testThemeExtendingAnotherThemeUsesItsTemplates(): void
    {
        service('autoloader')->addNamespace('FormsTestViews', dirname(__DIR__) . '/Support/Views');
        $this->config->themes['custom'] = ['views' => 'FormsTestViews\\partial', 'extends' => 'bootstrap5', 'classes' => $this->config->themes['bootstrap5']['classes']];
        $this->config->theme            = 'custom';

        $form = $this->make();
        $form->text('name');
        $form->checkboxes('tags', 'Tags', ['a' => 'A']);

        $this->assertStringContainsString('data-theme="partial"', $form->field('name'), 'its own template');
        $this->assertStringContainsString('<fieldset class="mb-3">', $form->field('tags'), 'missing template from the theme it extends');
    }

    public function testThemeExtendsChainAndLoops(): void
    {
        service('autoloader')->addNamespace('FormsTestViews', dirname(__DIR__) . '/Support/Views');
        $this->config->themes['middle'] = ['views' => 'FormsTestViews\\minimal', 'extends' => 'bootstrap5'];
        $this->config->themes['top']    = ['views' => 'FormsTestViews\\partial', 'extends' => 'middle'];
        $this->config->theme            = 'top';

        $form = $this->make();
        $form->checkboxes('tags', 'Tags', ['a' => 'A']);

        $this->assertStringContainsString('<fieldset', $form->field('tags'), 'found two levels up');

        $this->config->themes['middle']['extends'] = 'top';
        $form                                      = $this->make();
        $form->checkboxes('tags', 'Tags', ['a' => 'A']);

        $this->expectException(\CodeIgniter\View\Exceptions\ViewException::class);
        $form->field('tags');
    }

    public function testExtendingAnUnknownThemeIsRejected(): void
    {
        $this->config->themes['custom'] = ['views' => 'forms/custom', 'extends' => 'missing'];

        $this->expectException(FormException::class);
        $this->expectExceptionMessage('"extends" must name another registered theme.');

        $this->config->themeDefinition('custom');
    }

    public function testMissingTemplateOfUnknownThemeStillFails(): void
    {
        service('autoloader')->addNamespace('FormsTestViews', dirname(__DIR__) . '/Support/Views');
        $this->config->themes['custom'] = ['views' => 'FormsTestViews\\partial'];
        $this->config->theme            = 'custom';

        $form = $this->make();
        $form->checkboxes('tags', 'Tags', ['a' => 'A']);

        $this->expectException(\CodeIgniter\View\Exceptions\ViewException::class);

        $form->field('tags');
    }

    /**
     * Removes the view debug comments CodeIgniter adds outside production.
     */
    private function stripDebug(string $html): string
    {
        return (string) preg_replace('/<!-- DEBUG-VIEW (START|ENDED) [^>]*-->\n?/', '', $html);
    }
}
