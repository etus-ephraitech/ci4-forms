<?php

declare(strict_types=1);

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 */
final class AppointmentsTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    // Run the app's migrations before each test, on a fresh database.
    protected $migrate = true;
    protected $refresh = true;
    protected $namespace;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withRoutes([
            ['get', 'appointments/new', '\App\Controllers\Appointments::new'],
            ['post', 'appointments', '\App\Controllers\Appointments::create'],
            ['patch', 'appointments/(:num)', '\App\Controllers\Appointments::update/$1'],
        ]);
    }

    public function testBookingIsSaved(): void
    {
        $result = $this->post('appointments', [
            'full_name'        => 'Jane Namuli',
            'phone'            => '0772123456',
            'service'          => 'fitting',
            'appointment_date' => date('Y-m-d', strtotime('+7 days')),
            'appointment_time' => '10:30',
        ]);

        $result->assertRedirectTo('/appointments');
        $this->seeInDatabase('appointments', ['full_name' => 'Jane Namuli', 'service' => 'fitting']);
    }

    public function testInvalidBookingIsShownAgain(): void
    {
        $result = $this->post('appointments', ['full_name' => 'Jane Namuli']);

        $result->assertOK();
        $result->assertSee('value="Jane Namuli"', null);
        $result->assertSee('The Phone number field is required.', null);
        $this->dontSeeInDatabase('appointments', ['full_name' => 'Jane Namuli']);
    }

    public function testChangingABooking(): void
    {
        $id = $this->db->table('appointments')->insert([
            'full_name'        => 'Jane Namuli',
            'phone'            => '0772123456',
            'service'          => 'fitting',
            'appointment_date' => date('Y-m-d', strtotime('+7 days')),
            'appointment_time' => '10:30',
        ]) ? $this->db->insertID() : 0;

        // Browsers send POST with _method=PATCH; in feature tests, send PATCH
        // with a URL-encoded body. Clearing the POST data mimics a fresh request.
        service('superglobals')->setPostArray([]);

        $result = $this->withBody(http_build_query([
            'full_name'        => 'Jane Namuli',
            'phone'            => '0772123456',
            'service'          => 'alterations',
            'appointment_date' => date('Y-m-d', strtotime('+8 days')),
            'appointment_time' => '14:00',
        ]))->patch('appointments/' . $id);

        $result->assertRedirectTo('/appointments');
        $result->assertStatus(303);
        $this->seeInDatabase('appointments', ['id' => $id, 'service' => 'alterations']);
    }
}
