# Getting started

In this tutorial you build appointment booking for a small business: visitors
book an appointment, staff see a list of bookings, and anyone can change a
booking. Along the way you use the ideas that the rest of the package builds
on. It takes about 20 minutes.

By the end you will know how to:

- define a form as a class, with rules, options and a two-column layout
- show it, validate it, and save only clean data
- add a rule that validation rules cannot express
- reuse the same form to edit a saved record

!!! info "Before you start"
    You need a CodeIgniter 4.7 app with the package [installed](installation.md)
    and a working database connection. The views below are kept minimal; in your
    app they would sit inside your layout, which should load Bootstrap 5.

## 1. Create the table

Bookings need a table. Create a migration:

```php title="app/Database/Migrations/2026-10-29-000100_CreateAppointments.php"
--8<-- "docs/examples/app/Database/Migrations/2026-10-29-000100_CreateAppointments.php"
```

The time is stored as `HH:MM` text because that is exactly what a browser's
time picker sends, so the value goes into the database and back into the form
unchanged.

Run it:

```bash
php spark migrate
```

And add a model for the table:

```php title="app/Models/AppointmentModel.php"
--8<-- "docs/examples/app/Models/AppointmentModel.php"
```

## 2. Define the form

A form is a class with a `define()` method that adds its fields:

```php title="app/Forms/AppointmentForm.php"
--8<-- "docs/examples/app/Forms/AppointmentForm.php"
```

Each part does a specific job:

Fields and labels
:   `text()`, `tel()`, `email()`, `select()`, `date()`, `time()` and
    `textarea()` each add a field. The first argument is the name used in the
    request and the database; the second is the label people see. Leave the
    label out and it is made from the name: `full_name` becomes "Full name".

Rules
:   `rules()` takes CodeIgniter's own validation rules. The package also turns
    them into HTML attributes, so `required|max_length[100]` adds `required`
    and `maxlength="100"` to the input, which helps screen readers and mobile
    keyboards. Validation still always happens on the server.

Options
:   The service dropdown lists three options from a constant, which the list
    page reuses later. You do not need an `in_list` rule: a value that is not
    one of the options is rejected automatically, even if someone changes the
    page with their browser's developer tools.

Layout
:   `row()` puts the fields defined inside it side by side, and `width()` sets
    how many of 12 columns each takes. Name and phone share the first row; the
    service, date and time share the second. On phones the columns stack.

Extras
:   `placeholder()`, `help()`, `rows()`, and `min()`/`max()` on the time
    picker shape how fields look and behave.

!!! tip "Why a class?"
    The same form is used to book and to change an appointment. Defining it
    once means the fields, rules and layout can never drift apart between the
    two pages.

## 3. Add the routes

Add these routes to `app/Config/Routes.php`:

```php title="app/Config/Routes.php"
--8<-- "docs/examples/app/Config/appointment-routes.php"
```

The last route uses `PATCH`, the HTTP method for changing part of a record.
Browsers can only send `GET` and `POST`, so the package handles this for you,
as step 7 shows.

## 4. Book an appointment

Create the controller with the two booking actions. `new()` shows the empty
form; `create()` handles what was submitted:

```php title="app/Controllers/Appointments.php"
--8<-- "docs/examples/app/Controllers/Appointments.php:create"
```

Two small helpers keep the actions short. The first builds the form for
booking and sets where it is sent:

```php title="app/Controllers/Appointments.php"
--8<-- "docs/examples/app/Controllers/Appointments.php:forms"
```

The second shows the form page. Its partner, `findOrFail()`, is used in step 7:

```php title="app/Controllers/Appointments.php"
--8<-- "docs/examples/app/Controllers/Appointments.php:helpers"
```

The view prints the whole form with one line:

```php title="app/Views/appointments/form.php"
--8<-- "docs/examples/app/Views/appointments/form.php"
```

That line renders the labels, the inputs in their rows, the CSRF field, error
messages under each field, and whatever the visitor typed when the form comes
back with errors.

Here is what happens in `create()`:

1. `validate()` reads the submitted data and checks every rule. If anything
   fails, the page is shown again with the same form object, which now knows
   the submitted values and the errors.
2. `validated()` returns only the fields the form defines, ready for the
   model. Strings are trimmed and empty optional fields become `null`. Any
   extra field someone adds to the request, such as `is_admin`, is dropped.

**Try it.** Open `/appointments/new` and submit the form empty: each required
field shows its error. Fill in your name, leave the phone empty and submit
again: your name is still there. Then book a real appointment.

## 5. Add a business rule

Validation rules check each value on its own. Some rules need more: an
appointment must not be in the past, or a time slot must be free. The
`isBookable()` method that `create()` calls handles this:

```php title="app/Controllers/Appointments.php"
--8<-- "docs/examples/app/Controllers/Appointments.php:rule"
```

It runs only after validation passes, so `validated()` is safe to use.
`setError()` adds an error to a field exactly like a failed rule: it appears
under the date input, and `isValid()` now returns `false`, so `create()` shows
the form again. A check against the database for double bookings would go here
too.

**Try it.** Book an appointment for a date last year.

## 6. List the appointments

Add the list action to the controller:

```php title="app/Controllers/Appointments.php"
--8<-- "docs/examples/app/Controllers/Appointments.php:index"
```

And its view:

```php title="app/Views/appointments/index.php"
--8<-- "docs/examples/app/Views/appointments/index.php"
```

The table shows "Suit fitting" rather than the stored `fitting`, by looking the
value up in the form's `SERVICES` constant.

## 7. Change an appointment

The edit actions reuse the form:

```php title="app/Controllers/Appointments.php"
--8<-- "docs/examples/app/Controllers/Appointments.php:edit"
```

The form for editing comes from `editForm()`, which you added in step 4 with
`createForm()`. It differs in three ways:

- `method('patch')` sends the form as `PATCH`. The browser still sends `POST`,
  with a hidden `_method` field that CodeIgniter's router understands.
- `action()` points at this booking's address.
- The button says "Save changes" instead of "Book appointment".

In `edit()`, `fill()` puts the saved booking into the form. When the form is
submitted and comes back with an error, it shows what the visitor submitted,
not the saved values, so nothing they typed is lost. `update()` checks the
booking exists before doing anything, then validates and saves exactly like
`create()`.

!!! note "Why the 303?"
    After a change, `update()` redirects with status 303, "See Other", which
    tells any client to load the list with `GET`. CodeIgniter would otherwise
    answer a genuine `PATCH` request (from JavaScript or an API client) with
    307, which asks the client to repeat the `PATCH` at the new address.

**Try it.** Open the list, choose **Change** on your booking, pick another
service and save.

## The complete controller

??? example "app/Controllers/Appointments.php"

    ```php
    --8<-- "docs/examples/app/Controllers/Appointments.php"
    ```

## What you learned

- A form is a class whose `define()` adds fields; one class serves both the
  create and the edit page.
- Rules are CodeIgniter's own, and choices are checked against their options
  automatically.
- `row()` and `width()` lay fields out in columns without touching the view.
- `<?= $form ?>` renders everything, including errors and the visitor's input.
- `validate()` checks a submission, `validated()` returns only clean, defined
  fields, and `setError()` adds errors from your own checks.
- `fill()` loads saved data for editing, and `method('patch')` sends the form
  as `PATCH`.

!!! tip "This tutorial is tested"
    The files in this tutorial are real files in the package's repository. The
    test suite runs the booking, listing, the date rule and editing over HTTP
    against a database, so the code works with the version you install.

The [home page](index.md#what-the-package-covers) lists what else the package
covers, such as file uploads, multiple choices, forms stored as data, and
showing submissions.
