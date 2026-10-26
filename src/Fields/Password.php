<?php

declare(strict_types=1);

namespace Ephraitech\Forms\Fields;

/**
 * Password input.
 *
 * Passwords are never written back into the form after a failed submission
 * or from filled data, and they are never trimmed, because leading or
 * trailing spaces may be intentional.
 */
class Password extends InputField
{
    public const TYPE = 'password';

    public const INPUT_TYPE = 'password';

    public function shouldRepopulate(): bool
    {
        return false;
    }

    protected function trimsValue(): bool
    {
        return false;
    }
}
