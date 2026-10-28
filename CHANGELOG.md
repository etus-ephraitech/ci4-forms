# Changelog

All notable changes to `ephraitech/ci4-forms` are documented in this file.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Schema loading: `Form::fromSchema()`, `Form::fromJson()` and `$form->loadSchema()` build
  forms from arrays or JSON (schema version 1): every built-in field type and option, rows and
  fieldsets, form settings, and registered custom types. Loading is all or nothing: the
  schema is first built on a scratch form and every error is collected with its location
  (`fields[2].children[0].max_size: ...`), including typo suggestions and hints for HTML
  habits such as `maxlength`. Keys are applied in schema order.
- `Schema\SchemaException` with `getErrors()` and `getMessages()`; `Schema\SchemaLoader::errors()`
  checks a schema without loading it.
- `Contracts\SchemaConfigurableInterface` lets custom field types accept extra schema keys.
- `Schema\SchemaPolicy`: every schema is checked against a policy, restricted by default
  (`Form::fromSchema()`, `fromJson()` and `loadSchema()` all take an optional `policy`).
  `SchemaPolicy::restricted()`, for schemas written by admins or clients, blocks event-handler
  and behaviour attributes (`on*`, `formaction`, `style`, `data-*`), rules outside an
  allow-list with checked parameters (no `is_unique`, `regex_match` or custom rules), a
  schema-set form action, uploads without `max_size` (capped at 10 MB) or without an
  explicit `mimes` list from safe types (no SVG or HTML), protected field names such as
  `role` and `is_admin`, `password` and `hidden` fields, unlisted custom types, and oversized
  schemas. `SchemaPolicy::trusted()` is for developer-written schemas. Policies are immutable
  and can be customised (`allowTypes()`, `allowRules()`, `allowAttributes()`, `allowMimes()`,
  `forbidNames()`, `allowNames()`, `withLimits()`, `withMaxUploadSize()`, `allowAction()`);
  unsafe allowances such as `on*` attributes or SVG uploads are refused.
- A source-integrity test that fails when a docblock missing its closing `*/` hides class
  members.

## [0.2.0] - 2026-10-28

Multi-value fields, strict option checking, file uploads, layout, new inputs and custom field
types. Read "Upgrading from 0.1" in the README before upgrading.

### Added

- Multi-value fields: `select()->multiple()` (with `size()`) and a new `checkboxes()` group
  field. Both submit as `name[]` and `validated()` returns a list, empty when nothing is
  chosen.
- `eachRules()` for rules checked against every item of a multi-value field, using
  CodeIgniter's `name.*` wildcard. `permit_empty` is added automatically so an empty list
  passes; use `rules('required')` to require at least one item.
- New `field_checkboxes` view; views receive `$inputName`, `$values` and `$multiple`.
- Strict option checking: submitted values for selects, multi-selects, radios, checkbox
  groups and single checkboxes must be among the field's options (for a checkbox, its checked
  value). Controlled by `$strictOptions` and `$strictOptionsMessage` in config, per field with
  `->strictOptions(bool)` and `->messages(['options' => '...'])`. Errors survive
  `redirect()->withInput()`.
- `Contracts\ChoiceFieldInterface` (`getAllowedValues()`, `getStrictOptions()`) for custom
  fields with a fixed set of values; `Form::usesStrictOptions()`.
- File uploads: `file()` field with `required()`, `multiple()`, `maxSize()` (kilobytes),
  `extensions()`, `mimes()`, `image()` and `maxDimensions()`, mapped to CodeIgniter's file
  rules, plus a generated `accept` attribute. Optional file fields are only validated when a
  file is uploaded. Uploads are never part of `validated()`; get them with
  `Form::getFile()`, `Form::getFiles()` and `Form::hasUploadedFile()`.
- Forms with a file field are sent as multipart automatically.
- After a failed submission, file fields show a "choose the file again" hint
  (`$fileReselectMessage`, theme class `reselect`), since browsers cannot refill them.
- `Contracts\FileFieldInterface` for custom upload fields; new `field_file` view.
- Theme templates missing from a theme's views folder fall back to the package's bundled copy
  of that theme, so views published before an upgrade keep working with new field types.
  `php spark forms:publish` adds the missing templates without touching customised ones.
- Layout: `row()` places fields side by side and `fieldset()` groups them under a titled
  section (`description()`, `id()`); both nest and take `addClass()`. Fields and fieldsets get
  `width(1-12)` for their column inside a row; fields without a width share the row. Layout is
  defined in the form class and rendered by `<?= $form ?>` and `fields()`; fields themselves
  stay a flat list, so values, validation and `field()` are unaffected.
- `Form::getLayout()`, `Layout\Row`, `Layout\Fieldset`; new `layout_row` and
  `layout_fieldset` views and theme classes `row`, `column` (`{width}` placeholder),
  `column_auto`, `section`, `section_legend`, `section_description`.
- New inputs: `time()`, `datetimeLocal()`, `color()` (values lower-cased) and `search()`.
  `date()`, `time()` and `datetimeLocal()` share `min()`, `max()` and `step()` through the new
  `Fields\DateTimeInputField` base class.
- Custom field type registry: `$fieldTypes = ['money' => MoneyField::class]` in config makes
  `$this->money('price')` a builder method, including inline chaining and inside groups.
  Registrations are checked: camelCase names, no clashes with built-in types or `Form`
  methods, and classes must implement `FieldInterface`.
- `Form::addField($type, $name, $label)` creates any built-in or registered field by type key;
  `Form::hasFieldType()`; `Support\FieldTypes::BUILT_IN` lists the built-in keys.
- Custom fields can render their own view: a `VIEW` containing a path (`fields/money` or
  `Vendor\Package\Views\money`) is loaded as given instead of from the theme. Field views can
  ask `$class()` for any theme class key.

### Changed

- **Behaviour change:** strict option checking is on by default. A form that accepted values
  outside a field's options in 0.1 now rejects them. Set `$strictOptions = false` in
  `app/Config/Forms.php` to restore the 0.1 behaviour, or opt single fields out with
  `->strictOptions(false)`. Existing `in_list` rules keep working; they are now optional for
  option fields.
- `FieldInterface` has three new methods: `getEachRules()`, `isMultiple()` and `getWidth()`. Custom fields
  extending `Fields\Field` get both automatically; classes implementing the interface
  directly must add them.
- `getOptionId()` moved from `Radio` into the shared `HasOptions` trait.
- `fields()` with no arguments now renders the form's layout (rows and fieldsets) instead of
  a flat list. `fields('a', 'b')` with names is unchanged. Published `form.php` views get
  layouts automatically.
- The `field_select` view uses `$inputName` and `$values`. Re-publish the views
  (`php spark forms:publish --force`) or apply the same change if you customised it.

### Fixed

- An array posted to a single-value field (for example `nickname[]=x`) could pass validation
  on fields without rules and reach `validated()` as an array. Single-value fields now treat
  a posted array as no value, and an unticked checkbox's array counts as unticked.

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

[Unreleased]: https://github.com/etus-ephraitech/ci4-forms/compare/v0.2.0...HEAD
[0.2.0]: https://github.com/etus-ephraitech/ci4-forms/compare/v0.1.0...v0.2.0
[0.1.0]: https://github.com/etus-ephraitech/ci4-forms/releases/tag/v0.1.0
