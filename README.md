# ephraitech/ci4-forms

[![Tests](https://github.com/etus-ephraitech/ci4-forms/actions/workflows/tests.yml/badge.svg)](https://github.com/etus-ephraitech/ci4-forms/actions/workflows/tests.yml)

A form builder for CodeIgniter 4.7+. Define a form once in PHP and the package
renders it with Bootstrap 5, validates it with CodeIgniter's own Validation
service, repopulates input after errors, and hands back only clean data for
the fields you defined.

```php
class BookingForm extends Form
{
    protected function define(): void
    {
        $this->text('full_name')->rules('required|max_length[100]');
        $this->email('email')->rules('required|valid_email');
        $this->select('service', 'Service', ['fitting' => 'Suit fitting', 'alterations' => 'Alterations'])
            ->placeholder('Choose a service')
            ->rules('required|in_list[fitting,alterations]');
        $this->submit('Book now');
    }
}
```

```php
// Controller
$form = new BookingForm();

if (! $form->validate()) {
    return view('bookings/create', ['form' => $form]);
}

$this->bookingModel->insert($form->validated());
```

```php
<!-- View -->
<?= $form ?>
```

## Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Defining forms](#defining-forms)
- [Field types](#field-types)
- [Field options](#field-options)
- [Multiple values](#multiple-values)
- [Strict option checking](#strict-option-checking)
- [File uploads](#file-uploads)
- [Layout](#layout)
- [Custom field types](#custom-field-types)
- [Controllers and validation](#controllers-and-validation)
- [Filling forms for editing](#filling-forms-for-editing)
- [Rendering](#rendering)
- [Configuration](#configuration)
- [Customising the views](#customising-the-views)
- [Security notes](#security-notes)
- [Testing](#testing)
- [Upgrading from 0.1](#upgrading-from-01)
- [Versioning](#versioning)

## Requirements

- PHP 8.2 or newer
- CodeIgniter 4.7 or newer

CodeIgniter 4.6 is not supported: every 4.6 release has published security
advisories, and Composer blocks installing them by default. Upgrade to 4.7.

## Installation

```bash
composer require ephraitech/ci4-forms
```

That is all. CodeIgniter discovers the package automatically, including the
`forms:publish` command.

To customise settings, create `app/Config/Forms.php` (see
[Configuration](#configuration)), or let the publish command create it:

```bash
php spark forms:publish --config
```

## Defining forms

### Form classes

Use a class for any form you render or validate in more than one place, such
as create and edit screens. Override `define()` and add fields:

```php
namespace App\Forms;

use Ephraitech\Forms\Form;

class ContactForm extends Form
{
    protected function define(): void
    {
        $this->text('name', 'Your name')->rules('required|max_length[100]');
        $this->email('email')->rules('required|valid_email');
        $this->textarea('message')->rows(5)->rules('required|max_length[2000]');
        $this->submit('Send message');
    }
}
```

The form name is derived from the class: `ContactForm` becomes `contact`,
`UserProfileForm` becomes `user_profile`. Pass a name to override it, for
example to put two instances on one page: `new ContactForm('footer_contact')`.

### Inline forms

For one-off forms, build inline and finish with `end()`, which returns the
form from the last field:

```php
$form = Form::make('newsletter')
    ->email('email')->rules('required|valid_email')
    ->submit('Subscribe')
    ->end();
```

### Form settings

```php
$form->action(url_to('contact.send'));   // relative paths go through site_url()
$form->method('patch');                  // get, post, put, patch or delete
$form->multipart();                      // enctype for file uploads
$form->id('contact-form');               // default: the form name
$form->addClass('card p-3');
$form->attribute('data-turbo', 'false');
```

PUT, PATCH and DELETE forms are sent as POST with a hidden `_method` field,
which CodeIgniter's router understands.

## Field types

| Builder                                  | Renders                           | Notes                                              |
| ---------------------------------------- | --------------------------------- | -------------------------------------------------- |
| `text($name, $label)`                    | `<input type="text">`             |                                                    |
| `email($name, $label)`                   | `<input type="email">`            | Pair with `valid_email`                            |
| `password($name, $label)`                | `<input type="password">`         | Never repopulated, never trimmed                   |
| `number($name, $label)`                  | `<input type="number">`           | `min`/`max` from rules; `step()`                   |
| `tel($name, $label)`                     | `<input type="tel">`              |                                                    |
| `url($name, $label)`                     | `<input type="url">`              | Pair with `valid_url_strict`                       |
| `search($name, $label)`                  | `<input type="search">`           |                                                    |
| `date($name, $label)`                    | `<input type="date">`             | `min()`/`max()`/`step()`; `valid_date[Y-m-d]`      |
| `time($name, $label)`                    | `<input type="time">`             | `min()`/`max()`/`step()`; `valid_date[H:i]`        |
| `datetimeLocal($name, $label)`           | `<input type="datetime-local">`   | `valid_date[Y-m-d\TH:i]`                           |
| `color($name, $label)`                   | `<input type="color">`            | Value lower-cased, e.g. `#1a73e8`                  |
| `hidden($name, $default)`                | `<input type="hidden">`           | Always validate hidden values                      |
| `textarea($name, $label)`                | `<textarea>`                      | `rows()`                                           |
| `select($name, $label, $options)`        | `<select>`                        | Option groups; `multiple()`, `size()`              |
| `radio($name, $label, $options)`         | Radio group in a `<fieldset>`     | `inline()`                                         |
| `checkbox($name, $label)`                | Single checkbox                   | `checkedValue()`, `inline()`                       |
| `checkboxes($name, $label, $options)`    | Checkbox group in a `<fieldset>`  | Value is a list; `inline()`                        |
| `file($name, $label)`                    | `<input type="file">`             | See [File uploads](#file-uploads)                  |
| `submit($label, $name = 'submit')`       | `<button type="submit">`          | Never validated or returned                        |

The label defaults to the humanised name: `appointment_date` becomes
"Appointment date".

Any type can also be created by its key, which is handy when building forms
from data: `$form->addField('datetime_local', 'starts_at', 'Starts at')`.
Your own field types are covered under [Custom field types](#custom-field-types).

## Field options

Every field supports:

```php
$this->text('phone', 'Phone number')
    ->rules('required|regex_match[/^\+256\d{9}$/]')   // string or array, callables allowed
    ->messages(['regex_match' => 'Enter a Ugandan number.'])
    ->help('Spaces and dashes are fine.')
    ->placeholder('0772 123 456')
    ->default('+256')
    ->addClass('form-control-lg')
    ->attribute('data-mask', 'phone')
    ->autocomplete('tel')
    ->autofocus()
    ->readonly()                                       // still submitted and validated
    ->disabled()                                       // not submitted, not validated, not returned
    ->id('custom-id');                                 // default: <form name>_<field name>
```

HTML attributes are derived from the rules automatically: `required`,
`maxlength`/`minlength` from `max_length`, `min_length` and `exact_length`,
and `min`/`max` on number fields from `greater_than_equal_to` and
`less_than_equal_to`. They help browsers and screen readers; validation
always happens on the server.

### Options for select and radio

```php
// Array of value => label
$this->select('service', 'Service', ['fitting' => 'Suit fitting', 'alterations' => 'Alterations']);

// Closure, called once and only when the form is rendered or validated
$this->select('service_id', 'Service', fn () => model(ServiceModel::class)->findColumn('name', 'id'));

// Option groups (select only)
$this->select('style', 'Style', [
    'Suits'  => ['1' => 'Two-piece', '2' => 'Three-piece'],
    'Shirts' => ['3' => 'Formal'],
]);
```

Users can submit any value regardless of what the list shows, so the package
rejects values that are not among the options automatically (see
[Strict option checking](#strict-option-checking)); no `in_list` rule is
needed.

## Multiple values

A multi-select and a checkbox group hold a list:

```php
$this->select('languages', 'Languages', ['en' => 'English', 'lg' => 'Luganda', 'sw' => 'Swahili'])
    ->multiple()
    ->size(3);

$this->checkboxes('interests', 'Interests', ['suits' => 'Suits', 'shirts' => 'Shirts'])
    ->rules('required')                     // at least one ticked
    ->eachRules('max_length[20]')           // every ticked value
    ->inline();
```

Both are submitted as `name[]`, and `validated()` returns a list such as
`['suits', 'shirts']`, or `[]` when nothing is chosen, never `null`.
`rules()` applies to the list as a whole; `eachRules()` applies to every item,
and an empty list always passes it.

Values are also forced into the right shape: a single value posted to a list
field is wrapped in a list, and an array posted to a single-value field is
treated as empty, so it can never reach your database as an array.

## Strict option checking

Selects, multi-selects, radios, checkbox groups and single checkboxes only
accept their own options (for a single checkbox, its checked value). A
tampered value such as `service=free_suit` gets "Please choose a valid option
for Service." This runs after your rules, ignores empty values (that is what
`required` is for) and survives `redirect()->withInput()`.

```php
// Per field: options filled in by JavaScript
$this->select('city')->strictOptions(false);

// Per field message
$this->radio('payment', 'Payment', $methods)
    ->messages(['options' => 'We only accept cash or mobile money.']);
```

Turn it off globally with `$strictOptions = false`, or change the message
with `$strictOptionsMessage`, in `app/Config/Forms.php`.

## File uploads

```php
$this->file('photo', 'Profile photo')
    ->required()
    ->maxSize(2048)                          // kilobytes
    ->image()
    ->mimes(['image/jpeg', 'image/png']);

$this->file('documents', 'Supporting documents')
    ->multiple()
    ->extensions(['pdf', 'docx'])
    ->mimes(['application/pdf', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']);
```

| Method                  | CodeIgniter rule | Checks                                  |
| ----------------------- | ---------------- | --------------------------------------- |
| `required()`            | `uploaded`       | A file was uploaded                     |
| `maxSize($kb)`          | `max_size`       | File size in kilobytes                  |
| `extensions([...])`     | `ext_in`         | The file name's extension               |
| `mimes([...])`          | `mime_in`        | The file's real content type            |
| `image()`               | `is_image`       | The file is an image                    |
| `maxDimensions($w, $h)` | `max_dims`       | Image size in pixels                    |

Extensions come from the user's file name and are easy to fake, so combine
`extensions()` with `mimes()` or `image()`. The helpers also set the input's
`accept` attribute, so the browser's file picker shows matching files.

An optional file field is only validated when a file is uploaded. A form with
a file field is sent as multipart automatically.

Uploads are never part of `validated()`. Store them after validation passes:

```php
if (! $form->validate()) {
    return view('profile/edit', ['form' => $form]);
}

$data = $form->validated();
$data['photo_path'] = $form->getFile('photo')?->store('photos');

foreach ($form->getFiles('documents') as $file) {
    $file->store('documents');
}
```

Browsers cannot refill a file input, so after a failed submission each file
field without its own error shows "Please choose the file again."
(`$fileReselectMessage`; set it to `null` to hide it).

## Layout

Rows place fields side by side and fieldsets group them under a title. Both
are defined in the form class, so `<?= $form ?>` renders the full layout:

```php
protected function define(): void
{
    $this->row(function () {
        $this->text('first_name')->width(6);
        $this->text('last_name')->width(6);
    });

    $this->fieldset('Delivery address', function () {
        $this->text('street');

        $this->row(function () {
            $this->text('city')->width(8);
            $this->text('postcode');            // no width: shares the rest
        });
    })->description('Where should we deliver?');
}
```

Widths are out of 12 and stack on small screens. Groups nest freely, take
`addClass()`, and fieldsets can sit side by side in a row with `->width(6)`.
Hidden fields in a row take no column. Each closure also receives the form,
which suits arrow functions and inline forms.

Layout only decides where fields appear: validation, `validated()` and
`field('city')` work exactly the same. `fields()` without arguments renders
the layout; `fields('a', 'b')` renders the named fields without it. Column
and section classes are in the theme config (`row`, `column` with a
`{width}` placeholder, `column_auto`, `section`, `section_legend`,
`section_description`).

## Custom field types

Write a field class, usually by extending `Ephraitech\Forms\Fields\InputField`
or `Field`:

```php
namespace App\Forms\Fields;

use Ephraitech\Forms\Config\Forms;
use Ephraitech\Forms\Fields\InputField;

class MoneyField extends InputField
{
    public const TYPE = 'money';

    public const VIEW = 'forms/fields/money';    // app/Views/forms/fields/money.php

    protected function normaliseScalar(mixed $value, Forms $config): mixed
    {
        if (is_string($value)) {
            $value = str_replace([',', ' '], '', $value);   // "1,250,000" -> "1250000"
        }

        return parent::normaliseScalar($value, $config);
    }
}
```

Register it in `app/Config/Forms.php`:

```php
public array $fieldTypes = [
    'money' => \App\Forms\Fields\MoneyField::class,
];
```

It then works like a built-in field, including chaining and layout groups:

```php
$this->money('budget', 'Budget')->rules('permit_empty|is_natural');
```

Names are camelCase and must not clash with built-in types or `Form`
methods; invalid registrations fail with a clear message. A `VIEW` without a
path (such as `field_input`) comes from the active theme; a `VIEW` with a path
is loaded as given and receives the same variables as the package's views.
Fields with a fixed set of values can implement `ChoiceFieldInterface` to get
strict option checking.

## Controllers and validation

`validate()` reads the current request (POST data for POST, PUT, PATCH and
DELETE forms, the query string for GET forms, the decoded body for JSON
requests), runs the rules and remembers the submission. You can also pass an
`IncomingRequest` or a plain array.

### Re-render on failure

```php
public function store()
{
    $form = new BookingForm();

    if (! $form->validate()) {
        return view('bookings/create', ['form' => $form]);
    }

    $this->bookingModel->insert($form->validated());

    return redirect()->to('/bookings')->with('message', 'Booking saved.');
}
```

### Redirect back on failure

```php
if (! $form->validate()) {
    return redirect()->back()->withInput();
}
```

Errors and input are carried through the session and shown when the form is
rendered on the next request. Each form only picks up its own old input and
errors, so several forms on one page never leak into each other.

### validated()

`validated()` returns only the form's enabled fields, so extra request keys
never reach your model. Values are cleaned according to the config:

- strings are trimmed (except passwords)
- empty strings become `null`
- an unticked checkbox returns `0` instead of being missing

Leave out fields that are needed for validation but should not be saved:

```php
$this->userModel->insert($form->validated('password_confirm'));
```

`validated()` throws a `FormException` if called before `validate()` or after
a failed validation, so unvalidated data cannot be saved by mistake.

### Errors

```php
$form->hasErrors();
$form->hasError('email');
$form->getError('email');      // ?string
$form->getErrors();            // ['email' => '...']
```

Add errors that need more than a validation rule, such as a database check.
They also survive `redirect()->withInput()`:

```php
if (! $form->validate()) {
    return view('bookings/create', ['form' => $form]);
}

if ($this->bookingModel->isSlotTaken($form->validated()['appointment_date'])) {
    $form->setError('appointment_date', 'That date is fully booked.');

    return view('bookings/create', ['form' => $form]);
}
```

### Cleaning input before validation

Override `prepareForValidation()` in a form class. The returned data is what
gets validated, repopulated and returned by `validated()`:

```php
protected function prepareForValidation(array $data): array
{
    if (isset($data['phone']) && is_string($data['phone'])) {
        $data['phone'] = preg_replace('/[\s\-]/', '', $data['phone']);
    }

    return $data;
}
```

### Reusing the rules elsewhere

`getValidationRules()` returns the rules in CodeIgniter's format, for example
for a controller's own `$this->validate()` or an API endpoint.

## Filling forms for editing

```php
public function edit(int $id)
{
    $booking = $this->bookingModel->find($id);

    $form = (new BookingForm())
        ->method('patch')
        ->action(url_to('bookings.update', $id))
        ->fill($booking)          // array, Entity or object
        ->remove('terms');        // drop fields the edit screen does not need

    $form->getField('submit')->label('Save changes');

    return view('bookings/edit', ['form' => $form]);
}
```

Each field shows, in order of priority: the submitted value, old input after
a redirect, the filled data, then the field's default. Once a submission
exists, a missing value means empty, so a checkbox the user unticked stays
unticked even if the saved record has it ticked.

## Rendering

### The whole form

```php
<?= $form ?>
```

### Custom layouts

```php
<?= $form->open() ?>
<?= $form->errorSummary() ?>

<div class="row">
    <div class="col-md-6"><?= $form->field('full_name') ?></div>
    <div class="col-md-6"><?= $form->field('email') ?></div>
</div>

<?= $form->fields('service', 'notes') ?>
<?= $form->control('submit') ?>
<?= $form->close() ?>
```

| Method                  | Output                                                           |
| ----------------------- | ---------------------------------------------------------------- |
| `open($attributes)`     | `<form>` plus hidden form key, CSRF field and `_method` field    |
| `close()`               | `</form>`                                                        |
| `field($name)`          | Label, control, error and help text                              |
| `fields(...$names)`     | Several fields in the given order; all fields with no arguments  |
| `control($name)`        | The control only, for fully custom markup                        |
| `errorSummary()`        | A list of all errors linking to their fields, or an empty string |
| `render()` / `$form`    | The whole form                                                   |

Always render forms through `open()` or `<?= $form ?>`. The hidden `_form`
field it adds is how the package tells which form a submission belongs to.

Output is escaped, errors and help text are linked with `aria-describedby`,
invalid fields get `aria-invalid`, and radio groups use a `<fieldset>` and
`<legend>`.

## Configuration

Create `app/Config/Forms.php` and override only what you need:

```php
namespace Config;

use Ephraitech\Forms\Config\Forms as BaseForms;

class Forms extends BaseForms
{
    public bool $showErrorSummary = true;

    public function __construct()
    {
        parent::__construct();

        $this->themes['bootstrap5']['classes']['submit'] = 'btn btn-success';
    }
}
```

| Setting                   | Default        | Effect                                                         |
| ------------------------- | -------------- | -------------------------------------------------------------- |
| `$theme`                  | `'bootstrap5'` | Active theme                                                   |
| `$themes`                 | Bootstrap 5    | Views path, CSS classes and required marker per theme          |
| `$defaultMethod`          | `'post'`       | Method for forms that do not set one                           |
| `$csrf`                   | `true`         | Add the CSRF field to non-GET forms                            |
| `$spoofMethods`           | `true`         | Send PUT, PATCH and DELETE as POST with `_method`              |
| `$novalidate`             | `true`         | Disable browser validation so server messages show consistently |
| `$ruleAttributes`         | `true`         | Add `required`, `maxlength` and similar from rules             |
| `$showErrorSummary`       | `false`        | Show an error list above the fields in `<?= $form ?>`          |
| `$useOldInput`            | `true`         | Repopulate after `redirect()->withInput()`                     |
| `$prefixIdsWithFormName`  | `true`         | Ids like `booking_email`, unique across forms                  |
| `$trimStrings`            | `true`         | Trim strings before validation and in `validated()`            |
| `$emptyStringsToNull`     | `true`         | Empty strings become `null` in `validated()`                   |
| `$checkboxUncheckedValue` | `0`            | Value of an unticked checkbox in `validated()`                 |
| `$strictOptions`          | `true`         | Reject values outside a choice field's options                 |
| `$strictOptionsMessage`   | see config     | Message for such values; `{field}` becomes the label           |
| `$fileReselectMessage`    | see config     | Hint under file fields after a failed submission, or `null`    |
| `$fieldTypes`             | `[]`           | Custom field types, as name => class                           |

Scalar settings can also be set from `.env`, for example
`forms.showErrorSummary = true`.

## Customising the views

Copy the Bootstrap 5 views into your app:

```bash
php spark forms:publish --config
```

This creates `app/Views/forms/bootstrap5/` and an `app/Config/Forms.php`
pointing the theme at it. Edit the views freely. If you already have a config
file, the command prints the one line to add to it.

Running the command again skips files that exist, so your changes are safe,
and adds templates that are missing, for example after an upgrade that brings
new field types. Until then, missing templates are taken from the package
automatically. Use `--force` to overwrite your copies with the package's
latest versions.

Views receive prepared variables (`$name`, `$id`, `$label`, `$value`,
`$error`, `$help`, `$attributes` and more) plus two helpers: `$e($text)` to
escape and `$class('group')` for a theme class attribute. The variables are
documented at the top of `Ephraitech\Forms\Renderer` and in each view.

## Security notes

- Every value, label, message and option is escaped. The only raw HTML is the
  theme's `required_marker`, which comes from your config.
- `validated()` returns only defined, enabled fields, which blocks mass
  assignment through extra request keys.
- Disabled fields are never accepted from the request. Read-only and hidden
  fields are submitted by the browser and can be changed by the user, so
  validate them like any other field.
- Choice fields reject values outside their options automatically. Hidden
  fields have no options, so validate them against what is allowed (for
  example `is_not_unique[bookings.id]`).
- Uploads are checked by content with `mimes()` and `image()`; never trust the
  file name's extension alone.
- Passwords are never written back into the page.

## Testing

```bash
composer install
composer test
```

The suite runs against CodeIgniter's real Validation service, request
objects, session and view renderer.

## Upgrading from 0.1

Most apps need no changes. Check these four points:

1. **Strict option checking is on.** Values outside a field's options are now
   rejected. If a form relied on accepting them, set
   `->strictOptions(false)` on that field or `$strictOptions = false` in
   config. Existing `in_list` rules keep working and can be removed.
2. **Published views:** if you ran `forms:publish` in 0.1, update your
   `field_select.php` with the package's new version (it uses `$inputName`
   and `$values`), otherwise multi-selects post only one value. Then run
   `php spark forms:publish` to add the new templates; existing files are not
   touched.
3. **Custom fields implementing `FieldInterface` directly** must add
   `getEachRules()`, `isMultiple()` and `getWidth()`. Fields extending
   `Field` or `InputField` need no changes.
4. **`fields()` without arguments** now renders the layout. Forms without
   rows or fieldsets render exactly as before.

## Versioning

The package follows [Semantic Versioning](https://semver.org). Until 1.0,
minor releases (0.2, 0.3) may change the public API; each change is listed in
[CHANGELOG.md](CHANGELOG.md).

Planned for 0.3: loading forms from array or JSON schemas, built on
`addField()` and the field type registry.

## License

MIT. See [LICENSE](LICENSE).
