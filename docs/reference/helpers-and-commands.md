# Helpers and commands

## The `forms` helper

Load it with `helper('forms')`. Its functions are prefixed `forms_`, so it
never clashes with CodeIgniter's `form` helper, and both can be loaded
together.

| Function | Description |
| -------- | ----------- |
| <code>forms_load(array&#124;string $schema, ?SchemaPolicy $policy = null)</code> | Builds a form from a schema array or JSON text. Restricted policy unless another is passed |
| <code>forms_check(array&#124;string $schema, ?SchemaPolicy $policy = null)</code> | Checks a schema array or JSON text without throwing; returns a `SchemaCheck`. Restricted policy unless another is passed |
| <code>forms_present(Form $form, array&#124;object $data, bool $includeEmpty = true)</code> | Stored data as escaped HTML, with labels and option texts |

## Commands

### forms:publish

Copies the theme's views into `app/Views/forms/<theme>/` so you can edit them.
Files that exist are skipped, so your changes are kept; missing ones are
added.

```bash
php spark forms:publish [--theme bootstrap5] [--config] [--force]
```

| Option | Effect |
| ------ | ------ |
| `--theme` | The theme to publish (`bootstrap5`) |
| `--config` | Also creates `app/Config/Forms.php` pointing the theme at the copies, unless the file exists |
| `--force` | Overwrites copies published before; your changes to them are lost |

### forms:schema

Checks a schema file, or exports a form class as a schema.

```bash
php spark forms:schema check <file> [--trusted]
php spark forms:schema export <FormClass> [--output <file>]
```

| Option | Effect |
| ------ | ------ |
| `--trusted` | `check`: uses the trusted policy instead of the restricted one |
| `--output` | `export`: writes the schema to this file instead of printing it |

`check` lists every error and exits with a non-zero status when there are any,
so it can run in a deployment script. `export` creates the form class, so the
class must be loadable from the command line.
