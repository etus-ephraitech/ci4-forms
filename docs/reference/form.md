# Form

`Ephraitech\Forms\Form` holds a form's fields and layout, the data it works
with, and its errors. Setters return the form, so calls can be chained.

## Creating forms

| Method | Description |
| ------ | ----------- |
| `__construct(?string $name = null, ?Forms $config = null)` | Creates a form and calls `define()`. The name defaults to the class name without "Form", in snake_case (`BookingForm` becomes `booking`). The configuration defaults to `app/Config/Forms.php`, or the package's defaults |
| `make(?string $name = null, ?Forms $config = null)` | Static. Creates a form for inline definitions: `Form::make('newsletter')->email('email')->...->end()` |
| `fromSchema(array $schema, ?Forms $config = null, ?SchemaPolicy $policy = null)` | Static. Builds a form from a schema array. Restricted policy unless another is passed. Throws `SchemaException` listing every problem |
| `fromJson(string $json, ?Forms $config = null, ?SchemaPolicy $policy = null)` | Static. As `fromSchema()`, from JSON text |
| `loadSchema(array $schema, ?SchemaPolicy $policy = null)` | Adds a schema's fields and settings to this form, for example inside `define()`. All or nothing |
| `toSchema()` | The form as a schema array that `fromSchema()` builds back into the same form. Throws `SchemaException` for parts that cannot be data, such as closure rules |
| <code>toJson(int $flags = JSON_PRETTY_PRINT &#124; JSON_UNESCAPED_SLASHES &#124; JSON_UNESCAPED_UNICODE)</code> | `toSchema()` as JSON |
| `toArray()` | A plain array describing the form and all its fields, for debugging. Resolves options, so it calls option providers |

Override `define()` (protected) in a form class to add fields, and
`prepareForValidation(array $data): array` (protected) to clean submitted data
before validation.

## Form settings

| Method | Description |
| ------ | ----------- |
| `action(?string $action)` | Where the form is sent. Relative paths go through `site_url()`; empty means the current address |
| `method(string $method)` | `get`, `post`, `put`, `patch` or `delete`. PUT, PATCH and DELETE are sent as POST with a hidden `_method` field |
| `id(?string $id)` | The form's HTML id; the form name when not set |
| `multipart(bool $multipart = true)` | Sends the form as `multipart/form-data`. Forms with a file field are multipart without this |
| `attributes(array $attributes)` | Several HTML attributes for the `<form>` tag |
| <code>attribute(string $attribute, bool&#124;float&#124;int&#124;string&#124;Stringable&#124;null $value)</code> | One attribute; `true` renders it without a value, `false` or `null` removes it. `id` and `class` are routed to `id()` and `addClass()` |
| `addClass(string ...$classes)` | Adds CSS classes to the `<form>` tag |
| `getConfig()` | The configuration the form uses |
| `getName()` | The form name |
| `getAction()` | The action, or `null` |
| `getMethod()` | The declared method |
| `getHtmlMethod()` | The method the browser sends: `get` or `post` |
| `getSpoofedMethod()` | The value of the hidden `_method` field (`PUT`, `PATCH` or `DELETE`), or `null` |
| `needsCsrf()` | Whether the form includes the CSRF field (non-GET forms, when enabled in the configuration) |
| `getExplicitId()` | The id set with `id()`, or `null` |
| `getId()` | The form's HTML id |
| `isMultipart()` | Whether the form is sent as multipart |
| `getAttributes()` | The `<form>` tag's attributes |
| `getClasses()` | The `<form>` tag's extra classes |
| `getIdPrefix()` | The prefix for field ids (the form id), or `''` when ids are not prefixed |

## Adding fields

Each builder adds a field and returns it, so the field's own methods can be
chained. Calls the field does not have go back to the form.

| Method | Adds |
| ------ | ---- |
| `text(string $name, ?string $label = null)` | A text input |
| `email(string $name, ?string $label = null)` | An email input |
| `password(string $name, ?string $label = null)` | A password input; never filled back in |
| `number(string $name, ?string $label = null)` | A number input |
| `tel(string $name, ?string $label = null)` | A phone number input |
| `url(string $name, ?string $label = null)` | A web address input |
| `search(string $name, ?string $label = null)` | A search box |
| `date(string $name, ?string $label = null)` | A date picker |
| `time(string $name, ?string $label = null)` | A time picker |
| `datetimeLocal(string $name, ?string $label = null)` | A date and time picker |
| `color(string $name, ?string $label = null)` | A colour picker |
| `hidden(string $name, mixed $default = null)` | A hidden input with an initial value |
| `textarea(string $name, ?string $label = null)` | A text area |
| <code>select(string $name, ?string $label = null, array&#124;Closure&#124;null $options = null)</code> | A dropdown |
| <code>radio(string $name, ?string $label = null, array&#124;Closure&#124;null $options = null)</code> | A group of radio buttons |
| `checkbox(string $name, ?string $label = null)` | A single checkbox |
| <code>checkboxes(string $name, ?string $label = null, array&#124;Closure&#124;null $options = null)</code> | A group of checkboxes; the value is a list |
| `file(string $name, ?string $label = null)` | A file upload; makes the form multipart |
| `submit(string $label = 'Submit', string $name = 'submit')` | A submit button |
| `addField(string $type, string $name, ?string $label = null)` | A field by type name, built-in or registered in `$fieldTypes` |
| `add(FieldInterface $field)` | A field object you created, such as an instance of your own class |
| `hasFieldType(string $type)` | Whether a type name is built in or registered |

Registered custom types are also builder methods: with `money` registered,
`$form->money('budget')`.

## Layout

| Method | Description |
| ------ | ----------- |
| `row(Closure $define)` | Places the fields defined in the closure side by side. Returns the row |
| `fieldset(string $legend, Closure $define)` | Groups the fields defined in the closure under a title. Returns the fieldset |
| `getLayout()` | The top-level layout: field names and groups in order |

## Fields

| Method | Description |
| ------ | ----------- |
| `has(string $name)` | Whether the form has a field |
| `getField(string $name)` | A field; throws `FormException` when it does not exist |
| `getFields()` | All fields, by name, in order |
| `getSubmittableFields()` | Fields whose values are submitted: all but buttons and disabled fields |
| `remove(string ...$names)` | Removes fields, also from rows and fieldsets |

## Values

| Method | Description |
| ------ | ----------- |
| <code>fill(array&#124;object $data)</code> | Loads saved data, from an array, an Entity or an object |
| `getFilled()` | The data passed to `fill()` |
| `getValue(string $name)` | The value a field shows: submitted, else old input, else filled, else its default |
| `getValues()` | `getValue()` for every field |
| `setSubmittedData(array $data)` | Sets the submission directly, without validating |
| `clearSubmittedData()` | Forgets the submission |
| `getSubmittedData()` | The current submission, or `null` |
| `hasSubmission()` | Whether there is a submission |
| `getOldInput()` | The input carried by `redirect()->withInput()`, when it belongs to this form |
| `setOldInput(?array $input)` | Replaces the old input, for tests |
| `ownsInput(array $input)` | Whether request data came from this form (by its hidden form name) |

## Validation

| Method | Description |
| ------ | ----------- |
| <code>validate(array&#124;IncomingRequest&#124;null $input = null)</code> | Validates the current request (form post, query string for GET forms, JSON body, or the body of a genuine PUT, PATCH or DELETE), an `IncomingRequest`, or an array. Returns whether it passed |
| `isValid()` | Whether `validate()` ran and there are no errors, including ones added with `setError()` |
| `validated(string ...$except)` | The clean data of every submittable field except the ones named. Throws `FormException` before `validate()` or after it failed |
| `getValidationRules()` | The rules in the format of CodeIgniter's `Validation::setRules()` |
| `usesStrictOptions(FieldInterface $field)` | Whether submitted values of a field must be among its options |
| `setValidation(ValidationInterface $validation)` | Uses another Validation instance |
| `getValidation()` | The Validation instance used |

## Uploads

| Method | Description |
| ------ | ----------- |
| `getFile(string $name)` | The first valid uploaded file of a file field, or `null` |
| `getFiles(string $name)` | All valid uploaded files of a file field |
| `hasUploadedFile(string $name)` | Whether a file was chosen for a file field |

## Errors

| Method | Description |
| ------ | ----------- |
| `setError(string $field, string $message)` | Adds an error, for checks validation rules cannot make. It survives `redirect()->withInput()` |
| `getErrors()` | All errors, by field name, including ones carried by a redirect |
| `getError(string $field)` | A field's error, or `null` |
| `hasError(string $field)` | Whether a field has an error |
| `hasErrors()` | Whether there are errors |
| `clearErrors()` | Removes all errors |
| `setOldErrors(?array $errors)` | Replaces the errors carried by a redirect, for tests |

## Rendering

| Method | Description |
| ------ | ----------- |
| `render()` | The whole form, as `<?= $form ?>` does |
| `open(array $attributes = [])` | The `<form>` tag with the hidden form name, CSRF field and `_method` field |
| `close()` | `</form>` |
| `field(string $name)` | One field with its label, error and help text |
| `fields(string ...$names)` | The named fields in that order; every field in its layout when no names are given |
| `control(string $name)` | Only the field's input, select or button |
| `errorSummary()` | A list of all errors linking to their fields, or `''` |
| `setRenderer(Renderer $renderer)` | Uses another renderer |
| `getRenderer()` | The renderer used |

## Presenting stored data

| Method | Description |
| ------ | ----------- |
| <code>present(array&#124;object $data, bool $includeEmpty = true)</code> | Stored data as rows with each field's label and readable value (option labels, Yes/No). Rows are plain text |
| <code>renderPresented(array&#124;object $data, bool $includeEmpty = true)</code> | The rows as escaped HTML |
