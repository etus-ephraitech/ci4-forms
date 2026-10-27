<?php

declare(strict_types=1);

namespace Ephraitech\Forms\Contracts;

use Ephraitech\Forms\Config\Forms;

/**
 * A form field definition.
 *
 * Fields describe themselves only. They never hold submitted values and
 * never produce HTML: the form resolves values and the renderer builds markup.
 */
interface FieldInterface
{
    /**
     * Name used for the HTML name attribute, validation and validated() keys.
     */
    public function getName(): string;

    /**
     * Human-readable label, also used in validation error messages.
     */
    public function getLabel(): string;

    /**
     * Short type key, such as "text", "select" or "checkbox".
     */
    public function getType(): string;

    /**
     * Template name, relative to the active theme's views path.
     */
    public function getView(): string;

    /**
     * @return list<callable|string>
     */
    public function getRules(): array;

    /**
     * Rules applied to every item of a multi-value field (validated with
     * CodeIgniter's "name.*" wildcard). Empty for single-value fields.
     *
     * @return list<callable|string>
     */
    public function getEachRules(): array;

    /**
     * Whether the field's value is a list (multi-select, checkbox group),
     * submitted as name[] and returned by validated() as an array.
     */
    public function isMultiple(): bool;

    /**
     * Custom validation messages keyed by rule name.
     *
     * @return array<string, string>
     */
    public function getMessages(): array;

    /**
     * Extra HTML attributes. True renders a bare attribute; false and null
     * are omitted by the renderer.
     *
     * @return array<string, bool|float|int|string|\Stringable|null>
     */
    public function getAttributes(): array;

    /**
     * Extra CSS classes added to the theme's class for this element.
     *
     * @return list<string>
     */
    public function getClasses(): array;

    public function getHelp(): ?string;

    /**
     * Column width (1 to 12) when the field sits inside a row, or null to
     * share the row's space equally.
     */
    public function getWidth(): ?int;

    /**
     * Element id: the explicit id if one was set, otherwise generated from
     * the name, optionally prefixed (the form name, to keep ids unique).
     */
    public function getId(string $prefix = ''): string;

    /**
     * Value shown when there is no submitted, old or filled value.
     */
    public function getDefault(): mixed;

    public function getPlaceholder(): ?string;

    /**
     * Whether the rules include "required".
     */
    public function isRequired(): bool;

    public function isDisabled(): bool;

    /**
     * Whether the field's value belongs in validated() data. Buttons and
     * disabled fields are excluded.
     */
    public function isSubmittable(): bool;

    /**
     * Whether a previous value may be written back into the field. False for
     * passwords, which must never be echoed back to the browser.
     */
    public function shouldRepopulate(): bool;

    /**
     * HTML attributes derived from the validation rules, such as required
     * and maxlength. Empty when the config disables rule attributes.
     *
     * @return array<string, bool|float|int|string>
     */
    public function ruleAttributes(Forms $config): array;

    /**
     * Cleans a submitted value for validated(): trimming and empty-to-null
     * conversion according to the config. Multi-value fields always return a
     * list; single-value fields never return an array.
     */
    public function normaliseValue(mixed $value, Forms $config): mixed;

    /**
     * Value validated() uses when the request did not contain the field.
     */
    public function valueWhenMissing(Forms $config): mixed;

    /**
     * Plain array description of the field.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
