<?php

declare(strict_types=1);

namespace Ephraitech\Forms\Fields;

use Ephraitech\Forms\Config\Forms;

/**
 * Colour picker. Browsers always submit a value (#000000 when untouched) in
 * lowercase #rrggbb form, so "required" has no effect; validate the format
 * with regex_match[/^#[0-9a-f]{6}$/] if the value matters. Submitted values
 * are lower-cased.
 */
class Color extends InputField
{
    public const TYPE = 'color';

    public const INPUT_TYPE = 'color';

    public function ruleAttributes(Forms $config): array
    {
        return [];
    }

    protected function normaliseScalar(mixed $value, Forms $config): mixed
    {
        $value = parent::normaliseScalar($value, $config);

        return is_string($value) ? strtolower($value) : $value;
    }
}
