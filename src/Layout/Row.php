<?php

declare(strict_types=1);

namespace Ephraitech\Forms\Layout;

/**
 * Places fields side by side. Each field takes the columns set with
 * ->width() (out of 12); fields without a width share the remaining space
 * equally. On small screens the theme stacks the columns.
 */
class Row extends Container
{
    public const TYPE = 'row';
}
