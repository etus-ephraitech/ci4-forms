<?php

declare(strict_types=1);

namespace Ephraitech\Forms\Tests\Feature;

use CodeIgniter\Config\Services;
use CodeIgniter\Test\CIUnitTestCase;
use Ephraitech\Forms\Config\Forms;
use Ephraitech\Forms\Fields\Color;
use Ephraitech\Forms\Fields\Date;
use Ephraitech\Forms\Fields\DatetimeLocal;
use Ephraitech\Forms\Fields\Search;
use Ephraitech\Forms\Fields\Time;
use Ephraitech\Forms\Form;

/**
 * time, datetime-local, color and search inputs.
 *
 * @internal
 */
final class NewInputTypesTest extends CIUnitTestCase
{
    private Forms $config;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = new Forms();
        Services::resetSingle('validation');
    }

    private function make(): Form
    {
        return Form::make('event', $this->config)->setOldInput(null)->setOldErrors(null);
    }

    private function strip(string $html): string
    {
        return (string) preg_replace('/<!-- DEBUG-VIEW (START|ENDED) [^>]*-->\n?/', '', $html);
    }

    public function testBuildersAndTypes(): void
    {
        $form = $this->make();

        $this->assertInstanceOf(Time::class, $form->time('starts'));
        $this->assertInstanceOf(DatetimeLocal::class, $form->datetimeLocal('opens_at'));
        $this->assertInstanceOf(Color::class, $form->color('accent'));
        $this->assertInstanceOf(Search::class, $form->search('q'));

        $this->assertSame('time', $form->getField('starts')->getInputType());
        $this->assertSame('datetime-local', $form->getField('opens_at')->getInputType());
        $this->assertSame('datetime_local', $form->getField('opens_at')->getType());
        $this->assertSame('color', $form->getField('accent')->getInputType());
        $this->assertSame('search', $form->getField('q')->getInputType());
    }

    public function testDateLikeInputsShareMinMaxStep(): void
    {
        $time = (new Time('starts'))->rules('required|max_length[5]')->min('08:00')->max('18:00')->step(900);

        $this->assertSame(['min' => '08:00', 'max' => '18:00', 'step' => 900], $time->getAttributes());
        $this->assertSame(['required' => true], $time->ruleAttributes($this->config), 'no length attributes on time inputs');

        $date = (new Date('d'))->min('2026-01-01');

        $this->assertSame(['min' => '2026-01-01'], $date->getAttributes(), 'Date keeps its v0.1 API');
    }

    public function testTimeValidation(): void
    {
        $form = $this->make();
        $form->time('starts', 'Start time')->rules('required|valid_date[H:i]');

        $this->assertTrue($form->validate(['starts' => '09:30']));
        $this->assertFalse($form->validate(['starts' => '25:99']));
    }

    public function testDatetimeLocalValidation(): void
    {
        $form = $this->make();
        $form->datetimeLocal('opens_at', 'Opens at')->rules('required|valid_date[Y-m-d\TH:i]');

        $this->assertTrue($form->validate(['opens_at' => '2026-11-20T09:30']));
        $this->assertSame(['opens_at' => '2026-11-20T09:30'], $form->validated());
        $this->assertFalse($form->validate(['opens_at' => '2026-11-20 09:30']));
    }

    public function testColorIsLowerCasedAndHasNoRuleAttributes(): void
    {
        $form = $this->make();
        $form->color('accent', 'Accent colour')->rules('required|regex_match[/^#[0-9a-f]{6}$/]');

        $this->assertTrue($form->validate(['accent' => '#1A73E8']));
        $this->assertSame(['accent' => '#1a73e8'], $form->validated());
        $this->assertSame([], $form->getField('accent')->ruleAttributes($this->config));
    }

    public function testSearchKeepsLengthAttributes(): void
    {
        $this->assertSame(['maxlength' => 80], (new Search('q'))->rules('max_length[80]')->ruleAttributes($this->config));
    }

    public function testMarkup(): void
    {
        $form = $this->make()->fill(['starts' => '09:30', 'opens_at' => '2026-11-20T09:30', 'accent' => '#1a73e8', 'q' => 'suits']);
        $form->time('starts', 'Start time')->min('08:00');
        $form->datetimeLocal('opens_at', 'Opens at');
        $form->color('accent', 'Accent colour');
        $form->search('q', 'Search')->placeholder('Search bookings');

        $this->assertStringContainsString('<input type="time" name="starts" id="event_starts" value="09:30" class="form-control" min="08:00">', $this->strip($form->field('starts')));
        $this->assertStringContainsString('<input type="datetime-local" name="opens_at" id="event_opens_at" value="2026-11-20T09:30" class="form-control">', $this->strip($form->field('opens_at')));
        $this->assertStringContainsString('<input type="color" name="accent" id="event_accent" value="#1a73e8" class="form-control">', $this->strip($form->field('accent')));
        $this->assertStringContainsString('<input type="search" name="q" id="event_q" value="suits" class="form-control" placeholder="Search bookings">', $this->strip($form->field('q')));
    }
}
