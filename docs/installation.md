# Installation

## Requirements

| Requirement   | Version                                                     |
| ------------- | ----------------------------------------------------------- |
| PHP           | 8.2 or newer                                                |
| CodeIgniter   | 4.7 or newer                                                |
| Bootstrap     | 5.x on your pages, for the default theme's look             |

CodeIgniter 4.6 is not supported: every 4.6 release has published security
advisories, and Composer refuses to install them by default.

## Install with Composer

```bash
composer require ephraitech/ci4-forms
```

That is all the setup the package needs. CodeIgniter finds it automatically,
including its configuration, views, helper and `spark` commands. You can check
that the commands are available:

```bash
php spark list --simple | grep forms
```

```text
forms:publish
forms:schema
```

### Choosing a version constraint

Until version 1.0, a new minor version (0.4 to 0.5) may change behaviour, and
each such change is listed in the changelog with upgrade notes. A constraint
such as `^0.4` accepts bug-fix releases (0.4.1, 0.4.2) but not the next minor
version, so upgrades only happen when you choose them:

```bash
composer require ephraitech/ci4-forms:^0.4
```

## Add Bootstrap to your layout

The default theme renders Bootstrap 5 markup. If your layout does not include
Bootstrap yet, add its stylesheet:

```html
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
```

Forms need no JavaScript. Without Bootstrap they still work and stay
accessible; they only lose the styling.

## Turn on CSRF protection

Every form the package renders includes CodeIgniter's CSRF field, but
CodeIgniter only checks it when its CSRF filter is on. If your app does not
use it yet, enable it for all requests in `app/Config/Filters.php`:

```php title="app/Config/Filters.php"
public array $globals = [
    'before' => [
        'csrf',
    ],
    // ...
];
```

## Configuration (optional)

The defaults suit most apps. To change a setting, create
`app/Config/Forms.php` extending the package's configuration and override only
what differs:

```php title="app/Config/Forms.php"
<?php

namespace Config;

use Ephraitech\Forms\Config\Forms as BaseForms;

class Forms extends BaseForms
{
    public bool $showErrorSummary = true;
}
```

`php spark forms:publish --config` creates this file for you, together with a
copy of the views you can customise.

## Your first form

Put the four files from the [complete example](index.md#a-complete-example) on
the home page into your app, then run the migration that creates the table for
the messages:

```php title="app/Database/Migrations/2026-10-29-000000_CreateContactMessages.php"
--8<-- "docs/examples/app/Database/Migrations/2026-10-29-000000_CreateContactMessages.php"
```

```php title="app/Models/ContactMessageModel.php"
--8<-- "docs/examples/app/Models/ContactMessageModel.php"
```

```bash
php spark migrate
```

Open `/contact` in your browser, submit the form empty to see the error
messages, then send a real message and find it in the `contact_messages`
table.

Then follow [Getting started](getting-started.md) to build a booking feature
with a layout, a business rule and editing.
