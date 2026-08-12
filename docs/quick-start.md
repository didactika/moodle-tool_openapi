# Quick start

From a fresh install to a first authorized request. Five minutes, and you
need site administrator access.

## 1. Install

Unpack the plugin into `admin/tool/openapi` — on Moodle 5.x that is
`public/admin/tool/openapi`, since 5.0 moved the webroot — and visit **Site
administration → Notifications**. Other install routes are in
[installation.md](installation.md).

## 2. Find it

**Site administration → Plugins → Admin tools → OpenAPI documentation.**

There are two pages, and the tab bar is on both:

- **Access control** — who may read the document. This is where the plugin
  opens.
- **Documentation** — the viewer, the downloads and the cache.

## 3. Look at the catalog before opening anything

You do not need to turn on any access method to see the document yourself.
Go to **Documentation → Open the interactive viewer**. The pages of this
plugin answer to the `tool/openapi:manage` capability, not to the access
methods, so an administrator can always get at the catalog.

If you only wanted to read the spec, you are done: download it as JSON or
YAML from the same page.

## 4. Decide who else may read it

The access methods control **one URL and nothing else**:

```
<your site>/admin/tool/openapi/openapi.php
```

That page states this in full, with the address for your site and a button to
copy it. Right now every method is off, so that URL answers `403` to
everybody.

Pick the one that matches who is asking:

| Who is asking | Method |
| --- | --- |
| A developer with a Moodle account | Moodle session |
| A server you control, on a fixed address | IP address |
| An integration with no Moodle account | Plugin token |
| An integration that already has a webservice token | Existing webservice token |

The full description of each is in [access-control.md](access-control.md).

## 5. Issue a token

Assuming a plugin token: turn on **Plugin token**, then click the cog on that
row.

**Create token**, give it a name that says who it is for, and save. The token
is displayed **once**, on the page you land on. Copy it now — only a SHA-256
hash is stored, so nobody can recover it later, not even you.

Two optional restrictions, both off by default:

- *Restrict to specific webservices* — pick exactly which functions this
  token may see documented.
- *Allow only requests from certain IP addresses* — the same kind of
  restriction a Moodle webservice token can carry.

## 6. Make the request

```console
curl -fsS -H "Authorization: Bearer <your token>" \
  'https://moodle.example/admin/tool/openapi/openapi.php?format=yaml'
```

The token goes in the header, never in the query string: a query parameter
ends up in web server access logs, in proxies and in browser history.

If you get `403 {"error":"access_denied"}`, work through
[troubleshooting.md](troubleshooting.md) — the usual causes are the method
still being off, the header not reaching PHP, or an IP restriction on the
token.

## 7. Optional: try a real call

The viewer's **Try it out** sends a real request to Moodle's REST endpoint.
That needs Moodle's own web services enabled, which is separate from this
plugin — see [viewer.md](viewer.md). The viewer tells you if they are off.

## Where to go next

- Narrow what an integration can see: [tokens.md](tokens.md).
- Understand the document you just downloaded:
  [openapi-document.md](openapi-document.md).
- Keep a copy up to date automatically: [automation.md](automation.md).
