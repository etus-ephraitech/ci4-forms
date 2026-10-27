<?php

declare(strict_types=1);

namespace Ephraitech\Forms\Fields;

use Ephraitech\Forms\Fields\Concerns\HasOptions;

/**
 * Dropdown list, single or multiple.
 *
 * Options may be grouped: ['Suits' => ['1' => 'Two-piece', '2' => 'Three-piece']]
 * renders an <optgroup>. The placeholder becomes an empty first option on
 * single selects. A multiple select is submitted as name[] and returns a list
 * from validated(); use eachRules() for rules on every selected value.
 * Validate submitted values against the options, for example with in_list or
 * is_not_unique, because the browser list can be bypassed.
 */
class Select extends Field
{
    use HasOptions;

    public const TYPE = 'select';

    public const VIEW = 'field_select';

    protected bool $multiple = false;

    /**
     * Lets the user choose several options.
     */
    public function multiple(bool $multiple = true): static
    {
        $this->multiple = $multiple;

        return $this;
    }

    public function isMultiple(): bool
    {
        return $this->multiple;
    }

    /**
     * Number of visible rows for a multiple select.
     */
    public function size(int $rows): static
    {
        return $this->attribute('size', max(1, $rows));
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), ['options' => $this->getOptions()]);
    }

    protected function allowsOptionGroups(): bool
    {
        return true;
    }
}
