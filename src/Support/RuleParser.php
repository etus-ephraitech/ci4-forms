<?php

declare(strict_types=1);

namespace Ephraitech\Forms\Support;

/**
 * Reads CodeIgniter 4 validation rule definitions.
 *
 * Rules are split on "|" except inside square brackets, so parameters such as
 * regex_match[/^(a|b)$/] stay intact, matching how CodeIgniter's own
 * Validation service treats them.
 */
final class RuleParser
{
    /**
     * Splits a rule string, or cleans a rule array, into a list of rules.
     *
     * Callables (supported by CodeIgniter 4.3+) are kept as they are.
     *
     * @param array<array-key, mixed>|string $rules
     *
     * @return list<callable|string>|null Null when an entry is neither a
     *                                    non-empty string nor a callable.
     */
    public static function split(array|string $rules): ?array
    {
        if (is_string($rules)) {
            return self::splitString($rules);
        }

        $result = [];

        foreach ($rules as $rule) {
            if (is_string($rule)) {
                $rule = trim($rule);

                if ($rule === '') {
                    return null;
                }

                $result[] = $rule;

                continue;
            }

            if (is_callable($rule)) {
                $result[] = $rule;

                continue;
            }

            return null;
        }

        return $result;
    }

    /**
     * The rule name without parameters: "max_length[100]" becomes "max_length".
     */
    public static function name(string $rule): string
    {
        $bracket = strpos($rule, '[');

        return trim($bracket === false ? $rule : substr($rule, 0, $bracket));
    }

    /**
     * The raw parameter string: "max_length[100]" becomes "100".
     * Returns null when the rule has no brackets.
     */
    public static function param(string $rule): ?string
    {
        $open  = strpos($rule, '[');
        $close = strrpos($rule, ']');

        if ($open === false || $close === false || $close < $open) {
            return null;
        }

        return substr($rule, $open + 1, $close - $open - 1);
    }

    /**
     * Whether a rule with this name is present.
     *
     * @param list<callable|string> $rules
     */
    public static function has(array $rules, string $name): bool
    {
        foreach ($rules as $rule) {
            if (is_string($rule) && self::name($rule) === $name) {
                return true;
            }
        }

        return false;
    }

    /**
     * Parameter of the first rule with this name; '' when the rule has no
     * parameter, null when the rule is absent.
     *
     * @param list<callable|string> $rules
     */
    public static function paramOf(array $rules, string $name): ?string
    {
        foreach ($rules as $rule) {
            if (is_string($rule) && self::name($rule) === $name) {
                return self::param($rule) ?? '';
            }
        }

        return null;
    }

    /**
     * Numeric parameter of a rule, or null when absent or not numeric.
     *
     * @param list<callable|string> $rules
     */
    public static function numericParamOf(array $rules, string $name): int|float|null
    {
        $param = self::paramOf($rules, $name);

        if ($param === null || ! is_numeric(trim($param))) {
            return null;
        }

        return trim($param) + 0;
    }

    /**
     * @return list<string>
     */
    private static function splitString(string $rules): array
    {
        $result  = [];
        $current = '';
        $depth   = 0;
        $length  = strlen($rules);

        for ($i = 0; $i < $length; $i++) {
            $char = $rules[$i];

            if ($char === '[') {
                $depth++;
            } elseif ($char === ']' && $depth > 0) {
                $depth--;
            } elseif ($char === '|' && $depth === 0) {
                $result[] = $current;
                $current  = '';

                continue;
            }

            $current .= $char;
        }

        $result[] = $current;

        return array_values(array_filter(
            array_map('trim', $result),
            static fn (string $rule): bool => $rule !== '',
        ));
    }
}
