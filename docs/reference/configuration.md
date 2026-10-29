# Configuration

Settings live in `app/Config/Forms.php`, a class extending
`Ephraitech\Forms\Config\Forms`. Override only what differs from the defaults:

```php title="app/Config/Forms.php"
<?php

namespace Config;

use Ephraitech\Forms\Config\Forms as BaseForms;

class Forms extends BaseForms
{
    public bool $showErrorSummary = true;
}
```

Scalar settings can also be set in `.env`, for example
`forms.showErrorSummary = true`.

## Settings

### Behaviour

| Setting | Default | Effect |
| ------- | ------- | ------ |
| `$defaultMethod` | `'post'` | Method of forms that do not set one |
| `$csrf` | `true` | Adds CodeIgniter's CSRF field to every non-GET form |
| `$spoofMethods` | `true` | Sends PUT, PATCH and DELETE forms as POST with a hidden `_method` field |
| `$novalidate` | `true` | Adds `novalidate` to forms, so the server's messages are shown rather than the browser's |
| `$ruleAttributes` | `true` | Adds attributes such as `required` and `maxlength` derived from the rules |
| `$showErrorSummary` | `false` | Shows a list of all errors above the fields when a whole form is rendered |
| `$useOldInput` | `true` | Fills forms from the input carried by `redirect()->withInput()` |
| `$prefixIdsWithFormName` | `true` | Prefixes field ids with the form id (`booking_email`), so two forms on a page never share an id |

### Values

| Setting | Default | Effect |
| ------- | ------- | ------ |
| `$trimStrings` | `true` | Trims strings before validation and in `validated()`; passwords are never trimmed |
| `$emptyStringsToNull` | `true` | Turns empty strings into `null` in `validated()` |
| `$checkboxUncheckedValue` | `0` | The value `validated()` returns for an unticked checkbox |
| `$strictOptions` | `true` | Rejects values outside a choice field's options; fields can override this with `strictOptions()` |
| `$strictOptionsMessage` | `'Please choose a valid option for {field}.'` | The message for such values; `{field}` becomes the label |

### Display

| Setting | Default | Effect |
| ------- | ------- | ------ |
| `$theme` | `'bootstrap5'` | The active theme, a key of `$themes` |
| `$themes` | Bootstrap 5 | The registered themes; see below |
| `$fileReselectMessage` | `'Please choose the file again.'` | The hint under file fields after a failed submission; `null` hides it |
| `$presentYes` | `'Yes'` | How `present()` shows a ticked checkbox |
| `$presentNo` | `'No'` | How `present()` shows an unticked checkbox |
| `$presentEmpty` | `'—'` | How `renderPresented()` shows an empty value |

### Extensions

| Setting | Default | Effect |
| ------- | ------- | ------ |
| `$fieldTypes` | `[]` | Custom field types, as `name => ClassName::class`; each name becomes a builder method and a schema type |
| `$optionProviders` | `[]` | Named option sources, as `name => [ClassName::class, 'method']`, `'ClassName::method'` or a closure set in the constructor |

## Themes

`$themes` maps a theme name to its definition:

| Key | Description |
| --- | ----------- |
| `views` | Where its templates are: a namespaced path, or a folder in `app/Views` such as `forms/compact` |
| `classes` | CSS classes, by the part they style (see below) |
| `required_marker` | HTML added to labels of required fields. It comes from your configuration and is not escaped |
| `extends` | Optional. Another theme whose templates are used for any this theme lacks |

Change single classes in the constructor:

```php
public function __construct()
{
    parent::__construct();

    $this->themes['bootstrap5']['classes']['submit'] = 'btn btn-success';
}
```

### Class keys

The Bootstrap 5 theme defines these keys. Custom views can ask for any of them
with `$class('key')`.

| Key | Styles |
| --- | ------ |
| `form` | The `<form>` tag |
| `group` | The wrapper around a field with its label |
| `label` | Labels |
| `input` | Text-like inputs |
| `textarea` | Text areas |
| `select` | Dropdowns |
| `check_wrapper` | The wrapper of one checkbox or radio button |
| `check_inline` | Added to that wrapper for inline options |
| `check_input` | Checkboxes and radio buttons |
| `check_label` | Their labels |
| `fieldset` | The fieldset around a radio or checkbox group |
| `legend` | That fieldset's legend |
| `invalid` | Added to inputs with an error |
| `error` | Error messages |
| `error_block` | Error messages of radio and checkbox groups |
| `help` | Help text |
| `reselect` | The "choose the file again" hint |
| `submit` | Submit buttons |
| `error_summary` | The error summary |
| `presented` | The list rendered by `renderPresented()` |
| `presented_label` | Its labels |
| `presented_value` | Its values |
| `presented_empty` | Its empty values |
| `row` | Layout rows |
| `column` | A column with a width; `{width}` becomes the number |
| `column_auto` | A column without a width |
| `section` | Layout fieldsets |
| `section_legend` | Their titles |
| `section_description` | Their descriptions |
