# tool_openapi

A Moodle admin tool that exposes the site's own webservice function catalog
(`external_functions`) as an OpenAPI 3.1 document, with configurable access
control (Moodle session, IP allowlist, plugin token, or a reused `wstoken`).

**Status: early development, nothing usable yet.** This repository currently
has no plugin logic — see `CHANGELOG.md` for what has actually shipped.

## Requirements

Moodle 4.5 through 5.2 (`$plugin->supported` in `version.php`).

## Development

```console
composer create-project -n --no-dev --prefer-dist moodlehq/moodle-plugin-ci ci ^4
```

runs the same checks as `.github/workflows/ci.yml` locally — see that file
for the exact sequence (`phplint`, `phpcs`, `phpunit`, `behat`, ...).

## License

[GPL v3 or later](http://www.gnu.org/copyleft/gpl.html), same as Moodle
itself.
