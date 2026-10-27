<?php

declare(strict_types=1);

namespace Ephraitech\Forms\Fields\Concerns;

use Closure;
use Ephraitech\Forms\Exceptions\FormException;
use Stringable;

/**
 * Option handling shared by select and radio fields.
 *
 * Options are value => label pairs. They can be given directly or as a
 * closure, which is called once, the first time the options are needed, so a
 * database query only runs when the form is actually rendered or validated.
 */
trait HasOptions
{
    /**
     * @var array<array-key, mixed>|Closure|null
     */
    protected array|Closure|null $optionSource = null;

    /**
     * @var array<string, array<string, string>|string>|null
     */
    protected ?array $resolvedOptions = null;

    /**
     * @param array<array-key, mixed>|Closure(): array<array-key, mixed> $options
     */
    public function options(array|Closure $options): static
    {
        $this->optionSource    = $options;
        $this->resolvedOptions = null;

        return $this;
    }

    /**
     * Resolved options with values cast to strings. For fields that allow
     * groups, a group label maps to its own value => label array.
     *
     * @return array<string, array<string, string>|string>
     */
    public function getOptions(): array
    {
        if ($this->resolvedOptions !== null) {
            return $this->resolvedOptions;
        }

        $source = $this->optionSource instanceof Closure
            ? ($this->optionSource)()
            : ($this->optionSource ?? []);

        if (! is_array($source)) {
            throw FormException::forInvalidOptions($this->getName(), 'the options closure must return an array.');
        }

        $options = [];

        foreach ($source as $value => $label) {
            if (is_array($label)) {
                if (! $this->allowsOptionGroups()) {
                    throw FormException::forInvalidOptions($this->getName(), 'option groups are not supported by this field type.');
                }

                $group = [];

                foreach ($label as $groupValue => $groupLabel) {
                    if (is_array($groupLabel)) {
                        throw FormException::forInvalidOptions($this->getName(), 'option groups cannot be nested.');
                    }

                    $group[(string) $groupValue] = $this->optionLabel($groupLabel);
                }

                $options[(string) $value] = $group;

                continue;
            }

            $options[(string) $value] = $this->optionLabel($label);
        }

        return $this->resolvedOptions = $options;
    }

    /**
     * Every selectable value, flattened across groups.
     *
     * @return list<string>
     */
    public function getOptionValues(): array
    {
        $values = [];

        foreach ($this->getOptions() as $value => $label) {
            if (is_array($label)) {
                foreach (array_keys($label) as $groupValue) {
                    $values[] = (string) $groupValue;
                }

                continue;
            }

            $values[] = (string) $value;
        }

        return $values;
    }

    /**
     * Whether the given value matches one of the options.
     */
    public function hasOptionValue(mixed $value): bool
    {
        if (! is_scalar($value)) {
            return false;
        }

        return in_array((string) $value, $this->getOptionValues(), true);
    }

    /**
     * Id of one option's input, used to link its label (radio and checkbox
     * groups).
     */
    public function getOptionId(string $value, string $prefix = ''): string
    {
        $suffix = (string) preg_replace('/[^A-Za-z0-9_\-]/', '_', $value);

        return $this->getId($prefix) . '_' . ($suffix === '' ? 'empty' : $suffix);
    }

    protected function allowsOptionGroups(): bool
    {
        return false;
    }

    private function optionLabel(mixed $label): string
    {
        if (is_scalar($label) || $label instanceof Stringable) {
            return (string) $label;
        }

        throw FormException::forInvalidOptions($this->getName(), 'each option label must be a string or number.');
    }
}
