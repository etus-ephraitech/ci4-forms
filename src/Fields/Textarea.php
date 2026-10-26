<?php

declare(strict_types=1);

namespace Ephraitech\Forms\Fields;

/**
 * Multi-line text area.
 */
class Textarea extends Field
{
    public const TYPE = 'textarea';

    public const VIEW = 'field_textarea';

    public function rows(int $rows): static
    {
        return $this->attribute('rows', max(1, $rows));
    }

    protected function extraRuleAttributes(): array
    {
        return $this->lengthRuleAttributes();
    }
}
