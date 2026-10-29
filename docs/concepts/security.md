# Security

Forms are where untrusted input enters an application. This page lists what
the package does about it, why, and what remains your responsibility.

## What the package does

Output is escaped
:   Every value, label, help text, error message and option label is escaped
    when rendered, including text that came from visitors or from schemas. The
    only HTML printed as it is comes from your own configuration (the required
    marker).

Unknown keys are dropped
:   `validated()` returns only the fields the form defines. A visitor who adds
    `is_admin=1` to the request cannot set that column through a model.

Choices are checked
:   A value that is not one of a field's options is rejected, whatever the
    browser sent. A select, radio group, checkbox group or checkbox cannot be
    used to submit values you never offered.

Disabled fields are ignored
:   A disabled field is never submitted by a browser, so a value posted for it
    can only be tampering. It is never validated or returned.

Passwords stay out of pages
:   Password fields are never filled back in, so a password is never written
    into the HTML.

Uploads are checked by content
:   `mimes()` and `image()` look at what a file contains, not its name. The
    guide warns about `extensions()`, which only reads the name, and about SVG
    images, which can carry JavaScript.

CSRF field
:   Every non-GET form includes CodeIgniter's CSRF field.

## Forms written by admins

A schema written by an admin or a client is user input too. A rendered
attribute such as `onfocus="..."` would run JavaScript in every visitor's
browser, and escaping cannot prevent that because the attribute itself is the
attack. So schemas are checked against a policy, and the restricted policy is
the default everywhere:

| Blocked | Because |
| ------- | ------- |
| `on*`, `formaction`, `style`, `srcdoc` and `data-*` attributes | They run script, send the form elsewhere, or trigger front-end behaviour (Bootstrap's `data-bs-sanitize="false"` switches off its own sanitiser) |
| Rules outside an allow-list, such as `is_unique` and `regex_match` | They read your database, or a badly written pattern can hang a request |
| A form action | It could send every submission to another site |
| Uploads without a size limit or an explicit list of safe types | Unbounded uploads fill disks; SVG and HTML can carry script |
| Names such as `role`, `is_admin` and `user_id` | Saved into a table, they would let visitors set those columns |
| Password and hidden fields | They invite phishing, or collect data visitors cannot see |
| Oversized schemas | One bad save should not produce a form that breaks a page |

Option providers follow the same idea: a schema can only name a provider you
registered; it cannot choose a table or run code. Loading is all or nothing, so
a schema with one problem never produces half a form.

## What remains your job

- **Turn on the CSRF filter.** The package adds the token field; CodeIgniter
  only checks it when its `csrf` filter runs. See
  [Installation](../installation.md#turn-on-csrf-protection).
- **Decide who may do what.** The package validates input; it does not know
  whether the current user may change a record. Check permissions in your
  controllers, and protect admin screens that edit schemas.
- **Validate hidden fields.** A hidden field's value can be changed by anyone.
  Check it against what is allowed, for example with `is_not_unique`, and never
  use it to decide permissions.
- **Serve uploads carefully.** Store uploads outside the public folder (the
  default `writable/uploads` is), and send them with headers that stop browsers
  from running them, such as `Content-Disposition: attachment`.
- **Store admin-defined answers as a payload.** See
  [Forms written by admins](../guides/forms-written-by-admins.md#the-public-page).
- **Limit request rates** on public forms, with CodeIgniter's Throttler or your
  web server.
