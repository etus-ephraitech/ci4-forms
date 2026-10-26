<?php

declare(strict_types=1);

namespace Ephraitech\Forms\Fields;

use Ephraitech\Forms\Fields\Concerns\HasOptions;

/**
 * A group of radio buttons sharing one name; the user picks one option.
 */
class Radio extends Field
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

    /**
     * Id of one option's input, used to link its label.
     */
    public function getOptionId(string $value, string $prefix = ''): string
    {
        $suffix = (string) preg_replace('/[^A-Za-z0-9_\-]/', '_', $value);

        return $this->getId($prefix) . '_' . ($suffix === '' ? 'empty' : $suffix);
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'options' => $this->getOptions(),
            'inline'  => $this->inline,
        ]);
    }
}
