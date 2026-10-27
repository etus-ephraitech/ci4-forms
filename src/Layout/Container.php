<?php

declare(strict_types=1);

namespace Ephraitech\Forms\Layout;

use Ephraitech\Forms\Exceptions\FormException;

/**
 * A layout group (row or fieldset) holding field names and nested groups.
 *
 * Layout never owns fields: the form keeps every field in one flat list for
 * values and validation, and containers only record where each field is
 * shown. Calls to methods a container does not have are forwarded to its
 * form, so inline definitions can continue after a group:
 *
 *     Form::make('signup')
 *         ->row(function (Form $form) {
 *             $form->text('first_name')->width(6);
 *             $form->text('last_name')->width(6);
 *         })
 *         ->email('email')
 *         ->end();
 */
abstract class Container
{
    public const TYPE = '';

    /**
     * Field names and nested containers, in display order.
     *
     * @var list<Container|string>
     */
    protected array $children = [];

    /**
     * @var list<string>
     */
    protected array $classes = [];

    protected ?int $width = null;

    private ?object $parent = null;

    /**
     * @param list<mixed> $arguments
     */
    public function __call(string $method, array $arguments): mixed
    {
        if ($this->parent !== null && method_exists($this->parent, $method)) {
            return $this->parent->{$method}(...$arguments);
        }

        throw FormException::forUndefinedMethod(static::class, $method);
    }

    /**
     * @internal Called by the form; not part of the public API.
     */
    public function setParent(object $parent): static
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Returns the form this group belongs to.
     */
    public function end(): object
    {
        if ($this->parent === null) {
            throw FormException::forLayoutWithoutForm(static::TYPE);
        }

        return $this->parent;
    }

    /**
     * @internal Called by the form while fields are defined.
     */
    public function addChild(Container|string $child): static
    {
        $this->children[] = $child;

        return $this;
    }

    /**
     * Removes a field from this group and any nested group.
     *
     * @internal Called by Form::remove().
     */
    public function removeField(string $name): void
    {
        $children = [];

        foreach ($this->children as $child) {
            if ($child === $name) {
                continue;
            }

            if ($child instanceof self) {
                $child->removeField($name);
            }

            $children[] = $child;
        }

        $this->children = $children;
    }

    /**
     * Extra CSS classes, added to the theme's class for this group.
     */
    public function addClass(string ...$classes): static
    {
        foreach ($classes as $class) {
            foreach (preg_split('/\s+/', trim($class)) ?: [] as $single) {
                if ($single !== '' && ! in_array($single, $this->classes, true)) {
                    $this->classes[] = $single;
                }
            }
        }

        return $this;
    }

    /**
     * Column width (1 to 12) when this group sits inside a row.
     */
    public function width(?int $columns): static
    {
        if ($columns !== null && ($columns < 1 || $columns > 12)) {
            throw FormException::forInvalidWidth(static::TYPE, $columns);
        }

        $this->width = $columns;

        return $this;
    }

    public function getType(): string
    {
        return static::TYPE;
    }

    /**
     * @return list<Container|string>
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    /**
     * @return list<string>
     */
    public function getClasses(): array
    {
        return $this->classes;
    }

    public function getWidth(): ?int
    {
        return $this->width;
    }

    /**
     * Whether no field is left anywhere inside this group.
     */
    public function isEmpty(): bool
    {
        foreach ($this->children as $child) {
            if (is_string($child) || ! $child->isEmpty()) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type'     => static::TYPE,
            'classes'  => $this->classes,
            'width'    => $this->width,
            'children' => array_map(
                static fn (Container|string $child): array|string => $child instanceof self ? $child->toArray() : $child,
                $this->children,
            ),
        ];
    }
}
