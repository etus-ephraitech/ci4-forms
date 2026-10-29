# Values

A field can get its value from four places. Which one wins, and what happens
to values on the way to your database, explains most of the package's
behaviour.

## Where a value comes from

When a form is rendered, each field shows the first value it finds, in this
order:

1. **The submission**: what was just posted, when `validate()` failed and the
   same page is shown again.
2. **Old input**: what was posted before `redirect()->withInput()`.
3. **Filled data**: what you passed to `fill()`, such as a saved record.
4. **The default**: what you set with `default()`.

So an edit page shows the saved record until the visitor submits, and from
then on shows what they typed, even if it is invalid. Nothing they entered is
lost to an error.

## Once submitted, missing means empty

Browsers send nothing at all for an unticked checkbox or a checkbox group with
nothing ticked. If the form then fell back to the saved record, a box the
visitor had just unticked would appear ticked again.

So once a submission (or old input) belongs to the form, a field missing from
it is empty, not filled from saved data: an unticked checkbox stays unticked, a
checkbox group with nothing ticked shows nothing ticked, and a missing text
field shows nothing.

## Old input belongs to one form

`redirect()->withInput()` stores the posted data for the next page. With two
forms on that page, both would pick it up. Every form the package renders
contains a hidden field with its name, and a form only uses old input (and the
errors carried with it) when that name matches. This is one reason to always
render forms through `open()` or `<?= $form ?>`.

## Passwords are never shown

Password fields skip all four sources: they render empty after an error, and
`fill()` never puts a stored value into them. A password is never written back
into a page where it could be read from the HTML.

## What `validated()` returns

`validated()` turns the submission into data you can save:

| Situation | Result |
| --------- | ------ |
| A field the form defines | Included |
| A key the form does not define, such as `is_admin` | Left out |
| A disabled field | Left out, even if a value was posted |
| A button or file field | Left out (get files with `getFile()`) |
| Text with spaces around it | Trimmed (`$trimStrings`) |
| A password | Never trimmed: spaces may be part of it |
| An empty optional field | `null` (`$emptyStringsToNull`) |
| An unticked checkbox | `0` (`$checkboxUncheckedValue`) |
| A checkbox group or multiple select | Always a list, `[]` when nothing was chosen |
| An array posted to a single-value field | `null`, so it can never reach the database as an array |

Because only defined fields come back, passing `validated()` straight to a
model's `insert()` or `update()` is safe from mass assignment.

## Related

- [Validation](validation.md) for what happens before `validated()` can be called
- [Getting started](../getting-started.md) for `fill()` on an edit page
