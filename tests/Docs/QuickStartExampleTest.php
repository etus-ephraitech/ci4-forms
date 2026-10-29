<?php

declare(strict_types=1);

namespace Ephraitech\Forms\Tests\Docs;

use App\Database\Migrations\CreateContactMessages;
use App\Forms\ContactForm;
use CodeIgniter\Config\Services;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;

/**
 * Runs the contact form example included on the documentation's home and
 * installation pages (docs/examples/app), so the published example always
 * works: form class, controller, model, migration, view and routes.
 *
 * @internal
 */
final class QuickStartExampleTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    private const EXAMPLES = __DIR__ . '/../../docs/examples/app';

    protected $migrate = false;

    private string $viewFile;

    protected function setUp(): void
    {
        parent::setUp();

        // The example view lives in docs/; CodeIgniter looks for app views in
        // APPPATH, so it is placed there for the duration of the test.
        $this->viewFile = APPPATH . 'Views/contact.php';
        copy(self::EXAMPLES . '/Views/contact.php', $this->viewFile);

        require_once self::EXAMPLES . '/Database/Migrations/2026-10-29-000000_CreateContactMessages.php';
        (new CreateContactMessages(Database::forge($this->DBGroup)))->up();

        Services::resetSingle('validation');
    }

    protected function tearDown(): void
    {
        (new CreateContactMessages(Database::forge($this->DBGroup)))->down();

        if (is_file($this->viewFile)) {
            unlink($this->viewFile);
        }

        parent::tearDown();
    }

    /**
     * Loads the example routes file into the route collection, as
     * app/Config/Routes.php would.
     */
    private function loadExampleRoutes(): void
    {
        $routes = service('routes');
        $routes->resetRoutes();

        require self::EXAMPLES . '/Config/contact-routes.php';
    }

    public function testTheFormClass(): void
    {
        $form = new ContactForm();

        $this->assertSame('contact', $form->getName());
        $this->assertSame(['full_name', 'email', 'topic', 'message', 'submit'], array_keys($form->getFields()));
        $this->assertFalse($form->validate(['full_name' => '', 'email' => 'not-an-email', 'topic' => 'free_suit', 'message' => '']));
        $this->assertSame(['full_name', 'email', 'message', 'topic'], array_keys($form->getErrors()));
    }

    public function testTheRoutesFileDefinesBothRoutes(): void
    {
        $this->loadExampleRoutes();

        $routes = service('routes');

        $this->assertSame('\App\Controllers\Contact::index', $routes->getRoutes('GET')['contact'] ?? null);
        $this->assertSame('\App\Controllers\Contact::send', $routes->getRoutes('POST')['contact'] ?? null);
    }

    public function testTheContactPageShowsTheForm(): void
    {
        $result = $this->withRoutes([['get', 'contact', '\App\Controllers\Contact::index']])->get('contact');

        $result->assertOK();
        $result->assertSee('<h1>Contact us</h1>', null);
        $result->assertSee('name="full_name"', null);
        $result->assertSee('<option value="pricing">Prices</option>', null);
        $result->assertSee('Send message</button>', null);
    }

    public function testAnInvalidMessageIsShownAgainWithErrors(): void
    {
        $result = $this->withRoutes([['post', 'contact', '\App\Controllers\Contact::send']])
            ->post('contact', ['full_name' => 'Jane', 'email' => 'not-an-email', 'topic' => 'pricing', 'message' => '']);

        $result->assertOK();
        $result->assertSee('value="Jane"', null);
        $result->assertSee('The Email address field must contain a valid email address.', null);
        $result->assertSee('The Message field is required.', null);
        $this->dontSeeInDatabase('contact_messages', ['full_name' => 'Jane']);
    }

    public function testAValidMessageIsSavedAndRedirects(): void
    {
        $result = $this->withRoutes([['post', 'contact', '\App\Controllers\Contact::send']])
            ->post('contact', ['full_name' => '  Jane Namuli ', 'email' => 'jane@example.com', 'topic' => 'booking', 'message' => 'Can I book a fitting on Saturday?', 'is_admin' => '1']);

        $result->assertRedirectTo('/contact');
        $result->assertSessionHas('message', 'Thank you. We will reply within one working day.');
        $this->seeInDatabase('contact_messages', ['full_name' => 'Jane Namuli', 'email' => 'jane@example.com', 'topic' => 'booking']);
    }
}
