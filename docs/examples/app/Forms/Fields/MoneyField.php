<?php

declare(strict_types=1);

namespace App\Forms\Fields;

use Ephraitech\Forms\Config\Forms;
use Ephraitech\Forms\Contracts\SchemaConfigurableInterface;
use Ephraitech\Forms\Fields\InputField;

/**
 * An amount with a currency in front, such as "UGX 1,500,000".
 */
class MoneyField extends InputField implements SchemaConfigurableInterface
{
    public const TYPE = 'money';

    // A view name with a path is loaded from app/Views, not from the theme.
    public const VIEW = 'forms/fields/money';

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

    // Shows a numeric keypad on phones.
    public function getAttributes(): array
    {
        return array_merge(['inputmode' => 'decimal'], parent::getAttributes());
    }

    // "1,500,000" or "1 500 000" becomes "1500000" before validation and saving.
    protected function normaliseScalar(mixed $value, Forms $config): mixed
    {
        if (is_string($value)) {
            $value = str_replace([',', ' '], '', $value);
        }

        return parent::normaliseScalar($value, $config);
    }

    // "UGX 1,500,000" when showing stored data with $form->present().
    public function presentValue(mixed $value, Forms $config): array|string
    {
        return is_numeric($value) ? $this->currency . ' ' . number_format((float) $value) : parent::presentValue($value, $config);
    }

    // Lets schemas use {"type": "money", "currency": "USD"}.
    public static function schemaProperties(): array
    {
        return ['currency' => 'currency'];
    }

    public function schemaValues(): array
    {
        return $this->currency === 'UGX' ? [] : ['currency' => $this->currency];
    }
}
