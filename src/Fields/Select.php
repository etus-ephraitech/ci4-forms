<?php

declare(strict_types=1);

namespace Ephraitech\Forms\Fields;

use Ephraitech\Forms\Fields\Concerns\HasOptions;

/**
 * Dropdown list.
 *
 * Options may be grouped: ['Suits' => ['1' => 'Two-piece', '2' => 'Three-piece']]
 * renders an <optgroup>. The placeholder becomes an empty first option.
 * Validate the submitted value against the options, for example with
 * in_list or is_not_unique, because the browser list can be bypassed.
 */
class Select extends Field
{
    use HasOptions;

    public const TYPE = 'select';

    public const VIEW = 'field_select';

    public function toArray(): array
    {
        return array_merge(parent::toArray(), ['options' => $this->getOptions()]);
    }

    protected function allowsOptionGroups(): bool
    {
        return true;
    }
}
