<?php

declare(strict_types=1);

namespace Ephraitech\Forms\Publishing;

use Ephraitech\Forms\Exceptions\FormException;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Copies the package's theme views, and optionally a config file, into an
 * application so they can be customised.
 *
 * Views are published to app/Views/forms/<theme>/, and the theme is pointed
 * at them with: $this->themes['<theme>']['views'] = 'forms/<theme>';
 */
final class ViewPublisher
{
    public const CREATED     = 'created';
    public const OVERWRITTEN = 'overwritten';
    public const SKIPPED     = 'skipped';

    private string $sourceRoot;

    /**
     * @param string|null $sourceRoot Folder holding one subfolder per theme;
     *                                defaults to the package's src/Views.
     */
    public function __construct(?string $sourceRoot = null)
    {
        $this->sourceRoot = rtrim($sourceRoot ?? dirname(__DIR__) . '/Views', '/\\');
    }

    /**
     * Themes shipped with the package.
     *
     * @return list<string>
     */
    public function availableThemes(): array
    {
        $themes = [];

        foreach (new FilesystemIterator($this->sourceRoot, FilesystemIterator::SKIP_DOTS) as $entry) {
            if ($entry instanceof SplFileInfo && $entry->isDir()) {
                $themes[] = $entry->getFilename();
            }
        }

        sort($themes);

        return $themes;
    }

    /**
     * The value for a theme's "views" setting once its views are published.
     */
    public function publishedViewsPath(string $theme): string
    {
        return 'forms/' . $theme;
    }

    /**
     * Copies a theme's views to <appPath>/Views/forms/<theme>/.
     *
     * Existing files are left untouched unless $force is true, so running
     * the command again never destroys customisations by accident.
     *
     * @return list<array{file: string, status: string}> One entry per file, in path order
     *
     * @throws FormException When the theme does not exist or a file cannot be written.
     */
    public function publishViews(string $theme, string $appPath, bool $force = false): array
    {
        $available = $this->availableThemes();

        if (! in_array($theme, $available, true)) {
            throw FormException::forUnknownTheme($theme, $available);
        }

        $source      = $this->sourceRoot . '/' . $theme;
        $destination = rtrim($appPath, '/\\') . '/Views/' . $this->publishedViewsPath($theme);

        $this->ensureDirectory($destination);

        $files = [];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if ($file instanceof SplFileInfo && $file->isFile()) {
                $files[] = substr($file->getPathname(), strlen($source) + 1);
            }
        }

        sort($files);

        $results = [];

        foreach ($files as $relative) {
            $target = $destination . '/' . str_replace('\\', '/', $relative);
            $exists = is_file($target);

            if ($exists && ! $force) {
                $results[] = ['file' => $target, 'status' => self::SKIPPED];

                continue;
            }

            $this->ensureDirectory(dirname($target));

            if (! @copy($source . '/' . $relative, $target)) {
                throw FormException::forPublishFailed($target, 'the file could not be copied.');
            }

            $results[] = ['file' => $target, 'status' => $exists ? self::OVERWRITTEN : self::CREATED];
        }

        return $results;
    }

    /**
     * Creates <appPath>/Config/Forms.php extending the package config. An
     * existing file is never overwritten.
     *
     * @return array{file: string, status: string}
     *
     * @throws FormException When the file cannot be written.
     */
    public function publishConfig(string $appPath, string $theme, bool $pointAtPublishedViews): array
    {
        $directory = rtrim($appPath, '/\\') . '/Config';
        $target    = $directory . '/Forms.php';

        if (is_file($target)) {
            return ['file' => $target, 'status' => self::SKIPPED];
        }

        $this->ensureDirectory($directory);

        if (@file_put_contents($target, $this->configTemplate($theme, $pointAtPublishedViews)) === false) {
            throw FormException::forPublishFailed($target, 'the file could not be written.');
        }

        return ['file' => $target, 'status' => self::CREATED];
    }

    /**
     * Source of the app config file.
     */
    public function configTemplate(string $theme, bool $pointAtPublishedViews): string
    {
        $themeKey  = var_export($theme, true);
        $viewsPath = var_export($this->publishedViewsPath($theme), true);

        $constructor = $pointAtPublishedViews
            ? <<<PHP

                    public function __construct()
                    {
                        parent::__construct();

                        // Views published with "php spark forms:publish".
                        \$this->themes[{$themeKey}]['views'] = {$viewsPath};
                    }

                PHP
            : '';

        return <<<PHP
            <?php

            declare(strict_types=1);

            namespace Config;

            use Ephraitech\\Forms\\Config\\Forms as BaseForms;

            /**
             * App settings for ephraitech/ci4-forms. Override only what differs from
             * the package defaults; see vendor/ephraitech/ci4-forms/src/Config/Forms.php.
             */
            class Forms extends BaseForms
            {
                public string \$theme = {$themeKey};
            {$constructor}}

            PHP;
    }

    private function ensureDirectory(string $directory): void
    {
        if (is_dir($directory)) {
            return;
        }

        if (! @mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw FormException::forPublishFailed($directory, 'the folder could not be created.');
        }
    }
}
