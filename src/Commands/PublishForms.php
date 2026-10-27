<?php

declare(strict_types=1);

namespace Ephraitech\Forms\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Ephraitech\Forms\Exceptions\FormException;
use Ephraitech\Forms\Publishing\ViewPublisher;

/**
 * php spark forms:publish [--theme bootstrap5] [--config] [--force]
 */
class PublishForms extends BaseCommand
{
    /**
     * @var string
     */
    protected $group = 'Forms';

    /**
     * @var string
     */
    protected $name = 'forms:publish';

    /**
     * @var string
     */
    protected $description = 'Copies the form views (and optionally a config file) into the app for customisation.';

    /**
     * @var string
     */
    protected $usage = 'forms:publish [options]';

    /**
     * @var array<string, string>
     */
    protected $options = [
        '--theme'  => 'Theme to publish (default: bootstrap5).',
        '--config' => 'Also create app/Config/Forms.php pointing the theme at the published views.',
        '--force'  => 'Overwrite views that were published before. Your changes to them are lost.',
    ];

    /**
     * @param array<int|string, string|null> $params
     */
    public function run(array $params): int
    {
        $theme = $this->option($params, 'theme');
        $theme = is_string($theme) && $theme !== '' ? $theme : 'bootstrap5';

        $publisher = new ViewPublisher();

        try {
            $results = $publisher->publishViews($theme, APPPATH, $this->flag($params, 'force'));
        } catch (FormException $e) {
            CLI::error($e->getMessage());

            return EXIT_ERROR;
        }

        CLI::write('Views for theme "' . $theme . '":', 'yellow');

        foreach ($results as $result) {
            $this->report($result['status'], $result['file']);
        }

        if (in_array(ViewPublisher::SKIPPED, array_column($results, 'status'), true)) {
            CLI::write('Skipped files already exist. Use --force to overwrite them.', 'light_gray');
        }

        $viewsLine = sprintf("\$this->themes['%s']['views'] = '%s';", $theme, $publisher->publishedViewsPath($theme));

        if ($this->flag($params, 'config')) {
            try {
                $config = $publisher->publishConfig(APPPATH, $theme, true);
            } catch (FormException $e) {
                CLI::error($e->getMessage());

                return EXIT_ERROR;
            }

            CLI::newLine();
            CLI::write('Config:', 'yellow');
            $this->report($config['status'], $config['file']);

            if ($config['status'] === ViewPublisher::CREATED) {
                CLI::newLine();
                CLI::write('Done. Forms now render from ' . $this->display(APPPATH . 'Views/' . $publisher->publishedViewsPath($theme)) . '.', 'green');

                return EXIT_SUCCESS;
            }
        }

        CLI::newLine();
        CLI::write('To render from the published views, add this to the constructor of app/Config/Forms.php', 'yellow');
        CLI::write('(after parent::__construct()), or rerun with --config if that file does not exist yet:');
        CLI::write('    ' . $viewsLine, 'green');

        return EXIT_SUCCESS;
    }

    /**
     * @param array<int|string, string|null> $params
     */
    private function option(array $params, string $name): mixed
    {
        return array_key_exists($name, $params) ? $params[$name] : CLI::getOption($name);
    }

    /**
     * @param array<int|string, string|null> $params
     */
    private function flag(array $params, string $name): bool
    {
        return array_key_exists($name, $params) || CLI::getOption($name) !== null;
    }

    private function report(string $status, string $file): void
    {
        $colour = match ($status) {
            ViewPublisher::CREATED     => 'green',
            ViewPublisher::OVERWRITTEN => 'yellow',
            default                    => 'light_gray',
        };

        CLI::write('  ' . CLI::color(str_pad($status, 12), $colour) . $this->display($file));
    }

    private function display(string $path): string
    {
        $root = rtrim(str_replace('\\', '/', ROOTPATH), '/') . '/';
        $path = str_replace('\\', '/', $path);

        return str_starts_with($path, $root) ? substr($path, strlen($root)) : $path;
    }
}
