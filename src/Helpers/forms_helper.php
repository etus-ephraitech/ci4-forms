<?php

declare(strict_types=1);

/**
 * Helpers for ephraitech/ci4-forms. Load with helper('forms').
 *
 * Every function is prefixed "forms_" so nothing collides with
 * CodeIgniter's own form helper (form_open(), form_input(), ...). They are
 * shortcuts; the classes remain the full API.
 */

use Ephraitech\Forms\Form;
use Ephraitech\Forms\Schema\FormSchema;
use Ephraitech\Forms\Schema\SchemaCheck;
use Ephraitech\Forms\Schema\SchemaException;
use Ephraitech\Forms\Schema\SchemaPolicy;

if (! function_exists('forms_load')) {
    /**
     * Builds a form from a schema given as an array or as JSON text.
     * Restricted policy unless another is passed.
     *
     * @param array<array-key, mixed>|string $schema
     *
     * @throws SchemaException
     */
    function forms_load(array|string $schema, ?SchemaPolicy $policy = null): Form
    {
        return FormSchema::load($schema, $policy);
    }
}

if (! function_exists('forms_check')) {
    /**
     * Checks a schema given as an array or as JSON text, without throwing.
     * Restricted policy unless another is passed.
     *
     * @param array<array-key, mixed>|string $schema
     */
    function forms_check(array|string $schema, ?SchemaPolicy $policy = null): SchemaCheck
    {
        return FormSchema::check($schema, $policy);
    }
}

if (! function_exists('forms_present')) {
    /**
     * Stored data shown with the form's labels and option texts, as HTML,
     * for example a submission on an admin screen:
     *
     *     <?= forms_present($form, json_decode($submission['payload'], true)) ?>
     *
     * Use $form->present($data) for the rows themselves.
     *
     * @param array<array-key, mixed>|object $data
     */
    function forms_present(Form $form, array|object $data, bool $includeEmpty = true): string
    {
        return $form->renderPresented($data, $includeEmpty);
    }
}
