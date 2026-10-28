<?php

declare(strict_types=1);

namespace Ephraitech\Forms\Fields;

use Ephraitech\Forms\Contracts\FileFieldInterface;
use Ephraitech\Forms\Exceptions\FormException;
use Ephraitech\Forms\Support\RuleParser;
use Stringable;

/**
 * File upload input, single or multiple.
 *
 * The helpers build CodeIgniter's file rules for you:
 *
 *     $this->file('photo', 'Profile photo')
 *         ->required()
 *         ->maxSize(2048)                   // kilobytes
 *         ->image()
 *         ->mimes(['image/jpeg', 'image/png']);
 *
 * Extra rules passed to rules() run after the generated ones. An optional
 * file field with nothing uploaded is not validated at all; once a file is
 * uploaded every rule applies.
 *
 * Browsers cannot refill a file input, so after a failed submission the
 * field shows a hint asking the user to choose the file again.
 */
class File extends InputField implements FileFieldInterface
{
    public const TYPE = 'file';

    public const VIEW = 'field_file';

    public const INPUT_TYPE = 'file';

    protected bool $multiple = false;

    protected bool $uploadRequired = false;

    protected ?int $maxSize = null;

    /**
     * @var list<string>
     */
    protected array $extensions = [];

    /**
     * @var list<string>
     */
    protected array $mimes = [];

    protected bool $image = false;

    /**
     * @var array{int, int}|null
     */
    protected ?array $maxDimensions = null;

    /**
     * Requires at least one uploaded file (CodeIgniter's "uploaded" rule).
     */
    public function required(bool $required = true): static
    {
        $this->uploadRequired = $required;

        return $this;
    }

    /**
     * Accepts several files, submitted as name[].
     */
    public function multiple(bool $multiple = true): static
    {
        $this->multiple = $multiple;

        return $this;
    }

    /**
     * Largest allowed file in kilobytes (2048 = 2 MB). PHP's own
     * upload_max_filesize and post_max_size limits apply first.
     */
    public function maxSize(int $kilobytes): static
    {
        if ($kilobytes < 1) {
            throw FormException::forInvalidFileSetting($this->name, 'maxSize() must be at least 1 kilobyte.');
        }

        $this->maxSize = $kilobytes;

        return $this;
    }

    /**
     * Allowed file name extensions, such as ['pdf', 'docx']. Also sets the
     * input's accept attribute. Extensions come from the user's file name
     * and are easy to fake, so combine this with mimes() or image().
     *
     * @param list<string>|string $extensions
     */
    public function extensions(array|string $extensions): static
    {
        $list = [];

        foreach ((array) $extensions as $extension) {
            $extension = strtolower(ltrim(trim((string) $extension), '.'));

            if (preg_match('/^[a-z0-9]+$/', $extension) !== 1) {
                throw FormException::forInvalidFileSetting($this->name, sprintf('"%s" is not a valid file extension.', $extension));
            }

            $list[] = $extension;
        }

        $this->extensions = array_values(array_unique($list));

        return $this;
    }

    /**
     * Allowed MIME types, detected from the file's content, such as
     * ['application/pdf', 'image/png']. Also sets the accept attribute when
     * no extensions are given.
     *
     * @param list<string>|string $mimes
     */
    public function mimes(array|string $mimes): static
    {
        $list = [];

        foreach ((array) $mimes as $mime) {
            $mime = strtolower(trim((string) $mime));

            if (preg_match('#^[a-z0-9][a-z0-9!\#$&^_.+-]*/[a-z0-9][a-z0-9!\#$&^_.+-]*$#', $mime) !== 1) {
                throw FormException::forInvalidFileSetting($this->name, sprintf('"%s" is not a valid MIME type.', $mime));
            }

            $list[] = $mime;
        }

        $this->mimes = array_values(array_unique($list));

        return $this;
    }

    /**
     * Accepts images only (CodeIgniter's "is_image" rule).
     */
    public function image(bool $image = true): static
    {
        $this->image = $image;

        return $this;
    }

    /**
     * Largest allowed image size in pixels.
     */
    public function maxDimensions(int $width, int $height): static
    {
        if ($width < 1 || $height < 1) {
            throw FormException::forInvalidFileSetting($this->name, 'maxDimensions() needs a width and height of at least 1 pixel.');
        }

        $this->maxDimensions = [$width, $height];

        return $this;
    }

    public function isMultiple(): bool
    {
        return $this->multiple;
    }

    /**
     * Whether required() was set.
     */
    public function isUploadRequired(): bool
    {
        return $this->uploadRequired;
    }

    /**
     * Rules set with rules(), without the ones the helpers generate.
     *
     * @return list<callable|string>
     */
    public function getExtraRules(): array
    {
        return $this->rules;
    }

    public function isRequired(): bool
    {
        return $this->uploadRequired || RuleParser::has($this->rules, 'uploaded');
    }

    /**
     * The generated file rules followed by any rules set with rules().
     */
    public function getRules(): array
    {
        $name  = $this->name;
        $rules = [];

        if ($this->uploadRequired && ! RuleParser::has($this->rules, 'uploaded')) {
            $rules[] = 'uploaded[' . $name . ']';
        }

        if ($this->maxSize !== null) {
            $rules[] = 'max_size[' . $name . ',' . $this->maxSize . ']';
        }

        if ($this->extensions !== []) {
            $rules[] = 'ext_in[' . $name . ',' . implode(',', $this->extensions) . ']';
        }

        if ($this->mimes !== []) {
            $rules[] = 'mime_in[' . $name . ',' . implode(',', $this->mimes) . ']';
        }

        if ($this->image) {
            $rules[] = 'is_image[' . $name . ']';
        }

        if ($this->maxDimensions !== null) {
            $rules[] = 'max_dims[' . $name . ',' . $this->maxDimensions[0] . ',' . $this->maxDimensions[1] . ']';
        }

        return array_merge($rules, $this->rules);
    }

    /**
     * Includes a generated accept attribute (unless one was set) and the
     * multiple attribute.
     *
     * @return array<string, bool|float|int|string|Stringable|null>
     */
    public function getAttributes(): array
    {
        $attributes = $this->attributes;

        if (! array_key_exists('accept', $attributes)) {
            $accept = match (true) {
                $this->extensions !== [] => implode(',', array_map(static fn (string $ext): string => '.' . $ext, $this->extensions)),
                $this->mimes !== []      => implode(',', $this->mimes),
                $this->image             => 'image/*',
                default                  => null,
            };

            if ($accept !== null) {
                $attributes['accept'] = $accept;
            }
        }

        if ($this->multiple) {
            $attributes['multiple'] = true;
        }

        return $attributes;
    }

    /**
     * Never shown when presenting stored data.
     */
    public function isPresentable(): bool
    {
        return false;
    }

    public function shouldRepopulate(): bool
    {
        return false;
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'rules'          => array_values(array_filter($this->getRules(), 'is_string')),
            'required'       => $this->uploadRequired,
            'max_size'       => $this->maxSize,
            'extensions'     => $this->extensions,
            'mimes'          => $this->mimes,
            'image'          => $this->image,
            'max_dimensions' => $this->maxDimensions,
        ]);
    }

    protected function extraRuleAttributes(): array
    {
        return [];
    }
}
