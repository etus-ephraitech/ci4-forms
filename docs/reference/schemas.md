# Schemas

Classes for forms stored as data. For the keys a schema may contain, see
[Schema keys](schema-keys.md); for a complete feature, see
[Forms written by admins](../guides/forms-written-by-admins.md).

Schemas are loaded with `Form::fromSchema()`, `Form::fromJson()` and
`$form->loadSchema()`, described on the [Form](form.md#creating-forms) page.

## FormSchema

`Ephraitech\Forms\Schema\FormSchema` has static entry points. Each uses the
restricted policy unless another is passed, and the app's configuration
unless another is passed.

| Method | Description |
| ------ | ----------- |
| <code>check(array&#124;string $schema, ?SchemaPolicy $policy = null, ?Forms $config = null)</code> | Checks a schema array or JSON text without throwing and without calling option providers. Returns a `SchemaCheck` |
| <code>load(array&#124;string $schema, ?SchemaPolicy $policy = null, ?Forms $config = null)</code> | Builds a form from a schema array or JSON text |
| `export(Form $form)` | `$form->toSchema()` |
| `describe(?SchemaPolicy $policy = null, ?Forms $config = null)` | What schemas may contain under a policy, as JSON-ready data: types with labels, keys, value kinds, explanations, required and exclusive keys; allowed rules, attributes, providers, upload types and limits |

## SchemaCheck

The result of `FormSchema::check()`.

| Method | Description |
| ------ | ----------- |
| `isValid()` | Whether the schema has no errors |
| `getErrors()` | Every error as `['path' => 'fields[2].max_size', 'message' => '...']` |
| `getMessages()` | Every error as one line: `fields[2].max_size: must be ...` |
| `getErrorsFor(string $path)` | Errors at a path or inside it, such as `fields[3]` |
| `getSchema()` | The decoded schema, ready to store, or `null` when the JSON was invalid |
| `toArray()` | `['valid' => bool, 'errors' => [...]]`, for JSON responses |

## SchemaPolicy

What a schema may contain. `SchemaPolicy::restricted()` is the default and
suits schemas written by admins; `SchemaPolicy::trusted()` only checks
structure and suits schemas developers commit with the code. Policies are
immutable: every change returns a new policy.

### Creating and changing

| Method | Description |
| ------ | ----------- |
| `restricted()` | Static. The restricted policy |
| `trusted()` | Static. The trusted policy |
| `allowTypes(string ...$types)` | Allows more field types, such as registered custom types |
| `disallowTypes(string ...$types)` | Removes field types |
| `allowRules(array $rules)` | Allows rules, as `name => SchemaPolicy::PARAM_*` |
| `disallowRules(string ...$names)` | Removes rules |
| `allowAttributes(string ...$names)` | Allows attributes; a trailing `*` allows a prefix. Event handlers, `style`, `formaction`, `srcdoc` and all `data-*` are refused |
| `allowMimes(string ...$mimes)` | Allows upload types; SVG, HTML and script types are refused |
| `forbidNames(string ...$names)` | Forbids more field names, such as your own columns |
| `allowNames(string ...$names)` | Allows names forbidden by default |
| `withLimits(array $limits)` | Changes `max_fields`, `max_depth`, `max_options`, `max_text`, `max_rules`, `max_classes` or `max_name_length` |
| `withMaxUploadSize(int $kilobytes)` | Changes the largest `max_size` allowed |
| `onlyProviders(string ...$names)` | Limits schemas to these option providers |
| `disallowProviders(string ...$names)` | Keeps schemas from using these option providers |
| `allowAction(bool $allow = true)` | Lets schemas set the form action |

Changing a trusted policy throws `FormException`; customise the restricted one.

Parameter kinds for `allowRules()`: `PARAM_NONE`, `PARAM_INT`, `PARAM_INTS`,
`PARAM_NUMBER`, `PARAM_FIELD`, `PARAM_FIELDS`, `PARAM_LIST`,
`PARAM_OPTIONAL_SCHEMES` and `PARAM_OPTIONAL_DATE_FORMAT`.

### Reading

| Method | Description |
| ------ | ----------- |
| `isTrusted()` | Whether this is the trusted policy |
| `allowsType(string $type)` | Whether a field type is allowed |
| `getTypes()` | The allowed field types |
| `ruleParameterKind(string $rule)` | An allowed rule's parameter kind, or `null` when not allowed |
| `getRules()` | Allowed rules and their parameter kinds, or `null` for all |
| `allowsAttribute(string $name)` | Whether an attribute is allowed |
| `getAttributes()` | Allowed attributes, or `null` for all |
| `allowsMime(string $mime)` | Whether an upload type is allowed |
| `getMimes()` | Allowed upload types |
| `getMaxUploadSize()` | The largest `max_size` allowed |
| `forbidsName(string $name)` | Whether a field name is forbidden |
| `getForbiddenNames()` | The forbidden names |
| `limit(string $key)` | A size limit |
| `getLimits()` | All size limits, or `null` for none |
| `allowsProvider(string $name)` | Whether an option provider may be used |
| `allowsAction()` | Whether schemas may set the action |

## SchemaException

Thrown when a schema has errors. It extends `FormException`.

| Method | Description |
| ------ | ----------- |
| `getErrors()` | Every error as `['path' => ..., 'message' => ...]` |
| `getMessages()` | Every error as one readable line |
| `forErrors(array $errors)` | Static. Creates the exception from a list of errors |
| `forInvalidJson(string $reason)` | Static. Creates the exception for JSON that cannot be decoded |
