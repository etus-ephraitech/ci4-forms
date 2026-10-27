# Changelog

All notable changes to `ephraitech/ci4-forms` are documented in this file.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.1.0] - 2026-10-27

First release. Requires PHP 8.2+ and CodeIgniter 4.7+ (every 4.6 release has published
security advisories).

### Added

- Package skeleton: Composer manifest, PSR-4 autoloading (`Ephraitech\Forms\` → `src/`),
  PHPUnit configuration on the CodeIgniter 4 test bootstrap, and environment smoke tests.
- `Config\Forms`: theme registry (Bootstrap 5 by default) with view paths, CSS classes and
  required-field marker; behaviour switches for CSRF, method spoofing, `novalidate`,
  rule-derived HTML attributes, error summary, old input, id prefixing, string trimming,
  empty-to-null conversion and unchecked checkbox values.
- `Exceptions\FormException` for configuration and definition errors.
- Field layer: `Contracts\FieldInterface`, base `Fields\Field` (fluent setters, rules with
  custom messages, safe attribute handling, CSS classes, help text, ids prefixed by form name,
  required detection, rule-derived HTML attributes, value normalisation, chaining back to the
  parent form) and field types `Text`, `Email`, `Password`, `Number`, `Tel`, `Url`, `Date`,
  `Hidden`, `Textarea`, `Select` (with option groups), `Radio`, `Checkbox` and `Submit`.
- Lazy, cached option closures for `Select` and `Radio`.
- `Support\RuleParser` for bracket-aware splitting and reading of CodeIgniter rule strings.
- `Form` class: class-based forms via `define()` and inline forms via `Form::make()` with
  `end()`; names derived from class names; action, method (with PUT/PATCH/DELETE spoofing),
  id, classes, attributes and multipart settings; typed builders for every field type plus
  `add()` for custom fields; `remove()`, `has()`, `getField()`, `getSubmittableFields()`;
  `fill()` from arrays, Entities and objects; value resolution in priority order (submitted,
  old input, filled, default) with correct unticked-checkbox and password handling; old input
  scoped to the form that produced it via a hidden `_form` key.
- Validation: `validate()` from the current request, an `IncomingRequest` (POST, GET or JSON)
  or an array, through CodeIgniter's shared Validation service with field labels and custom
  messages; values trimmed before validation; `prepareForValidation()` hook;
  `getValidationRules()` export; `isValid()`.
- `validated()` returning only defined, submittable fields, normalised per config, with
  unchecked checkbox values and optional excluded fields.
- Errors: `getErrors()`, `getError()`, `hasError()`, `hasErrors()`, `clearErrors()` and
  `setError()` for manual errors; errors (including manual ones) survive
  `redirect()->withInput()` and are scoped to the form that produced them.
- Rendering: `Renderer` plus Bootstrap 5 theme views for every field type, the whole form and
  an error summary. `Form` gains `render()`/`__toString()`, `open()` (hidden form key, CSRF,
  `_method` spoofing, multipart, `novalidate`), `close()`, `field()`, `fields()`, `control()`
  for custom layouts and `errorSummary()`. Output is escaped like `esc()`, with error and help
  text linked through `aria-describedby` and `aria-invalid`.
- `php spark forms:publish` command (`--theme`, `--config`, `--force`) to copy the theme views
  and an app config file into the application; existing files are kept unless forced.
- GitHub Actions workflow testing PHP 8.2 to 8.5 against CodeIgniter 4.7.

[Unreleased]: https://github.com/etus-ephraitech/ci4-forms/compare/v0.1.0...HEAD
[0.1.0]: https://github.com/etus-ephraitech/ci4-forms/releases/tag/v0.1.0
