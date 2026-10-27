<?php

declare(strict_types=1);

namespace Ephraitech\Forms\Fields;

use Ephraitech\Forms\Contracts\ChoiceFieldInterface;
use Ephraitech\Forms\Fields\Concerns\HasOptions;

/**
 * A group of radio buttons sharing one name; the user picks one option.
 */
class Radio extends Field implements ChoiceFieldInterface
{
    use HasOptions;

    public const TYPE = 'radio';

    public const VIEW = 'field_radio';

    protected bool $inline = false;

    /**
     * Lay the options out on one line instead of stacking them.
     */
    public function inline(bool $inline = true): static
    {
        $this->inline = $inline;

        return $this;
    }

    public function isInline(): bool
    {
        return $this->inline;
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'options' => $this->getOptions(),
            'inline'  => $this->inline,
        ]);
    }
}
