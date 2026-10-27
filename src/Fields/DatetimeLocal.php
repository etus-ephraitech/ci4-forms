<?php

declare(strict_types=1);

namespace Ephraitech\Forms\Fields;

/**
 * Date and time input without a time zone. Browsers submit Y-m-d\TH:i, so
 * validate with valid_date[Y-m-d\TH:i]; min() and max() take the same
 * format, for example '2026-11-01T09:00'.
 */
class DatetimeLocal extends DateTimeInputField
{
    public const TYPE = 'datetime_local';

    public const INPUT_TYPE = 'datetime-local';
}
