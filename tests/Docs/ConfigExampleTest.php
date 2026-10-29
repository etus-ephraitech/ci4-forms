<?php

declare(strict_types=1);

namespace Ephraitech\Forms\Tests\Docs;

use App\Forms\Fields\MoneyField;
use App\Forms\QuoteForm;
use CodeIgniter\Test\CIUnitTestCase;
use Ephraitech\Forms\Form;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;

/**
 * Runs the app/Config/Forms.php example used by the custom field types and
 * customising views guides.
 *
 * That file declares Config\Forms, which every form in the process would
 * then use as its configuration, so each test runs in its own process.
 *
 * @internal
 */
final class ConfigExampleTest extends CIUnitTestCase
{
    private const EXAMPLES = __DIR__ . '/../../docs/examples/app';

    /**
     * @var list<string>
     */
    private array $cleanup = [];

    protected function setUp(): void
    {
        parent::setUp();

        require_once self::EXAMPLES . '/Config/Forms.php';
    }

    protected function tearDown(): void
    {
        foreach (array_reverse($this->cleanup) as $path) {
            is_dir($path) ? @rmdir($path) : @unlink($path);
        }

        parent::tearDown();
    }

    private function useView(string $view): void
    {
        $target = APPPATH . 'Views/' . $view;
        $dirs   = [];

        for ($dir = dirname($target); ! is_dir($dir); $dir = dirname($dir)) {
            $dirs[] = $dir;
        }

        foreach (array_reverse($dirs) as $dir) {
            mkdir($dir, 0755);
            $this->cleanup[] = $dir;
        }

        copy(self::EXAMPLES . '/Views/' . $view, $target);
        $this->cleanup[] = $target;
    }

    private function strip(string $html): string
    {
        return trim((string) preg_replace('/>\s+</', '><', (string) preg_replace('/<!-- DEBUG-VIEW (START|ENDED) [^>]*-->\n?/', '', $html)));
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testTheAppConfigIsPickedUp(): void
    {
        $form = Form::make('x');

        $this->assertInstanceOf(\Config\Forms::class, $form->getConfig());
        $this->assertTrue($form->getConfig()->showErrorSummary);
        $this->assertSame(['money' => MoneyField::class], $form->getConfig()->fieldTypes);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testCustomFieldTypesGuide(): void
    {
        $this->useView('forms/fields/money.php');

        $form = (new QuoteForm())->setOldInput(null)->setOldErrors(null);

        $this->assertInstanceOf(MoneyField::class, $form->getField('budget'));
        $this->assertSame('USD', $form->getField('deposit')->getCurrency());

        $html = $this->strip($form->field('budget'));

        $this->assertStringContainsString('<div class="input-group"><span class="input-group-text">UGX</span><input type="text" name="budget" id="quote_budget" value="" class="form-control" required inputmode="decimal" placeholder="1,500,000">', $html);

        $this->assertTrue($form->validate(['item' => 'Wedding suit', 'budget' => '1,500,000', 'deposit' => '']));
        $this->assertSame(['item' => 'Wedding suit', 'budget' => '1500000', 'deposit' => null], $form->validated());

        $this->assertSame('UGX 1,500,000', $form->present(['budget' => '1500000'])[1]['value']);

        $loaded = Form::fromSchema(['fields' => [['type' => 'money', 'name' => 'budget', 'currency' => 'EUR']]], null, \Ephraitech\Forms\Schema\SchemaPolicy::restricted()->allowTypes('money'));

        $this->assertSame('EUR', $loaded->getField('budget')->getCurrency());
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testCustomisingViewsGuideClassOverrides(): void
    {
        $form = Form::make('x');
        $form->text('name');
        $form->submit('Save');

        $html = $this->strip($form->render());

        $this->assertStringContainsString('<div class="mb-4"><label for="x_name"', $html);
        $this->assertStringContainsString('class="btn btn-success">Save</button>', $html);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testCustomisingViewsGuideCompactTheme(): void
    {
        $this->useView('forms/compact/field_input.php');

        $form = Form::make('x');
        $form->getConfig()->theme = 'compact';
        $form->text('name')->rules('required');
        $form->select('size', 'Size', ['s' => 'Small']);

        $html = $this->strip($form->render());

        $this->assertStringContainsString('<div class="mb-2"><div class="row align-items-center g-2"><label for="x_name" class="col-sm-4 col-form-label col-form-label-sm">Name <abbr title="required">*</abbr></label>', $html, 'own view for text inputs');
        $this->assertStringContainsString('class="form-control form-control-sm" required', $html);
        $this->assertStringContainsString('<select name="size" id="x_size" class="form-select">', $html, 'templates the theme lacks come from Bootstrap 5');
    }
}
