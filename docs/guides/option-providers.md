# Option providers

Use an option provider when a select, radio group or checkbox group should
list rows from your database, such as active services. You register a named
source once, and any form, including forms stored as schemas, can use it.

## Example

A model method that returns options as `id => name`:

```php title="app/Models/ServiceModel.php"
--8<-- "docs/examples/app/Models/ServiceModel.php"
```

Register it under a name in `app/Config/Forms.php`:

```php title="app/Config/Forms.php"
--8<-- "docs/examples/app/Config/Forms.php:option-providers"
```

Use it in a form with `optionsFrom()`:

```php title="app/Forms/ServiceRequestForm.php"
--8<-- "docs/examples/app/Forms/ServiceRequestForm.php"
```

In a schema, the same field is
`{"type": "select", "name": "service_id", "options_from": "services"}`.

## How it works

Registering
:   A provider is `[ClassName::class, 'method']`, `'ClassName::method'`, or a
    closure assigned in the configuration's constructor. Models are loaded with
    `model()`; other classes must not need constructor arguments, and the method
    must be public and take no arguments. The method returns `value => label`
    pairs; a select may also receive groups (`'Group' => [value => label]`).

Called lazily, once
:   The provider runs the first time the options are needed, when the form is
    rendered or validated, and only once per field. Building a form, or loading
    and checking a schema, never calls it.

Options are checked
:   A submitted value must be one the provider returned. In the example, an
    inactive service is not an option, so it is rejected even if someone sends
    its id.

Named, not coded
:   A schema can only name a registered provider. It cannot pass arguments,
    choose a table or run code, which keeps forms written by admins safe.

## Common mistakes

**Returning a list instead of `id => name`.** `findColumn('name')` returns
names without their ids. Use `array_column($rows, 'name', 'id')` as in the
example.

**Exposing data that should stay private.** Forms written by admins can use
every registered provider unless their policy narrows the list. Register only
data that is safe on any form, or block a provider for admins with
`SchemaPolicy::restricted()->disallowProviders('staff')`.

**Using a provider name that is not registered.** `optionsFrom()` fails at
once with the list of registered names, and schemas get a "did you mean"
suggestion.

## Related

- [Forms written by admins](forms-written-by-admins.md) for `options_from` in schemas
- [Multiple choices](multiple-choices.md) for checkbox groups fed by a provider
