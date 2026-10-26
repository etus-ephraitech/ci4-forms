<?php

declare(strict_types=1);

namespace Ephraitech\Forms\Fields;

/**
 * Date input. Browsers submit dates as Y-m-d, so validate with
 * valid_date[Y-m-d].
 */
class Date extends InputField
{
    public const TYPE = 'date';

    public const INPUT_TYPE = 'date';

    /**
     * Earliest selectable date (Y-m-d). Browser hint only; enforce limits
     * with validation rules as well.
     */
    public function min(string $date): static
    {
        return $this->attribute('min', $date);
    }

    /**
     * Latest selectable date (Y-m-d). Browser hint only.
     */
    public function max(string $date): static
    {
        return $this->attribute('max', $date);
    }

    protected function extraRuleAttributes(): array
    {
        return [];
    }
}
