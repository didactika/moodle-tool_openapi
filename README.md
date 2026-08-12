# tool_openapi

A Moodle admin tool that publishes the site's own webservice catalog
(`external_functions`) as an OpenAPI 3.1 document, with a browsable viewer
and four independent ways to control who may read it.

Nothing is exposed until an administrator turns a method on: on a fresh
install every access method is off, and the document endpoint answers `403`
to everyone.

## Requirements

Moodle 4.5 through 5.2 (`$plugin->supported` in `version.php`).

## Installation

Unpack into `admin/tool/openapi` (`public/admin/tool/openapi` on Moodle
5.x, which moved the webroot) and visit **Site administration → Notifications**
to run the install. Nothing else needs configuring for the plugin to be
installed; it simply stays closed until you open it.

## Administration

Everything lives under **Site administration → Plugins → Admin tools →
OpenAPI documentation**, which has two pages:

**Access control** lists the four access methods with a switch each. Methods
that have something of their own to configure carry a cog:

| Method | What authorizes the request | Cog leads to |
| --- | --- | --- |
| Moodle session | A logged-in user holding `tool/openapi:view` | — (managed from Moodle's roles pages) |
| IP address | The caller's address matching an enabled IP rule | This plugin's IP rules |
| Plugin token | An `Authorization: Bearer <token>` header | This plugin's tokens |
| Existing webservice token | Any valid Moodle webservice token | Moodle's own token page |

**Documentation** opens the interactive viewer, downloads the document as
JSON or YAML, and purges the cached copy.

See [docs/access-control.md](docs/access-control.md) for what each method
authorizes and how the scope of a token or IP rule narrows what it returns.

## Reading the document

```
GET /admin/tool/openapi/openapi.php
```

| Parameter | Values | Default |
| --- | --- | --- |
| `format` | `json`, `yaml` | `json` |
| `version` | `3.1`, `3.0` | `3.1` |
| `service` | An external service shortname | the whole catalog |

An unauthorized request gets `403 {"error":"access_denied"}` and nothing
else -- never a hint about whether the requested service exists.

```console
curl -H 'Authorization: Bearer <token>' \
  'https://moodle.example/admin/tool/openapi/openapi.php?format=yaml'
```

What comes back, and why it is shaped the way it is, is described in
[docs/openapi-document.md](docs/openapi-document.md).

## Caching

The catalog is expensive to build (every function is introspected), so it is
cached as a whole. Moodle purges every application cache when a plugin is
installed, upgraded or uninstalled -- the only moment `external_functions`
can change -- and a scheduled task rebuilds it hourly so no real request
pays the build cost. The Documentation page can purge it by hand.

## Privacy

The only personal data stored is which administrator issued each token. It
is exported on request without the token itself, and a deletion request
detaches it rather than destroying a credential some integration is still
using. Details in [docs/privacy.md](docs/privacy.md).

## Bundled libraries

The viewer uses [Swagger UI](https://github.com/swagger-api/swagger-ui)
(Apache 2.0), shipped under `thirdparty/swagger-ui` and declared in
`thirdpartylibs.xml`. Nothing is fetched from a CDN at runtime.

## Development

```console
composer create-project -n --no-dev --prefer-dist moodlehq/moodle-plugin-ci ci ^4
```

runs the same checks as `.github/workflows/ci.yml` locally -- see that file
for the exact sequence (`phplint`, `phpcs`, `phpunit`, `behat`, ...).

## License

[GPL v3 or later](http://www.gnu.org/copyleft/gpl.html), same as Moodle
itself.
