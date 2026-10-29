<?php

declare(strict_types=1);

namespace Ephraitech\Forms\Tests\Docs;

use CodeIgniter\Config\Services;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Runs the JavaScript and APIs guide's JSON endpoints.
 *
 * @internal
 */
final class ApiExampleTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    private const EXAMPLES = __DIR__ . '/../../docs/examples/app';

    protected $migrate = true;

    protected $refresh = true;

    protected $namespace;

    protected function setUp(): void
    {
        parent::setUp();

        $routes = service('routes');
        $routes->resetRoutes();
        require self::EXAMPLES . '/Config/api-routes.php';
        $this->routes = $routes;

        Services::resetSingle('validation');
    }

    /**
     * @return array<string, string>
     */
    private function appointment(array $overrides = []): array
    {
        return array_merge([
            'full_name'        => 'Jane Namuli',
            'phone'            => '0772123456',
            'service'          => 'fitting',
            'appointment_date' => '2030-01-15',
            'appointment_time' => '10:30',
        ], $overrides);
    }

    public function testCreateFromJson(): void
    {
        $result = $this->withBodyFormat('json')->post('api/appointments', $this->appointment(['is_admin' => true]));

        $result->assertStatus(201);
        $data = json_decode($result->getJSON(), true);

        $this->assertSame('Jane Namuli', $data['full_name']);
        $this->assertArrayNotHasKey('is_admin', $data);
        $this->seeInDatabase('appointments', ['id' => $data['id'], 'full_name' => 'Jane Namuli']);
    }

    public function testValidationErrorsAsJson(): void
    {
        $result = $this->withBodyFormat('json')->post('api/appointments', ['full_name' => 'J', 'service' => 'free_suit']);

        $result->assertStatus(400);
        $errors = json_decode($result->getJSON(), true)['messages'];

        $this->assertSame('The Full name field must be at least 3 characters in length.', $errors['full_name']);
        $this->assertSame('The Phone number field is required.', $errors['phone']);
        $this->assertSame('Please choose a valid option for Service.', $errors['service']);
    }

    public function testGenuinePatchWithJson(): void
    {
        $id = $this->db->table('appointments')->insert($this->appointment()) ? $this->db->insertID() : 0;

        service('superglobals')->setPostArray([]);

        $result = $this->withBodyFormat('json')->patch('api/appointments/' . $id, $this->appointment(['service' => 'alterations']));

        $result->assertStatus(200);
        $this->seeInDatabase('appointments', ['id' => $id, 'service' => 'alterations']);
    }

    public function testPatchOfAMissingAppointment(): void
    {
        $this->withBodyFormat('json')->patch('api/appointments/999', $this->appointment())->assertStatus(404);
    }
}
