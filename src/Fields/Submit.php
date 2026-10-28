<?php

declare(strict_types=1);

namespace Ephraitech\Forms\Fields;

use Ephraitech\Forms\Config\Forms;
use Ephraitech\Forms\Exceptions\FormException;

/**
 * Submit button. Never validated and never included in validated() data.
 */
class Submit extends Field
{
    public const TYPE = 'submit';

    public const VIEW = 'field_submit';

    public function __construct(string $label = 'Submit', string $name = 'submit')
    {
        parent::__construct($name, $label);
    }

    public function rules(array|string $rules): static
    {
        throw FormException::forRulesOnSubmit($this->name);
    }

    public function isSubmittable(): bool
    {
        return false;
    }

    /**
     * Never shown when presenting stored data.
     */
    public function isPresentable(): bool
    {
        return false;
    }

    public function shouldRepopulate(): bool
    {
        return false;
    }

    public function ruleAttributes(Forms $config): array
    {
        return [];
    }
}
