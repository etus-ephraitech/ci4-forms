# Architecture

The package is built from a few parts, each with one job. Knowing them helps
when you extend it.

```text
Form ─────────── holds the data, runs validation, owns the errors
 ├─ fields ───── a flat list, by name: definitions, no data, no HTML
 ├─ layout ───── a tree of rows and fieldsets that refers to fields by name
 ├─ Renderer ─── turns fields and layout into HTML using a theme's views
 └─ Config ───── settings and themes (app/Config/Forms.php)

Schemas ──────── SchemaLoader builds a Form from data, checked by a SchemaPolicy;
                 SchemaExporter turns a Form back into data
```

## Forms

`Form` is the object your controllers and views use. It knows its fields and
layout, holds the data it works with (the submission, old input, filled data),
runs validation and keeps the errors. A form class is simply a `Form` whose
`define()` adds fields.

## Fields

A field only describes itself: its name, label, rules, options and
attributes. It holds no submitted value and produces no HTML. This keeps
fields small, easy to test and easy to write yourself.

Fields implement `FieldInterface`, and optional interfaces add abilities:

| Interface | Gives a field |
| --------- | ------------- |
| `ChoiceFieldInterface` | Strict option checking against `getAllowedValues()` |
| `FileFieldInterface` | Upload handling: never in `validated()`, makes the form multipart |
| `PresentableFieldInterface` | A readable display of stored values for `present()` |
| `SchemaConfigurableInterface` | Extra keys in schemas, and their export |

Extending `Field` or `InputField` gives a custom field the common behaviour
and `PresentableFieldInterface`.

## Layout

Fields are kept in one flat list, so values, validation, `validated()` and
`field('name')` never depend on where a field appears. Rows and fieldsets form
a separate tree that refers to fields by name, and only the renderer reads it.
Changing a form's layout can never change what it accepts.

## Rendering and themes

The `Renderer` prepares everything a field needs (its value, errors, classes
and escaped attributes) and passes it to the active theme's view for that
field type. Views contain only markup, which is why they are easy to copy and
change.

A template is looked up in the active theme's folder, then in the package's
bundled copy of a theme with the same name, then the same for the theme it
extends. This is how published views keep working after an upgrade adds new
templates, and how a theme can override only the templates it changes.

## Schemas

`SchemaLoader` builds a form from a schema in two passes. First it builds the
whole schema on a scratch form, collecting every error with its location; only
when there are none does it build the real form. Validation and building run
the same code, so they cannot disagree, and a schema with errors never produces
part of a form.

`SchemaPolicy` decides what a schema may contain. `SchemaExporter` turns a form
back into a schema, and `FormSchema::describe()` reports what a policy allows,
using the loader's own lists of keys.

## Support classes

| Class | Job |
| ----- | --- |
| `RuleParser` | Splits CodeIgniter rule strings, keeping <code>&#124;</code> inside brackets |
| `FieldTypes` | Maps type names to field classes, built-in and registered |
| `OptionProviders` | Checks and calls registered option providers |
| `ViewPublisher` | Copies theme views into an app for `forms:publish` |
