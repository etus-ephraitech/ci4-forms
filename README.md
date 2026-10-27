# ephraitech/ci4-forms

[![Tests](https://github.com/etus-ephraitech/ci4-forms/actions/workflows/tests.yml/badge.svg)](https://github.com/etus-ephraitech/ci4-forms/actions/workflows/tests.yml)

A form builder for CodeIgniter 4. Define a form once in PHP and the package
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
- [Controllers and validation](#controllers-and-validation)
- [Filling forms for editing](#filling-forms-for-editing)
- [Rendering](#rendering)
- [Configuration](#configuration)
- [Customising the views](#customising-the-views)
- [Security notes](#security-notes)
- [Testing](#testing)
- [Versioning](#versioning)

## Requirements

- PHP 8.2 or newer
- CodeIgniter 4.6 or newer (tested on 4.6 and 4.7)

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
| `date($name, $label)`                    | `<input type="date">`             | `min()`/`max()`; validate with `valid_date[Y-m-d]` |
| `hidden($name, $default)`                | `<input type="hidden">`           | Always validate hidden values                      |
| `textarea($name, $label)`                | `<textarea>`                      | `rows()`                                           |
| `select($name, $label, $options)`        | `<select>`                        | Option groups; placeholder as empty option         |
| `radio($name, $label, $options)`         | Radio group in a `<fieldset>`     | `inline()`                                         |
| `checkbox($name, $label)`                | Single checkbox                   | `checkedValue()`, `inline()`                       |
| `submit($label, $name = 'submit')`       | `<button type="submit">`          | Never validated or returned                        |

The label defaults to the humanised name: `appointment_date` becomes
"Appointment date".

Custom field classes can be added with `$form->add(new MyField('name'))` as
long as they implement `Ephraitech\Forms\Contracts\FieldInterface`.

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

Always validate the submitted value against the options, for example with
`in_list[...]` or `is_not_unique[services.id]`, because users can submit any
value regardless of what the list shows.

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

Running the command again skips files that exist, so your changes are safe.
Use `--force` to overwrite them with the package's latest versions.

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
- Validate select, radio and hidden values against what is allowed
  (`in_list`, `is_not_unique`), not just against what the page showed.
- Passwords are never written back into the page.

## Testing

```bash
composer install
composer test
```

The suite runs against CodeIgniter's real Validation service, request
objects, session and view renderer.

## Versioning

The package follows [Semantic Versioning](https://semver.org). Until 1.0,
minor releases (0.2, 0.3) may change the public API; each change is listed in
[CHANGELOG.md](CHANGELOG.md).

Planned for 0.2: file uploads, multi-selects, field groups and rows, and a
custom field type registry. Planned for 0.3: loading forms from array or JSON
schemas.

## License

MIT. See [LICENSE](LICENSE).
