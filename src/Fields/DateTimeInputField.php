<?php

declare(strict_types=1);

namespace Ephraitech\Forms\Fields;

/**
 * Base for date and time pickers: date, time and datetime-local.
 *
 * min() and max() are browser hints in the input's own format; enforce
 * limits with validation rules as well. Length rules never become
 * attributes, since they do not apply to these inputs.
 */
abstract class DateTimeInputField extends InputField
{
    /**
     * Earliest selectable value, in the input's format.
     */
    public function min(string $value): static
    {
        return $this->attribute('min', $value);
    }

    /**
     * Latest selectable value, in the input's format.
     */
    public function max(string $value): static
    {
        return $this->attribute('max', $value);
    }

    /**
     * Interval between selectable values, in seconds for time inputs and
     * days for date inputs; 'any' allows every value.
     *
     * @param int|'any' $step
     */
    public function step(int|string $step): static
    {
        return $this->attribute('step', $step);
    }

    protected function extraRuleAttributes(): array
    {
        return [];
    }
}
