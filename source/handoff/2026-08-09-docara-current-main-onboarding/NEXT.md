# Next action: `explicit_user_decision`

Terminal state: `docara_terminal_no_active_implementation`

Release authorized: `false`

There is no automatic implementation continuation.

A future user decision is required before opening any lifecycle action. It must
state at least:

1. whether a version/release action is desired at all;
2. the exact source revision and artifact identity;
3. the requested version/channel;
4. whether the scope includes a tag, package publication, release page or
   deployment;
5. the required fresh verification and, for deployment, target/rollback gates.

Until such a decision is explicit, stop after read-only inspection or bounded
governance maintenance. Do not tag, release, publish or deploy.
