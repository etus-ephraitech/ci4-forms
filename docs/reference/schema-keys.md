# Schema keys

<!-- Generated from FormSchema::describe() by `composer docs:reference`. Do not edit by hand. -->

Every key a schema may contain, for each field type. This page is generated from
`FormSchema::describe()`, so it always matches the version it documents. For
loading and checking schemas, see [Schemas](schemas.md).

Field types are listed as admins may use them under the restricted policy (the
default). [Developer-only types](#developer-only-types) are allowed by the trusted
policy only.

## The form

| Key | Value |
| --- | ----- |
| `version` | `1`, the schema format version |
| `name` | A field name: letters, digits, underscores and hyphens, starting with a letter or underscore; becomes the form name |
| `method` | One of `get`, `post`, `put`, `patch`, `delete` |
| `id` | Text, or `null` |
| `classes` | A class string, or a list of them |
| `attributes` | An object of attribute name to value |
| `fields` | A list of fields and groups (required) |
| `action` | Text. Trusted policy only, or a restricted policy with `allowAction()` |

## Field types

### Text (`text`)

Required: `type`, `name`.

| Key | Value | Description |
| --- | ----- | ----------- |
| `type` | `type` | The kind of field or group. |
| `name` | `name` | Unique name used when the form is submitted; letters, digits, underscores and hyphens. |
| `label` | `text` | Text shown with the field. |
| `id` | `optional_text` | HTML id; generated when left out. |
| `classes` | `classes` | Extra CSS classes. |
| `attributes` | `attributes` | Extra HTML attributes. |
| `width` | `width` | Column width inside a row, from 1 to 12. |
| `rules` | `rules` | Validation rules, such as "required|max_length[100]". |
| `messages` | `messages` | Custom error messages by rule name; "options" for invalid choices. |
| `help` | `optional_text` | Hint shown under the field. |
| `default` | `default` | Initial value. |
| `disabled` | `bool` | Shown but not editable, and never submitted. |
| `readonly` | `bool` | Shown but not editable; still submitted. |
| `autofocus` | `bool` | Put the cursor here when the page opens. |
| `placeholder` | `optional_text` | Example text shown inside an empty field. |
| `autocomplete` | `text` | Browser autofill hint, such as "name", "email" or "tel". |

### Email (`email`)

Required: `type`, `name`.

| Key | Value | Description |
| --- | ----- | ----------- |
| `type` | `type` | The kind of field or group. |
| `name` | `name` | Unique name used when the form is submitted; letters, digits, underscores and hyphens. |
| `label` | `text` | Text shown with the field. |
| `id` | `optional_text` | HTML id; generated when left out. |
| `classes` | `classes` | Extra CSS classes. |
| `attributes` | `attributes` | Extra HTML attributes. |
| `width` | `width` | Column width inside a row, from 1 to 12. |
| `rules` | `rules` | Validation rules, such as "required|max_length[100]". |
| `messages` | `messages` | Custom error messages by rule name; "options" for invalid choices. |
| `help` | `optional_text` | Hint shown under the field. |
| `default` | `default` | Initial value. |
| `disabled` | `bool` | Shown but not editable, and never submitted. |
| `readonly` | `bool` | Shown but not editable; still submitted. |
| `autofocus` | `bool` | Put the cursor here when the page opens. |
| `placeholder` | `optional_text` | Example text shown inside an empty field. |
| `autocomplete` | `text` | Browser autofill hint, such as "name", "email" or "tel". |

### Number (`number`)

Required: `type`, `name`.

| Key | Value | Description |
| --- | ----- | ----------- |
| `type` | `type` | The kind of field or group. |
| `name` | `name` | Unique name used when the form is submitted; letters, digits, underscores and hyphens. |
| `label` | `text` | Text shown with the field. |
| `id` | `optional_text` | HTML id; generated when left out. |
| `classes` | `classes` | Extra CSS classes. |
| `attributes` | `attributes` | Extra HTML attributes. |
| `width` | `width` | Column width inside a row, from 1 to 12. |
| `rules` | `rules` | Validation rules, such as "required|max_length[100]". |
| `messages` | `messages` | Custom error messages by rule name; "options" for invalid choices. |
| `help` | `optional_text` | Hint shown under the field. |
| `default` | `default` | Initial value. |
| `disabled` | `bool` | Shown but not editable, and never submitted. |
| `readonly` | `bool` | Shown but not editable; still submitted. |
| `autofocus` | `bool` | Put the cursor here when the page opens. |
| `placeholder` | `optional_text` | Example text shown inside an empty field. |
| `autocomplete` | `text` | Browser autofill hint, such as "name", "email" or "tel". |
| `step` | `step` | Interval between allowed values, or "any". |

### Phone number (`tel`)

Required: `type`, `name`.

| Key | Value | Description |
| --- | ----- | ----------- |
| `type` | `type` | The kind of field or group. |
| `name` | `name` | Unique name used when the form is submitted; letters, digits, underscores and hyphens. |
| `label` | `text` | Text shown with the field. |
| `id` | `optional_text` | HTML id; generated when left out. |
| `classes` | `classes` | Extra CSS classes. |
| `attributes` | `attributes` | Extra HTML attributes. |
| `width` | `width` | Column width inside a row, from 1 to 12. |
| `rules` | `rules` | Validation rules, such as "required|max_length[100]". |
| `messages` | `messages` | Custom error messages by rule name; "options" for invalid choices. |
| `help` | `optional_text` | Hint shown under the field. |
| `default` | `default` | Initial value. |
| `disabled` | `bool` | Shown but not editable, and never submitted. |
| `readonly` | `bool` | Shown but not editable; still submitted. |
| `autofocus` | `bool` | Put the cursor here when the page opens. |
| `placeholder` | `optional_text` | Example text shown inside an empty field. |
| `autocomplete` | `text` | Browser autofill hint, such as "name", "email" or "tel". |

### Web address (`url`)

Required: `type`, `name`.

| Key | Value | Description |
| --- | ----- | ----------- |
| `type` | `type` | The kind of field or group. |
| `name` | `name` | Unique name used when the form is submitted; letters, digits, underscores and hyphens. |
| `label` | `text` | Text shown with the field. |
| `id` | `optional_text` | HTML id; generated when left out. |
| `classes` | `classes` | Extra CSS classes. |
| `attributes` | `attributes` | Extra HTML attributes. |
| `width` | `width` | Column width inside a row, from 1 to 12. |
| `rules` | `rules` | Validation rules, such as "required|max_length[100]". |
| `messages` | `messages` | Custom error messages by rule name; "options" for invalid choices. |
| `help` | `optional_text` | Hint shown under the field. |
| `default` | `default` | Initial value. |
| `disabled` | `bool` | Shown but not editable, and never submitted. |
| `readonly` | `bool` | Shown but not editable; still submitted. |
| `autofocus` | `bool` | Put the cursor here when the page opens. |
| `placeholder` | `optional_text` | Example text shown inside an empty field. |
| `autocomplete` | `text` | Browser autofill hint, such as "name", "email" or "tel". |

### Search box (`search`)

Required: `type`, `name`.

| Key | Value | Description |
| --- | ----- | ----------- |
| `type` | `type` | The kind of field or group. |
| `name` | `name` | Unique name used when the form is submitted; letters, digits, underscores and hyphens. |
| `label` | `text` | Text shown with the field. |
| `id` | `optional_text` | HTML id; generated when left out. |
| `classes` | `classes` | Extra CSS classes. |
| `attributes` | `attributes` | Extra HTML attributes. |
| `width` | `width` | Column width inside a row, from 1 to 12. |
| `rules` | `rules` | Validation rules, such as "required|max_length[100]". |
| `messages` | `messages` | Custom error messages by rule name; "options" for invalid choices. |
| `help` | `optional_text` | Hint shown under the field. |
| `default` | `default` | Initial value. |
| `disabled` | `bool` | Shown but not editable, and never submitted. |
| `readonly` | `bool` | Shown but not editable; still submitted. |
| `autofocus` | `bool` | Put the cursor here when the page opens. |
| `placeholder` | `optional_text` | Example text shown inside an empty field. |
| `autocomplete` | `text` | Browser autofill hint, such as "name", "email" or "tel". |

### Date (`date`)

Required: `type`, `name`.

| Key | Value | Description |
| --- | ----- | ----------- |
| `type` | `type` | The kind of field or group. |
| `name` | `name` | Unique name used when the form is submitted; letters, digits, underscores and hyphens. |
| `label` | `text` | Text shown with the field. |
| `id` | `optional_text` | HTML id; generated when left out. |
| `classes` | `classes` | Extra CSS classes. |
| `attributes` | `attributes` | Extra HTML attributes. |
| `width` | `width` | Column width inside a row, from 1 to 12. |
| `rules` | `rules` | Validation rules, such as "required|max_length[100]". |
| `messages` | `messages` | Custom error messages by rule name; "options" for invalid choices. |
| `help` | `optional_text` | Hint shown under the field. |
| `default` | `default` | Initial value. |
| `disabled` | `bool` | Shown but not editable, and never submitted. |
| `readonly` | `bool` | Shown but not editable; still submitted. |
| `autofocus` | `bool` | Put the cursor here when the page opens. |
| `autocomplete` | `text` | Browser autofill hint, such as "name", "email" or "tel". |
| `min` | `text` | Earliest selectable value. |
| `max` | `text` | Latest selectable value. |
| `step` | `whole_step` | Interval between allowed values, or "any". |

### Time (`time`)

Required: `type`, `name`.

| Key | Value | Description |
| --- | ----- | ----------- |
| `type` | `type` | The kind of field or group. |
| `name` | `name` | Unique name used when the form is submitted; letters, digits, underscores and hyphens. |
| `label` | `text` | Text shown with the field. |
| `id` | `optional_text` | HTML id; generated when left out. |
| `classes` | `classes` | Extra CSS classes. |
| `attributes` | `attributes` | Extra HTML attributes. |
| `width` | `width` | Column width inside a row, from 1 to 12. |
| `rules` | `rules` | Validation rules, such as "required|max_length[100]". |
| `messages` | `messages` | Custom error messages by rule name; "options" for invalid choices. |
| `help` | `optional_text` | Hint shown under the field. |
| `default` | `default` | Initial value. |
| `disabled` | `bool` | Shown but not editable, and never submitted. |
| `readonly` | `bool` | Shown but not editable; still submitted. |
| `autofocus` | `bool` | Put the cursor here when the page opens. |
| `autocomplete` | `text` | Browser autofill hint, such as "name", "email" or "tel". |
| `min` | `text` | Earliest selectable value. |
| `max` | `text` | Latest selectable value. |
| `step` | `whole_step` | Interval between allowed values, or "any". |

### Date and time (`datetime_local`)

Required: `type`, `name`.

| Key | Value | Description |
| --- | ----- | ----------- |
| `type` | `type` | The kind of field or group. |
| `name` | `name` | Unique name used when the form is submitted; letters, digits, underscores and hyphens. |
| `label` | `text` | Text shown with the field. |
| `id` | `optional_text` | HTML id; generated when left out. |
| `classes` | `classes` | Extra CSS classes. |
| `attributes` | `attributes` | Extra HTML attributes. |
| `width` | `width` | Column width inside a row, from 1 to 12. |
| `rules` | `rules` | Validation rules, such as "required|max_length[100]". |
| `messages` | `messages` | Custom error messages by rule name; "options" for invalid choices. |
| `help` | `optional_text` | Hint shown under the field. |
| `default` | `default` | Initial value. |
| `disabled` | `bool` | Shown but not editable, and never submitted. |
| `readonly` | `bool` | Shown but not editable; still submitted. |
| `autofocus` | `bool` | Put the cursor here when the page opens. |
| `autocomplete` | `text` | Browser autofill hint, such as "name", "email" or "tel". |
| `min` | `text` | Earliest selectable value. |
| `max` | `text` | Latest selectable value. |
| `step` | `whole_step` | Interval between allowed values, or "any". |

### Colour (`color`)

Required: `type`, `name`.

| Key | Value | Description |
| --- | ----- | ----------- |
| `type` | `type` | The kind of field or group. |
| `name` | `name` | Unique name used when the form is submitted; letters, digits, underscores and hyphens. |
| `label` | `text` | Text shown with the field. |
| `id` | `optional_text` | HTML id; generated when left out. |
| `classes` | `classes` | Extra CSS classes. |
| `attributes` | `attributes` | Extra HTML attributes. |
| `width` | `width` | Column width inside a row, from 1 to 12. |
| `rules` | `rules` | Validation rules, such as "required|max_length[100]". |
| `messages` | `messages` | Custom error messages by rule name; "options" for invalid choices. |
| `help` | `optional_text` | Hint shown under the field. |
| `default` | `default` | Initial value. |
| `disabled` | `bool` | Shown but not editable, and never submitted. |
| `readonly` | `bool` | Shown but not editable; still submitted. |
| `autofocus` | `bool` | Put the cursor here when the page opens. |

### Long text (`textarea`)

Required: `type`, `name`.

| Key | Value | Description |
| --- | ----- | ----------- |
| `type` | `type` | The kind of field or group. |
| `name` | `name` | Unique name used when the form is submitted; letters, digits, underscores and hyphens. |
| `label` | `text` | Text shown with the field. |
| `id` | `optional_text` | HTML id; generated when left out. |
| `classes` | `classes` | Extra CSS classes. |
| `attributes` | `attributes` | Extra HTML attributes. |
| `width` | `width` | Column width inside a row, from 1 to 12. |
| `rules` | `rules` | Validation rules, such as "required|max_length[100]". |
| `messages` | `messages` | Custom error messages by rule name; "options" for invalid choices. |
| `help` | `optional_text` | Hint shown under the field. |
| `default` | `default` | Initial value. |
| `disabled` | `bool` | Shown but not editable, and never submitted. |
| `readonly` | `bool` | Shown but not editable; still submitted. |
| `autofocus` | `bool` | Put the cursor here when the page opens. |
| `placeholder` | `optional_text` | Example text shown inside an empty field. |
| `autocomplete` | `text` | Browser autofill hint, such as "name", "email" or "tel". |
| `rows` | `positive_int` | Visible lines of text. |

### Dropdown (`select`)

Required: `type`, `name`, `options`.

| Key | Value | Description |
| --- | ----- | ----------- |
| `type` | `type` | The kind of field or group. |
| `name` | `name` | Unique name used when the form is submitted; letters, digits, underscores and hyphens. |
| `label` | `text` | Text shown with the field. |
| `id` | `optional_text` | HTML id; generated when left out. |
| `classes` | `classes` | Extra CSS classes. |
| `attributes` | `attributes` | Extra HTML attributes. |
| `width` | `width` | Column width inside a row, from 1 to 12. |
| `rules` | `rules` | Validation rules, such as "required|max_length[100]". |
| `messages` | `messages` | Custom error messages by rule name; "options" for invalid choices. |
| `help` | `optional_text` | Hint shown under the field. |
| `default` | `default` | Initial value. |
| `disabled` | `bool` | Shown but not editable, and never submitted. |
| `readonly` | `bool` | Shown but not editable; still submitted. |
| `autofocus` | `bool` | Put the cursor here when the page opens. |
| `options` | `options_with_groups` | Choices as value => label. |
| `strict_options` | `bool` | Reject submitted values that are not among the choices. |
| `placeholder` | `optional_text` | Example text shown inside an empty field. |
| `multiple` | `bool` | Allow several choices or files. |
| `size` | `positive_int` | Visible rows of a multiple choice list. |
| `each_rules` | `rules` | Rules for every chosen item. |

### Single choice (`radio`)

Required: `type`, `name`, `options`.

| Key | Value | Description |
| --- | ----- | ----------- |
| `type` | `type` | The kind of field or group. |
| `name` | `name` | Unique name used when the form is submitted; letters, digits, underscores and hyphens. |
| `label` | `text` | Text shown with the field. |
| `id` | `optional_text` | HTML id; generated when left out. |
| `classes` | `classes` | Extra CSS classes. |
| `attributes` | `attributes` | Extra HTML attributes. |
| `width` | `width` | Column width inside a row, from 1 to 12. |
| `rules` | `rules` | Validation rules, such as "required|max_length[100]". |
| `messages` | `messages` | Custom error messages by rule name; "options" for invalid choices. |
| `help` | `optional_text` | Hint shown under the field. |
| `default` | `default` | Initial value. |
| `disabled` | `bool` | Shown but not editable, and never submitted. |
| `readonly` | `bool` | Shown but not editable; still submitted. |
| `autofocus` | `bool` | Put the cursor here when the page opens. |
| `options` | `options` | Choices as value => label. |
| `strict_options` | `bool` | Reject submitted values that are not among the choices. |
| `inline` | `bool` | Show the choices on one line. |

### Tick box (`checkbox`)

Required: `type`, `name`.

| Key | Value | Description |
| --- | ----- | ----------- |
| `type` | `type` | The kind of field or group. |
| `name` | `name` | Unique name used when the form is submitted; letters, digits, underscores and hyphens. |
| `label` | `text` | Text shown with the field. |
| `id` | `optional_text` | HTML id; generated when left out. |
| `classes` | `classes` | Extra CSS classes. |
| `attributes` | `attributes` | Extra HTML attributes. |
| `width` | `width` | Column width inside a row, from 1 to 12. |
| `rules` | `rules` | Validation rules, such as "required|max_length[100]". |
| `messages` | `messages` | Custom error messages by rule name; "options" for invalid choices. |
| `help` | `optional_text` | Hint shown under the field. |
| `default` | `default` | Initial value. |
| `disabled` | `bool` | Shown but not editable, and never submitted. |
| `readonly` | `bool` | Shown but not editable; still submitted. |
| `autofocus` | `bool` | Put the cursor here when the page opens. |
| `strict_options` | `bool` | Reject submitted values that are not among the choices. |
| `checked_value` | `checked_value` | Value sent when the box is ticked. |
| `inline` | `bool` | Show the choices on one line. |

### Multiple choice (`checkboxes`)

Required: `type`, `name`, `options`.

| Key | Value | Description |
| --- | ----- | ----------- |
| `type` | `type` | The kind of field or group. |
| `name` | `name` | Unique name used when the form is submitted; letters, digits, underscores and hyphens. |
| `label` | `text` | Text shown with the field. |
| `id` | `optional_text` | HTML id; generated when left out. |
| `classes` | `classes` | Extra CSS classes. |
| `attributes` | `attributes` | Extra HTML attributes. |
| `width` | `width` | Column width inside a row, from 1 to 12. |
| `rules` | `rules` | Validation rules, such as "required|max_length[100]". |
| `messages` | `messages` | Custom error messages by rule name; "options" for invalid choices. |
| `help` | `optional_text` | Hint shown under the field. |
| `default` | `default` | Initial value. |
| `disabled` | `bool` | Shown but not editable, and never submitted. |
| `readonly` | `bool` | Shown but not editable; still submitted. |
| `autofocus` | `bool` | Put the cursor here when the page opens. |
| `options` | `options` | Choices as value => label. |
| `strict_options` | `bool` | Reject submitted values that are not among the choices. |
| `inline` | `bool` | Show the choices on one line. |
| `each_rules` | `rules` | Rules for every chosen item. |

### File upload (`file`)

Required: `type`, `name`, `max_size`, `mimes`.

| Key | Value | Description |
| --- | ----- | ----------- |
| `type` | `type` | The kind of field or group. |
| `name` | `name` | Unique name used when the form is submitted; letters, digits, underscores and hyphens. |
| `label` | `text` | Text shown with the field. |
| `id` | `optional_text` | HTML id; generated when left out. |
| `classes` | `classes` | Extra CSS classes. |
| `attributes` | `attributes` | Extra HTML attributes. |
| `width` | `width` | Column width inside a row, from 1 to 12. |
| `rules` | `rules` | Validation rules, such as "required|max_length[100]". |
| `messages` | `messages` | Custom error messages by rule name; "options" for invalid choices. |
| `help` | `optional_text` | Hint shown under the field. |
| `disabled` | `bool` | Shown but not editable, and never submitted. |
| `autofocus` | `bool` | Put the cursor here when the page opens. |
| `required` | `bool` | A file must be uploaded. |
| `multiple` | `bool` | Allow several choices or files. |
| `max_size` | `positive_int` | Largest file size in kilobytes. |
| `extensions` | `strings` | Allowed file name extensions, such as "pdf". |
| `mimes` | `strings` | Allowed file types, such as "image/png". |
| `image` | `bool` | Only images. |
| `max_dimensions` | `dimensions` | Largest image size as [width, height] in pixels. |

### Button (`submit`)

Required: `type`.

| Key | Value | Description |
| --- | ----- | ----------- |
| `type` | `type` | The kind of field or group. |
| `name` | `name` | Unique name used when the form is submitted; letters, digits, underscores and hyphens. |
| `label` | `text` | Button text. |
| `id` | `optional_text` | HTML id; generated when left out. |
| `classes` | `classes` | Extra CSS classes. |
| `attributes` | `attributes` | Extra HTML attributes. |
| `width` | `width` | Column width inside a row, from 1 to 12. |
| `disabled` | `bool` | Shown but not editable, and never submitted. |

### Row (`row`)

Required: `children`.
A group: its `children` are fields and other groups.

| Key | Value | Description |
| --- | ----- | ----------- |
| `type` | `type` | The kind of field or group. |
| `children` | `children` | Fields and groups inside this group. |
| `classes` | `classes` | Extra CSS classes. |
| `width` | `width` | Column width inside a row, from 1 to 12. |

### Section (`fieldset`)

Required: `children`, `legend`.
A group: its `children` are fields and other groups.

| Key | Value | Description |
| --- | ----- | ----------- |
| `type` | `type` | The kind of field or group. |
| `legend` | `text` | Title of the section; use "" for none. |
| `description` | `optional_text` | Short explanation shown under the section title. |
| `id` | `optional_text` | HTML id; generated when left out. |
| `classes` | `classes` | Extra CSS classes. |
| `width` | `width` | Column width inside a row, from 1 to 12. |
| `children` | `children` | Fields and groups inside this group. |

## Developer-only types

These types are not allowed by the restricted policy: password fields invite
phishing, and hidden fields carry data the visitor cannot see. Schemas loaded
with `SchemaPolicy::trusted()` may use them.

### Password (`password`)

Required: `type`, `name`.

| Key | Value | Description |
| --- | ----- | ----------- |
| `type` | `type` | The kind of field or group. |
| `name` | `name` | Unique name used when the form is submitted; letters, digits, underscores and hyphens. |
| `label` | `text` | Text shown with the field. |
| `id` | `optional_text` | HTML id; generated when left out. |
| `classes` | `classes` | Extra CSS classes. |
| `attributes` | `attributes` | Extra HTML attributes. |
| `width` | `width` | Column width inside a row, from 1 to 12. |
| `rules` | `rules` | Validation rules, such as "required|max_length[100]". |
| `messages` | `messages` | Custom error messages by rule name; "options" for invalid choices. |
| `help` | `optional_text` | Hint shown under the field. |
| `default` | `default` | Initial value. |
| `disabled` | `bool` | Shown but not editable, and never submitted. |
| `readonly` | `bool` | Shown but not editable; still submitted. |
| `autofocus` | `bool` | Put the cursor here when the page opens. |
| `placeholder` | `optional_text` | Example text shown inside an empty field. |
| `autocomplete` | `text` | Browser autofill hint, such as "name", "email" or "tel". |

### Hidden value (`hidden`)

Required: `type`, `name`.

| Key | Value | Description |
| --- | ----- | ----------- |
| `type` | `type` | The kind of field or group. |
| `name` | `name` | Unique name used when the form is submitted; letters, digits, underscores and hyphens. |
| `id` | `optional_text` | HTML id; generated when left out. |
| `attributes` | `attributes` | Extra HTML attributes. |
| `default` | `default` | Initial value. |
| `rules` | `rules` | Validation rules, such as "required|max_length[100]". |
| `messages` | `messages` | Custom error messages by rule name; "options" for invalid choices. |

## Value kinds

| Kind | Accepts |
| ---- | ------- |
| `type` | The type name |
| `name` | A field name: letters, digits, underscores and hyphens, starting with a letter or underscore |
| `text` | Text |
| `optional_text` | Text, or `null` |
| `bool` | `true` or `false` |
| `positive_int` | A whole number of at least 1 |
| `width` | A whole number from 1 to 12, or `null` |
| `rules` | A rule string such as `"required|max_length[100]"`, or a list of rule strings |
| `messages` | An object of rule name to message, such as `{"required": "Tell us your name."}` |
| `default` | A value, or a list of values for fields that hold several |
| `options` | An object of value to label, such as `{"suit": "Suit"}` |
| `options_with_groups` | An object of value to label; a label may be an object of its own to form a group |
| `provider` | The name of a registered option provider |
| `attributes` | An object of attribute name to value |
| `classes` | A class string, or a list of them |
| `step` | A positive number, or `"any"` |
| `whole_step` | A positive whole number, or `"any"` |
| `checked_value` | Text or a whole number |
| `strings` | A string, or a list of strings |
| `dimensions` | `[width, height]` or `{"width": ..., "height": ...}`, in whole pixels |
| `children` | A list of fields and groups |
| `mixed` | Defined by the field type |

## Rules allowed by the restricted policy

Any other rule is refused, with the reason for well-known ones such as
`is_unique` (it reads the database) and `regex_match` (a badly written pattern
can hang the server). The trusted policy allows every rule.

| Rule | Parameter |
| ---- | --------- |
| `required` | none |
| `permit_empty` | none |
| `string` | none |
| `min_length` | a whole number, such as `[100]` |
| `max_length` | a whole number, such as `[100]` |
| `exact_length` | one or more whole numbers, such as `[6]` or `[6,8]` |
| `alpha` | none |
| `alpha_space` | none |
| `alpha_dash` | none |
| `alpha_numeric` | none |
| `alpha_numeric_space` | none |
| `alpha_numeric_punct` | none |
| `numeric` | none |
| `integer` | none |
| `decimal` | none |
| `is_natural` | none |
| `is_natural_no_zero` | none |
| `greater_than` | a number, such as `[0]` |
| `greater_than_equal_to` | a number, such as `[0]` |
| `less_than` | a number, such as `[0]` |
| `less_than_equal_to` | a number, such as `[0]` |
| `valid_email` | none |
| `valid_emails` | none |
| `valid_url_strict` | optional URL schemes, such as `[https]` |
| `valid_date` | an optional date format, such as `[Y-m-d]` |
| `in_list` | a list of up to 100 values, such as `[a,b,c]` |
| `not_in_list` | a list of up to 100 values, such as `[a,b,c]` |
| `matches` | another field in the form, such as `[password]` |
| `differs` | another field in the form, such as `[password]` |
| `required_with` | other fields in the form, such as `[phone,email]` |
| `required_without` | other fields in the form, such as `[phone,email]` |

## Other limits of the restricted policy

| Limit | Value |
| ----- | ----- |
| Attributes | `title`, `placeholder`, `autocomplete`, `inputmode`, `spellcheck`, `autocapitalize`, `enterkeyhint`, `dir`, `lang`, `aria-*` (`aria-*` means any attribute starting with `aria-`) |
| Upload types (`mimes`) | `image/jpeg`, `image/png`, `image/gif`, `image/webp`, `application/pdf`, `application/vnd.openxmlformats-officedocument.wordprocessingml.document`, `application/vnd.openxmlformats-officedocument.spreadsheetml.sheet`, `application/vnd.openxmlformats-officedocument.presentationml.presentation`, `application/msword`, `application/vnd.ms-excel`, `text/plain`, `text/csv` |
| Largest `max_size` | 10240 kilobytes |
| Forbidden field names | `id`, `user_id`, `owner_id`, `tenant_id`, `role`, `roles`, `is_admin`, `admin`, `permissions`, `password`, `password_hash`, `token`, `api_key`, `secret`, `created_at`, `updated_at`, `deleted_at`, `created_by`, `updated_by` |
| `max_fields` | 100 |
| `max_depth` | 5 |
| `max_options` | 500 |
| `max_text` | 1000 |
| `max_rules` | 20 |
| `max_classes` | 20 |
| `max_name_length` | 64 |

Change any of these per app with the methods of `SchemaPolicy`; see
[Schemas](schemas.md#schemapolicy).
