# Multiple choices

Use a checkbox group or a multiple select when people may choose several
options. The value is a list, such as `['suits', 'kanzu']`.

## Example

```php title="app/Forms/PreferencesForm.php"
--8<-- "docs/examples/app/Forms/PreferencesForm.php"
```

`validated()` returns lists:

```php
['interests' => ['suits', 'kanzu'], 'branches' => []]
```

## How it works

Checkbox groups and multiple selects
:   `checkboxes()` shows one box per option; `select()->multiple()` shows a
    list where several items can be selected, with `size()` setting how many
    rows are visible. Both are submitted as `name[]`.

Always a list
:   `validated()` returns a list, and an empty one (`[]`) when nothing is
    chosen, never `null` or a missing key. A single value sent instead of a
    list is wrapped in one.

Rules
:   `rules()` applies to the list as a whole: `required` means at least one
    item. `eachRules()` applies to every item, for example
    `eachRules('max_length[20]')`.

Options are checked
:   Every chosen value must be one of the options. Anything else, such as a
    value added with the browser's developer tools, is rejected with "Please
    choose a valid option for ...". You do not need an `in_list` rule.

Editing
:   `fill()` accepts a list, and the matching boxes are ticked. After a failed
    submission, exactly the boxes the visitor ticked stay ticked.

## Storing lists

A list can be stored as JSON in a text column. CodeIgniter models convert it
for you with casts:

```php title="app/Models/CustomerPreferenceModel.php"
--8<-- "docs/examples/app/Models/CustomerPreferenceModel.php"
```

```php title="app/Database/Migrations/2026-10-29-000200_CreateCustomerPreferences.php"
--8<-- "docs/examples/app/Database/Migrations/2026-10-29-000200_CreateCustomerPreferences.php"
```

With the casts, `$model->insert($form->validated())` stores
`["suits","kanzu"]`, `find()` returns the list again, and `fill()` puts it
back into the form. When you need to search or join on the choices, store them
in a separate table with one row per choice instead.

## Common mistakes

**Saving the list straight into a plain column.** Without a cast (or
`json_encode()`), saving an array fails. Use the casts above.

**Using `rules('in_list[...]')` for the items.** It checks the list as a
whole, not each item. The options are checked automatically; for other item
rules use `eachRules()`.

**Expecting a placeholder on a multiple select.** A multiple select has no
"Choose..." option, because it would itself be selectable. Use `help()`
instead.

## Related

- [Layout](layout.md) to place a checkbox group in a row
- [Getting started](../getting-started.md) for a single-choice select
