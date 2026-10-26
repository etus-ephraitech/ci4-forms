<?php

declare(strict_types=1);

namespace Ephraitech\Forms\Fields;

/**
 * Email address input. Pair it with the valid_email rule; the input
 * type only helps browsers and mobile keyboards.
 */
class Email extends InputField
{
    public const TYPE = 'email';

    public const INPUT_TYPE = 'email';
}
