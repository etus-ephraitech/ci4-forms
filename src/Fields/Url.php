<?php

declare(strict_types=1);

namespace Ephraitech\Forms\Fields;

/**
 * URL input. Pair it with the valid_url_strict rule for server-side
 * checking.
 */
class Url extends InputField
{
    public const TYPE = 'url';

    public const INPUT_TYPE = 'url';
}
