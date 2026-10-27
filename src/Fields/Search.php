<?php

declare(strict_types=1);

namespace Ephraitech\Forms\Fields;

/**
 * Search box: a text input that browsers style for searching, often with a
 * clear button. Usually used on GET forms.
 */
class Search extends InputField
{
    public const TYPE = 'search';

    public const INPUT_TYPE = 'search';
}
