<?php

declare(strict_types=1);

namespace Ephraitech\Forms\Tests\Unit\Publishing;

use CodeIgniter\Test\CIUnitTestCase;
use Ephraitech\Forms\Exceptions\FormException;
use Ephraitech\Forms\Publishing\ViewPublisher;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * @internal
 */
final class ViewPublisherTest extends CIUnitTestCase
{
    private string $appPath;

    private ViewPublisher $publisher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->appPath   = sys_get_temp_dir() . '/ci4-forms-publish-' . bin2hex(random_bytes(6));
        $this->publisher = new ViewPublisher();

        mkdir($this->appPath, 0755, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->appPath);

        parent::tearDown();
    }

    public function testAvailableThemes(): void
    {
        $this->assertSame(['bootstrap5'], $this->publisher->availableThemes());
    }

    public function testPublishesEveryViewUnchanged(): void
    {
        $results = $this->publisher->publishViews('bootstrap5', $this->appPath);
        $source  = dirname(__DIR__, 3) . '/src/Views/bootstrap5';
        $target  = $this->appPath . '/Views/forms/bootstrap5';

        $expected = array_map('basename', glob($source . '/*.php') ?: []);
        sort($expected);

        $this->assertSame($expected, array_map(static fn (array $r): string => basename($r['file']), $results));
        $this->assertSame([ViewPublisher::CREATED], array_values(array_unique(array_column($results, 'status'))));

        foreach ($expected as $file) {
            $this->assertFileEquals($source . '/' . $file, $target . '/' . $file);
        }
    }

    public function testExistingViewsAreSkippedUnlessForced(): void
    {
        $this->publisher->publishViews('bootstrap5', $this->appPath);

        $custom = $this->appPath . '/Views/forms/bootstrap5/field_input.php';
        file_put_contents($custom, '<!-- my customised view -->');

        $again = $this->publisher->publishViews('bootstrap5', $this->appPath);

        $this->assertSame([ViewPublisher::SKIPPED], array_values(array_unique(array_column($again, 'status'))));
        $this->assertSame('<!-- my customised view -->', file_get_contents($custom));

        $forced = $this->publisher->publishViews('bootstrap5', $this->appPath, true);

        $this->assertSame([ViewPublisher::OVERWRITTEN], array_values(array_unique(array_column($forced, 'status'))));
        $this->assertStringNotContainsString('my customised view', (string) file_get_contents($custom));
    }

    public function testMissingFilesArePublishedAlongsideSkippedOnes(): void
    {
        $this->publisher->publishViews('bootstrap5', $this->appPath);
        unlink($this->appPath . '/Views/forms/bootstrap5/field_radio.php');

        $results  = $this->publisher->publishViews('bootstrap5', $this->appPath);
        $statuses = array_combine(
            array_map(static fn (array $r): string => basename($r['file']), $results),
            array_column($results, 'status'),
        );

        $this->assertSame(ViewPublisher::CREATED, $statuses['field_radio.php']);
        $this->assertSame(ViewPublisher::SKIPPED, $statuses['field_input.php']);
    }

    public function testUnknownThemeThrows(): void
    {
        $this->expectException(FormException::class);
        $this->expectExceptionMessage('Form theme "tailwind" is not registered. Available themes: bootstrap5.');

        $this->publisher->publishViews('tailwind', $this->appPath);
    }

    public function testPublishConfigPointingAtViews(): void
    {
        $result = $this->publisher->publishConfig($this->appPath, 'bootstrap5', true);
        $file   = $this->appPath . '/Config/Forms.php';

        $this->assertSame(['file' => $file, 'status' => ViewPublisher::CREATED], $result);

        $source = (string) file_get_contents($file);

        $this->assertStringContainsString('namespace Config;', $source);
        $this->assertStringContainsString('class Forms extends BaseForms', $source);
        $this->assertStringContainsString("\$this->themes['bootstrap5']['views'] = 'forms/bootstrap5';", $source);
        $this->assertValidPhp($source);
    }

    public function testPublishConfigWithoutViewsOverride(): void
    {
        $this->publisher->publishConfig($this->appPath, 'bootstrap5', false);

        $source = (string) file_get_contents($this->appPath . '/Config/Forms.php');

        $this->assertStringNotContainsString('__construct', $source);
        $this->assertStringContainsString("public string \$theme = 'bootstrap5';", $source);
        $this->assertValidPhp($source);
    }

    public function testExistingConfigIsNeverOverwritten(): void
    {
        mkdir($this->appPath . '/Config', 0755, true);
        file_put_contents($this->appPath . '/Config/Forms.php', '<?php // mine');

        $result = $this->publisher->publishConfig($this->appPath, 'bootstrap5', true);

        $this->assertSame(ViewPublisher::SKIPPED, $result['status']);
        $this->assertSame('<?php // mine', file_get_contents($this->appPath . '/Config/Forms.php'));
    }

    public function testPublishedViewsPath(): void
    {
        $this->assertSame('forms/bootstrap5', $this->publisher->publishedViewsPath('bootstrap5'));
    }

    private function assertValidPhp(string $source): void
    {
        $tokens = token_get_all($source, TOKEN_PARSE);

        $this->assertNotEmpty($tokens);
    }

    private function removeDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        $items = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }

        rmdir($directory);
    }
}
