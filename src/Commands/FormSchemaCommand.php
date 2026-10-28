<?php

declare(strict_types=1);

namespace Ephraitech\Forms\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Ephraitech\Forms\Form;
use Ephraitech\Forms\Schema\FormSchema;
use Ephraitech\Forms\Schema\SchemaException;
use Ephraitech\Forms\Schema\SchemaPolicy;
use Throwable;

/**
 * php spark forms:schema check <file> [--trusted]
 * php spark forms:schema export <FormClass> [--output <file>]
 */
class FormSchemaCommand extends BaseCommand
{
    /**
     * @var string
     */
    protected $group = 'Forms';

    /**
     * @var string
     */
    protected $name = 'forms:schema';

    /**
     * @var string
     */
    protected $description = 'Checks a form schema file, or exports a form class as a schema.';

    /**
     * @var string
     */
    protected $usage = 'forms:schema check <file> [--trusted] | forms:schema export <FormClass> [--output <file>]';

    /**
     * @var array<string, string>
     */
    protected $arguments = [
        'action' => '"check" a schema file or "export" a form class.',
        'target' => 'A JSON schema file (check) or a form class name such as App\\Forms\\BookingForm (export).',
    ];

    /**
     * @var array<string, string>
     */
    protected $options = [
        '--trusted' => 'check: use the trusted policy (developer-written schema) instead of the restricted one.',
        '--output'  => 'export: write the schema to this file instead of printing it.',
    ];

    /**
     * @param array<int|string, string|null> $params
     */
    public function run(array $params): int
    {
        $action = $params[0] ?? null;
        $target = $params[1] ?? null;

        if (! in_array($action, ['check', 'export'], true) || ! is_string($target) || $target === '') {
            CLI::error('Usage: php spark ' . $this->usage);

            return EXIT_USER_INPUT;
        }

        return $action === 'check' ? $this->check($target, $params) : $this->export($target, $params);
    }

    /**
     * @param array<int|string, string|null> $params
     */
    private function check(string $file, array $params): int
    {
        $path = $this->resolvePath($file);

        if ($path === null) {
            CLI::error(sprintf('File not found: %s', $file));

            return EXIT_USER_INPUT;
        }

        $trusted = array_key_exists('trusted', $params) || CLI::getOption('trusted') !== null;
        $check   = FormSchema::check((string) file_get_contents($path), $trusted ? SchemaPolicy::trusted() : null);
        $label   = $trusted ? 'trusted' : 'restricted';

        if ($check->isValid()) {
            CLI::write(sprintf('%s is a valid schema (%s policy).', $file, $label), 'green');

            return EXIT_SUCCESS;
        }

        $count = count($check->getErrors());

        CLI::error(sprintf('%s has %d %s (%s policy):', $file, $count, $count === 1 ? 'error' : 'errors', $label));

        foreach ($check->getMessages() as $message) {
            CLI::write('  - ' . $message);
        }

        return EXIT_ERROR;
    }

    /**
     * @param array<int|string, string|null> $params
     */
    private function export(string $class, array $params): int
    {
        $class = ltrim(str_replace('/', '\\', $class), '\\');

        if (! class_exists($class) || ! is_a($class, Form::class, true)) {
            CLI::error(sprintf('%s is not a form class (it must extend %s).', $class, Form::class));

            return EXIT_USER_INPUT;
        }

        try {
            /** @var Form $form */
            $form = new $class();
            $json = $form->toJson();
        } catch (SchemaException $e) {
            CLI::error(sprintf('%s cannot be exported:', $class));

            foreach ($e->getMessages() as $message) {
                CLI::write('  - ' . $message);
            }

            return EXIT_ERROR;
        } catch (Throwable $e) {
            CLI::error(sprintf('%s could not be created: %s', $class, $e->getMessage()));

            return EXIT_ERROR;
        }

        $output = $params['output'] ?? CLI::getOption('output');

        if (! is_string($output) || $output === '') {
            CLI::write($json);

            return EXIT_SUCCESS;
        }

        if (@file_put_contents($output, $json . "\n") === false) {
            CLI::error(sprintf('Could not write %s.', $output));

            return EXIT_ERROR;
        }

        CLI::write(sprintf('Exported %s to %s.', $class, $output), 'green');

        return EXIT_SUCCESS;
    }

    private function resolvePath(string $file): ?string
    {
        foreach ([$file, ROOTPATH . ltrim($file, '/\\')] as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }
}
