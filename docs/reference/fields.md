# Fields

Every field extends `Ephraitech\Forms\Fields\Field`. Setters return the field,
so calls can be chained; a call the field does not have goes to its form.

## Methods every field has

### Setting up

| Method | Description |
| ------ | ----------- |
| `label(string $label)` | The text shown with the field |
| <code>rules(array&#124;string $rules)</code> | CodeIgniter validation rules, as <code>'required&#124;max_length[100]'</code> or a list |
| <code>eachRules(array&#124;string $rules)</code> | Rules for every item of a field that holds several values |
| `messages(array $messages)` | Custom error messages by rule name; `options` for values outside the options |
| `help(?string $help)` | A hint shown under the field |
| `placeholder(?string $placeholder)` | Example text shown in an empty field; a select's empty first option |
| `default(mixed $value)` | The value shown when there is no submitted or filled value |
| `width(?int $columns)` | Columns (1 to 12) inside a row |
| `id(?string $id)` | The HTML id; generated from the form and field names when not set |
| `attributes(array $attributes)` | Several HTML attributes |
| <code>attribute(string $attribute, bool&#124;float&#124;int&#124;string&#124;Stringable&#124;null $value)</code> | One attribute. `name`, `type` and `value` cannot be set; `id`, `class`, `disabled` and `placeholder` are routed to their own methods |
| `addClass(string ...$classes)` | Extra CSS classes for the input |
| `disabled(bool $disabled = true)` | Shown but not editable; not submitted, validated or returned |
| `readonly(bool $readonly = true)` | Shown but not editable; still submitted and validated |
| `autofocus(bool $autofocus = true)` | Puts the cursor here when the page opens |
| `autocomplete(string $value)` | The browser's autofill hint, such as `name` or `email` |
| `end()` | Returns the form, ending an inline chain |

### Reading

| Method | Description |
| ------ | ----------- |
| `getName()` | The field name |
| `getLabel()` | The label |
| `getType()` | The type name, such as `text` or `datetime_local` |
| `getView()` | The template name |
| `getRules()` | The rules, as a list |
| `getEachRules()` | The item rules |
| `getMessages()` | The custom messages |
| `getHelp()` | The help text, or `null` |
| `getPlaceholder()` | The placeholder, or `null` |
| `getDefault()` | The default value |
| `getWidth()` | The width, or `null` |
| `getId(string $prefix = '')` | The HTML id |
| `getExplicitId()` | The id set with `id()`, or `null` |
| `getAttributes()` | The attributes the field renders with |
| `getOwnAttributes()` | The attributes set on the field itself |
| `getClasses()` | The extra classes |
| `isRequired()` | Whether the rules include `required` |
| `isMultiple()` | Whether the value is a list |
| `isDisabled()` | Whether the field is disabled |
| `toArray()` | A plain array describing the field |

### Used by forms, renderers and custom fields

| Method | Description |
| ------ | ----------- |
| `isSubmittable()` | Whether the value belongs in `validated()`: false for buttons and disabled fields |
| `shouldRepopulate()` | Whether earlier values may be written back into the field; false for passwords |
| `ruleAttributes(Forms $config)` | HTML attributes derived from the rules, such as `required` and `maxlength` |
| `normaliseValue(mixed $value, Forms $config)` | Cleans a submitted value: trimming, empty to `null`, lists for multi-value fields |
| `valueWhenMissing(Forms $config)` | The value `validated()` uses when the field was not submitted |
| `isPresentable()` | Whether `present()` shows the field |
| `presentValue(mixed $value, Forms $config)` | A stored value as readable text, or a list of texts |

## Text-like inputs

`text`, `email`, `password`, `tel`, `url`, `search`, `number`, `date`, `time`,
`datetime_local`, `color` and `hidden` are inputs:

| Method | Description |
| ------ | ----------- |
| `getInputType()` | The input's `type` attribute, such as `email` or `datetime-local` |

Rules become attributes: `max_length` and `min_length` (and `exact_length`)
become `maxlength` and `minlength`, and on number inputs
`greater_than_equal_to` and `less_than_equal_to` become `min` and `max`.

### Number

| Method | Description |
| ------ | ----------- |
| <code>step(float&#124;int&#124;string $step)</code> | The interval between allowed values, or `'any'` |

### Date, time and date and time

| Method | Description |
| ------ | ----------- |
| `min(string $value)` | The earliest selectable value, in the input's format (`Y-m-d`, `H:i` or `Y-m-d\TH:i`) |
| `max(string $value)` | The latest selectable value |
| <code>step(int&#124;string $step)</code> | The interval in seconds (time) or days (date), or `'any'` |

### Password

Never filled back in after an error or from saved data, never trimmed, and
never shown by `present()`.

### Colour

Values are lower-cased. Browsers always send a value, so `required` has no
effect and no rule attributes are rendered.

### Hidden

No rule attributes are rendered and no column is taken in rows.

## Text area

| Method | Description |
| ------ | ----------- |
| `rows(int $rows)` | Visible lines of text |

## Choice fields

`select`, `radio` and `checkboxes` share these methods:

| Method | Description |
| ------ | ----------- |
| <code>options(array&#124;Closure $options)</code> | Choices as `value => label`; a closure is called once, when the options are first needed |
| `optionsFrom(string $provider, ?Forms $config = null)` | Choices from a provider registered in `$optionProviders` |
| `getOptionProvider()` | The provider's name, or `null` |
| `getOptions()` | The choices, with values as strings |
| `getOptionValues()` | Every selectable value, including those in groups |
| `getAllowedValues()` | The values strict option checking accepts |
| `hasOptionValue(mixed $value)` | Whether a value is one of the options |
| `getOptionId(string $value, string $prefix = '')` | The HTML id of one option's input |
| `strictOptions(bool $strict = true)` | Whether submitted values must be among the options; the configuration decides when not set |
| `getStrictOptions()` | The field's setting, or `null` |

### Select

Options may be grouped: `['Suits' => ['1' => 'Two-piece']]` renders an
`<optgroup>`.

| Method | Description |
| ------ | ----------- |
| `multiple(bool $multiple = true)` | Allows several choices; the value becomes a list |
| `size(int $rows)` | Visible rows of a multiple select |

### Radio group and checkbox group

| Method | Description |
| ------ | ----------- |
| `inline(bool $inline = true)` | Shows the options on one line |
| `isInline()` | Whether the options are on one line |

A checkbox group's value is always a list. It renders no `required`
attribute, because browsers would then require every box.

## Checkbox

| Method | Description |
| ------ | ----------- |
| <code>checkedValue(int&#124;string $value)</code> | The value sent when ticked; `1` by default |
| `getCheckedValue()` | The value sent when ticked |
| `isChecked(mixed $value)` | Whether a value means ticked |
| `inline(bool $inline = true)` | Shows the box inline |
| `isInline()` | Whether the box is inline |
| `getAllowedValues()` | Its checked value, for strict option checking |
| `strictOptions(bool $strict = true)` | Whether any other submitted value is rejected |
| `getStrictOptions()` | The field's setting, or `null` |

An unticked checkbox returns the configured unchecked value (`0`) from
`validated()`.

## File

| Method | Description |
| ------ | ----------- |
| `required(bool $required = true)` | A file must be uploaded |
| `multiple(bool $multiple = true)` | Accepts several files |
| `maxSize(int $kilobytes)` | The largest file size |
| <code>extensions(array&#124;string $extensions)</code> | Allowed file name extensions |
| <code>mimes(array&#124;string $mimes)</code> | Allowed content types, detected from the file |
| `image(bool $image = true)` | Images only |
| `maxDimensions(int $width, int $height)` | The largest image size in pixels |
| `isUploadRequired()` | Whether `required()` was set |
| `getExtraRules()` | Rules set with `rules()`, without the generated ones |

File fields are never part of `validated()`; see
[File uploads](../guides/file-uploads.md).

## Submit

A button. Its label is the button text. It is never validated or returned,
and `rules()` throws `FormException`.

## Layout groups

`row()` and `fieldset()` return groups with these methods:

| Method | Description |
| ------ | ----------- |
| `addClass(string ...$classes)` | Extra CSS classes |
| `width(?int $columns)` | Columns (1 to 12) when the group sits inside a row |
| `end()` | Returns the form |
| `getType()` | `row` or `fieldset` |
| `getChildren()` | Field names and nested groups, in order |
| `getClasses()` | The extra classes |
| `getWidth()` | The width, or `null` |
| `isEmpty()` | Whether no field is left inside the group |
| `toArray()` | A plain array describing the group |

Fieldsets also have:

| Method | Description |
| ------ | ----------- |
| `legend(string $legend)` | The title |
| `description(?string $description)` | A line under the title |
| `id(?string $id)` | The fieldset's HTML id |
| `getLegend()` | The title |
| `getDescription()` | The description, or `null` |
| `getId()` | The id, or `null` |
