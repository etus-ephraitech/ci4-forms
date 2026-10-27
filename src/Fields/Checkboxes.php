<?php

declare(strict_types=1);

namespace Ephraitech\Forms\Fields;

use Ephraitech\Forms\Config\Forms;
use Ephraitech\Forms\Contracts\ChoiceFieldInterface;
use Ephraitech\Forms\Fields\Concerns\HasOptions;

/**
 * A group of checkboxes sharing one name; the user ticks any number of
 * options. Submitted as name[] and returned by validated() as a list, which
 * is empty when nothing is ticked.
 *
 * rules() applies to the list as a whole ("required" means at least one
 * ticked); eachRules() applies to every ticked value.
 */
class Checkboxes extends Field implements ChoiceFieldInterface
{
    use HasOptions;

    public const TYPE = 'checkboxes';

    public const VIEW = 'field_checkboxes';

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

    public function isMultiple(): bool
    {
        return true;
    }

    /**
     * No rule-derived attributes: a "required" attribute on each checkbox
     * would make the browser demand that every box is ticked.
     */
    public function ruleAttributes(Forms $config): array
    {
        return [];
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'options' => $this->getOptions(),
            'inline'  => $this->inline,
        ]);
    }
}
