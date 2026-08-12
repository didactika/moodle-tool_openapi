# Development

## Running the checks CI runs

```console
composer create-project -n --no-dev --prefer-dist moodlehq/moodle-plugin-ci ci ^4
```

`.github/workflows/ci.yml` is the authoritative sequence — `phplint`,
`phpmd`, `phpcs`, `phpdoc`, `validate`, `savepoints`, `mustache`, `grunt`,
`phpunit`, `behat`. Read it rather than duplicating the list here, because it
is the file that actually runs.

Two of those steps are worth knowing about before they surprise you:

- **`phpcs --max-warnings 0`.** Warnings fail the build, and one of them is
  alphabetical ordering of language strings. Check the summary, not just the
  exit code: phpcs exits 0 with warnings outstanding.
- **`grunt --max-lint-warnings 0`.** Anything in `amd/src` has to pass
  Moodle's eslint config, where `camelcase` and `capitalized-comments` are
  warnings — and warnings fail here too.

## The test matrix

Built from `$plugin->supported` in `version.php`, which is the only place
this repository declares which Moodle versions it targets. The
version-to-PHP mapping lives in one table inside `ci.yml`; a supported
version with no row there fails the build loudly rather than silently
testing nothing.

## When CI runs

On pull requests, and on manual dispatch. Not on push: `CI complete` is the
required status check on the protected branches, so a push to one is always
an already-tested merge. What runs on a push is `release.yml`, which only
decides whether `version.php` changed enough to tag a release.

## Working on Windows

`core.autocrlf=true` rewrites every file in the working tree to CRLF, which
produces two failures that look like real ones:

- phpcs reports "End of line character is invalid" on files that are fine.
- eslint fails `linebreak-style` on `amd/src/*.js`.

Neither is in the committed content. `git diff --numstat` tells them apart:
an entry with `0 0` changed nothing. Normalise before linting:

```console
sed -i 's/\r$//' <file>
```

Vendored files under `thirdparty/` are marked `-text` in `.gitattributes`, so
they keep the bytes upstream shipped and are exempt from all of this.

## Rebuilding the AMD modules

`amd/build/*.min.js` is committed and must match what grunt produces, or
`moodle-plugin-ci grunt` fails on a dirty tree. Rebuild from a Moodle
checkout with the plugin in place:

```console
npx grunt amd --root=admin/tool/openapi
```

## Adding a language string

`lang/en/tool_openapi.php` is the source. The plugin also ships Spanish,
Portuguese, Italian and French; a string added to English and not to those
leaves them incomplete, and nothing fails to tell you. Keep the four in step,
and keep every file alphabetically ordered — phpcs enforces it.

Other languages belong to Moodle's translation system (AMOS) once the plugin
is listed in the Plugins Directory, and are not kept here.

## Layout

| Path | What lives there |
| --- | --- |
| `classes/access/` | The four gates, the scope they return, and the checker that composes them |
| `classes/generator/` | Turning `external_functions` into the document |
| `classes/output/` | One renderable per page, each with an `export_for_template()` |
| `classes/privacy/` | The privacy provider |
| `pages/` | Admin pages, grouped by feature |
| `templates/` | Mustache, mirroring `pages/` |
| `thirdparty/` | Vendored Swagger UI, declared in `thirdpartylibs.xml` |

`openapi.php` sits at the plugin root rather than under `pages/`, following
the convention Moodle's own `webservice/rest/server.php` uses for a
machine-facing entry point.

## Conventions

Conventional Commits. Deliverables — code, comments, documentation — in
English.
