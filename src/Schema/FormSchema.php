<?php

declare(strict_types=1);

namespace Ephraitech\Forms\Schema;

use Ephraitech\Forms\Config\Forms;
use Ephraitech\Forms\Form;

/**
 * Entry points for working with schemas outside a form: checking what an
 * admin typed before saving it, and loading or exporting.
 *
 *     // Admin "save" action
 *     $check = FormSchema::check($this->request->getPost('schema'));
 *
 *     if (! $check->isValid()) {
 *         return redirect()->back()->withInput()->with('schema_errors', $check->getMessages());
 *     }
 *
 *     $this->formModel->update($id, ['schema' => json_encode($check->getSchema())]);
 *
 * Checking is restricted by default, like loading.
 */
final class FormSchema
{
    /**
     * Checks a schema (decoded array or JSON text) without throwing and
     * without calling any option provider.
     *
     * @param array<array-key, mixed>|string $schema
     */
    public static function check(array|string $schema, ?SchemaPolicy $policy = null, ?Forms $config = null): SchemaCheck
    {
        if (is_string($schema)) {
            try {
                $schema = SchemaLoader::decodeJson($schema);
            } catch (SchemaException $e) {
                return new SchemaCheck($e->getErrors(), null);
            }
        }

        $errors = (new SchemaLoader($config ?? self::config(), $policy))->errors($schema);

        return new SchemaCheck($errors, $schema);
    }

    /**
     * Loads a schema (decoded array or JSON text) into a new form.
     *
     * @param array<array-key, mixed>|string $schema
     *
     * @throws SchemaException
     */
    public static function load(array|string $schema, ?SchemaPolicy $policy = null, ?Forms $config = null): Form
    {
        return is_string($schema)
            ? Form::fromJson($schema, $config, $policy)
            : Form::fromSchema($schema, $config, $policy);
    }

    /**
     * What a schema may contain under a policy (restricted by default): the
     * allowed field types with their keys and value kinds, and the allowed
     * rules, attributes, option providers, upload types and limits. Plain
     * data, ready for json_encode(), for visual builders and help pages.
     *
     * @return array<string, mixed>
     */
    public static function describe(?SchemaPolicy $policy = null, ?Forms $config = null): array
    {
        return (new SchemaDescriber($config ?? self::config(), $policy ?? SchemaPolicy::restricted()))->describe();
    }

    /**
     * Exports a form as a schema array.
     *
     * @return array<string, mixed>
     *
     * @throws SchemaException When part of the form cannot be written as data.
     */
    public static function export(Form $form): array
    {
        return (new SchemaExporter())->export($form);
    }

    private static function config(): Forms
    {
        $class = class_exists(\Config\Forms::class) && is_a(\Config\Forms::class, Forms::class, true)
            ? \Config\Forms::class
            : Forms::class;

        $config = config($class);

        return $config instanceof Forms ? $config : new Forms();
    }
}
