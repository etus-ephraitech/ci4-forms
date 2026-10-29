# Contributing

## Running the tests

```bash
composer install
composer test
```

The suite includes `tests/Docs`, which runs the documentation's examples.

## Working on the documentation

The documentation is built from the `docs/` folder with
[Zensical](https://zensical.org), the successor to Material for MkDocs from the
same team, and published to GitHub Pages when a version is tagged. The
configuration is `mkdocs.yml`, which Zensical reads unchanged.

Preview it locally (Python 3.10 or newer):

```bash
python3 -m venv .venv
source .venv/bin/activate        # Windows: .venv\Scripts\activate
pip install -r docs/requirements.txt
zensical serve
```

Then open <http://127.0.0.1:8000>. Pages reload as you edit them.

Before opening a pull request, build with `--strict`, as the Docs workflow does:

```bash
zensical build --strict
```

The same files also build with Material for MkDocs 9.8 (supported until
5 May 2027): install the versions listed in `docs/requirements.txt` and run
`mkdocs build --strict`.

### Examples are files, not copied code

Code examples live in `docs/examples/app/`, laid out like a CodeIgniter app
(`Forms`, `Controllers`, `Models`, `Views`, ...). Pages include them with the
snippets syntax:

````markdown
```php title="app/Forms/ContactForm.php"
--8<-- "docs/examples/app/Forms/ContactForm.php"
```
````

Each example has a test in `tests/Docs/`. When you add or change an example,
add or update its test, so the published examples always work. A missing file
fails the documentation build.

### The reference is checked

- `docs/reference/schema-keys.md` is generated from `FormSchema::describe()`.
  Never edit it by hand; after changing schema keys, types or the restricted
  policy, run `composer docs:reference`. `SchemaReferenceTest` fails while the
  page is out of date.
- `ApiReferenceTest` fails when a public method, setting, theme class key,
  helper or command option is missing from the reference pages, or when a page
  documents something that no longer exists. Add a row for every new public
  method. Mark methods that are not part of the public API with `@internal`.
- A method row starts with its signature in the first cell: ``| `name(...)` |``.
  When the signature contains `|` (a union type), write
  `| <code>name(array&#124;string $value)</code> |` instead, because Markdown
  keeps the backslash of `\|` inside code.

### Concept pages make checked claims

`ConceptsClaimsTest` has one test per statement on the Concepts pages, named
after the page and section (`testValuesMissingMeansEmptyOnceSubmitted`). When
behaviour changes, update the paragraph and its test together.

### Writing style

- Write for a developer who knows CodeIgniter but not this package.
- Use "you", the present tense and short sentences.
- Explain why, not only how, and say what happens when something goes wrong.
- Start each guide with what it is for and a minimal working example; end with
  common mistakes and related pages.
- Only add a page to the navigation in `mkdocs.yml` when it is complete.
