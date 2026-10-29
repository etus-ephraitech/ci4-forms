<?php

declare(strict_types=1);

namespace Ephraitech\Forms\Tests\Docs;

use App\Database\Migrations\CreateAppointments;
use App\Forms\AppointmentForm;
use CodeIgniter\Config\Services;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;

/**
 * Runs the Getting started tutorial's example app (docs/examples/app) over
 * HTTP, step by step: booking, listing, editing and the date rule.
 *
 * @internal
 */
final class GettingStartedExampleTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    private const EXAMPLES = __DIR__ . '/../../docs/examples/app';

    protected $migrate = false;

    /**
     * @var list<string>
     */
    private array $copiedViews = [];

    protected function setUp(): void
    {
        parent::setUp();

        // Example views live in docs/; CodeIgniter looks for app views in
        // APPPATH, so they are placed there for the duration of the test.
        if (! is_dir(APPPATH . 'Views/appointments')) {
            mkdir(APPPATH . 'Views/appointments', 0755, true);
        }

        foreach (['appointments/form.php', 'appointments/index.php'] as $view) {
            copy(self::EXAMPLES . '/Views/' . $view, APPPATH . 'Views/' . $view);
            $this->copiedViews[] = APPPATH . 'Views/' . $view;
        }

        require_once self::EXAMPLES . '/Database/Migrations/2026-10-29-000100_CreateAppointments.php';
        (new CreateAppointments(Database::forge($this->DBGroup)))->up();

        // The tutorial's own routes file, loaded the way app/Config/Routes.php would be.
        $routes = service('routes');
        $routes->resetRoutes();
        require self::EXAMPLES . '/Config/appointment-routes.php';
        $this->routes = $routes;

        Services::resetSingle('validation');
    }

    protected function tearDown(): void
    {
        (new CreateAppointments(Database::forge($this->DBGroup)))->down();

        foreach ($this->copiedViews as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        @rmdir(APPPATH . 'Views/appointments');

        parent::tearDown();
    }

    /**
     * @return array<string, string>
     */
    private function booking(array $overrides = []): array
    {
        return array_merge([
            'full_name'        => 'Jane Namuli',
            'phone'            => '0772123456',
            'email'            => '',
            'service'          => 'fitting',
            'appointment_date' => date('Y-m-d', strtotime('+7 days')),
            'appointment_time' => '10:30',
            'notes'            => '',
        ], $overrides);
    }

    /**
     * Sends a genuine PATCH with a URL-encoded body, starting from an empty
     * $_POST as a real request does (the booking POST before it would
     * otherwise still be in CodeIgniter's superglobals snapshot).
     *
     * @param array<string, string> $data
     */
    private function patchAppointment(int $id, array $data): \CodeIgniter\Test\TestResponse
    {
        service('superglobals')->setPostArray([]);

        return $this->withBody(http_build_query($data))->patch('appointments/' . $id);
    }

    private function bookOne(): int
    {
        $this->post('appointments', $this->booking());

        return (int) db_connect($this->DBGroup)->table('appointments')->selectMax('id')->get()->getRow()->id;
    }

    // -- Step: the form class ---------------------------------------------------

    public function testTheFormClassRendersTwoRows(): void
    {
        $html = (new AppointmentForm())->setOldInput(null)->render();

        $this->assertSame(3, substr_count($html, 'class="col-md-6"'), 'name, phone and service take half a row');
        $this->assertSame(2, substr_count($html, 'class="col-md-3"'), 'date and time take a quarter each');
        $this->assertStringContainsString('min="09:00" max="17:00"', $html);
        $this->assertStringContainsString('<option value="" selected>Choose a service</option>', $html);
    }

    // -- Step: booking --------------------------------------------------------

    public function testTheBookingPageShowsTheForm(): void
    {
        $result = $this->get('appointments/new');

        $result->assertOK();
        $result->assertSee('<h1>Book an appointment</h1>', null);
        $result->assertSee('action="' . site_url('appointments') . '"', null);
        $result->assertSee('Book appointment</button>', null);
    }

    public function testAnEmptyBookingShowsErrors(): void
    {
        $result = $this->post('appointments', ['full_name' => 'Jane']);

        $result->assertOK();
        $result->assertSee('value="Jane"', null);
        $result->assertSee('The Phone number field is required.', null);
        $result->assertSee('The Service field is required.', null);
        $this->dontSeeInDatabase('appointments', ['full_name' => 'Jane']);
    }

    public function testAnUnknownServiceIsRejected(): void
    {
        $result = $this->post('appointments', $this->booking(['service' => 'free_suit']));

        $result->assertSee('Please choose a valid option for Service.', null);
        $this->dontSeeInDatabase('appointments', ['service' => 'free_suit']);
    }

    public function testAValidBookingIsSaved(): void
    {
        $result = $this->post('appointments', $this->booking(['full_name' => '  Jane Namuli ', 'is_admin' => '1']));

        $result->assertRedirectTo('/appointments');
        $result->assertSessionHas('message', 'Appointment booked.');
        $this->seeInDatabase('appointments', ['full_name' => 'Jane Namuli', 'service' => 'fitting', 'appointment_time' => '10:30', 'email' => null]);
    }

    // -- Step: the business rule --------------------------------------------------

    public function testAPastDateIsRejected(): void
    {
        $result = $this->post('appointments', $this->booking(['appointment_date' => '2020-01-15']));

        $result->assertOK();
        $result->assertSee('Choose today or a later date.', null);
        $this->dontSeeInDatabase('appointments', ['appointment_date' => '2020-01-15']);
    }

    // -- Step: listing --------------------------------------------------------

    public function testTheListShowsBookingsWithServiceNames(): void
    {
        $this->bookOne();

        $result = $this->get('appointments');

        $result->assertOK();
        $result->assertSee('Jane Namuli', null);
        $result->assertSee('<td>Suit fitting</td>', null);
        $result->assertSee('Change</a>', null);
    }

    public function testAnEmptyList(): void
    {
        $this->get('appointments')->assertSee('No appointments yet.', null);
    }

    // -- Step: editing --------------------------------------------------------

    public function testTheEditPageIsFilledAndSendsPatch(): void
    {
        $id     = $this->bookOne();
        $result = $this->get('appointments/' . $id . '/edit');

        $result->assertOK();
        $result->assertSee('<h1>Change appointment</h1>', null);
        $result->assertSee('value="Jane Namuli"', null);
        $result->assertSee('<option value="fitting" selected>', null);
        $result->assertSee('name="_method" value="PATCH"', null);
        $result->assertSee('action="' . site_url('appointments/' . $id) . '"', null);
        $result->assertSee('Save changes</button>', null);
    }

    /*
     * Browsers send the edit form as POST with a hidden _method=PATCH field,
     * which CodeIgniter turns into PATCH before routing. The feature-test
     * harness fixes the HTTP verb before that step, so these tests send a
     * genuine PATCH with a URL-encoded body, as an API client would; the edit
     * page test above checks the hidden field is there.
     */
    public function testUpdatingSavesTheChange(): void
    {
        $id = $this->bookOne();

        $result = $this->patchAppointment($id, $this->booking(['service' => 'alterations', 'appointment_time' => '14:00']));

        $result->assertRedirectTo('/appointments');
        $result->assertStatus(303);
        $result->assertSessionHas('message', 'Appointment changed.');
        $this->seeInDatabase('appointments', ['id' => $id, 'service' => 'alterations', 'appointment_time' => '14:00']);
    }

    public function testAnInvalidChangeIsShownAgain(): void
    {
        $id = $this->bookOne();

        $result = $this->patchAppointment($id, $this->booking(['phone' => '']));

        $result->assertOK();
        $result->assertSee('The Phone number field is required.', null);
        $this->seeInDatabase('appointments', ['id' => $id, 'phone' => '0772123456']);
    }

    public function testEditingAMissingBookingIsNotFound(): void
    {
        $this->expectException(PageNotFoundException::class);

        $this->get('appointments/999/edit');
    }
}
