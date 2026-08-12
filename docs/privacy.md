# Privacy

## What is stored

One field in this plugin is personal data: `tool_openapi_tokens.createdby`,
the administrator who issued a token.

Nothing else is. A token's hash identifies the integration holding it, not a
Moodle user. An IP rule is a statement about a network. There are no user
preferences and no files.

## Export

A subject access request returns, for each token that user issued, its name,
its IP restriction if it has one, when it was issued and when it was last
used.

The token itself is never exported. It is a live credential, and an export
archive is a file that leaves the site -- Moodle takes the same position for
its own webservice tokens.

## Deletion

An approved deletion request sets `createdby` to 0. The token keeps working.

This is deliberate, and it is the one place this plugin treats a table as
something other than the user's own content. A token is site
infrastructure: some integration is authenticating with it right now, and
that integration has nothing to do with whoever happened to press the create
button. Deleting the person's account should not silently break it. Core
reaches the same conclusion for `external_tokens` and simply never deletes
on `creatorid` alone; setting the field to 0 goes one step further and
removes the personal datum as well.

An administrator who does want the credential gone deletes it from the
Tokens page. That is a separate, deliberate act, and it is logged.

## Logs

Creating and deleting a token each raise an event
(`\tool_openapi\event\token_created`, `\tool_openapi\event\token_deleted`)
that Moodle stores in the site log with the acting user, as it does for any
administrative action. Neither event ever carries the token or its hash.
