<?php

declare(strict_types=1);

namespace Ephraitech\Forms\Contracts;

/**
 * A field whose submitted value must be one of a fixed set of values:
 * selects, radios, checkbox groups and single checkboxes.
 *
 * When strict option checking applies, the form rejects any submitted value
 * (or, for multi-value fields, any item) that is not in getAllowedValues().
 */
interface ChoiceFieldInterface extends FieldInterface
{
    /**
     * Every value the field accepts, as strings.
     *
     * @return list<string>
     */
    public function getAllowedValues(): array;

    /**
     * True or false when set on the field; null to follow the
     * $strictOptions config setting.
     */
    public function getStrictOptions(): ?bool;
}
