# Workflow: stabilize the Docara page shell during loading

Date: 2026-08-27
Status: completed
Owner: Docara / Development

## Goal

Keep the visible Docara shell stable from the first paint through Framework
readiness and reduce avoidable work during the initial document load.

## Scope

- prevent Framework's compatibility bootstrap from hiding an already rendered
  Docara shell;
- stop revealing the body independently at `DOMContentLoaded`;
- keep the large documentation navigation inert until the mobile sheet is
  opened for the first time;
- exclude the statically planned navigation trees from Framework's dynamic
  utility scanner so route labels cannot be mistaken for utility usage;
- advance the Framework integration cache revision so browsers do not replay a
  speculative module list learned from the previous shell;
- eagerly load only the default icon font and load optional icon variants on
  demand;
- verify the result in Docara tests, Docara documentation and the current
  local `ui-doc.test` build.

## Boundaries

- Example rendering, embedded Example iframes and Example height mechanics are
  intentionally outside this batch.
- No ui-doc content, public runtime deployment, commit, push, tag or release.
- Existing unrelated changes in Docara and ui-doc are preserved.
- HTTP cache headers remain a hosting concern; this batch does not modify the
  local ServBay or public server configuration.

## Done When

- the page body is never hidden by Framework after Docara's blocking shell CSS
  has loaded;
- the preferences bootstrap no longer contains an independent body reveal;
- the mobile documentation tree is hydrated once, on first menu opening;
- navigation labels produce no speculative Framework utility requests;
- rounded and sharp icon fonts are not requested on pages that use only the
  default icon style;
- focused tests, static builds, reference verification and browser smoke pass;
- project graph, workflow evidence and current memory are synchronized.

## Result

- Docara now keeps its already rendered shell visible while the pinned
  Framework runtime becomes ready; the former independent body reveal was
  removed.
- The documentation tree in the mobile sheet remains inert until the first
  open, then hydrates once with the current-page state and all links intact.
- Static navigation surfaces are excluded from dynamic utility discovery, and
  the integration cache revision was advanced so previously learned false
  positives are discarded.
- Only Material Symbols Outlined loads on a page that uses no rounded or sharp
  icons; variant fonts remain available and load on demand.
- The final browser pass returned 116 responses, zero failed requests, zero
  HTTP errors and zero Docara render errors. The adjacent-page transition
  completed in about 401 ms with the shell visible.

## Verification

- Main portable/typography regression run: 53 tests, 1,272 assertions; the
  only initial failure came from a test process started before its assertion
  was updated. Re-running the two affected scenarios on current code passed
  with 659 assertions.
- Docara documentation: 127 source pages, 261 HTML pages, 32,983 local
  references, zero broken.
- ui-doc: 912 source pages, 1,665 HTML pages, 356,272 local references, zero
  broken.
- `validate project` for Docara passed 128 technical checks with zero errors;
  four pilot authoring profiles remain advisory `review_required` as designed.
- `validate project` for ui-doc passed 913 checks and reported 11 existing
  missing image targets, all on `/ru/fundamentals/colors-and-themes/` under
  `/ru/assets/reference/image-*.png`. They are content defects outside this
  shell-loading batch; production static verification still found no broken
  references in the generated output.
- Browser desktop and mobile smoke: pass. The mobile sheet has five primary
  links before hydration and 280 links after first open; the current page is
  preserved.
- Translation tracking remains report-only and was not remediated.
- The existing ui-doc source defects were not edited or silently waived.
- No commit, push, tag, release or public deployment was performed.
