# Showing submissions

Stored answers are values such as `large` and `1`. `present()` turns them back
into what people should read, using the form's labels and option texts:
"Number of guests: More than 50", "You may call me: Yes".

## Example

An answers screen for the forms from [Forms written by admins](forms-written-by-admins.md):

```php title="app/Controllers/Admin/Submissions.php"
--8<-- "docs/examples/app/Controllers/Admin/Submissions.php"
```

```php title="app/Views/admin/submissions.php"
--8<-- "docs/examples/app/Views/admin/submissions.php"
```

`forms_present()` renders each answer as a description list. It works the same
for form classes: `forms_present(new BookingForm(), $row)`.

## How it works

`$form->present($data)` returns one row per field, in the form's order:

```php
['name' => 'guests', 'label' => 'Number of guests', 'value' => 'More than 50', 'text' => 'More than 50', 'empty' => false]
```

| Field | Shown as |
| ----- | -------- |
| Select, radio | The option's label |
| Checkbox group, multiple select | A list of option labels |
| Checkbox | "Yes" or "No" |
| Option no longer offered | The stored value, so old answers stay readable |
| Password, button, upload | Never shown |

Keys that are not fields, such as an `is_admin` someone tried to submit, are
ignored. The texts "Yes", "No" and "—" (for empty values) are set by
`$presentYes`, `$presentNo` and `$presentEmpty` in the configuration, and a
custom field controls its own display with `presentValue()`.

| Call | Gives |
| ---- | ----- |
| `$form->present($data)` | The rows, for your own layout |
| `$form->renderPresented($data)` | HTML, using the theme's `presented` view |
| `forms_present($form, $data)` | The same HTML, after `helper('forms')` |

Pass `false` as the last argument to leave out empty fields.

## Common mistakes

**Printing rows without escaping.** The labels and values in `present()` rows
are plain text and may contain what visitors typed. Use `esc()` when you print
them yourself; `renderPresented()` and `forms_present()` escape everything.

**Using a different form than the one that collected the data.** Labels and
options come from the form you pass. For admin-defined forms, load the current
schema; answers to fields that were removed are not shown.

## Related

- [Forms written by admins](forms-written-by-admins.md) for storing answers as a payload
- [Custom field types](custom-field-types.md) for `presentValue()`
