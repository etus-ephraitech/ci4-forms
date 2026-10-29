<?php

declare(strict_types=1);

namespace Ephraitech\Forms\Tests\Docs;

use App\Models\FormDefinitionModel;
use CodeIgniter\Config\Services;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Runs the forms-written-by-admins and showing-submissions guides' example
 * app: editing and checking a schema, the public page, storing answers as a
 * payload, and the answers screen.
 *
 * @internal
 */
final class AdminFormsExampleTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    private const EXAMPLES = __DIR__ . '/../../docs/examples/app';

    protected $migrate = true;

    protected $refresh = true;

    protected $namespace;

    private int $formId;

    protected function setUp(): void
    {
        parent::setUp();

        $routes = service('routes');
        $routes->resetRoutes();
        require self::EXAMPLES . '/Config/admin-forms-routes.php';
        $this->routes = $routes;

        Services::resetSingle('validation');

        $this->formId = (int) model(FormDefinitionModel::class)->insert([
            'title'  => 'Wedding enquiry',
            'schema' => ['name' => 'wedding', 'fields' => [
                ['type' => 'text', 'name' => 'full_name', 'label' => 'Full name', 'rules' => 'required'],
                ['type' => 'select', 'name' => 'guests', 'label' => 'Number of guests', 'options' => ['small' => 'Up to 50', 'large' => 'More than 50'], 'rules' => 'required'],
                ['type' => 'checkbox', 'name' => 'contact_me', 'label' => 'You may call me'],
                ['type' => 'submit', 'label' => 'Send'],
            ]],
        ]);
    }

    /**
     * @param array<string, mixed> $schema
     */
    private function save(array|string $schema): \CodeIgniter\Test\TestResponse
    {
        return $this->post('admin/forms/' . $this->formId, ['schema' => is_string($schema) ? $schema : json_encode($schema)]);
    }

    public function testEditorShowsTheSchema(): void
    {
        $result = $this->get('admin/forms/' . $this->formId . '/edit');

        $result->assertOK();
        $result->assertSee('Edit form: Wedding enquiry', null);
        $result->assertSee('"name": "full_name"', null);
    }

    public function testUnsafeSchemaIsNotSaved(): void
    {
        $result = $this->save(['fields' => [
            ['type' => 'text', 'name' => 'x', 'attributes' => ['onfocus' => 'steal()']],
            ['type' => 'email', 'name' => 'email', 'rules' => 'is_unique[users.email]'],
            ['type' => 'text', 'name' => 'payload'],
        ]]);

        $result->assertStatus(422);
        $result->assertSee('the "onfocus" attribute is not allowed in this schema', null);
        $result->assertSee('the "is_unique" rule is not allowed in this schema: it reads the database.', null);
        $result->assertSee('"payload" is not allowed as a field name in this schema', null, 'names the app uses itself are forbidden');

        $this->assertSame('full_name', model(FormDefinitionModel::class)->find($this->formId)['schema']['fields'][0]['name']);
    }

    public function testInvalidJsonIsReported(): void
    {
        $this->save('{"fields": [')->assertSee('The schema is not valid JSON (Syntax error).', null);
    }

    public function testValidSchemaIsSaved(): void
    {
        $result = $this->save(['name' => 'wedding', 'fields' => [['type' => 'text', 'name' => 'full_name'], ['type' => 'date', 'name' => 'wedding_date']]]);

        $result->assertRedirectTo('/admin/forms/' . $this->formId . '/edit');
        $this->assertSame('wedding_date', model(FormDefinitionModel::class)->find($this->formId)['schema']['fields'][1]['name']);
    }

    public function testCheckEndpoint(): void
    {
        $result = $this->post('admin/forms/check', ['schema' => '{"fields":[{"type":"txt","name":"a"}]}']);
        $data   = json_decode($result->getJSON(), true);

        $this->assertFalse($data['valid']);
        $this->assertSame(['fields[0].type: unknown field type "txt" (did you mean "text"?).'], $data['messages']);
        $this->assertNotEmpty($data['csrf']);
    }

    public function testPublicPageAndSubmission(): void
    {
        $page = $this->get('forms/' . $this->formId);

        $page->assertOK();
        $page->assertSee('<h1>Wedding enquiry</h1>', null);
        $page->assertSee('action="' . site_url('forms/' . $this->formId) . '"', null);

        $bad = $this->post('forms/' . $this->formId, ['full_name' => 'Jane', 'guests' => 'everyone']);
        $bad->assertSee('Please choose a valid option for Number of guests.', null);

        $good = $this->post('forms/' . $this->formId, ['full_name' => '  Jane Namuli ', 'guests' => 'large', 'contact_me' => '1', 'is_admin' => '1']);
        $good->assertRedirectTo('/forms/' . $this->formId);
        $good->assertStatus(303);

        $row = $this->db->table('form_submissions')->get()->getRowArray();

        $this->assertSame((string) $this->formId, (string) $row['form_definition_id']);
        $this->assertSame(['full_name' => 'Jane Namuli', 'guests' => 'large', 'contact_me' => '1'], json_decode($row['payload'], true));
    }

    public function testBrokenStoredSchemaTakesTheFormOffline(): void
    {
        $this->db->table('form_definitions')->where('id', $this->formId)->update(['schema' => json_encode(['fields' => [['type' => 'text', 'name' => 'a', 'rules' => 'is_unique[users.email]']]])]);

        $result = $this->get('forms/' . $this->formId);

        $result->assertStatus(503);
        $result->assertSee('This form is being updated.', null);
        $result->assertDontSee('<form', null);
    }

    public function testAnswersScreen(): void
    {
        $this->post('forms/' . $this->formId, ['full_name' => 'Jane Namuli', 'guests' => 'large', 'contact_me' => '1']);

        $result = $this->get('admin/forms/' . $this->formId . '/answers');

        $result->assertOK();
        $result->assertSee('Answers: Wedding enquiry', null);
        $result->assertSee('More than 50', null);
        $result->assertSee('Yes', null);
        $result->assertDontSee('>large<', null);
    }
}
