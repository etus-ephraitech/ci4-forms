<?php

declare(strict_types=1);

namespace Ephraitech\Forms\Tests\Support\Fields;

use Ephraitech\Forms\Config\Forms;
use Ephraitech\Forms\Fields\InputField;

/**
 * Example custom field: an amount with a currency prefix and its own view.
 * Accepts "1,250,000" and returns "1250000".
 */
class MoneyField extends InputField
{
    public const TYPE = 'money';

    public const VIEW = 'FormsTestViews\money_field';

    public const INPUT_TYPE = 'text';

    protected string $currency = 'UGX';

    public function currency(string $currency): static
    {
        $this->currency = $currency;

        return $this;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getAttributes(): array
    {
        return array_merge(['inputmode' => 'decimal'], parent::getAttributes());
    }

    protected function normaliseScalar(mixed $value, Forms $config): mixed
    {
        if (is_string($value)) {
            $value = str_replace([',', ' '], '', $value);
        }

        return parent::normaliseScalar($value, $config);
    }
}
