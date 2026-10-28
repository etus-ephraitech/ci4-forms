<?php

declare(strict_types=1);

namespace Ephraitech\Forms\Contracts;

/**
 * Lets a custom field type accept extra keys in form schemas, and export
 * them again.
 *
 * schemaProperties() maps schema key => setter method; the loader calls the
 * method with the key's value. A setter that rejects a value should throw a
 * FormException (or have a typed parameter), and the loader reports it at
 * the key's path. schemaValues() returns the current values of those keys
 * for Form::toSchema(); leave out keys that hold their default.
 *
 *     public static function schemaProperties(): array
 *     {
 *         return ['currency' => 'currency'];   // "currency": "USD" calls ->currency('USD')
 *     }
 *
 *     public function schemaValues(): array
 *     {
 *         return $this->currency === 'UGX' ? [] : ['currency' => $this->currency];
 *     }
 */
interface SchemaConfigurableInterface
{
    /**
     * @return array<string, string>
     */
    public static function schemaProperties(): array;

    /**
     * @return array<string, mixed>
     */
    public function schemaValues(): array;
}
