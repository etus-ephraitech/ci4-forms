<?php

declare(strict_types=1);

namespace Ephraitech\Forms\Fields;

/**
 * Date input. Browsers submit dates as Y-m-d, so validate with
 * valid_date[Y-m-d]; min() and max() take the same format.
 */
class Date extends DateTimeInputField
{
    public const TYPE = 'date';

    public const INPUT_TYPE = 'date';
}
