# IP rules

An IP rule authorizes a request **by where it came from, with no credential
at all**. It is the right tool for a server you control on a fixed address —
a documentation site, an internal generator, a CI runner with a static
egress IP — and the wrong tool for anything else.

**Where:** Access control → the cog on *IP address*.

## Before using this

Anyone able to reach the site from a listed address is authorized. There is
no second factor and nothing to steal, because there is nothing to hold.

That makes two mistakes worth naming:

- **Do not list a shared egress address.** An office NAT gateway, a cloud
  provider's shared outbound range or a VPN concentrator puts everyone behind
  it inside the allowlist.
- **Do not list an address you do not control.** An address can be reassigned;
  a rule cannot notice.

If neither of those is comfortably false, issue a [token](tokens.md) instead.

## Creating a rule

**Add IP rule** takes:

- **IP address or CIDR range** — a single address (`192.0.2.1`) or a range
  (`192.0.2.0/24`), several allowed when comma-separated. Matching uses
  Moodle's own `address_in_subnet()`, the same function core uses for
  webservice token IP restrictions, so the syntax behaves identically to the
  rest of Moodle.
- **Description** — what this entry is. Not decoration: a rule with no
  description is a rule nobody dares delete two years later.
- **Restrict to specific webservices** — off by default, meaning the whole
  catalog. Turned on, this rule authorizes only the functions you choose.
- **Enabled** — a rule can be switched off without being deleted, which is
  what you want while diagnosing whether it is the one letting something in.

## How rules combine

Rules are tried in order and the first enabled one that matches wins; its
scope is what the caller gets. A disabled rule is skipped entirely.

If no rule matches, this method simply declines and the next enabled access
method gets its turn — the four are composed with OR. The request is only
refused when none of them accepts it.

## Deleting a rule

Deleted outright, with a confirmation step. Unlike a token, there is no audit
trail to preserve: an IP rule is site configuration — which addresses are
allowed — not a record of what a credential did.
