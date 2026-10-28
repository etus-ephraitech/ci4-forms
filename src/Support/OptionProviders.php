<?php

declare(strict_types=1);

namespace Ephraitech\Forms\Support;

use Closure;
use CodeIgniter\Model;
use Ephraitech\Forms\Config\Forms;
use Ephraitech\Forms\Exceptions\FormException;
use ReflectionMethod;

/**
 * Named sources of options, registered in the $optionProviders config
 * setting, so schemas can say "options_from": "services" without being able
 * to run code. Developers can use them in code too:
 *
 *     $this->select('service_id', 'Service')->optionsFrom('services');
 *
 * A provider is one of:
 *
 * - [ClassName::class, 'method']: a static method, a method on a CodeIgniter
 *   model (loaded with model()), or a method on a class with no required
 *   constructor arguments
 * - 'ClassName::method'
 * - a Closure, assigned in the config class's constructor
 *
 * It returns value => label pairs; select fields may also return groups.
 */
final class OptionProviders
{
    /**
     * Registered provider names.
     *
     * @return list<string>
     */
    public static function names(Forms $config): array
    {
        return array_map('strval', array_keys($config->optionProviders));
    }

    public static function has(Forms $config, string $name): bool
    {
        return array_key_exists($name, $config->optionProviders);
    }

    /**
     * Calls a provider and returns its options.
     *
     * @return array<array-key, mixed>
     *
     * @throws FormException When the provider is unknown, invalid or returns something other than an array.
     */
    public static function call(Forms $config, string $name): array
    {
        if (! self::has($config, $name)) {
            throw FormException::forUnknownOptionProvider($name, self::names($config));
        }

        $result = self::callable($name, $config->optionProviders[$name])();

        if (! is_array($result)) {
            throw FormException::forInvalidOptionProvider($name, sprintf('it returned %s instead of an array of value => label.', get_debug_type($result)));
        }

        return $result;
    }

    /**
     * Checks every registration without calling any provider.
     *
     * @throws FormException When a registration is invalid.
     */
    public static function check(Forms $config): void
    {
        foreach ($config->optionProviders as $name => $provider) {
            self::callable((string) $name, $provider);
        }
    }

    private static function callable(string $name, mixed $provider): Closure
    {
        if (preg_match('/^[a-z][a-z0-9_]*$/', $name) !== 1) {
            throw FormException::forInvalidOptionProvider($name, 'names use lowercase letters, digits and underscores, such as "services" or "branch_offices".');
        }

        if ($provider instanceof Closure) {
            return $provider;
        }

        if (is_string($provider) && str_contains($provider, '::')) {
            $provider = explode('::', $provider, 2);
        }

        if (! is_array($provider) || count($provider) !== 2 || ! is_string($provider[0] ?? null) || ! is_string($provider[1] ?? null)) {
            throw FormException::forInvalidOptionProvider($name, 'register [ClassName::class, \'method\'], \'ClassName::method\' or a Closure.');
        }

        [$class, $method] = $provider;

        if (! class_exists($class)) {
            throw FormException::forInvalidOptionProvider($name, sprintf('class "%s" does not exist.', $class));
        }

        if (! method_exists($class, $method)) {
            throw FormException::forInvalidOptionProvider($name, sprintf('%s has no method "%s".', $class, $method));
        }

        $reflection = new ReflectionMethod($class, $method);

        if (! $reflection->isPublic()) {
            throw FormException::forInvalidOptionProvider($name, sprintf('%s::%s() must be public.', $class, $method));
        }

        if ($reflection->getNumberOfRequiredParameters() > 0) {
            throw FormException::forInvalidOptionProvider($name, sprintf('%s::%s() must not require arguments.', $class, $method));
        }

        if ($reflection->isStatic()) {
            return static fn (): mixed => $class::$method();
        }

        if (is_subclass_of($class, Model::class)) {
            return static fn (): mixed => model($class, false)->{$method}();
        }

        return static fn (): mixed => (new $class())->{$method}();
    }
}
