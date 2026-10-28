<?php

declare(strict_types=1);

namespace Ephraitech\Forms\Tests\Support\Providers;

/**
 * Option provider fixtures: a static method, an instance method, and a call
 * counter to prove providers run lazily and once.
 */
final class ServiceOptions
{
    public static int $calls = 0;

    /**
     * @return array<string, string>
     */
    public static function all(): array
    {
        self::$calls++;

        return ['fitting' => 'Suit fitting', 'alterations' => 'Alterations'];
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function grouped(): array
    {
        return ['Suits' => ['two_piece' => 'Two-piece', 'three_piece' => 'Three-piece'], 'Shirts' => ['formal' => 'Formal']];
    }

    public static function broken(): string
    {
        return 'not an array';
    }

    /**
     * @return array<string, string>
     */
    public static function needsArgument(string $branch): array
    {
        return [$branch => $branch];
    }

    /**
     * @return array<string, string>
     */
    private static function hidden(): array
    {
        return [];
    }
}
