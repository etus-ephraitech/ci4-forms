<?php

declare(strict_types=1);

namespace Ephraitech\Forms\Fields\Concerns;

/**
 * Per-field override of the $strictOptions config setting.
 */
trait HasStrictOptions
{
    protected ?bool $strictOptions = null;

    /**
     * Whether submitted values must be among the field's options. Turn it
     * off only when options are added in the browser (for example by
     * JavaScript), and validate the value with rules instead.
     */
    public function strictOptions(bool $strict = true): static
    {
        $this->strictOptions = $strict;

        return $this;
    }

    public function getStrictOptions(): ?bool
    {
        return $this->strictOptions;
    }
}
