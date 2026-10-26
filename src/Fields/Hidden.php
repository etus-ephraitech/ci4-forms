<?php

declare(strict_types=1);

namespace Ephraitech\Forms\Fields;

use Ephraitech\Forms\Config\Forms;

/**
 * Hidden input.
 *
 * Hidden values can be changed by anyone with browser dev tools, so always
 * validate them and never trust them for authorisation decisions.
 */
class Hidden extends InputField
{
    public const TYPE = 'hidden';

    public const VIEW = 'field_hidden';

    public const INPUT_TYPE = 'hidden';

    public function ruleAttributes(Forms $config): array
    {
        return [];
    }
}
