# Upgrading

Until version 1.0, a new minor version may change behaviour. Use a constraint
such as `^0.5` so upgrades only happen when you choose them, and read the notes
below for every version you skip.

```bash
composer require ephraitech/ci4-forms:^0.5
```

## From 0.4 to 0.5

No changes are needed. `^0.4` does not install 0.5; change your constraint to
`^0.5`.

- Genuine PUT, PATCH and DELETE requests (from JavaScript or API clients) are
  now validated with the data in their body. Browser forms were not affected.
- Themes can extend another theme with `'extends' => 'bootstrap5'`.

## From 0.3 to 0.4

0.4 only adds features. Custom fields extending `Field` gain `isPresentable()`
and `presentValue()` automatically; classes implementing `FieldInterface`
directly need nothing, and are presented as plain text.

## From 0.2 to 0.3

0.3 adds schemas and changes nothing existing forms rely on. Forms can now
also be created on the command line (spark commands, queue workers, seeders);
in 0.2 that failed when a form was built outside a web request.

## From 0.1 to 0.2

Most apps need no changes. Check these four points:

1. **Strict option checking is on.** Values outside a field's options are now
   rejected. If a form relied on accepting them, use `->strictOptions(false)` on
   that field, or `$strictOptions = false` in the configuration. Existing
   `in_list` rules keep working and can be removed.
2. **Published views.** If you ran `forms:publish` in 0.1, replace your
   `field_select.php` with the package's new version (it uses `$inputName` and
   `$values`); otherwise multiple selects post only one value. Then run
   `php spark forms:publish` to add the new templates; existing files are not
   touched.
3. **Custom fields implementing `FieldInterface` directly** must add
   `getEachRules()`, `isMultiple()` and `getWidth()`. Fields extending `Field`
   or `InputField` need no changes.
4. **`fields()` without arguments** now renders the layout. Forms without rows
   or fieldsets render exactly as before.

## Published views

Views you published with `forms:publish` are yours and are not updated. After
an upgrade, check the [changelog](changelog.md) for view changes, then either
compare your copies or publish again with `--force` and reapply your edits.
Templates for new field types are used from the package until you publish
them.
