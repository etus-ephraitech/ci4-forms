<?php

declare(strict_types=1);

namespace Ephraitech\Forms\Exceptions;

use RuntimeException;

/**
 * Thrown when the package is misconfigured or a form is defined incorrectly.
 *
 * These are developer errors (a typo in a theme name, an invalid field name),
 * so they fail loudly instead of silently rendering a broken form.
 */
class FormException extends RuntimeException
{
    /**
     * @param list<string> $available
     */
    public static function forUnknownTheme(string $theme, array $available): static
    {
        return new static(sprintf(
            'Form theme "%s" is not registered. Available themes: %s.',
            $theme,
            $available === [] ? '(none)' : implode(', ', $available),
        ));
    }

    public static function forInvalidTheme(string $theme, string $reason): static
    {
        return new static(sprintf('Form theme "%s" is invalid: %s', $theme, $reason));
    }

    /**
     * @param list<string> $allowed
     */
    public static function forInvalidMethod(string $method, array $allowed): static
    {
        return new static(sprintf(
            'HTTP method "%s" is not supported for forms. Use one of: %s.',
            $method,
            implode(', ', $allowed),
        ));
    }

    public static function forInvalidFieldName(string $name): static
    {
        return new static(sprintf(
            'Field name "%s" is invalid. Use a letter or underscore first, then letters, digits, underscores or hyphens.',
            $name,
        ));
    }

    public static function forInvalidAttributeName(string $field, string $attribute): static
    {
        return new static(sprintf('Field "%s": "%s" is not a valid HTML attribute name.', $field, $attribute));
    }

    public static function forReservedAttribute(string $field, string $attribute): static
    {
        return new static(sprintf(
            'Field "%s": the "%s" attribute is managed by the package and cannot be set directly.',
            $field,
            $attribute,
        ));
    }

    public static function forInvalidAttributeValue(string $field, string $attribute): static
    {
        return new static(sprintf(
            'Field "%s": attribute "%s" must be a string, number, boolean, null or Stringable.',
            $field,
            $attribute,
        ));
    }

    public static function forInvalidRule(string $field): static
    {
        return new static(sprintf('Field "%s": each rule must be a non-empty string or a callable.', $field));
    }

    public static function forInvalidOptions(string $field, string $reason): static
    {
        return new static(sprintf('Field "%s" has invalid options: %s', $field, $reason));
    }

    public static function forRulesOnSubmit(string $field): static
    {
        return new static(sprintf('Field "%s" is a submit button; buttons are never validated.', $field));
    }

    public static function forUndefinedMethod(string $class, string $method): static
    {
        return new static(sprintf('Call to undefined method %s::%s().', $class, $method));
    }
}
