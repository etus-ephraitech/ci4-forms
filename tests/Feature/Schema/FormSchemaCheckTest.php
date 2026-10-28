<?php

declare(strict_types=1);

namespace Ephraitech\Forms\Tests\Feature\Schema;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\StreamFilterTrait;
use Ephraitech\Forms\Config\Forms;
use Ephraitech\Forms\Form;
use Ephraitech\Forms\Schema\FormSchema;
use Ephraitech\Forms\Schema\SchemaCheck;
use Ephraitech\Forms\Schema\SchemaPolicy;
use Ephraitech\Forms\Tests\Support\Providers\ServiceOptions;

/**
 * FormSchema::check() for admin screens, and the forms:schema command.
 *
 * @internal
 */
final class FormSchemaCheckTest extends CIUnitTestCase
{
    use StreamFilterTrait;

    private Forms $config;

    /**
     * @var list<string>
     */
    private array $temp = [];

    protected function setUp(): void
    {
        parent::setUp();

        ServiceOptions::$calls         = 0;
        $this->config                  = new Forms();
        $this->config->optionProviders = ['services' => [ServiceOptions::class, 'all']];
    }

    protected function tearDown(): void
    {
        foreach ($this->temp as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        parent::tearDown();
    }

    private function tempFile(string $contents): string
    {
        $path = tempnam(sys_get_temp_dir(), 'schema') . '.json';
        file_put_contents($path, $contents);

        return $this->temp[] = $path;
    }

    // -- FormSchema::check() --------------------------------------------------

    public function testValidSchemaFromJson(): void
    {
        $check = FormSchema::check('{"name":"enquiry","fields":[{"type":"select","name":"service","options_from":"services"}]}', null, $this->config);

        $this->assertInstanceOf(SchemaCheck::class, $check);
        $this->assertTrue($check->isValid());
        $this->assertSame([], $check->getMessages());
        $this->assertSame('enquiry', $check->getSchema()['name']);
        $this->assertSame(['valid' => true, 'errors' => []], $check->toArray());
        $this->assertSame(0, ServiceOptions::$calls, 'checking never calls providers');
    }

    public function testInvalidJsonIsAResultNotAnException(): void
    {
        $check = FormSchema::check('{"fields": [', null, $this->config);

        $this->assertFalse($check->isValid());
        $this->assertNull($check->getSchema());
        $this->assertSame(['The schema is not valid JSON (Syntax error).'], $check->getMessages());
    }

    public function testCheckIsRestrictedByDefault(): void
    {
        $schema = ['fields' => [['type' => 'text', 'name' => 'a', 'attributes' => ['onclick' => 'x()']]]];

        $this->assertFalse(FormSchema::check($schema, null, $this->config)->isValid());
        $this->assertTrue(FormSchema::check($schema, SchemaPolicy::trusted(), $this->config)->isValid());
    }

    public function testErrorsForOneField(): void
    {
        $check = FormSchema::check(['fields' => [
            ['type' => 'text', 'name' => 'a', 'width' => 20],
            ['type' => 'row', 'children' => [['type' => 'email', 'name' => 'b', 'lable' => 'B'], ['type' => 'text']]],
        ]], null, $this->config);

        $this->assertCount(3, $check->getErrors());
        $this->assertSame(['fields[0].width'], array_column($check->getErrorsFor('fields[0]'), 'path'));
        $this->assertSame(['fields[1].children[0].lable', 'fields[1].children[1].name'], array_column($check->getErrorsFor('fields[1]'), 'path'));
        $this->assertSame(['fields[1].children[1].name'], array_column($check->getErrorsFor('fields[1].children[1]'), 'path'));
        $this->assertSame([], $check->getErrorsFor('fields[2]'));
        $this->assertSame([], $check->getErrorsFor('fields[1'), 'prefixes match whole segments only');
    }

    public function testLoadAndExportShortcuts(): void
    {
        $form = FormSchema::load('{"name":"x","fields":[{"type":"text","name":"a"}]}', null, $this->config);

        $this->assertInstanceOf(Form::class, $form);
        $this->assertSame($form->toSchema(), FormSchema::export($form));
    }

    // -- forms:schema command -------------------------------------------------

    public function testCommandChecksAValidFile(): void
    {
        $file = $this->tempFile('{"fields":[{"type":"text","name":"full_name","rules":"required"}]}');

        command('forms:schema check ' . $file);

        $this->assertStringContainsString('is a valid schema (restricted policy).', $this->getStreamFilterBuffer());
    }

    public function testCommandListsEveryError(): void
    {
        $file = $this->tempFile('{"fields":[{"type":"txt","name":"a"},{"type":"text","name":"b","rules":"is_unique[users.email]"}]}');

        command('forms:schema check ' . $file);

        $output = $this->getStreamFilterBuffer();

        $this->assertStringContainsString('has 2 errors (restricted policy):', $output);
        $this->assertStringContainsString('- fields[0].type: unknown field type "txt" (did you mean "text"?).', $output);
        $this->assertStringContainsString('the "is_unique" rule is not allowed in this schema: it reads the database.', $output);
    }

    public function testCommandTrustedOption(): void
    {
        $file = $this->tempFile('{"fields":[{"type":"password","name":"pin"}]}');

        command('forms:schema check ' . $file . ' --trusted');

        $this->assertStringContainsString('is a valid schema (trusted policy).', $this->getStreamFilterBuffer());
    }

    public function testCommandExportsAFormClass(): void
    {
        command('forms:schema export Ephraitech\\\\Forms\\\\Tests\\\\Support\\\\BookingForm');

        $output = $this->getStreamFilterBuffer();

        $this->assertStringContainsString('"name": "booking"', $output);
        $this->assertStringContainsString('"type": "submit"', $output);
    }

    public function testCommandExportsToAFile(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'export') . '.json';
        $this->temp[] = $file;

        command('forms:schema export Ephraitech\\\\Forms\\\\Tests\\\\Support\\\\BookingForm --output ' . $file);

        $this->assertStringContainsString('Exported', $this->getStreamFilterBuffer());
        $this->assertSame('booking', json_decode((string) file_get_contents($file), true)['name']);
    }

    public function testCommandRejectsBadInput(): void
    {
        command('forms:schema export App\\\\Nope');
        $this->assertStringContainsString('is not a form class', $this->getStreamFilterBuffer());

        command('forms:schema check /no/such/file.json');
        $this->assertStringContainsString('File not found', $this->getStreamFilterBuffer());

        command('forms:schema frobnicate x');
        $this->assertStringContainsString('Usage: php spark forms:schema', $this->getStreamFilterBuffer());
    }
}
