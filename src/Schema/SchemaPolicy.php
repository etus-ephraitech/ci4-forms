<?php

declare(strict_types=1);

namespace Ephraitech\Forms\Schema;

use Ephraitech\Forms\Exceptions\FormException;

/**
 * What a form schema is allowed to contain.
 *
 * Schemas written by admins or clients are user input, so the loader applies
 * SchemaPolicy::restricted() unless told otherwise. It blocks the ways a
 * schema could harm the site or its visitors:
 *
 * - attributes that run script (onclick, formaction) or trigger front-end
 *   behaviour (data-bs-*): only harmless attributes are accepted
 * - rules that read the database (is_unique) or can hang the server
 *   (regex_match): only listed rules, with checked parameters
 * - a form action, which could send submissions to another site
 * - uploads without a size limit or with dangerous types such as SVG or HTML
 * - field names that could overwrite sensitive columns (role, is_admin)
 * - password and hidden fields
 * - oversized schemas (field count, nesting, options, text length)
 *
 * Option providers are registered by developers, so admin schemas may use
 * every registered provider unless onlyProviders() or disallowProviders()
 * narrows the list.
 *
 * Schemas written by developers and committed with the code can use
 * SchemaPolicy::trusted(), which only checks structure.
 *
 * Policies are immutable: every change returns a new policy.
 *
 *     $policy = SchemaPolicy::restricted()
 *         ->allowRules(['valid_ip' => SchemaPolicy::PARAM_NONE])
 *         ->allowTypes('money');
 */
final class SchemaPolicy
{
    public const PARAM_NONE                 = 'none';
    public const PARAM_INT                  = 'int';
    public const PARAM_INTS                 = 'ints';
    public const PARAM_NUMBER               = 'number';
    public const PARAM_FIELD                = 'field';
    public const PARAM_FIELDS               = 'fields';
    public const PARAM_LIST                 = 'list';
    public const PARAM_OPTIONAL_SCHEMES     = 'optional_schemes';
    public const PARAM_OPTIONAL_DATE_FORMAT = 'optional_date_format';

    public const PARAM_KINDS = [
        self::PARAM_NONE, self::PARAM_INT, self::PARAM_INTS, self::PARAM_NUMBER, self::PARAM_FIELD,
        self::PARAM_FIELDS, self::PARAM_LIST, self::PARAM_OPTIONAL_SCHEMES, self::PARAM_OPTIONAL_DATE_FORMAT,
    ];

    /**
     * Field types admins may use by default.
     */
    public const DEFAULT_TYPES = [
        'text', 'email', 'tel', 'url', 'search', 'number', 'date', 'time', 'datetime_local', 'color',
        'textarea', 'select', 'radio', 'checkboxes', 'checkbox', 'file', 'submit',
    ];

    /**
     * Validation rules admins may use by default, with the kind of
     * parameter each accepts.
     */
    public const DEFAULT_RULES = [
        'required'              => self::PARAM_NONE,
        'permit_empty'          => self::PARAM_NONE,
        'string'                => self::PARAM_NONE,
        'min_length'            => self::PARAM_INT,
        'max_length'            => self::PARAM_INT,
        'exact_length'          => self::PARAM_INTS,
        'alpha'                 => self::PARAM_NONE,
        'alpha_space'           => self::PARAM_NONE,
        'alpha_dash'            => self::PARAM_NONE,
        'alpha_numeric'         => self::PARAM_NONE,
        'alpha_numeric_space'   => self::PARAM_NONE,
        'alpha_numeric_punct'   => self::PARAM_NONE,
        'numeric'               => self::PARAM_NONE,
        'integer'               => self::PARAM_NONE,
        'decimal'               => self::PARAM_NONE,
        'is_natural'            => self::PARAM_NONE,
        'is_natural_no_zero'    => self::PARAM_NONE,
        'greater_than'          => self::PARAM_NUMBER,
        'greater_than_equal_to' => self::PARAM_NUMBER,
        'less_than'             => self::PARAM_NUMBER,
        'less_than_equal_to'    => self::PARAM_NUMBER,
        'valid_email'           => self::PARAM_NONE,
        'valid_emails'          => self::PARAM_NONE,
        'valid_url_strict'      => self::PARAM_OPTIONAL_SCHEMES,
        'valid_date'            => self::PARAM_OPTIONAL_DATE_FORMAT,
        'in_list'               => self::PARAM_LIST,
        'not_in_list'           => self::PARAM_LIST,
        'matches'               => self::PARAM_FIELD,
        'differs'               => self::PARAM_FIELD,
        'required_with'         => self::PARAM_FIELDS,
        'required_without'      => self::PARAM_FIELDS,
    ];

    /**
     * Why well-known rules are not allowed, shown in error messages.
     */
    public const BLOCKED_RULE_REASONS = [
        'is_unique'        => 'it reads the database',
        'is_not_unique'    => 'it reads the database',
        'field_exists'     => 'it inspects other request data',
        'regex_match'      => 'a badly written pattern can hang the server',
        'uploaded'         => 'use "required" on the file field instead',
        'max_size'         => 'use the file field\'s "max_size" key instead',
        'is_image'         => 'use the file field\'s "mimes" key instead',
        'mime_in'          => 'use the file field\'s "mimes" key instead',
        'ext_in'           => 'use the file field\'s "extensions" key instead',
        'max_dims'         => 'use the file field\'s "max_dimensions" key instead',
        'min_dims'         => 'it is not available in schemas',
    ];

    /**
     * Attributes admins may set by default. Names ending in "*" are
     * prefixes. data-* is deliberately absent: front-end libraries such as
     * Bootstrap act on data attributes.
     */
    public const DEFAULT_ATTRIBUTES = [
        'title', 'placeholder', 'autocomplete', 'inputmode', 'spellcheck', 'autocapitalize',
        'enterkeyhint', 'dir', 'lang', 'aria-*',
    ];

    /**
     * Upload types admins may allow by default. SVG and HTML are absent:
     * both can carry script.
     */
    public const DEFAULT_MIMES = [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp',
        'application/pdf',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'application/msword', 'application/vnd.ms-excel',
        'text/plain', 'text/csv',
    ];

    /**
     * Field names admins may not use by default, because a controller that
     * saves validated() into a table could let visitors overwrite them.
     */
    public const DEFAULT_FORBIDDEN_NAMES = [
        'id', 'user_id', 'owner_id', 'tenant_id', 'role', 'roles', 'is_admin', 'admin', 'permissions',
        'password', 'password_hash', 'token', 'api_key', 'secret',
        'created_at', 'updated_at', 'deleted_at', 'created_by', 'updated_by',
    ];

    public const DEFAULT_LIMITS = [
        'max_fields'      => 100,
        'max_depth'       => 5,
        'max_options'     => 500,
        'max_text'        => 1000,
        'max_rules'       => 20,
        'max_classes'     => 20,
        'max_name_length' => 64,
    ];

    /**
     * Largest upload limit, in kilobytes, an admin may set (10 MB).
     */
    public const DEFAULT_MAX_UPLOAD_KB = 10240;

    /**
     * @param list<string>          $types
     * @param array<string, string> $rules
     * @param list<string>          $attributes
     * @param list<string>          $mimes
     * @param list<string>          $forbiddenNames
     * @param array<string, int>    $limits
     */
    private function __construct(
        private readonly bool $trusted,
        private array $types = [],
        private array $rules = [],
        private array $attributes = [],
        private array $mimes = [],
        private array $forbiddenNames = [],
        private array $limits = [],
        private int $maxUploadKb = 0,
        private bool $actionAllowed = false,
        private ?array $providers = null,
        private array $blockedProviders = [],
    ) {
    }

    /**
     * For schemas written by admins or clients. The default.
     */
    public static function restricted(): self
    {
        return new self(
            false,
            self::DEFAULT_TYPES,
            self::DEFAULT_RULES,
            self::DEFAULT_ATTRIBUTES,
            self::DEFAULT_MIMES,
            self::DEFAULT_FORBIDDEN_NAMES,
            self::DEFAULT_LIMITS,
            self::DEFAULT_MAX_UPLOAD_KB,
        );
    }

    /**
     * For schemas written by developers and committed with the code: every
     * feature is allowed; only the structure is checked.
     */
    public static function trusted(): self
    {
        return new self(true);
    }

    public function isTrusted(): bool
    {
        return $this->trusted;
    }

    // -------------------------------------------------------------------------
    // Customising (each returns a new policy)
    // -------------------------------------------------------------------------

    /**
     * Allows more field types, including registered custom types.
     */
    public function allowTypes(string ...$types): self
    {
        $policy        = $this->restrictedCopy();
        $policy->types = array_values(array_unique([...$policy->types, ...$types]));

        return $policy;
    }

    public function disallowTypes(string ...$types): self
    {
        $policy        = $this->restrictedCopy();
        $policy->types = array_values(array_diff($policy->types, $types));

        return $policy;
    }

    /**
     * Allows more rules, as rule name => parameter kind (PARAM_* constant).
     *
     * @param array<string, string> $rules
     */
    public function allowRules(array $rules): self
    {
        $policy = $this->restrictedCopy();

        foreach ($rules as $name => $kind) {
            if (! in_array($kind, self::PARAM_KINDS, true)) {
                throw new FormException(sprintf('Rule "%s": "%s" is not a parameter kind; use a SchemaPolicy::PARAM_* constant.', $name, $kind));
            }

            $policy->rules[(string) $name] = $kind;
        }

        return $policy;
    }

    public function disallowRules(string ...$names): self
    {
        $policy = $this->restrictedCopy();

        foreach ($names as $name) {
            unset($policy->rules[$name]);
        }

        return $policy;
    }

    /**
     * Allows more attributes. A trailing "*" allows a prefix, such as
     * "data-track-*". Event handlers (on*) can never be allowed.
     */
    public function allowAttributes(string ...$names): self
    {
        $policy = $this->restrictedCopy();

        foreach ($names as $name) {
            $name = strtolower($name);

            if (str_starts_with($name, 'on') || in_array(rtrim($name, '*'), ['', 'data-', 'formaction', 'srcdoc', 'style'], true)) {
                throw new FormException(sprintf('The attribute "%s" cannot be allowed in schemas.', $name));
            }

            $policy->attributes[] = $name;
        }

        $policy->attributes = array_values(array_unique($policy->attributes));

        return $policy;
    }

    /**
     * Allows more upload types. SVG, HTML and script types cannot be
     * allowed.
     */
    public function allowMimes(string ...$mimes): self
    {
        $policy = $this->restrictedCopy();

        foreach ($mimes as $mime) {
            $mime = strtolower($mime);

            if (preg_match('#(svg|html|javascript|ecmascript|php|x-httpd|x-sh|^text/xml$|^application/xml$)#', $mime) === 1) {
                throw new FormException(sprintf('The upload type "%s" can carry script and cannot be allowed in schemas.', $mime));
            }

            $policy->mimes[] = $mime;
        }

        $policy->mimes = array_values(array_unique($policy->mimes));

        return $policy;
    }

    /**
     * Forbids more field names, for example the columns of the table your
     * controller saves submissions into.
     */
    public function forbidNames(string ...$names): self
    {
        $policy                 = $this->restrictedCopy();
        $policy->forbiddenNames = array_values(array_unique([...$policy->forbiddenNames, ...array_map('strtolower', $names)]));

        return $policy;
    }

    /**
     * Allows names that are forbidden by default, such as "status".
     */
    public function allowNames(string ...$names): self
    {
        $policy                 = $this->restrictedCopy();
        $policy->forbiddenNames = array_values(array_diff($policy->forbiddenNames, array_map('strtolower', $names)));

        return $policy;
    }

    /**
     * Changes size limits: max_fields, max_depth, max_options, max_text,
     * max_rules, max_classes, max_name_length.
     *
     * @param array<string, int> $limits
     */
    public function withLimits(array $limits): self
    {
        $policy = $this->restrictedCopy();

        foreach ($limits as $key => $value) {
            if (! array_key_exists($key, self::DEFAULT_LIMITS)) {
                throw new FormException(sprintf('"%s" is not a schema limit. Limits: %s.', $key, implode(', ', array_keys(self::DEFAULT_LIMITS))));
            }

            if ($value < 1) {
                throw new FormException(sprintf('The "%s" limit must be at least 1.', $key));
            }

            $policy->limits[$key] = $value;
        }

        return $policy;
    }

    /**
     * Changes the largest upload limit (kilobytes) an admin may set.
     */
    public function withMaxUploadSize(int $kilobytes): self
    {
        if ($kilobytes < 1) {
            throw new FormException('The upload size limit must be at least 1 kilobyte.');
        }

        $policy              = $this->restrictedCopy();
        $policy->maxUploadKb = $kilobytes;

        return $policy;
    }

    /**
     * Limits schemas to these registered option providers. By default every
     * registered provider may be used.
     */
    public function onlyProviders(string ...$names): self
    {
        $policy            = $this->restrictedCopy();
        $policy->providers = array_values(array_unique($names));

        return $policy;
    }

    /**
     * Keeps schemas from using these registered option providers, for
     * example one listing staff that should only appear on internal forms.
     */
    public function disallowProviders(string ...$names): self
    {
        $policy                   = $this->restrictedCopy();
        $policy->blockedProviders = array_values(array_unique([...$policy->blockedProviders, ...$names]));

        return $policy;
    }

    /**
     * Lets schemas set the form action. Only allow this when every possible
     * action is safe: a schema could otherwise send submissions elsewhere.
     */
    public function allowAction(bool $allow = true): self
    {
        $policy                = $this->restrictedCopy();
        $policy->actionAllowed = $allow;

        return $policy;
    }

    // -------------------------------------------------------------------------
    // Questions the loader asks
    // -------------------------------------------------------------------------

    public function allowsType(string $type): bool
    {
        return $this->trusted || in_array($type, $this->types, true);
    }

    /**
     * @return list<string>
     */
    public function getTypes(): array
    {
        return $this->types;
    }

    /**
     * The parameter kind of an allowed rule, or null when not allowed.
     */
    public function ruleParameterKind(string $rule): ?string
    {
        return $this->rules[$rule] ?? null;
    }

    public function allowsAttribute(string $name): bool
    {
        if ($this->trusted) {
            return true;
        }

        $name = strtolower($name);

        foreach ($this->attributes as $allowed) {
            if ($allowed === $name || (str_ends_with($allowed, '*') && str_starts_with($name, rtrim($allowed, '*')))) {
                return true;
            }
        }

        return false;
    }

    public function allowsMime(string $mime): bool
    {
        return $this->trusted || in_array(strtolower($mime), $this->mimes, true);
    }

    /**
     * @return list<string>
     */
    public function getMimes(): array
    {
        return $this->mimes;
    }

    public function forbidsName(string $name): bool
    {
        return ! $this->trusted && in_array(strtolower($name), $this->forbiddenNames, true);
    }

    public function limit(string $key): int
    {
        return $this->trusted ? PHP_INT_MAX : ($this->limits[$key] ?? self::DEFAULT_LIMITS[$key]);
    }

    public function getMaxUploadSize(): int
    {
        return $this->trusted ? PHP_INT_MAX : $this->maxUploadKb;
    }

    /**
     * Whether a schema may use a registered option provider.
     */
    public function allowsProvider(string $name): bool
    {
        if ($this->trusted) {
            return true;
        }

        if (in_array($name, $this->blockedProviders, true)) {
            return false;
        }

        return $this->providers === null || in_array($name, $this->providers, true);
    }

    /**
     * Allowed rules as name => parameter kind, or null when every rule is
     * allowed (trusted).
     *
     * @return array<string, string>|null
     */
    public function getRules(): ?array
    {
        return $this->trusted ? null : $this->rules;
    }

    /**
     * Allowed attribute names ("aria-*" style prefixes included), or null
     * when every attribute is allowed (trusted).
     *
     * @return list<string>|null
     */
    public function getAttributes(): ?array
    {
        return $this->trusted ? null : $this->attributes;
    }

    /**
     * @return list<string>
     */
    public function getForbiddenNames(): array
    {
        return $this->trusted ? [] : $this->forbiddenNames;
    }

    /**
     * Size limits, or null when there are none (trusted).
     *
     * @return array<string, int>|null
     */
    public function getLimits(): ?array
    {
        return $this->trusted ? null : array_merge(self::DEFAULT_LIMITS, $this->limits);
    }

    public function allowsAction(): bool
    {
        return $this->trusted || $this->actionAllowed;
    }

    private function restrictedCopy(): self
    {
        if ($this->trusted) {
            throw new FormException('A trusted policy already allows everything; customise SchemaPolicy::restricted() instead.');
        }

        return clone $this;
    }
}
