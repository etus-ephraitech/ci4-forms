# Testing your forms

Forms are ordinary classes, so you test them with CodeIgniter's testing tools.
Test the form class for its rules, and your controllers for the whole request.

## Testing a form class

`validate()` accepts an array, so no request is needed:

```php title="tests/AppointmentFormTest.php"
--8<-- "docs/examples/tests/AppointmentFormTest.php"
```

Useful assertions:

| Method | Checks |
| ------ | ------ |
| `validate($data)` | Returns whether the data is valid |
| `getErrors()`, `getError('field')`, `hasError('field')` | Which fields failed, and their messages |
| `validated()` | The clean data a controller would save |
| `render()`, `field('name')` | The HTML, for checking attributes or options |

## Testing a controller

Feature tests send real requests through your routes and controller. With
`$migrate = true`, your migrations run on a fresh test database before each
test:

```php title="tests/AppointmentsTest.php"
--8<-- "docs/examples/tests/AppointmentsTest.php"
```

## Common mistakes

**Posting a PATCH, PUT or DELETE form in a feature test.** Browsers send these
forms as `POST` with a hidden `_method` field, but the test framework sets the
HTTP method before CodeIgniter reads `_method`, so a POST never reaches a
`PATCH` route. Send the real method with a URL-encoded body, as
`testChangingABooking()` does, and clear the previous request's POST data first
with `service('superglobals')->setPostArray([])`.

**Expecting an upload to be valid in a test.** PHP's `is_uploaded_file()` only
accepts files from a real upload. Define replacements in the
`CodeIgniter\HTTP\Files` namespace in your test bootstrap, as CodeIgniter's
own test suite does, then set the files with
`service('superglobals')->setFilesArray([...])` before the request:

```php
namespace CodeIgniter\HTTP\Files;

function is_uploaded_file(string $filename): bool
{
    return is_file($filename);
}

function move_uploaded_file(string $from, string $to): bool
{
    return copy($from, $to);
}
```

**Posting without a CSRF token.** If your app runs CodeIgniter's CSRF filter
on every request, it checks tokens in feature tests too, and a post without
one fails with "The action you requested is not allowed." Add the token to the
posted data:

```php
$this->post('appointments', [csrf_token() => csrf_hash(), 'full_name' => 'Jane Namuli', /* ... */]);
```

## Related

- [Getting started](../getting-started.md) for the controller these tests cover
- [JavaScript and APIs](javascript-and-apis.md) for testing JSON endpoints
