# ci4-forms

**A form builder for CodeIgniter 4.7+.** Define a form once in PHP, and the
package renders it with Bootstrap 5, validates it with CodeIgniter's own
Validation service, puts the user's input back after an error, and hands you
only clean data for the fields you defined.

```bash
composer require ephraitech/ci4-forms
```

## A complete example

A contact form that saves messages to the database. The form is a class:

```php title="app/Forms/ContactForm.php"
--8<-- "docs/examples/app/Forms/ContactForm.php"
```

The controller shows the form, and saves it when it is valid:

```php title="app/Controllers/Contact.php"
--8<-- "docs/examples/app/Controllers/Contact.php"
```

The view prints the whole form, including labels, error messages, the CSRF
field and Bootstrap classes:

```php title="app/Views/contact.php"
--8<-- "docs/examples/app/Views/contact.php"
```

Two routes connect them:

```php title="app/Config/Routes.php"
--8<-- "docs/examples/app/Config/contact-routes.php"
```

!!! tip "These examples are tested"
    Every example in this documentation is a real file in the package's
    repository, and the test suite runs it against CodeIgniter. This one is
    run end to end: the page is requested, an invalid message is shown again
    with its errors, and a valid one is saved to a database. You can copy
    examples knowing they work with the version you install.

## What happens when someone submits

- If anything is invalid, the same page comes back with each error under its
  field and everything the visitor typed still filled in.
- `validated()` returns only the four fields the form defines, trimmed, with
  empty optional values as `null`. Anything else a visitor adds to the request,
  such as an `is_admin` field, never reaches the model.
- The topic must be one of the three options. A value sent from the browser's
  developer tools, like `free_suit`, is rejected without you writing a rule
  for it.

## What the package covers

Forms in code
:   Text, email, number, date and time, select and multi-select, radio and
    checkbox groups, file uploads, custom field types, and layouts with rows
    and sections. Rules come from CodeIgniter's Validation; HTML attributes
    such as `required` and `maxlength` follow from them.

Safe by default
:   Output is escaped, unknown request keys are dropped, choices are checked
    against their options, passwords are never written back into a page, and
    uploads are checked by their real content.

Forms as data
:   A form can also be a JSON schema stored in a file or a database, so admins
    and clients can edit forms without code. Schemas written by admins are
    checked against a restrictive policy that blocks anything that could harm
    the site or its visitors.

Showing what was submitted
:   Stored submissions are shown with the form's labels and option texts,
    so admins read "Prices" instead of `pricing`.

## Requirements

- PHP 8.2 or newer
- CodeIgniter 4.7 or newer
- Bootstrap 5 on your pages, for the default look (the markup works without it)

Continue with [Installation](installation.md).
