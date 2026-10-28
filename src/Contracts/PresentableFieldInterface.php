<?php

declare(strict_types=1);

namespace Ephraitech\Forms\Contracts;

use Ephraitech\Forms\Config\Forms;

/**
 * A field that can turn a stored value into text people read, for screens
 * that show submissions: option labels instead of option values, "Yes"
 * instead of 1. Every field extending Fields\Field implements this.
 */
interface PresentableFieldInterface
{
    /**
     * Whether the field is shown when presenting stored data. False for
     * passwords, buttons and uploads.
     */
    public function isPresentable(): bool;

    /**
     * The value as text, or a list of texts for multi-value fields. Empty
     * values become '' (or an empty list).
     *
     * @return list<string>|string
     */
    public function presentValue(mixed $value, Forms $config): array|string;
}
