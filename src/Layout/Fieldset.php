<?php

declare(strict_types=1);

namespace Ephraitech\Forms\Layout;

/**
 * A titled section of a form, rendered as <fieldset> with a <legend>, which
 * screen readers announce before each field inside it.
 */
class Fieldset extends Container
{
    public const TYPE = 'fieldset';

    protected ?string $description = null;

    protected ?string $id = null;

    public function __construct(protected string $legend)
    {
    }

    public function legend(string $legend): static
    {
        $this->legend = $legend;

        return $this;
    }

    /**
     * A short explanation shown under the legend.
     */
    public function description(?string $description): static
    {
        $this->description = $description === null || trim($description) === '' ? null : $description;

        return $this;
    }

    public function id(?string $id): static
    {
        $this->id = $id === null || trim($id) === '' ? null : trim($id);

        return $this;
    }

    public function getLegend(): string
    {
        return $this->legend;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'legend'      => $this->legend,
            'description' => $this->description,
            'id'          => $this->id,
        ]);
    }
}
