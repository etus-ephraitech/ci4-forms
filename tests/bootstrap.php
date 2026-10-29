<?php

declare(strict_types=1);

/*
 * PHPUnit bootstrap: CodeIgniter's own test bootstrap, plus the
 * documentation's example app.
 *
 * docs/examples/app holds the code shown in the documentation, laid out like
 * an app/ folder. The tests treat it as part of the app.
 */

namespace Config {
    use CodeIgniter\Config\AutoloadConfig;

    /*
     * CodeIgniter rebuilds its autoloader from Config\Autoload at the start of
     * every test, and only looks for App migrations in the App namespace's
     * paths. This is the framework app's Autoload, with docs/examples/app as
     * a second path for the App namespace, so the example migrations run with
     * the standard $migrate = true. CodeIgniter's Boot only loads its own
     * Autoload (and the files below) when this class does not exist yet.
     */
    $framework = __DIR__ . '/../vendor/codeigniter4/framework';

    require_once $framework . '/system/Config/AutoloadConfig.php';
    require_once $framework . '/system/Modules/Modules.php';
    require_once $framework . '/app/Config/Modules.php';

    class Autoload extends AutoloadConfig
    {
        public $psr4 = [
            APP_NAMESPACE => [APPPATH, __DIR__ . '/../docs/examples/app/'],
        ];
        public $classmap = [];
        public $files    = [];
        public $helpers  = [];
    }
}

namespace {
    require __DIR__ . '/../vendor/codeigniter4/framework/system/Test/bootstrap.php';

    /*
     * CodeIgniter looks for app views only in APPPATH/Views. Place the example
     * views there for the test run. Files that already exist (for example when
     * a test runs in a separate process) are left alone, and only files this
     * process created are removed at the end.
     */
    (static function (): void {
        $source  = __DIR__ . '/../docs/examples/app/Views';
        $target  = APPPATH . 'Views';
        $created = [];

        $items = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST,
        );

        foreach ($items as $item) {
            $path = $target . '/' . substr($item->getPathname(), strlen($source) + 1);

            if ($item->isDir()) {
                if (! is_dir($path)) {
                    mkdir($path, 0755, true);
                    $created[] = $path;
                }
            } elseif (! is_file($path)) {
                copy($item->getPathname(), $path);
                $created[] = $path;
            }
        }

        register_shutdown_function(static function () use ($created): void {
            foreach (array_reverse($created) as $path) {
                is_dir($path) ? @rmdir($path) : @unlink($path);
            }
        });
    })();
}
