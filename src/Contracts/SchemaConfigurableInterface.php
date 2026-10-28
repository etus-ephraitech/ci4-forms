<?php

declare(strict_types=1);

namespace Ephraitech\Forms\Contracts;

/**
 * Lets a custom field type accept extra keys in form schemas.
 *
 * Return schema key => setter method; the loader calls the method with the
 * key's value. A setter that rejects a value should throw a FormException
 * (or have a typed parameter), and the loader reports it at the key's path.
 *
 *     public static function schemaProperties(): array
 *     {
 *         return ['currency' => 'currency'];   // "currency": "USD" calls ->currency('USD')
 *     }
 */
interface SchemaConfigurableInterface
{
    /**
     * @return array<string, string>
     */
    public static function schemaProperties(): array;
}
