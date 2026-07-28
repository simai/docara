# HCS workflow-binding control-plane gap

Date: 2026-07-18
Candidate context: `83d677c7eb5f22d9ca2f4ac16990fe16eddbe985`
Artifact-scoped HCS verdict: **PASS** for four inventoried surfaces
Carrier workflow-binding status: **PENDING CONTROL-PLANE CORRECTION**

The standalone artifact-scoped Human-Centered Simplicity checker passed with
no warning or blocker for all four accepted surfaces.

This verdict does not claim a file-by-file HCS classification of the complete
candidate diff. Full candidate acceptance is provided separately by the exact
code/contract, browser/UX and archive tester verdicts.

- review SHA-256:
  `e6c7098ed34187e5e4fb07a943ecbcd70c0a207a0f3b2153e8153109e72a2c60`;
- independent tester verdict SHA-256:
  `24bdd09491cfbed93f39794d0b47ff42206d451aa4bcdf8607aff0bae8379125`.

The existing central checker cannot additionally bind an
`artifact_comparison` review to the evidence-carrier workflow when a Git
baseline is present: embedded workflow binding requires the baseline, while
artifact mode is then rejected. Switching to `git_range` would require the
review carrier itself to be part of a target that must remain current HEAD,
which recreates the self-reference after commit.

No PASS is fabricated for that separate control-plane binding. The four-surface
HCS acceptance remains valid; the binding limitation does not change candidate
code, browser evidence, local publication or the explicit release nonclaims.
