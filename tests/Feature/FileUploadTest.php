<?php

declare(strict_types=1);

namespace Ephraitech\Forms\Tests\Feature;

require_once dirname(__DIR__) . '/Support/FakeUploads.php';

use CodeIgniter\Config\Services;
use CodeIgniter\HTTP\Files\UploadedFile;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\Test\CIUnitTestCase;
use Config\App;
use Ephraitech\Forms\Config\Forms;
use Ephraitech\Forms\Exceptions\FormException;
use Ephraitech\Forms\Form;

/**
 * File uploads through CodeIgniter's real request, file collection and file
 * validation rules.
 *
 * @internal
 */
final class FileUploadTest extends CIUnitTestCase
{
    /**
     * A valid 1x1 PNG.
     */
    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

    private Forms $config;

    /**
     * @var list<string>
     */
    private array $temp = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = new Forms();
    }

    protected function tearDown(): void
    {
        $_FILES = [];
        $_POST  = [];

        foreach ($this->temp as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        parent::tearDown();
    }

    // -- Helpers --------------------------------------------------------------

    private function png(): string
    {
        return $this->tempFile((string) base64_decode(self::PNG, true));
    }

    private function text(): string
    {
        return $this->tempFile("Just some text, not an image.\n");
    }

    private function tempFile(string $contents): string
    {
        $path = tempnam(sys_get_temp_dir(), 'ci4forms');
        file_put_contents($path, $contents);

        return $this->temp[] = $path;
    }

    /**
     * @return array<string, int|string>
     */
    private function upload(string $name, string $path, ?int $size = null): array
    {
        return ['name' => $name, 'type' => 'application/octet-stream', 'tmp_name' => $path, 'error' => UPLOAD_ERR_OK, 'size' => $size ?? (int) filesize($path)];
    }

    /**
     * @return array<string, int|string>
     */
    private function nothingChosen(): array
    {
        return ['name' => '', 'type' => '', 'tmp_name' => '', 'error' => UPLOAD_ERR_NO_FILE, 'size' => 0];
    }

    /**
     * @param list<array<string, int|string>> $uploads
     *
     * @return array<string, list<int|string>>
     */
    private function multiple(array $uploads): array
    {
        $result = [];

        foreach (['name', 'type', 'tmp_name', 'error', 'size'] as $key) {
            $result[$key] = array_column($uploads, $key);
        }

        return $result;
    }

    /**
     * Makes $files and $post the current request, as in a real upload.
     *
     * @param array<string, mixed> $files
     * @param array<string, mixed> $post
     */
    private function request(array $files, array $post = []): IncomingRequest
    {
        $_FILES = $files;
        $_POST  = $post;

        // CodeIgniter 4.7 reads uploads through a snapshot of the
        // superglobals taken once per request; a real request takes it at
        // the start. Rebuild it so this simulated upload is seen.
        Services::resetSingle('superglobals');

        /** @var IncomingRequest $request */
        $request = Services::incomingrequest(config(App::class), false);
        $request->setMethod('POST');
        $request->setGlobal('post', $post);

        Services::injectMock('request', $request);
        Services::resetSingle('validation');

        return $request;
    }

    private function profileForm(): Form
    {
        $form = Form::make('profile', $this->config)->setOldInput(null)->setOldErrors(null);

        $form->text('name')->rules('required');
        $form->file('photo', 'Profile photo')->required()->maxSize(1024)->image()->mimes(['image/png', 'image/jpeg']);
        $form->file('cv', 'CV')->extensions(['pdf', 'docx'])->maxSize(4096);

        return $form;
    }

    private function strip(string $html): string
    {
        return (string) preg_replace('/<!-- DEBUG-VIEW (START|ENDED) [^>]*-->\n?/', '', $html);
    }

    // -- Validation -----------------------------------------------------------

    public function testValidUploadPassesAndCanBeRetrieved(): void
    {
        $this->request(['photo' => $this->upload('me.png', $this->png()), 'cv' => $this->nothingChosen()], ['name' => 'Jane']);

        $form = $this->profileForm();

        $this->assertTrue($form->validate(), json_encode($form->getErrors()));

        $photo = $form->getFile('photo');

        $this->assertInstanceOf(UploadedFile::class, $photo);
        $this->assertSame('me.png', $photo->getClientName());
        $this->assertNull($form->getFile('cv'));
        $this->assertSame([], $form->getFiles('cv'));
    }

    public function testFilesAreNeverInValidatedData(): void
    {
        $this->request(['photo' => $this->upload('me.png', $this->png())], ['name' => 'Jane', 'photo' => 'tampered-text', 'cv' => 'x']);

        $form = $this->profileForm();

        $this->assertTrue($form->validate());
        $this->assertSame(['name' => 'Jane'], $form->validated());
    }

    public function testRequiredFileMissing(): void
    {
        $this->request(['photo' => $this->nothingChosen()], ['name' => 'Jane']);

        $form = $this->profileForm();

        $this->assertFalse($form->validate());
        $this->assertStringContainsString('Profile photo', (string) $form->getError('photo'));
    }

    public function testRequiredFileAbsentFromRequest(): void
    {
        $this->request([], ['name' => 'Jane']);

        $form = $this->profileForm();

        $this->assertFalse($form->validate());
        $this->assertTrue($form->hasError('photo'));
        $this->assertFalse($form->hasError('cv'), 'optional file absent from the request is not validated');
    }

    public function testOptionalFileIsValidatedOnceUploaded(): void
    {
        $this->request([
            'photo' => $this->upload('me.png', $this->png()),
            'cv'    => $this->upload('cv.exe', $this->text()),
        ], ['name' => 'Jane']);

        $form = $this->profileForm();

        $this->assertFalse($form->validate());
        $this->assertTrue($form->hasError('cv'));
        $this->assertFalse($form->hasError('photo'));
    }

    public function testMaxSize(): void
    {
        $this->request(['photo' => $this->upload('big.png', $this->png(), 2 * 1024 * 1024)], ['name' => 'Jane']);

        $form = $this->profileForm();

        $this->assertFalse($form->validate());
        $this->assertTrue($form->hasError('photo'));
    }

    public function testImageRuleRejectsNonImageContent(): void
    {
        $this->request(['photo' => $this->upload('photo.png', $this->text())], ['name' => 'Jane']);

        $form = $this->profileForm();

        $this->assertFalse($form->validate(), 'a text file renamed to .png is not an image');
        $this->assertTrue($form->hasError('photo'));
    }

    public function testMimeRuleChecksContent(): void
    {
        $this->request(['doc' => $this->upload('notes.png', $this->text())]);

        $form = Form::make('x', $this->config)->setOldInput(null);
        $form->file('doc')->required()->mimes(['image/png']);

        $this->assertFalse($form->validate());
    }

    public function testMaxDimensions(): void
    {
        $this->request(['photo' => $this->upload('me.png', $this->png())]);

        $form = Form::make('x', $this->config)->setOldInput(null);
        $form->file('photo')->required()->maxDimensions(800, 600);

        $this->assertTrue($form->validate(), '1x1 fits within 800x600');
    }

    public function testExtraRulesRunAfterGeneratedOnes(): void
    {
        $form = Form::make('x', $this->config);
        $form->file('photo')->required()->maxSize(500)->rules('max_dims[photo,100,100]');

        $this->assertSame(
            ['uploaded[photo]', 'max_size[photo,500]', 'max_dims[photo,100,100]'],
            $form->getField('photo')->getRules(),
        );
    }

    // -- Multiple files -------------------------------------------------------

    public function testMultipleFiles(): void
    {
        $this->request(['photos' => $this->multiple([
            $this->upload('a.png', $this->png()),
            $this->upload('b.png', $this->png()),
        ])]);

        $form = Form::make('gallery', $this->config)->setOldInput(null);
        $form->file('photos', 'Photos')->multiple()->required()->image();

        $this->assertTrue($form->validate(), json_encode($form->getErrors()));

        $files = $form->getFiles('photos');

        $this->assertCount(2, $files);
        $this->assertSame(['a.png', 'b.png'], array_map(static fn (UploadedFile $f): string => $f->getClientName(), $files));
        $this->assertSame('a.png', $form->getFile('photos')?->getClientName());
    }

    public function testOneBadFileFailsTheField(): void
    {
        $this->request(['photos' => $this->multiple([
            $this->upload('a.png', $this->png()),
            $this->upload('b.png', $this->text()),
        ])]);

        $form = Form::make('gallery', $this->config)->setOldInput(null);
        $form->file('photos', 'Photos')->multiple()->image();

        $this->assertFalse($form->validate());
        $this->assertTrue($form->hasError('photos'));
    }

    public function testMultipleWithNothingChosen(): void
    {
        $this->request(['photos' => $this->multiple([$this->nothingChosen()])]);

        $optional = Form::make('gallery', $this->config)->setOldInput(null);
        $optional->file('photos')->multiple()->image();

        $this->assertTrue($optional->validate());
        $this->assertFalse($optional->hasUploadedFile('photos'));

        $required = Form::make('gallery', $this->config)->setOldInput(null);
        $required->file('photos')->multiple()->required();

        $this->assertFalse($required->validate());
    }

    // -- Form behaviour -------------------------------------------------------

    public function testFileFieldMakesFormMultipart(): void
    {
        $form = Form::make('x', $this->config);

        $this->assertFalse($form->isMultipart());

        $form->file('photo');

        $this->assertTrue($form->isMultipart());
        $this->assertStringContainsString('enctype="multipart/form-data"', $form->open());

        $form->remove('photo');

        $this->assertFalse($form->isMultipart());
    }

    public function testFileGettersRejectNonFileFields(): void
    {
        $this->expectException(FormException::class);
        $this->expectExceptionMessage('field "name" is not a file field');

        $this->profileForm()->getFile('name');
    }

    public function testOptionalFileRulesAreLeftOutWhenNothingUploaded(): void
    {
        $this->request([]);

        $rules = $this->profileForm()->getValidationRules();

        $this->assertArrayHasKey('photo', $rules);
        $this->assertArrayNotHasKey('cv', $rules);
    }

    // -- Rendering ------------------------------------------------------------

    public function testFileInputMarkup(): void
    {
        $this->request([]);

        $form = $this->profileForm();
        $form->getField('photo')->help('JPG or PNG, up to 1 MB.');

        $html = $this->strip($form->field('photo'));

        $this->assertStringContainsString('<input type="file" name="photo" id="profile_photo" class="form-control" required accept="image/png,image/jpeg" aria-describedby="profile_photo_help">', $html);
        $this->assertStringNotContainsString('value=', $html);
        $this->assertStringContainsString('accept=".pdf,.docx"', $form->field('cv'));
    }

    public function testMultipleFileInputMarkup(): void
    {
        $this->request([]);

        $form = Form::make('gallery', $this->config)->setOldInput(null);
        $form->file('photos', 'Photos')->multiple()->image();

        $this->assertStringContainsString('<input type="file" name="photos[]" id="gallery_photos" class="form-control" accept="image/*" multiple>', $this->strip($form->field('photos')));
    }

    public function testReselectHintAfterFailedSubmission(): void
    {
        $this->request(['photo' => $this->upload('me.png', $this->png())], ['name' => '']);

        $form = $this->profileForm();
        $form->validate();

        $html = $form->field('photo');

        $this->assertStringContainsString('<div id="profile_photo_reselect" class="form-text text-warning-emphasis">Please choose the file again.</div>', $html);
        $this->assertStringContainsString('aria-describedby="profile_photo_reselect"', $html);
    }

    public function testNoHintWhenFieldHasItsOwnErrorOrFormIsFresh(): void
    {
        $this->request(['photo' => $this->nothingChosen()], ['name' => '']);

        $form = $this->profileForm();
        $form->validate();

        $this->assertStringNotContainsString('choose the file again', $form->field('photo'), 'its own error already says what to do');

        $this->request([]);

        $this->assertStringNotContainsString('choose the file again', $this->profileForm()->field('photo'));
    }

    public function testHintAfterRedirectAndConfigurable(): void
    {
        $this->request([]);

        $form = $this->profileForm()
            ->setOldInput(['_form' => 'profile', 'name' => ''])
            ->setOldErrors(['name' => 'The Name field is required.']);

        $this->assertStringContainsString('Please choose the file again.', $form->field('cv'));

        $this->config->fileReselectMessage = null;

        $this->assertStringNotContainsString('reselect', $form->field('cv'));
    }

    // -- Field settings -------------------------------------------------------

    public function testInvalidSettingsThrow(): void
    {
        $form = Form::make('x', $this->config);

        foreach ([
            static fn () => $form->file('a')->maxSize(0),
            static fn () => $form->file('b')->extensions(['p d f']),
            static fn () => $form->file('c')->mimes(['not a mime']),
            static fn () => $form->file('d')->maxDimensions(0, 10),
        ] as $i => $call) {
            try {
                $call();
                $this->fail('setting ' . $i . ' should throw');
            } catch (FormException $e) {
                $this->assertStringContainsString('File field', $e->getMessage());
            }
        }
    }

    public function testExtensionsAreNormalised(): void
    {
        $form = Form::make('x', $this->config);
        $field = $form->file('doc')->extensions(['.PDF', 'docx', 'pdf']);

        $this->assertSame(['ext_in[doc,pdf,docx]'], $field->getRules());
        $this->assertSame('.pdf,.docx', $field->getAttributes()['accept']);
        $this->assertFalse($field->isRequired());
        $this->assertFalse($field->shouldRepopulate());
    }

    public function testExplicitAcceptIsKept(): void
    {
        $form = Form::make('x', $this->config);

        $this->assertSame('application/pdf', $form->file('doc')->extensions('pdf')->attribute('accept', 'application/pdf')->getAttributes()['accept']);
    }
}
