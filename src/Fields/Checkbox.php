<?php

declare(strict_types=1);

namespace Ephraitech\Forms\Fields;

use Ephraitech\Forms\Config\Forms;

/**
 * A single on/off checkbox, such as "Active" or "I accept the terms".
 *
 * Browsers send nothing for an unticked checkbox, so validated() fills in the
 * configured unchecked value (0 by default) instead of dropping the key.
 */
class Checkbox extends Field
{
    public const TYPE = 'checkbox';

    public const VIEW = 'field_checkbox';

    protected string $checkedValue = '1';

    protected bool $inline = false;

    /**
     * Value sent when the box is ticked. Defaults to "1".
     */
    public function checkedValue(int|string $value): static
    {
        $this->checkedValue = (string) $value;

        return $this;
    }

    public function getCheckedValue(): string
    {
        return $this->checkedValue;
    }

    public function inline(bool $inline = true): static
    {
        $this->inline = $inline;

        return $this;
    }

    public function isInline(): bool
    {
        return $this->inline;
    }

    /**
     * Whether a resolved value means the box should be ticked.
     */
    public function isChecked(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (! is_scalar($value)) {
            return false;
        }

        return (string) $value === $this->checkedValue;
    }

    /**
     * A tampered array value counts as unticked.
     */
    public function normaliseValue(mixed $value, Forms $config): mixed
    {
        if (is_array($value)) {
            return $this->valueWhenMissing($config);
        }

        return parent::normaliseValue($value, $config);
    }

    public function valueWhenMissing(Forms $config): mixed
    {
        return $config->checkboxUncheckedValue;
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'checked_value' => $this->checkedValue,
            'inline'        => $this->inline,
        ]);
    }

    protected function trimsValue(): bool
    {
        return false;
    }
}
