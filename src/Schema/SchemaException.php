<?php

declare(strict_types=1);

namespace Ephraitech\Forms\Schema;

use Ephraitech\Forms\Exceptions\FormException;

/**
 * Thrown when a form schema has errors. Holds every error found, each with
 * the location in the schema where it occurs, so an admin screen can list
 * them all at once.
 */
class SchemaException extends FormException
{
    /**
     * @var list<array{path: string, message: string}>
     */
    private array $errors = [];

    /**
     * @param list<array{path: string, message: string}> $errors
     */
    public static function forErrors(array $errors): static
    {
        $count = count($errors);
        $lines = array_map(
            static fn (array $error): string => '- ' . self::line($error),
            $errors,
        );

        $exception = new static(sprintf(
            "The form schema has %d %s:\n%s",
            $count,
            $count === 1 ? 'error' : 'errors',
            implode("\n", $lines),
        ));

        $exception->errors = $errors;

        return $exception;
    }

    public static function forInvalidJson(string $reason): static
    {
        return static::forErrors([['path' => '', 'message' => 'the schema is not valid JSON (' . $reason . ').']]);
    }

    /**
     * Every error as ['path' => 'fields[2].max_size', 'message' => '...'].
     *
     * @return list<array{path: string, message: string}>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Every error as one readable line: "fields[2].max_size: must be ...".
     *
     * @return list<string>
     */
    public function getMessages(): array
    {
        return array_map(static fn (array $error): string => self::line($error), $this->errors);
    }

    /**
     * @param array{path: string, message: string} $error
     */
    private static function line(array $error): string
    {
        return $error['path'] === '' ? ucfirst($error['message']) : $error['path'] . ': ' . $error['message'];
    }
}
