# Installation

## Requirements

- **Moodle 4.5 through 5.2.** The authoritative statement is
  `$plugin->supported` in `version.php`; CI builds its test matrix from that
  array and from nowhere else.
- **PHP** as required by your Moodle release. The plugin adds no requirement
  of its own beyond what Moodle already demands.
- **A database Moodle supports.** CI runs against PostgreSQL and MariaDB.

No PHP extensions beyond Moodle's own, and no external services: the viewer's
library is shipped with the plugin rather than loaded from a CDN.

## Where it goes

```
<moodle>/admin/tool/openapi          Moodle 4.5
<moodle>/public/admin/tool/openapi   Moodle 5.0 and later
```

Moodle 5.0 moved the webroot into `public/`. Installing into the wrong one on
5.x produces a plugin Moodle never sees.

## Installing

**From the Moodle interface.** Site administration → Plugins → Install
plugins, and upload the release zip.

**From a zip by hand.** Unpack so that the plugin's own `version.php` lands at
`admin/tool/openapi/version.php`, then visit Site administration →
Notifications.

**From git.**

```console
git clone https://github.com/didactika/moodle-tool_openapi.git admin/tool/openapi
```

Then Notifications, or `php admin/cli/upgrade.php` on the server.

## After installing

Nothing is exposed. Every access method is off, and
`admin/tool/openapi/openapi.php` answers `403` to every caller until an
administrator turns one on. See [access-control.md](access-control.md).

The plugin adds one scheduled task, **Regenerate the cached OpenAPI catalog
document**, which runs hourly. It is not required for correctness — a cache
miss simply rebuilds on demand — but it keeps a real request from paying the
cost of introspecting several hundred functions.

## Upgrading

The usual Moodle route: replace the directory and visit Notifications, or run
`php admin/cli/upgrade.php`.

Upgrades that change the database carry a step in `db/upgrade.php`, so they
apply on their own. Moodle purges every application cache during a plugin
upgrade, which is exactly when `external_functions` can have changed, so the
catalog rebuilds itself with no action from you.

## Capabilities

Three, all at system level, all granted to `manager` by default:

| Capability | Grants |
| --- | --- |
| `tool/openapi:manage` | The plugin's admin pages: access methods, tokens, IP rules, viewer, downloads |
| `tool/openapi:view` | Reading one service's documentation with a Moodle session |
| `tool/openapi:viewfullcatalog` | Reading the whole catalog with a Moodle session |

Site administrators bypass capability checks, as everywhere in Moodle.

## Uninstalling

Site administration → Plugins → Plugins overview → Uninstall.

That removes both of the plugin's tables, and with them every token and IP
rule. Tokens are credentials: whatever was using one loses access
immediately, and since only hashes were ever stored there is nothing to
restore from a backup of the table. Moodle's own webservice tokens are
untouched — this plugin never created them.

The `token_created` and `token_deleted` entries already written to the site
log stay, as log entries do.
