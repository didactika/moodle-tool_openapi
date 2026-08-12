# Tokens

A token issued by this plugin authorizes reading the OpenAPI document, and
nothing else. It is not a Moodle webservice token: it cannot call a single
function. If what you want is an integration that both reads the
documentation and calls the API, see
[Existing webservice token](access-control.md#existing-webservice-token)
instead — one credential is better than two.

**Where:** Access control → the cog on *Plugin token*. The page is also in
the admin tree under OpenAPI documentation.

## Issuing one

**Create token** takes a name and two optional restrictions. The name is for
you: it is what tells you six months from now which integration a token
belongs to, and it is what appears in the site log when the token is created
or deleted.

The token itself is shown **once**, immediately after creation, on the list
page. Only a SHA-256 hash is stored, so:

- Nobody can recover it later — not another administrator, not you, not
  someone with database access.
- Refreshing that page does not show it again. It is held in a
  session-scoped cache for five minutes and dropped the first time it is
  displayed.
- Losing it means issuing a new one and deleting the old.

## Using one

```console
curl -H "Authorization: Bearer <token>" \
  '<site>/admin/tool/openapi/openapi.php'
```

In the header, never as a query parameter. A query string is written to web
server access logs, kept by proxies and stored in browser history; a header
is not. The plugin only reads the header, so there is no less careful option
to fall into by accident.

## Restricting what it can see

**Restrict to specific webservices** — off by default, meaning the whole
catalog. Turned on, it reveals a search-as-you-type picker of every function
the site has, and the token sees only the ones you choose. Useful when an
integrator asks for documentation of the three functions they were given
access to, rather than a map of everything the site can do.

**Allow only requests from certain IP addresses** — off by default, meaning
any address. Turned on, it takes a single address (`192.0.2.1`) or a CIDR
range (`192.0.2.0/24`), several allowed when comma-separated. The check is
Moodle's own `address_in_subnet()`, the same one core uses for a webservice
token's IP restriction, so the syntax behaves identically.

Both restrictions are per token. A token that fails the IP check is refused
exactly like an unknown token: `403 access_denied`, with no hint that the
token was valid but the address was not.

## What is recorded

The list shows each token's name, its scope, its IP restriction, when it was
created and when it was last used. `Last used` is written on every
successful authorization, which is how you find the token nobody has touched
in a year.

## Deleting one

Deletion is real: the row is removed, not flagged. That is deliberate, and
matches what Moodle core does with its own webservice tokens — a credential
an administrator revoked should not survive anywhere.

Whatever was using it loses access immediately, and there is no undo.

The lasting record is in the site log. The plugin raises
`\tool_openapi\event\token_created` and `\tool_openapi\event\token_deleted`,
which appear in **Site administration → Reports → Logs** with the acting
user, as any administrative action does. Neither event ever carries the
token or its hash.

## Privacy

The only personal datum on a token is who issued it. What that means for a
subject access request or a deletion request is in [privacy.md](privacy.md).
