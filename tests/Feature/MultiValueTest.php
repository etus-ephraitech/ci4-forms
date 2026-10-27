<?php

declare(strict_types=1);

namespace Ephraitech\Forms\Tests\Feature;

use CodeIgniter\Config\Services;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\Test\CIUnitTestCase;
use Config\App;
use Ephraitech\Forms\Config\Forms;
use Ephraitech\Forms\Form;

/**
 * Multi-selects and checkbox groups through real validation, requests,
 * redirects and rendering.
 *
 * @internal
 */
final class MultiValueTest extends CIUnitTestCase
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

        parent::tearDown();
    }

    private function form(): Form
    {
        $form = Form::make('profile', $this->config)->setOldInput(null)->setOldErrors(null);

        $form->text('name')->rules('required');
        $form->checkboxes('interests', 'Interests', ['suits' => 'Suits', 'shirts' => 'Shirts', 'shoes' => 'Shoes'])
            ->rules('required')
            ->eachRules('in_list[suits,shirts,shoes]')
            ->messages(['required' => 'Pick at least one interest.', 'in_list' => 'Choose interests from the list.']);
        $form->select('languages', 'Languages', ['en' => 'English', 'lg' => 'Luganda', 'sw' => 'Swahili'])
            ->multiple()
            ->eachRules('in_list[en,lg,sw]');
        $form->text('nickname');

        return $form;
    }

    private function request(array $post): IncomingRequest
    {
        /** @var IncomingRequest $request */
        $request = Services::incomingrequest(config(App::class), false);
        $request->setMethod('POST');
        $request->setGlobal('post', $post);

        return $request;
    }

    /**
     * Removes the view debug comments CodeIgniter adds outside production.
     */
    private function strip(string $html): string
    {
        return (string) preg_replace('/<!-- DEBUG-VIEW (START|ENDED) [^>]*-->\n?/', '', $html);
    }

    // -- Validation -----------------------------------------------------------

    public function testValidListsPass(): void
    {
        $form = $this->form();

        $this->assertTrue($form->validate(['name' => 'Jane', 'interests' => ['suits', 'shoes'], 'languages' => ['lg']]));
        $this->assertSame(
            ['name' => 'Jane', 'interests' => ['suits', 'shoes'], 'languages' => ['lg'], 'nickname' => null],
            $form->validated(),
        );
    }

    public function testRequiredListNeedsAtLeastOneItem(): void
    {
        $form = $this->form();

        $this->assertFalse($form->validate(['name' => 'Jane', 'interests' => []]));
        $this->assertSame('Pick at least one interest.', $form->getError('interests'));

        $form = $this->form();

        $this->assertFalse($form->validate(['name' => 'Jane']));
        $this->assertSame('Pick at least one interest.', $form->getError('interests'));
    }

    public function testItemErrorsAreReportedOnTheField(): void
    {
        $form = $this->form();

        $this->assertFalse($form->validate([
            'name'      => 'Jane',
            'interests' => ['suits', 'hats', 'gloves'],
            'languages' => ['en', 'fr'],
        ]));

        $errors = $form->getErrors();

        $this->assertSame(['interests', 'languages'], array_keys($errors));
        $this->assertSame('Choose interests from the list.', $errors['interests']);
        $this->assertStringContainsString('Languages', $errors['languages']);
    }

    public function testOptionalEmptyListPassesAndReturnsEmptyArray(): void
    {
        $form = $this->form();

        $this->assertTrue($form->validate(['name' => 'Jane', 'interests' => ['suits']]));
        $this->assertSame([], $form->validated()['languages']);
    }

    public function testScalarSentToListFieldIsWrapped(): void
    {
        $form = $this->form();

        $this->assertTrue($form->validate(['name' => 'Jane', 'interests' => 'shirts']));
        $this->assertSame(['shirts'], $form->validated()['interests']);
    }

    public function testInvalidScalarSentToListFieldStillFailsItemRules(): void
    {
        $form = $this->form();

        $this->assertFalse($form->validate(['name' => 'Jane', 'interests' => 'hats']));
        $this->assertTrue($form->hasError('interests'));
    }

    public function testArraySentToSingleFieldNeverReachesValidatedData(): void
    {
        $form = $this->form();

        $this->assertTrue($form->validate([
            'name'      => 'Jane',
            'interests' => ['suits'],
            'nickname'  => ['injected', 'array'],
        ]));
        $this->assertNull($form->validated()['nickname']);
    }

    public function testArraySentToRequiredSingleFieldFails(): void
    {
        $form = $this->form();

        $this->assertFalse($form->validate(['name' => ['Jane'], 'interests' => ['suits']]));
        $this->assertTrue($form->hasError('name'));
    }

    public function testValidationRulesIncludeWildcardEntries(): void
    {
        $rules = $this->form()->getValidationRules();

        $this->assertSame(['required'], $rules['interests']['rules']);
        $this->assertSame(['permit_empty', 'in_list[suits,shirts,shoes]'], $rules['interests.*']['rules']);
        $this->assertSame('Interests', $rules['interests.*']['label']);
        $this->assertArrayNotHasKey('languages', $rules);
        $this->assertArrayHasKey('languages.*', $rules);
    }

    public function testExplicitPermitEmptyIsNotDuplicated(): void
    {
        $form = Form::make('x', $this->config);
        $form->checkboxes('tags', 'Tags', ['a' => 'A'])->eachRules('permit_empty|in_list[a]');

        $this->assertSame(['permit_empty', 'in_list[a]'], $form->getValidationRules()['tags.*']['rules']);
    }

    public function testOldItemErrorsAreFoldedOntoTheField(): void
    {
        $form = $this->form()
            ->setOldInput(['_form' => 'profile', 'interests' => ['hats']])
            ->setOldErrors(['interests.0' => 'Choose interests from the list.', 'interests.1' => 'Second error.']);

        $this->assertSame(['interests' => 'Choose interests from the list.'], $form->getErrors());
    }

    public function testPostedArraysFromRequest(): void
    {
        $form = $this->form();

        $this->assertTrue($form->validate($this->request(['name' => 'Jane', 'interests' => ['suits', 'shirts']])));
        $this->assertSame(['suits', 'shirts'], $form->validated()['interests']);
    }

    // -- Rendering ------------------------------------------------------------

    public function testCheckboxGroupMarkup(): void
    {
        $form = $this->form()->fill(['interests' => ['shirts', 'shoes']]);

        $html = $this->strip($form->field('interests'));

        $this->assertStringContainsString('<fieldset class="mb-3">', $html);
        $this->assertStringContainsString('<legend class="col-form-label pt-0">Interests <span', $html);
        $this->assertStringContainsString('<input type="checkbox" name="interests[]" id="profile_interests_suits" value="suits" class="form-check-input">', $html);
        $this->assertStringContainsString('<input type="checkbox" name="interests[]" id="profile_interests_shirts" value="shirts" checked class="form-check-input">', $html);
        $this->assertStringContainsString('value="shoes" checked', $html);
        $this->assertStringContainsString('<label for="profile_interests_suits" class="form-check-label">Suits</label>', $html);
        $this->assertStringNotContainsString('required', str_replace('aria-hidden', '', $html));
    }

    public function testCheckboxGroupErrorUsesBlockFeedback(): void
    {
        $form = $this->form();
        $form->validate(['name' => 'Jane']);

        $html = $form->field('interests');

        $this->assertStringContainsString('<div id="profile_interests_error" class="invalid-feedback d-block">Pick at least one interest.</div>', $html);
        $this->assertStringContainsString('class="form-check-input is-invalid"', $html);
    }

    public function testInlineCheckboxGroup(): void
    {
        $form = $this->form();
        $form->getField('interests')->inline();

        $this->assertStringContainsString('<div class="form-check form-check-inline">', $form->field('interests'));
    }

    public function testMultiSelectMarkup(): void
    {
        $form = $this->form()->fill(['languages' => ['en', 'sw']]);
        $form->getField('languages')->placeholder('Ignored on multiple selects')->size(3);

        $html = $this->strip($form->field('languages'));

        $this->assertStringContainsString('<select name="languages[]" id="profile_languages" class="form-select" size="3" multiple>', $html);
        $this->assertStringContainsString('<option value="en" selected>English</option>', $html);
        $this->assertStringContainsString('<option value="lg">Luganda</option>', $html);
        $this->assertStringContainsString('<option value="sw" selected>Swahili</option>', $html);
        $this->assertStringNotContainsString('Ignored on multiple selects', $html);
    }

    public function testFilledSingleValueForListFieldIsShownAsSelected(): void
    {
        $form = $this->form()->fill(['interests' => 'suits']);

        $this->assertStringContainsString('value="suits" checked', $form->field('interests'));
    }

    public function testFailedSubmissionKeepsTickedItemsAndDropsFilledOnes(): void
    {
        $form = $this->form()->fill(['interests' => ['suits', 'shirts']]);
        $form->validate(['name' => '', 'interests' => ['shoes']]);

        $html = $form->field('interests');

        $this->assertStringContainsString('value="shoes" checked', $html);
        $this->assertStringNotContainsString('value="suits" checked', $html);
        $this->assertStringNotContainsString('value="shirts" checked', $html);
    }

    public function testUntickingEverythingAfterFillRendersNothingTicked(): void
    {
        $form = $this->form()->fill(['interests' => ['suits']]);
        $form->validate(['name' => '']);

        $this->assertStringNotContainsString(' checked', $form->field('interests'));
    }

    public function testSingleSelectStillWorks(): void
    {
        $form = Form::make('x', $this->config)->setOldInput(null)->fill(['service' => 'b']);
        $form->select('service', 'Service', ['a' => 'A', 'b' => 'B'])->placeholder('Choose');

        $html = $this->strip($form->field('service'));

        $this->assertStringContainsString('<select name="service" id="x_service" class="form-select">', $html);
        $this->assertStringContainsString('<option value="">Choose</option>', $html);
        $this->assertStringContainsString('<option value="b" selected>B</option>', $html);
    }

    // -- Redirect round trip --------------------------------------------------

    public function testListsAndItemErrorsSurviveRedirectWithInput(): void
    {
        $_POST = ['_form' => 'profile', 'name' => 'Jane', 'interests' => ['suits', 'hats'], 'languages' => ['lg']];
        Services::injectMock('request', $this->request($_POST));

        $first = Form::make('profile', $this->config);
        $first->text('name')->rules('required');
        $first->checkboxes('interests', 'Interests', ['suits' => 'Suits', 'shirts' => 'Shirts'])
            ->eachRules('in_list[suits,shirts]');
        $first->select('languages', 'Languages', ['en' => 'English', 'lg' => 'Luganda'])->multiple();

        $this->assertFalse($first->validate());

        (new RedirectResponse(config(App::class)))->withInput();
        Services::resetSingle('validation');

        $second = Form::make('profile', $this->config);
        $second->text('name')->rules('required');
        $second->checkboxes('interests', 'Interests', ['suits' => 'Suits', 'shirts' => 'Shirts']);
        $second->select('languages', 'Languages', ['en' => 'English', 'lg' => 'Luganda'])->multiple();

        $this->assertTrue($second->hasError('interests'), 'item errors keyed interests.1 must surface on interests');
        $this->assertStringContainsString('value="suits" checked', $second->field('interests'));
        $this->assertStringContainsString('<option value="lg" selected>Luganda</option>', $second->field('languages'));
    }
}
