<?php

declare(strict_types=1);

namespace Ephraitech\Forms\Tests\Docs;

require_once __DIR__ . '/../Support/FakeUploads.php';

use App\Database\Migrations\CreateCustomerPreferences;
use App\Forms\DeliveryForm;
use App\Forms\PreferencesForm;
use App\Forms\ProfileForm;
use App\Models\CustomerPreferenceModel;
use CodeIgniter\Config\Services;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;

/**
 * Runs the examples of the layout, multiple choices and file uploads guides.
 *
 * @internal
 */
final class GuidesExampleTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    private const EXAMPLES = __DIR__ . '/../../docs/examples/app';

    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

    protected $migrate = false;

    /**
     * @var list<string>
     */
    private array $cleanup = [];

    protected function setUp(): void
    {
        parent::setUp();

        Services::resetSingle('validation');
    }

    protected function tearDown(): void
    {
        foreach (array_reverse($this->cleanup) as $path) {
            is_dir($path) ? @rmdir($path) : @unlink($path);
        }

        service('superglobals')->setFilesArray([]);
        service('superglobals')->setPostArray([]);

        parent::tearDown();
    }

    private function strip(string $html): string
    {
        return trim((string) preg_replace('/>\s+</', '><', (string) preg_replace('/<!-- DEBUG-VIEW (START|ENDED) [^>]*-->\n?/', '', $html)));
    }

    private function useView(string $view): void
    {
        $target = APPPATH . 'Views/' . $view;

        if (! is_dir(dirname($target))) {
            mkdir(dirname($target), 0755, true);
            $this->cleanup[] = dirname($target);
        }

        copy(self::EXAMPLES . '/Views/' . $view, $target);
        $this->cleanup[] = $target;
    }

    // -- Layout ---------------------------------------------------------------

    public function testLayoutGuideForm(): void
    {
        $form = (new DeliveryForm())->setOldInput(null)->setOldErrors(null);
        $html = $this->strip($form->render());

        $this->assertSame(['order_id', 'first_name', 'last_name', 'phone', 'street', 'town', 'postcode', 'billing', 'deliver_on', 'submit'], array_keys($form->getFields()), 'layout never changes the flat field list');
        $this->assertStringContainsString('<legend class="h6 mb-3">Contact</legend><div class="row"><div class="col-md-6">', $html);
        $this->assertStringContainsString('<p class="text-body-secondary small mb-3">We deliver within Kampala and Entebbe.</p>', $html);
        $this->assertStringContainsString('<div class="col-md-8">', $html);
        $this->assertStringContainsString('<div class="col-md"><div class="mb-3"><label for="delivery_postcode"', $html, 'a field without width shares the rest');
        $this->assertStringContainsString('<div class="row g-4"><div class="col-md-6"><fieldset class="mb-4"><legend class="h6 mb-3">Billing</legend>', $html);
        $this->assertStringContainsString('<input type="hidden" name="order_id" value="">', $html);
    }

    public function testLayoutGuideCustomView(): void
    {
        $form = (new DeliveryForm())->setOldInput(null)->setOldErrors(null);
        $form->validate(['first_name' => '']);

        ob_start();
        include self::EXAMPLES . '/Views/delivery/custom.php';
        $html = $this->strip((string) ob_get_clean());

        $this->assertStringStartsWith('<form', $html);
        $this->assertStringContainsString('<div class="alert alert-danger" role="alert">', $html);
        $this->assertStringContainsString('<div class="row g-3"><div class="col-md-4"><div class="mb-3"><label for="delivery_first_name"', $html);
        $this->assertStringContainsString('<div class="d-flex gap-2"><button type="submit"', $html, 'control() renders the button without its wrapper');
        $this->assertStringContainsString('<input type="hidden" name="order_id"', $html);
        $this->assertStringEndsWith('</form>', $html);
    }

    // -- Multiple choices -----------------------------------------------------

    public function testMultipleChoicesGuide(): void
    {
        require_once self::EXAMPLES . '/Database/Migrations/2026-10-29-000200_CreateCustomerPreferences.php';
        $migration = new CreateCustomerPreferences(Database::forge($this->DBGroup));
        $migration->up();

        try {
            $form = (new PreferencesForm())->setOldInput(null);

            $this->assertFalse($form->validate(['branches' => ['kampala']]));
            $this->assertSame('Choose at least one.', $form->getError('interests'));

            $this->assertFalse($form->validate(['interests' => ['suits', 'free_shoes']]));
            $this->assertSame('Please choose a valid option for What are you interested in?.', $form->getError('interests'));

            $this->assertTrue($form->validate(['interests' => ['suits', 'kanzu']]));
            $this->assertSame(['interests' => ['suits', 'kanzu'], 'branches' => []], $form->validated());

            $model = model(CustomerPreferenceModel::class);
            $id    = $model->insert(['customer_id' => 7] + $form->validated());
            $row   = $model->find($id);

            $this->assertSame(['suits', 'kanzu'], $row['interests'], 'stored as JSON, read back as a list');
            $this->assertSame([], $row['branches']);

            $edit = (new PreferencesForm())->setOldInput(null)->fill($row);
            $html = $edit->field('interests') . $edit->field('branches');

            $this->assertStringContainsString('value="kanzu" checked', $html);
            $this->assertStringNotContainsString('value="shirts" checked', $html);
            $this->assertStringContainsString('<select name="branches[]" id="preferences_branches" class="form-select" size="3" multiple', $html);
        } finally {
            $migration->down();
        }
    }

    // -- File uploads ---------------------------------------------------------

    public function testFileUploadsGuide(): void
    {
        $this->useView('profile.php');

        $photo = tempnam(sys_get_temp_dir(), 'docs');
        file_put_contents($photo, (string) base64_decode(self::PNG, true));
        $this->cleanup[] = $photo;

        // The form must not be submitted without a photo.
        service('superglobals')->setFilesArray([]);
        $result = $this->withRoutes([['post', 'profile', '\App\Controllers\Profile::update']])
            ->post('profile', ['full_name' => 'Jane Namuli']);

        $result->assertOK();
        $result->assertSee('Profile photo is not a valid uploaded file.', null);

        // With a valid photo, it is stored and its path saved.
        service('superglobals')->setFilesArray([
            'photo' => ['name' => 'me.png', 'type' => 'image/png', 'tmp_name' => $photo, 'error' => UPLOAD_ERR_OK, 'size' => filesize($photo)],
        ]);

        $result = $this->withRoutes([['post', 'profile', '\App\Controllers\Profile::update']])
            ->post('profile', ['full_name' => 'Jane Namuli']);

        $result->assertRedirectTo('/profile');

        $saved = session('profile');

        $this->assertSame('Jane Namuli', $saved['full_name']);
        $this->assertArrayNotHasKey('photo', $saved, 'files are never part of validated()');
        $this->assertMatchesRegularExpression('#^photos/\d+_[0-9a-f]+\.png$#', $saved['photo_path']);
        $this->assertFileExists(WRITEPATH . 'uploads/' . $saved['photo_path']);
        $this->assertSame([], $saved['certificate_paths']);

        $this->cleanup[] = WRITEPATH . 'uploads/photos';
        $this->cleanup[] = WRITEPATH . 'uploads/' . $saved['photo_path'];
    }

    public function testFileUploadsGuideRendering(): void
    {
        $html = $this->strip((new ProfileForm())->setOldInput(null)->render());

        $this->assertStringContainsString('enctype="multipart/form-data"', $html);
        $this->assertStringContainsString('<input type="file" name="photo" id="profile_photo" class="form-control" required accept="image/jpeg,image/png,image/webp"', $html);
        $this->assertStringContainsString('<input type="file" name="certificates[]" id="profile_certificates" class="form-control" accept=".pdf" multiple', $html);
    }
}
