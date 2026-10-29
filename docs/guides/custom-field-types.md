# Custom field types

When the built-in fields are not enough, write your own field class, register
it, and use it in any form like a built-in field.

This guide builds a money field: an amount with a currency in front, which
accepts "1,500,000" and stores `1500000`.

## 1. Write the field class

Most fields extend `InputField` (one `<input>`) or `Field`:

```php title="app/Forms/Fields/MoneyField.php"
--8<-- "docs/examples/app/Forms/Fields/MoneyField.php"
```

Each part is optional; override only what your field needs:

| Member | Purpose |
| ------ | ------- |
| `TYPE` | The field's type name |
| `VIEW` | Its template. A name with a path, like `forms/fields/money`, is loaded from `app/Views`; a plain name, like `field_input`, comes from the active theme |
| Setters such as `currency()` | Your field's own options; return `static` so calls can be chained |
| `getAttributes()` | Extra HTML attributes |
| `normaliseScalar()` | Cleans submitted values before validation and in `validated()` |
| `presentValue()` | How a stored value is shown by `$form->present()` |
| `schemaProperties()`, `schemaValues()` | Extra keys for forms stored as schemas |

## 2. Write its view

```php title="app/Views/forms/fields/money.php"
--8<-- "docs/examples/app/Views/forms/fields/money.php"
```

Custom views receive the same variables as the package's own field views.
Always print values with `$e()`, which escapes them.

## 3. Register it

Add the class to `$fieldTypes` in `app/Config/Forms.php`:

```php title="app/Config/Forms.php"
--8<-- "docs/examples/app/Config/Forms.php:field-types"
```

## 4. Use it

The registered name becomes a method on every form:

```php title="app/Forms/QuoteForm.php"
--8<-- "docs/examples/app/Forms/QuoteForm.php"
```

`money()` returns your field, so its own setters and the usual ones (`rules()`,
`placeholder()`, `width()`) can be chained. It also works inside rows and
fieldsets, with `addField('money', 'budget')`, and in schemas as
`{"type": "money", "currency": "USD"}`.

Submitting "1,500,000" validates against `is_natural_no_zero` and
`validated()` returns `'1500000'`. `$form->present()` shows a stored
`1500000` as "UGX 1,500,000".

## Common mistakes

**Choosing a name that already exists.** A name such as `text` or `render` is
refused when the form is built, because the built-in field or `Form` method
would always win. Pick a new name such as `money` or `phoneNumber`.

**Printing values without `$e()`.** Every value in a field view can contain
text a visitor typed. Escape it.

**Using the field in admin-written schemas without allowing it.** Schemas
written by admins only accept the types their policy allows. Add yours with
`SchemaPolicy::restricted()->allowTypes('money')`.

## Related

- [Customising views](customising-views.md) for changing the built-in fields' HTML
