<?php

declare(strict_types=1);

namespace Ephraitech\Forms\Schema;

/**
 * The result of checking a schema, for admin screens: whether it can be
 * saved, and every problem with its location.
 *
 *     $check = FormSchema::check($this->request->getPost('schema'));
 *
 *     if (! $check->isValid()) {
 *         return view('admin/forms/edit', ['errors' => $check->getMessages()]);
 *     }
 */
final class SchemaCheck
{
    /**
     * @param list<array{path: string, message: string}> $errors
     * @param array<array-key, mixed>|null              $schema The decoded schema, or null when it could not be decoded
     */
    public function __construct(private readonly array $errors, private readonly ?array $schema)
    {
    }

    public function isValid(): bool
    {
        return $this->errors === [];
    }

    /**
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
        return array_map(
            static fn (array $error): string => $error['path'] === '' ? ucfirst($error['message']) : $error['path'] . ': ' . $error['message'],
            $this->errors,
        );
    }

    /**
     * Errors at a path or inside it, such as everything under "fields[3]".
     *
     * @return list<array{path: string, message: string}>
     */
    public function getErrorsFor(string $path): array
    {
        return array_values(array_filter(
            $this->errors,
            static fn (array $error): bool => $error['path'] === $path
                || str_starts_with($error['path'], $path . '.')
                || str_starts_with($error['path'], $path . '['),
        ));
    }

    /**
     * The decoded schema, ready to store, or null when it was not valid
     * JSON.
     *
     * @return array<array-key, mixed>|null
     */
    public function getSchema(): ?array
    {
        return $this->schema;
    }

    /**
     * For JSON responses, such as an AJAX "check" button in an editor.
     *
     * @return array{valid: bool, errors: list<array{path: string, message: string}>}
     */
    public function toArray(): array
    {
        return ['valid' => $this->isValid(), 'errors' => $this->errors];
    }
}
