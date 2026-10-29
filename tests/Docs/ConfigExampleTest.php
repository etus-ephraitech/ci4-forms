<?php

declare(strict_types=1);

namespace Ephraitech\Forms\Tests\Docs;

use App\Forms\Fields\MoneyField;
use App\Forms\QuoteForm;
use App\Forms\ServiceRequestForm;
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
        $this->assertArrayHasKey('services', $form->getConfig()->optionProviders);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testOptionProvidersGuide(): void
    {
        // Migration files start with a timestamp, so they are loaded by path.
        require_once self::EXAMPLES . '/Database/Migrations/2026-10-29-000300_CreateServices.php';

        $db = db_connect('tests');
        (new \App\Database\Migrations\CreateServices(\Config\Database::forge('tests')))->up();

        try {
            $db->table('services')->insertBatch([
                ['name' => 'Suit fitting', 'active' => 1],
                ['name' => 'Alterations', 'active' => 1],
                ['name' => 'Repairs (paused)', 'active' => 0],
            ]);

            $form = (new ServiceRequestForm())->setOldInput(null)->setOldErrors(null);

            $this->assertSame('services', $form->getField('service_id')->getOptionProvider());
            $this->assertSame(['2' => 'Alterations', '1' => 'Suit fitting'], $form->getField('service_id')->getOptions(), 'active services, by name');

            $html = $form->field('service_id');
            $this->assertStringContainsString('<option value="2">Alterations</option>', $html);
            $this->assertStringNotContainsString('Repairs', $html);

            $this->assertTrue($form->validate(['full_name' => 'Jane', 'service_id' => '1']));
            $this->assertFalse((new ServiceRequestForm())->validate(['full_name' => 'Jane', 'service_id' => '3']), 'an inactive service is not an option');

            $schemaForm = Form::fromSchema(['fields' => [['type' => 'select', 'name' => 'service_id', 'options_from' => 'services']]]);
            $this->assertSame(['2', '1'], $schemaForm->getField('service_id')->getOptionValues());
        } finally {
            (new \App\Database\Migrations\CreateServices(\Config\Database::forge('tests')))->down();
        }
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testCustomFieldTypesGuide(): void
    {
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
