# The generated document

The document is built from `external_functions`, this site's own registry of
webservice functions, by introspecting each one with
`external_api::external_function_info()`. It describes what the site
actually has installed, so a plugin that adds functions adds operations, and
one that is uninstalled removes them.

## One path per function

Moodle has a single REST endpoint, `/webservice/rest/server.php`, and picks
the function with a `wsfunction` parameter. OpenAPI cannot express that: a
path and method together identify exactly one operation, so several hundred
functions cannot share one path.

Every function therefore gets a synthetic path named after it:

```
POST /core_course_get_courses
```

This is a documentation convenience, not a URL that exists. Each operation
says so in three places: in its own `description`, in the
`x-moodle-real-endpoint` extension, and by making `wsfunction` a required
parameter pinned to that one function's name. The bundled viewer rewrites
Try-it-out requests to the real endpoint automatically.

## Request shape

`POST`, always -- Moodle's REST protocol accepts POST for every function,
regardless of whether the function itself reads or writes.

Parameters go in the query string:

| Parameter | Notes |
| --- | --- |
| `wstoken` | Required. A Moodle webservice token. |
| `wsfunction` | Required, pinned to the operation's function. |
| `moodlewsrestformat` | Optional; `json` or `xml`. Moodle defaults to `xml` when it is omitted. |

The function's own arguments go in the body as
`application/x-www-form-urlencoded`. Not JSON: `parse_request()` in
`webservice/rest/locallib.php` merges `$_GET` and `$_POST` and never reads a
JSON body, and PHP only populates `$_POST` for form-encoded and multipart
requests.

## Grouping

Operations are tagged so a viewer can group them. A function registered by a
plugin is tagged with that plugin (`mod_quiz`, `tool_dataprivacy`). Core's
functions cannot be: all ~400 of them are registered under the single
component `moodle`, which would collapse core into one unusable group.

Their names carry the grouping their component does not, so a core function
is tagged with the subsystem in its name -- `core_user_get_users` becomes
`core_user`, `core_course_get_courses` becomes `core_course`. The subsystem
is resolved against `core_component::get_core_subsystems()` rather than
guessed by splitting on underscores, which matters both ways:
`core_courseformat_*` must not land under `core_course` (the longest
matching subsystem wins), and `core_get_string` must not invent a `core_get`
group out of a verb (anything with no subsystem match is tagged plain
`core`).

Every tag in use is also declared at the document root, in alphabetical
order, so a viewer has something to order the groups by.

## Responses

Each operation documents its real return shape, mapped from the function's
`returns_desc`, plus the standard Moodle error body under `default`:

```json
{"exception": "...", "errorcode": "...", "message": "..."}
```

A function whose schema has no properties is emitted as `{}`, an empty
object, not `[]` -- an empty PHP array would encode as a JSON array, and
`properties` must be an object.

## Versions and formats

`?version=3.0` downgrades the document: 3.1's `const` becomes a
single-element `enum`, which is how the same constraint is written in 3.0.
`?format=yaml` emits YAML instead of JSON.

## Extensions

Each operation carries a few `x-moodle-*` fields that have no OpenAPI
equivalent:

| Extension | Meaning |
| --- | --- |
| `x-moodle-real-endpoint` | The one URL the request actually goes to |
| `x-moodle-capabilities` | Capabilities the function requires |
| `x-moodle-ajax-allowed` | Whether the function may be called from AJAX |

## Functions that cannot be introspected

A site can have an installed function whose implementation class does not
load -- a broken registration left by another plugin. Introspecting it
throws, and one such function must not take down the whole catalog, so it is
skipped and the rest is built. This is not hypothetical: a stock Moodle 4.5
has one.
