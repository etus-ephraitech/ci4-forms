# File uploads

Use `file()` to accept photos and documents. The package builds CodeIgniter's
file rules for you, checks files by their real content, and makes the form
multipart automatically.

## Example

A profile form with a required photo and optional PDF certificates:

```php title="app/Forms/ProfileForm.php"
--8<-- "docs/examples/app/Forms/ProfileForm.php"
```

The controller validates as usual, then stores the files:

```php title="app/Controllers/Profile.php"
--8<-- "docs/examples/app/Controllers/Profile.php"
```

## How it works

| Method | CodeIgniter rule | Checks |
| ------ | ---------------- | ------ |
| `required()` | `uploaded` | A file was uploaded |
| `maxSize(2048)` | `max_size` | Size in kilobytes |
| `extensions(['pdf'])` | `ext_in` | The file name's extension |
| `mimes(['application/pdf'])` | `mime_in` | The file's real content type |
| `image()` | `is_image` | The file is an image |
| `maxDimensions(800, 600)` | `max_dims` | Image size in pixels |

The helpers also set the input's `accept` attribute, so the file picker shows
matching files first.

Files are separate from data
:   `validated()` never contains files. Get them with `getFile('photo')` (the
    first file, or `null`) and `getFiles('certificates')` (a list), then call
    `store()` on each. `store('photos')` moves the file to
    `writable/uploads/photos/` under a random name and returns the path to
    save, such as `photos/1793219189_42150fa6c630c2ec.png`.

Optional fields
:   An optional file field is only validated when a file was chosen. Once one
    is, every rule applies.

Multiple files
:   `multiple()` accepts several files, submitted as `name[]`. One invalid file
    fails the field.

Multipart
:   A form with a file field gets `enctype="multipart/form-data"` without you
    doing anything.

After an error
:   Browsers cannot refill a file input. When the form comes back with errors,
    each file field without its own error shows "Please choose the file again."

## Common mistakes

**Trusting the extension.** `extensions()` reads the file name, which anyone
can change: a script renamed to `photo.jpg` passes it. Always add `mimes()` or
`image()`, which look at the content.

**Allowing SVG as an image.** `image()` accepts SVG files, which can contain
JavaScript. If uploaded images are shown back on your site, list the allowed
types with `mimes()` and leave out `image/svg+xml`.

**Expecting the file in `validated()`.** It is not there. Use `getFile()` and
store it yourself.

**Forgetting PHP's own limits.** `upload_max_filesize` and `post_max_size` in
`php.ini` apply before any rule. If they are lower than `maxSize()`, large
files are rejected by PHP first.

## Related

- [Custom field types](custom-field-types.md) if you need an upload field with extra behaviour
