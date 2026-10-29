<?php

declare(strict_types=1);

namespace Ephraitech\Forms\Tests\Docs;

use CodeIgniter\Test\CIUnitTestCase;
use Ephraitech\Forms\Commands\FormSchemaCommand;
use Ephraitech\Forms\Commands\PublishForms;
use Ephraitech\Forms\Config\Forms;
use Ephraitech\Forms\Fields;
use Ephraitech\Forms\Form;
use Ephraitech\Forms\Layout;
use Ephraitech\Forms\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

/**
 * Keeps the hand-written reference pages and the code in step: every public
 * method and setting must be documented, and everything documented must
 * exist. A method row in a reference page starts with "| `name(" (or
 * "| <code>name(" when the signature contains a "|").
 *
 * @internal
 */
final class ApiReferenceTest extends CIUnitTestCase
{
    private const DOCS = __DIR__ . '/../../docs/reference/';

    /**
     * @return array<string, array{string, list<class-string>}>
     */
    public static function pages(): array
    {
        return [
            'form'    => ['form.md', [Form::class]],
            'fields'  => ['fields.md', [
                Fields\Field::class, Fields\InputField::class, Fields\DateTimeInputField::class, Fields\Number::class,
                Fields\Textarea::class, Fields\Select::class, Fields\Radio::class, Fields\Checkboxes::class,
                Fields\Checkbox::class, Fields\File::class, Fields\Submit::class, Fields\Password::class,
                Fields\Color::class, Fields\Hidden::class, Layout\Container::class, Layout\Fieldset::class,
            ]],
            'schemas' => ['schemas.md', [Schema\FormSchema::class, Schema\SchemaCheck::class, Schema\SchemaPolicy::class, Schema\SchemaException::class]],
        ];
    }

    /**
     * @return list<string>
     */
    private function documentedMethods(string $page): array
    {
        preg_match_all('/^\|\s*(?:`|<code>)(\w+)\(/m', (string) file_get_contents(self::DOCS . $page), $matches);

        return array_values(array_unique($matches[1]));
    }

    /**
     * @param list<class-string> $classes
     */
    #[DataProvider('pages')]
    public function testEveryPublicMethodIsDocumented(string $page, array $classes): void
    {
        $documented = $this->documentedMethods($page);
        $missing    = [];

        foreach ($classes as $class) {
            foreach ((new ReflectionClass($class))->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                $internal = str_contains((string) $method->getDocComment(), '@internal');

                if ($method->getDeclaringClass()->getName() !== $class || str_starts_with($method->getName(), '__') || $internal) {
                    continue;
                }

                if (! in_array($method->getName(), $documented, true)) {
                    $missing[] = $class . '::' . $method->getName() . '()';
                }
            }
        }

        $this->assertSame([], $missing, 'Public methods missing from docs/reference/' . $page);
    }

    /**
     * @param list<class-string> $classes
     */
    #[DataProvider('pages')]
    public function testEverythingDocumentedExists(string $page, array $classes): void
    {
        $unknown = array_values(array_filter(
            $this->documentedMethods($page),
            static function (string $name) use ($classes): bool {
                foreach ($classes as $class) {
                    if (method_exists($class, $name)) {
                        return false;
                    }
                }

                return true;
            },
        ));

        $this->assertSame([], $unknown, 'Documented in docs/reference/' . $page . ' but not in the code');
    }

    public function testEverySettingIsDocumented(): void
    {
        $page       = (string) file_get_contents(self::DOCS . 'configuration.md');
        $properties = array_map(
            static fn (ReflectionProperty $property): string => $property->getName(),
            array_filter(
                (new ReflectionClass(Forms::class))->getProperties(ReflectionProperty::IS_PUBLIC),
                static fn (ReflectionProperty $property): bool => $property->getDeclaringClass()->getName() === Forms::class,
            ),
        );

        $missing = array_values(array_filter($properties, static fn (string $name): bool => ! str_contains($page, '| `$' . $name . '`')));
        $this->assertSame([], $missing, 'Settings missing from docs/reference/configuration.md');

        preg_match_all('/^\|\s*`\$(\w+)`/m', $page, $matches);
        $unknown = array_values(array_diff($matches[1], $properties));
        $this->assertSame([], $unknown, 'Settings documented but not in Config\Forms');
    }

    public function testEveryThemeClassKeyIsDocumented(): void
    {
        $page = (string) file_get_contents(self::DOCS . 'configuration.md');
        $keys = array_keys((new Forms())->themes['bootstrap5']['classes']);

        $missing = array_values(array_filter($keys, static fn (string $key): bool => ! str_contains($page, '| `' . $key . '` |')));
        $this->assertSame([], $missing, 'Theme class keys missing from docs/reference/configuration.md');

        $section = substr($page, (int) strpos($page, '### Class keys'));
        preg_match_all('/^\|\s*`(\w+)`\s*\|/m', $section, $matches);
        $this->assertSame([], array_values(array_diff($matches[1], $keys)), 'Class keys documented but not in the Bootstrap 5 theme');
    }

    public function testHelpersAreDocumentedAndExist(): void
    {
        helper('forms');

        $page = (string) file_get_contents(self::DOCS . 'helpers-and-commands.md');
        preg_match_all('/^\|\s*(?:`|<code>)(forms_\w+)\(/m', $page, $matches);

        $this->assertSame(['forms_load', 'forms_check', 'forms_present'], $matches[1]);

        foreach ($matches[1] as $function) {
            $this->assertTrue(function_exists($function), $function . '() is documented but does not exist');
        }

        $source = (string) file_get_contents(__DIR__ . '/../../src/Helpers/forms_helper.php');
        preg_match_all("/function_exists\\('(forms_\\w+)'\\)/", $source, $defined);
        $this->assertSame($defined[1], $matches[1], 'Every helper function is documented');
    }

    public function testCommandOptionsAreDocumented(): void
    {
        $page = (string) file_get_contents(self::DOCS . 'helpers-and-commands.md');

        foreach ([PublishForms::class, FormSchemaCommand::class] as $command) {
            $options = (new ReflectionClass($command))->getProperty('options')->getDefaultValue();
            $name    = (new ReflectionClass($command))->getProperty('name')->getDefaultValue();

            $this->assertStringContainsString('### ' . $name, $page);

            foreach (array_keys($options) as $option) {
                $this->assertStringContainsString('| `' . $option . '` |', $page, $name . ' option ' . $option . ' is not documented');
            }
        }
    }
}
