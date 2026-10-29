# ephraitech/ci4-forms

[![Tests](https://github.com/etus-ephraitech/ci4-forms/actions/workflows/tests.yml/badge.svg)](https://github.com/etus-ephraitech/ci4-forms/actions/workflows/tests.yml)
[![Docs](https://github.com/etus-ephraitech/ci4-forms/actions/workflows/docs.yml/badge.svg)](https://etus-ephraitech.github.io/ci4-forms/)

**A form builder for CodeIgniter 4.7+.** Define a form once in PHP, and the
package renders it with Bootstrap 5, validates it with CodeIgniter's own
Validation service, puts the user's input back after an error, and hands you
only clean data for the fields you defined.

**[Read the documentation](https://etus-ephraitech.github.io/ci4-forms/)**

```bash
composer require ephraitech/ci4-forms
```

## Example

A form is a class:

```php
<?php

declare(strict_types=1);

namespace App\Forms;

use Ephraitech\Forms\Form;

class ContactForm extends Form
{
    protected function define(): void
    {
        $this->text('full_name', 'Full name')
            ->rules('required|max_length[100]')
            ->autocomplete('name');

        $this->email('email', 'Email address')
            ->rules('required|valid_email|max_length[150]')
            ->autocomplete('email');

        $this->select('topic', 'Topic', [
            'booking' => 'A booking',
            'pricing' => 'Prices',
            'other'   => 'Something else',
        ])
            ->placeholder('Choose a topic')
            ->rules('required');

        $this->textarea('message', 'Message')
            ->rows(5)
            ->rules('required|max_length[2000]');

        $this->submit('Send message');
    }
}
```

The controller shows it, and saves it when it is valid:

```php
<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Forms\ContactForm;
use App\Models\ContactMessageModel;
use CodeIgniter\HTTP\RedirectResponse;

class Contact extends BaseController
{
    public function index(): string
    {
        return view('contact', ['form' => new ContactForm()]);
    }

    public function send(): RedirectResponse|string
    {
        $form = new ContactForm();

        if (! $form->validate()) {
            return view('contact', ['form' => $form]);
        }

        model(ContactMessageModel::class)->insert($form->validated());

        return redirect()->to('/contact')->with('message', 'Thank you. We will reply within one working day.');
    }
}
```

The view prints the whole form, with labels, errors, the CSRF field and
Bootstrap classes:

```php
<h1>Contact us</h1>

<?php if (session('message')): ?>
    <div class="alert alert-success"><?= esc(session('message')) ?></div>
<?php endif ?>

<?= $form ?>
```

`validated()` returns only the fields the form defines, trimmed, with empty
optional values as `null`, so a visitor who adds `is_admin=1` to the request
cannot reach your model. The topic must be one of its three options, without
you writing a rule for it. This example is tested end to end in the
package's test suite.

## What it covers

- **Forms in code**: text, email, number, date and time, selects and
  multi-selects, radio and checkbox groups, file uploads, custom field types,
  and layouts with rows and sections.
- **Safe by default**: escaped output, unknown request keys dropped, choices
  checked against their options, passwords never written back, uploads checked
  by content.
- **Forms as data**: JSON schemas stored in a file or database, so admins and
  clients can edit forms, checked against a restrictive policy.
- **Showing submissions** with the form's labels and option texts.

## Documentation

| Start here | Then |
| ---------- | ---- |
| [Installation](https://etus-ephraitech.github.io/ci4-forms/installation/) | [Guides](https://etus-ephraitech.github.io/ci4-forms/guides/): layout, multiple choices, uploads, custom fields, views, option providers, admin-written forms, submissions, testing, APIs |
| [Getting started](https://etus-ephraitech.github.io/ci4-forms/getting-started/): a 20-minute tutorial | [Concepts](https://etus-ephraitech.github.io/ci4-forms/concepts/): how values, validation and security work |
| [Upgrading](https://etus-ephraitech.github.io/ci4-forms/upgrading/) | [Reference](https://etus-ephraitech.github.io/ci4-forms/reference/): every method, setting, schema key, helper and command |

## Requirements

- PHP 8.2 or newer
- CodeIgniter 4.7 or newer
- Bootstrap 5 on your pages, for the default look

## Versioning

The package follows [Semantic Versioning](https://semver.org). Until 1.0, a
minor version may change behaviour, so use a constraint such as `^0.5` and
read the [upgrade notes](https://etus-ephraitech.github.io/ci4-forms/upgrading/). Changes are listed in
[CHANGELOG.md](CHANGELOG.md).

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md) for running the tests and working on the
documentation.

## License

MIT. See [LICENSE](LICENSE).
