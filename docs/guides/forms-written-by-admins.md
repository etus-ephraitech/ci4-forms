# Forms written by admins

A form can be stored as a JSON schema in your database, so admins and clients
can add or change forms without a developer. This guide builds the three parts
of such a feature: an editor that refuses unsafe schemas, a public page that
shows the form, and storage for the answers.

## The schema

A schema lists fields and groups as data. Every option of a field class has a
key:

```json
{
  "name": "wedding",
  "fields": [
    { "type": "text", "name": "full_name", "label": "Full name", "rules": "required" },
    { "type": "select", "name": "guests", "label": "Number of guests",
      "options": { "small": "Up to 50", "large": "More than 50" }, "rules": "required" },
    { "type": "checkbox", "name": "contact_me", "label": "You may call me" },
    { "type": "submit", "label": "Send" }
  ]
}
```

`row` and `fieldset` groups take a `children` list, exactly like `row()` and
`fieldset()` in a form class. `FormSchema::describe()` returns every type and
key your policy allows, with an explanation of each, if you want to build a
help page for admins.

## One policy for the whole feature

A schema written by an admin is user input. It is checked against a policy,
restricted by default, which blocks anything that could harm your site or its
visitors: event-handler attributes, rules that read the database or can hang
the server, a form action pointing elsewhere, unsafe uploads, password fields,
and names that could overwrite your own columns.

Keep the policy in one place, so the editor and the public page always agree:

```php title="app/Libraries/FormPolicy.php"
--8<-- "docs/examples/app/Libraries/FormPolicy.php"
```

## Storage

```php title="app/Database/Migrations/2026-10-29-000400_CreateFormTables.php"
--8<-- "docs/examples/app/Database/Migrations/2026-10-29-000400_CreateFormTables.php"
```

```php title="app/Models/FormDefinitionModel.php"
--8<-- "docs/examples/app/Models/FormDefinitionModel.php"
```

```php title="app/Models/FormSubmissionModel.php"
--8<-- "docs/examples/app/Models/FormSubmissionModel.php"
```

## The editor

`FormSchema::check()` checks what the admin typed without throwing, and lists
every problem with its location, such as
`fields[2].rules: the "is_unique" rule is not allowed in this schema: it reads the database.`
Only a clean schema is saved:

```php title="app/Controllers/Admin/FormEditor.php"
--8<-- "docs/examples/app/Controllers/Admin/FormEditor.php:save"
```

A "Check" button can call an endpoint that returns the same result as JSON:

```php title="app/Controllers/Admin/FormEditor.php"
--8<-- "docs/examples/app/Controllers/Admin/FormEditor.php:check"
```

??? example "The complete editor controller and view"

    ```php title="app/Controllers/Admin/FormEditor.php"
    --8<-- "docs/examples/app/Controllers/Admin/FormEditor.php"
    ```

    ```php title="app/Views/admin/form_editor.php"
    --8<-- "docs/examples/app/Views/admin/form_editor.php"
    ```

## The public page

Load the saved schema with the same policy. If it no longer passes, because it
was saved before you tightened the policy or edited in the database directly,
loading throws: show a short notice instead of the form and log the details for
your admins.

```php title="app/Controllers/Forms.php"
--8<-- "docs/examples/app/Controllers/Forms.php:load"
```

The application sets where the form is sent; a schema cannot.

Answers are stored as one JSON payload, never mapped onto columns, so a field
an admin adds can never write to data it should not touch:

```php title="app/Controllers/Forms.php"
--8<-- "docs/examples/app/Controllers/Forms.php:submit"
```

??? example "The complete public controller, views and routes"

    ```php title="app/Controllers/Forms.php"
    --8<-- "docs/examples/app/Controllers/Forms.php"
    ```

    ```php title="app/Views/forms/show.php"
    --8<-- "docs/examples/app/Views/forms/show.php"
    ```

    ```php title="app/Views/forms/unavailable.php"
    --8<-- "docs/examples/app/Views/forms/unavailable.php"
    ```

    ```php title="app/Config/Routes.php"
    --8<-- "docs/examples/app/Config/admin-forms-routes.php"
    ```

## Adjusting the policy

The restricted defaults suit public forms. Change them with methods that each
return a new policy:

```php
SchemaPolicy::restricted()
    ->allowTypes('money')                              // a registered custom field type
    ->allowRules(['valid_ip' => SchemaPolicy::PARAM_NONE])
    ->forbidNames('customer_id')                       // more of your own columns
    ->disallowProviders('staff')                       // keep internal lists off public forms
    ->withMaxUploadSize(20480);                        // kilobytes
```

Changes that would reopen a hole, such as allowing `onclick` attributes or SVG
uploads, are refused.

Schemas that developers write and commit with the code can use
`SchemaPolicy::trusted()`, which only checks their structure:

```php
$form = Form::fromJson(file_get_contents(APPPATH . 'Forms/enquiry.json'), policy: SchemaPolicy::trusted());
```

`php spark forms:schema check path/to/schema.json` checks a schema file from
the command line, and `php spark forms:schema export "App\Forms\BookingForm"`
turns a form class into a schema, a good starting point for admins.

## Common mistakes

**Loading admin schemas with the trusted policy.** The trusted policy allows
everything. Use it only for schemas in your code repository.

**Using different policies in different places.** If the editor allows
something the public page does not, a saved form goes offline. Keep one policy
class, as in the example.

**Saving answers into columns.** An admin could add a field named like one of
your columns. Store answers as a payload.

**Showing schema errors to visitors.** They describe your configuration. Log
them and show a neutral notice, as the example does.

## Related

- [Showing submissions](showing-submissions.md) to read the stored answers
- [Option providers](option-providers.md) for `options_from`
