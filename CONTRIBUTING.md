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

### Writing style

- Write for a developer who knows CodeIgniter but not this package.
- Use "you", the present tense and short sentences.
- Explain why, not only how, and say what happens when something goes wrong.
- Start each guide with what it is for and a minimal working example; end with
  common mistakes and related pages.
- Only add a page to the navigation in `mkdocs.yml` when it is complete.
