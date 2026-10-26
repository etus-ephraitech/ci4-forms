<?php

declare(strict_types=1);

namespace Ephraitech\Forms\Fields;

/**
 * Telephone number input. Shows a numeric keypad on mobile; it does not
 * validate the format.
 */
class Tel extends InputField
{
    public const TYPE = 'tel';

    public const INPUT_TYPE = 'tel';
}
