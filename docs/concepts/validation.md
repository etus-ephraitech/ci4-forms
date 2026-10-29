# Validation

`validate()` runs a fixed sequence of steps. Knowing them explains the order of
error messages, why some values pass that you might not expect to, and where
your own checks fit.

## The steps

1. **Read the input.** With no argument, `validate()` reads the current
   request: the form post (or the query string for GET forms), the decoded body
   for JSON, or the URL-encoded body of a genuine PUT, PATCH or DELETE request.
   You can also pass an `IncomingRequest` or an array.
2. **Prepare it.** `prepareForValidation()` runs first. Override it in a form
   class to clean input, for example to remove spaces from a phone number. Its
   result is what gets validated, shown again on errors, and returned by
   `validated()`.
3. **Remember the submission.** The data is stored as the form's submission,
   so the form can be shown again with what was typed.
4. **Run the rules.** CodeIgniter's Validation checks every field's rules.
   Values are trimmed first, so `" jane@example.com "` passes `valid_email` and
   a name of only spaces fails `required`. Empty values are kept as empty and
   missing keys stay missing, so `required` sees exactly what the visitor left
   out. Messages use the field's label: "The Full name field is required."
5. **Check the options.** For choice fields, any value that is not one of the
   options is rejected. This runs after the rules, and a field that already has
   an error is skipped, so each field shows one error at a time.

`validate()` returns `true` when there are no errors.

## Errors

| Situation | Behaviour |
| --------- | --------- |
| Several rules fail for one field | The first failing rule's message is shown |
| An item of a list fails `eachRules()` | The error is reported on the field itself |
| Errors from rules and from option checks | Rule errors come first, then option errors |
| You call `setError()` | The error is added like a rule error, and `isValid()` becomes `false` |
| You redirect with `redirect()->withInput()` | All errors, including yours from `setError()`, are shown on the next page |

Errors travel with redirects because the package uses CodeIgniter's shared
Validation service, which `withInput()` reads, and `setError()` adds your
errors to it.

## Your own checks

Some checks need more than a rule: a time slot must be free, a date must not
be a holiday. Run them after `validate()` passes, and report problems with
`setError()`:

```php
if (! $form->validate()) {
    return view('booking', ['form' => $form]);
}

if ($this->slots->isTaken($form->validated()['appointment_date'])) {
    $form->setError('appointment_date', 'That date is fully booked.');

    return view('booking', ['form' => $form]);
}
```

## `validated()` is guarded

`validated()` throws a `FormException` when called before `validate()`, or
after validation failed. Saving data that was never checked is a mistake worth
failing loudly in development rather than quietly in production.

## File fields

File rules read the uploaded files, not the posted data, so CodeIgniter's
`permit_empty` cannot make them optional. Instead, an optional file field with
no file chosen is not validated at all, and once a file is chosen every rule
applies.

## Related

- [Values](values.md) for what `validated()` returns
- [Getting started](../getting-started.md#5-add-a-business-rule) for a rule with `setError()`
