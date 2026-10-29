# JavaScript and APIs

The same form class that renders an HTML form can validate JSON from a mobile
app or a `fetch()` call, and return errors as JSON.

## Example

An API for the appointments from [Getting started](../getting-started.md),
reusing `AppointmentForm`:

```php title="app/Controllers/Api/Appointments.php"
--8<-- "docs/examples/app/Controllers/Api/Appointments.php"
```

```php title="app/Config/Routes.php"
--8<-- "docs/examples/app/Config/api-routes.php"
```

A request:

```bash
curl -X POST https://example.com/api/appointments \
  -H "Content-Type: application/json" \
  -d '{"full_name": "Jane Namuli", "phone": "0772123456", "service": "fitting",
       "appointment_date": "2030-01-15", "appointment_time": "10:30"}'
```

returns `201 Created` with the saved data and its `id`. Invalid data returns
`400` with one message per field:

```json
{
  "status": 400,
  "error": 400,
  "messages": {
    "phone": "The Phone number field is required.",
    "service": "Please choose a valid option for Service."
  }
}
```

## How it works

What `validate()` reads
:   With no argument, `validate()` reads the current request: the decoded body
    when the `Content-Type` is JSON; otherwise the form post. For a genuine
    `PUT`, `PATCH` or `DELETE` request, which PHP does not put in `$_POST`, it
    reads the URL-encoded body.

Same rules everywhere
:   Rules, the automatic option checking and `validated()` behave exactly as
    for HTML forms. Unknown keys, such as an `is_admin` in the JSON, never
    reach your model.

Errors as JSON
:   `getErrors()` returns `field => message`, ready for
    `failValidationErrors()` from CodeIgniter's `ResponseTrait`.

## Checking a form from JavaScript

To show errors without reloading the page, post the form's data with `fetch()`
to an endpoint that validates and answers with JSON:

```js
const response = await fetch('/api/appointments', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    body: JSON.stringify(Object.fromEntries(new FormData(form))),
});
const result = await response.json();   // result.messages holds the errors
```

For admin screens that edit schemas, `FormSchema::check()` and
`FormSchema::describe()` return JSON-ready data; see
[Forms written by admins](forms-written-by-admins.md).

## Common mistakes

**Redirecting after a genuine PATCH without 303.** CodeIgniter answers a
`PATCH` request's redirect with 307, which asks the client to repeat the
`PATCH` at the new address. APIs should return JSON instead; HTML pages that
redirect after an update should use `redirect()->to($url, 303)`.

**Expecting multipart PATCH or PUT bodies to work.** PHP only parses
`multipart/form-data` for `POST`. Send uploads with `POST`, or JSON and
URL-encoded data with the other methods.

**Forgetting CSRF for calls from your own pages.** With CodeIgniter's CSRF
filter on, send the token, for example in an `X-CSRF-TOKEN` header. Token-based
APIs for other clients are usually excluded from the CSRF filter.

## Related

- [Testing your forms](testing-your-forms.md) for testing JSON endpoints
- [Getting started](../getting-started.md) for the HTML version of these pages
