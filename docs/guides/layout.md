# Layout

Use rows to put fields side by side, fieldsets to group them under a title, and
widths to size the columns. The layout is part of the form class, so
`<?= $form ?>` renders it and every page using the form looks the same.

## Example

A delivery form with two sections, columns of different widths, and two
sections side by side:

```php title="app/Forms/DeliveryForm.php"
--8<-- "docs/examples/app/Forms/DeliveryForm.php"
```

The view stays a single line: `<?= $form ?>`.

## How it works

Rows
:   `row()` places the fields defined inside its closure next to each other.
    Each field takes the columns set with `width()`, out of 12. Fields without
    a width share whatever is left: in the address row, the town takes 8
    columns and the postcode the remaining 4. On small screens the columns
    stack.

Fieldsets
:   `fieldset('Title', ...)` groups fields under a `<legend>`, which screen
    readers announce before each field inside it. `description()` adds a line
    under the title. Use `''` as the title for a group without one.

Nesting
:   Rows and fieldsets can contain each other. Billing and Timing are
    fieldsets placed side by side in a row, each with `width(6)`.

Classes
:   `addClass()` works on rows and fieldsets, such as `g-4` for wider gutters.

Hidden fields
:   A hidden field never takes a column, wherever it is defined.

Layout only decides where fields appear. Validation, `validated()`, `fill()`
and `field('town')` work exactly as they do without it.

## Arranging a form in the view

When a page needs a layout of its own, build it in the view from pieces:

```php title="app/Views/delivery/custom.php"
--8<-- "docs/examples/app/Views/delivery/custom.php"
```

| Method | Renders |
| ------ | ------- |
| `open()` | The `<form>` tag with the CSRF field, the hidden form name and, for PUT, PATCH and DELETE forms, the `_method` field |
| `errorSummary()` | A list of every error linking to its field, or nothing when there are none |
| `field('name')` | One field with its label, error and help text |
| `fields('a', 'b')` | Several fields in that order, without the form's layout |
| `control('name')` | Only the input, select or button, without label or wrapper |
| `close()` | `</form>` |

`fields()` with no names renders every field in the form's layout.

## Common mistakes

**Leaving out `open()`.** Always start a custom layout with `open()`. Besides
the CSRF field, it adds a hidden field naming the form, which the package uses
to tell which form a submission or redirect belongs to.

**Forgetting fields in a custom view.** A field you do not render is not
submitted. Required fields then fail validation with an error the visitor
cannot see, unless you show `errorSummary()`.

**Widths outside rows.** `width()` only has an effect inside a row.

## Related

- [Getting started](../getting-started.md) uses a two-column layout
- [Customising views](customising-views.md) changes the classes rows and sections use
