<?php

declare(strict_types=1);

namespace Ephraitech\Forms\Fields;

/**
 * Time input. Browsers submit H:i (or H:i:s when step() is below 60
 * seconds), so validate with valid_date[H:i]; min() and max() take the same
 * format.
 */
class Time extends DateTimeInputField
{
    public const TYPE = 'time';

    public const INPUT_TYPE = 'time';
}
