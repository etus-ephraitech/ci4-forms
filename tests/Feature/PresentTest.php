<?php

declare(strict_types=1);

namespace Ephraitech\Forms\Tests\Feature;

use CodeIgniter\Config\Services;
use CodeIgniter\Test\CIUnitTestCase;
use Ephraitech\Forms\Config\Forms;
use Ephraitech\Forms\Form;
use Ephraitech\Forms\Schema\SchemaCheck;
use Ephraitech\Forms\Schema\SchemaPolicy;
use Ephraitech\Forms\Tests\Support\Fields\MoneyField;
use Ephraitech\Forms\Tests\Support\Providers\ServiceOptions;

/**
 * Presenting stored data, and the forms helper.
 *
 * @internal
 */
final class PresentTest extends CIUnitTestCase
{
    private Forms $config;

    protected function setUp(): void
    {
        parent::setUp();

        Services::resetSingle('validation');
        service('autoloader')->addNamespace('FormsTestViews', __DIR__ . '/../Support/Views');

        $this->config                  = new Forms();
        $this->config->fieldTypes      = ['money' => MoneyField::class];
        $this->config->optionProviders = ['services' => [ServiceOptions::class, 'all']];
    }

    private function enquiry(): Form
    {
        $form = Form::make('enquiry', $this->config);

        $form->text('full_name', 'Full name');
        $form->select('service', 'Service')->optionsFrom('services', $this->config);
        $form->select('fabric', 'Fabric', ['Wool' => ['w1' => 'Merino'], 'Cotton' => ['c1' => 'Poplin']]);
        $form->radio('branch', 'Nearest branch', ['kampala' => 'Kampala', 'jinja' => 'Jinja']);
        $form->checkboxes('interests', 'Also interested in', ['shirts' => 'Shirts', 'kanzu' => 'Kanzu']);
        $form->select('styles', 'Styles', ['slim' => 'Slim fit', 'classic' => 'Classic fit'])->multiple();
        $form->checkbox('consent', 'Contact consent');
        $form->money('budget', 'Budget');
        $form->textarea('message', 'Message');
        $form->password('pin', 'PIN');
        $form->file('photo', 'Photo');
        $form->hidden('source', 'web');
        $form->submit('Send');

        return $form;
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(): array
    {
        return [
            'full_name' => 'Jane Namuli',
            'service'   => 'alterations',
            'fabric'    => 'c1',
            'branch'    => 'jinja',
            'interests' => ['kanzu', 'shirts'],
            'styles'    => ['classic'],
            'consent'   => '1',
            'budget'    => '1500000',
            'message'   => "First line\nSecond line",
            'pin'       => '1234',
            'source'    => 'web',
            'is_admin'  => '1',
        ];
    }

    /**
     * @return array<string, list<string>|string>
     */
    private function values(Form $form, array|object $data, bool $includeEmpty = true): array
    {
        return array_column($form->present($data, $includeEmpty), 'value', 'label');
    }

    public function testValuesBecomeReadableText(): void
    {
        $this->assertSame(
            [
                'Full name'          => 'Jane Namuli',
                'Service'            => 'Alterations',
                'Fabric'             => 'Poplin',
                'Nearest branch'     => 'Jinja',
                'Also interested in' => ['Kanzu', 'Shirts'],
                'Styles'             => ['Classic fit'],
                'Contact consent'    => 'Yes',
                'Budget'             => 'UGX 1,500,000',
                'Message'            => "First line\nSecond line",
                'Source'             => 'web',
            ],
            $this->values($this->enquiry(), $this->payload()),
        );
    }

    public function testRowShape(): void
    {
        $rows = $this->enquiry()->present($this->payload());

        $this->assertSame(
            ['name' => 'interests', 'label' => 'Also interested in', 'value' => ['Kanzu', 'Shirts'], 'text' => 'Kanzu, Shirts', 'empty' => false],
            $rows[4],
        );
    }

    public function testPasswordsButtonsUploadsAndUnknownKeysAreNeverShown(): void
    {
        $names = array_column($this->enquiry()->present($this->payload()), 'name');

        $this->assertNotContains('pin', $names);
        $this->assertNotContains('submit', $names);
        $this->assertNotContains('photo', $names);
        $this->assertNotContains('is_admin', $names);
    }

    public function testEmptyValues(): void
    {
        $form = $this->enquiry();
        $rows = array_column($form->present(['full_name' => 'Jane', 'consent' => 0, 'interests' => [], 'message' => null]), null, 'name');

        $this->assertSame('No', $rows['consent']['value'], 'an unticked checkbox is a clear No, not empty');
        $this->assertTrue($rows['interests']['empty']);
        $this->assertSame([], $rows['interests']['value']);
        $this->assertTrue($rows['message']['empty']);

        $this->assertSame(['Full name', 'Contact consent'], array_keys($this->values($form, ['full_name' => 'Jane', 'consent' => 0], false)));
    }

    public function testRemovedOptionsShowTheStoredValue(): void
    {
        $values = $this->values($this->enquiry(), ['branch' => 'gulu', 'interests' => ['kanzu', 'hats']]);

        $this->assertSame('gulu', $values['Nearest branch']);
        $this->assertSame(['Kanzu', 'hats'], $values['Also interested in']);
    }

    public function testAcceptsObjectsAndJsonPayloads(): void
    {
        $row            = new \stdClass();
        $row->full_name = 'Jane';

        $this->assertSame('Jane', $this->values($this->enquiry(), $row)['Full name']);

        $stored = json_decode((string) json_encode($this->payload()), true);

        $this->assertSame('Alterations', $this->values($this->enquiry(), $stored)['Service']);
    }

    public function testConfigTexts(): void
    {
        $this->config->presentYes = 'Ndiyo';
        $this->config->presentNo  = 'Hapana';

        $form = $this->enquiry();

        $this->assertSame('Ndiyo', $this->values($form, ['consent' => '1'])['Contact consent']);
        $this->assertSame('Hapana', $this->values($form, ['consent' => '0'])['Contact consent']);
    }

    public function testRenderedHtml(): void
    {
        $html = (string) preg_replace('/<!-- DEBUG-VIEW (START|ENDED) [^>]*-->\n?/', '', $this->enquiry()->renderPresented($this->payload() + ['full_name' => '<b>Jane</b>']));

        $this->assertStringStartsWith('<dl class="row mb-0">', trim($html));
        $this->assertStringContainsString('<dt class="col-sm-4 fw-semibold">Service</dt>', $html);
        $this->assertMatchesRegularExpression('#<dd class="col-sm-8">\s*Alterations\s*</dd>#', $html);
        $this->assertStringContainsString('<li>Kanzu</li>', $html);
        $this->assertStringContainsString('First line<br>' . "\n" . 'Second line', $html);

        $html = $this->enquiry()->renderPresented(['full_name' => '<script>x</script>', 'message' => '']);

        $this->assertStringContainsString('&lt;script&gt;x&lt;/script&gt;', $html);
        $this->assertStringContainsString('<span class="text-body-secondary">—</span>', $html);
    }

    public function testSchemaFormsPresentTheSameWay(): void
    {
        $form = Form::fromSchema(['name' => 'enquiry', 'fields' => [
            ['type' => 'select', 'name' => 'service', 'label' => 'Service', 'options_from' => 'services'],
            ['type' => 'checkbox', 'name' => 'consent', 'label' => 'Consent'],
        ]], $this->config);

        $this->assertSame(['Service' => 'Suit fitting', 'Consent' => 'Yes'], $this->values($form, ['service' => 'fitting', 'consent' => 1]));
    }

    // -- Helper ---------------------------------------------------------------

    public function testHelperLoadsAndWorks(): void
    {
        // Loading CodeIgniter's form helper alongside must not clash.
        helper(['form', 'forms']);

        $this->assertTrue(function_exists('forms_load'));
        $this->assertTrue(function_exists('form_open'));
        $this->assertTrue(function_exists('form_dropdown'));

        $json = '{"name":"contact","fields":[{"type":"text","name":"full_name"},{"type":"checkbox","name":"consent"}]}';

        $form = forms_load($json);
        $this->assertSame('contact', $form->getName());
        $this->assertSame(['full_name', 'consent'], array_keys(forms_load(json_decode($json, true))->getFields()));

        $check = forms_check('{"fields":[{"type":"text","name":"a","attributes":{"onclick":"x()"}}]}');
        $this->assertInstanceOf(SchemaCheck::class, $check);
        $this->assertFalse($check->isValid(), 'restricted by default');
        $this->assertTrue(forms_check('{"fields":[{"type":"password","name":"a"}]}', SchemaPolicy::trusted())->isValid());

        $html = forms_present($form, ['full_name' => 'Jane', 'consent' => 1]);
        $this->assertStringContainsString('Jane', $html);
        $this->assertStringContainsString('Yes', $html);
    }
}
