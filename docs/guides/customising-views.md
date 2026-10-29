# Customising views

The package renders Bootstrap 5 markup by default. You can change it at three
levels, from a single class to a whole theme of your own.

## 1. Change classes

Every class the views use comes from the theme in the configuration. Override
single classes in the constructor of `app/Config/Forms.php`:

```php title="app/Config/Forms.php"
--8<-- "docs/examples/app/Config/Forms.php:settings"

--8<-- "docs/examples/app/Config/Forms.php:constructor"
```

The first two lines change classes for every form: green submit buttons and
more space between fields. The class keys are listed in
`vendor/ephraitech/ci4-forms/src/Config/Forms.php`. The rest of the
constructor creates a second theme, explained in section 3.

## 2. Edit the views

To change the HTML itself, copy the views into your app:

```bash
php spark forms:publish --config
```

This creates `app/Views/forms/bootstrap5/` with one file per field type, and
an `app/Config/Forms.php` pointing the theme at it. Edit any file there. If you
already have a configuration file, the command prints the one line to add.

Running the command again adds files that are missing, for example after an
upgrade that brings new field types, and never overwrites the ones you
changed. `--force` replaces them with the package's latest versions.

Views receive prepared variables and two helpers:

| Name | Contains |
| ---- | -------- |
| `$name`, `$inputName`, `$id`, `$label` | The field's name, its HTML name (`tags[]` for lists), id and label |
| `$value`, `$values` | The value to show, as text and as a list |
| `$error`, `$help` | The error message and help text, or `null` |
| `$attributes` | Extra attributes, already escaped |
| `$requiredMarker` | The required marker HTML, or an empty string |
| `$e($text)` | Escapes text for HTML |
| `$class('group')` | ` class="..."` with the theme's classes for that part |

The full list is documented at the top of each view file.

## 3. Create a theme

A theme of your own lives in its own folder. With `extends`, it only needs the
templates it changes; the rest come from the theme it extends. The
constructor in section 1 registers a `compact` theme for dense admin screens,
which changes only text-like inputs:

```php title="app/Views/forms/compact/field_input.php"
--8<-- "docs/examples/app/Views/forms/compact/field_input.php"
```

Select it for every form with `public string $theme = 'compact';` in the
configuration, or for one form in a controller:

```php
$form->getConfig()->theme = 'compact';
```

A theme's classes are not inherited, which is why the example starts from the
Bootstrap 5 classes with `array_merge()` and changes two.

## Common mistakes

**Editing files in `vendor/`.** Changes there are lost on the next
`composer update`. Publish the views instead.

**Printing values without `$e()`.** The variables contain what visitors typed.
Use `$e()` for anything you print; only `$attributes` and `$requiredMarker` are
ready to print.

**Expecting published views to update themselves.** Published files are yours.
After an upgrade, read the changelog for view changes and compare, or publish
again with `--force` and reapply your edits.

## Related

- [Layout](layout.md) for arranging fields without changing views
- [Custom field types](custom-field-types.md) for a field with a view of its own
