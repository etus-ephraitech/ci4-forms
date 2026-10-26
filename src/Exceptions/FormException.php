<?php

declare(strict_types=1);

namespace Ephraitech\Forms\Exceptions;

use RuntimeException;

/**
 * Thrown when the package is misconfigured or a form is defined incorrectly.
 *
 * These are developer errors (a typo in a theme name, an unsupported HTTP
 * method), so they fail loudly instead of silently rendering a broken form.
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
}
