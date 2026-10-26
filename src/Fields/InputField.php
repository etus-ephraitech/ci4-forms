<?php

declare(strict_types=1);

namespace Ephraitech\Forms\Fields;

/**
 * Base for fields rendered as a single <input> element.
 */
abstract class InputField extends Field
{
    public const VIEW = 'field_input';

    /**
     * Value of the input's type attribute.
     */
    public const INPUT_TYPE = 'text';

    public function getInputType(): string
    {
        return static::INPUT_TYPE;
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), ['input_type' => $this->getInputType()]);
    }

    protected function extraRuleAttributes(): array
    {
        return $this->lengthRuleAttributes();
    }
}
