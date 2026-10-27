<?php

declare(strict_types=1);

namespace Ephraitech\Forms\Support;

use Ephraitech\Forms\Config\Forms;
use Ephraitech\Forms\Contracts\FieldInterface;
use Ephraitech\Forms\Exceptions\FormException;
use Ephraitech\Forms\Fields\Checkbox;
use Ephraitech\Forms\Fields\Checkboxes;
use Ephraitech\Forms\Fields\Color;
use Ephraitech\Forms\Fields\Date;
use Ephraitech\Forms\Fields\DatetimeLocal;
use Ephraitech\Forms\Fields\Email;
use Ephraitech\Forms\Fields\File;
use Ephraitech\Forms\Fields\Hidden;
use Ephraitech\Forms\Fields\Number;
use Ephraitech\Forms\Fields\Password;
use Ephraitech\Forms\Fields\Radio;
use Ephraitech\Forms\Fields\Search;
use Ephraitech\Forms\Fields\Select;
use Ephraitech\Forms\Fields\Submit;
use Ephraitech\Forms\Fields\Tel;
use Ephraitech\Forms\Fields\Text;
use Ephraitech\Forms\Fields\Textarea;
use Ephraitech\Forms\Fields\Time;
use Ephraitech\Forms\Fields\Url;
use Ephraitech\Forms\Form;

/**
 * Maps field type keys to field classes: the built-in types plus the ones
 * registered in the $fieldTypes config setting.
 */
final class FieldTypes
{
    /**
     * Built-in types, keyed by the type key that Form::addField() accepts.
     *
     * @var array<string, class-string<FieldInterface>>
     */
    public const BUILT_IN = [
        'text'           => Text::class,
        'email'          => Email::class,
        'password'       => Password::class,
        'number'         => Number::class,
        'tel'            => Tel::class,
        'url'            => Url::class,
        'search'         => Search::class,
        'date'           => Date::class,
        'time'           => Time::class,
        'datetime_local' => DatetimeLocal::class,
        'color'          => Color::class,
        'hidden'         => Hidden::class,
        'textarea'       => Textarea::class,
        'select'         => Select::class,
        'radio'          => Radio::class,
        'checkbox'       => Checkbox::class,
        'checkboxes'     => Checkboxes::class,
        'file'           => File::class,
        'submit'         => Submit::class,
    ];

    /**
     * The class for a type key, built-in or registered, or null when the
     * type is unknown.
     *
     * @return class-string<FieldInterface>|null
     */
    public static function resolve(string $type, Forms $config): ?string
    {
        return self::BUILT_IN[$type] ?? self::registered($config)[$type] ?? null;
    }

    /**
     * The registered types, checked.
     *
     * @return array<string, class-string<FieldInterface>>
     *
     * @throws FormException When a registration is invalid.
     */
    public static function registered(Forms $config): array
    {
        $types = [];

        foreach ($config->fieldTypes as $type => $class) {
            $type = (string) $type;

            if (preg_match('/^[a-z][A-Za-z0-9]*$/', $type) !== 1) {
                throw FormException::forInvalidFieldType($type, 'use a camelCase name starting with a lowercase letter, such as "money" or "phoneNumber".');
            }

            if (array_key_exists($type, self::BUILT_IN) || method_exists(Form::class, $type)) {
                throw FormException::forInvalidFieldType($type, 'the name is already used by a built-in field or a Form method; choose another name.');
            }

            if (! is_string($class) || ! class_exists($class)) {
                throw FormException::forInvalidFieldType($type, sprintf('class "%s" does not exist.', is_string($class) ? $class : get_debug_type($class)));
            }

            if (! is_subclass_of($class, FieldInterface::class)) {
                throw FormException::forInvalidFieldType($type, sprintf('class "%s" must implement %s.', $class, FieldInterface::class));
            }

            $types[$type] = $class;
        }

        return $types;
    }
}
