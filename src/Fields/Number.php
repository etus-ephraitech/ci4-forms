<?php

declare(strict_types=1);

namespace Ephraitech\Forms\Fields;

use Ephraitech\Forms\Support\RuleParser;

/**
 * Numeric input.
 *
 * min and max are derived from the greater_than_equal_to and
 * less_than_equal_to rules; step can be set explicitly. Submitted values are
 * kept as strings so precision is never lost before reaching the database.
 */
class Number extends InputField
{
    public const TYPE = 'number';

    public const INPUT_TYPE = 'number';

    /**
     * @param float|int|'any' $step
     */
    public function step(float|int|string $step): static
    {
        return $this->attribute('step', $step);
    }

    protected function extraRuleAttributes(): array
    {
        $attributes = [];

        $min = RuleParser::numericParamOf($this->rules, 'greater_than_equal_to');
        $max = RuleParser::numericParamOf($this->rules, 'less_than_equal_to');

        if ($min !== null) {
            $attributes['min'] = $min;
        }

        if ($max !== null) {
            $attributes['max'] = $max;
        }

        return $attributes;
    }
}
