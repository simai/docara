# Workflow: Example observer isolation

Date: 2026-08-25
Status: completed

## Outcome

An HTML/CSS/JavaScript Example iframe is marked with
`data-sf-observer="ignore"`. The outer SIMAI Framework loader therefore does
not parse utility-looking class names embedded inside the iframe's `srcdoc`.

## Scope

- One renderer output attribute.
- One unit-contract assertion.
- No schema, authoring syntax, sandbox permission or public API change.

## Evidence

- PHP syntax checks pass for renderer and test files.
- Direct renderer assertion confirms the marker.
- A full ui-doc build passes static verification.
- Browser acceptance reports exact example durations and zero animation-loader
  warnings after the marker is present.
