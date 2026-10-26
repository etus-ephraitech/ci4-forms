# ephraitech/ci4-forms

A developer-side form builder for CodeIgniter 4. Define a form once in code and
let the package render it (Bootstrap 5), validate it (CodeIgniter's own
Validation service), repopulate old input, show per-field errors, and hand you
back only the data you defined.

> **Status:** under active development towards `v0.1.0`. The public API is not
> yet stable; do not use in production until the first tagged release.

## Requirements

- PHP 8.2 or newer
- CodeIgniter 4.5 or newer

## Installation

Once published on Packagist:

```bash
composer require ephraitech/ci4-forms
```

### Local development (before publishing)

Add a path repository to the host application's `composer.json`, pointing at
your local clone:

```json
"repositories": [
    {
        "type": "path",
        "url": "../ci4-forms",
        "options": { "symlink": true }
    }
]
```

Then require the development branch:

```bash
composer require ephraitech/ci4-forms:dev-master
```

With `symlink` enabled, changes in the package folder are picked up by the host
application immediately.

## Running the tests

```bash
composer install
composer test
```

## Roadmap

- **v0.1** — form classes and inline forms, core field types, CI4 validation
  integration, old-input handling, `fill()` and `validated()`, Bootstrap 5
  renderer
- **v0.2** — file uploads, multi-selects, field groups and rows, custom field
  type registry, publishable views
- **v0.3** — array/JSON schema loading

## License

MIT. See [LICENSE](LICENSE).
