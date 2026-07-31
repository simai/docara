# Code-block regression acceptance

Date: 2026-07-26
Verdict: PASS

## Root cause

The Highlight component used the Framework root as webpack's dynamic public
path. Its language chunks actually live below
`component/highlight/js/`, therefore the browser requested a non-existent
`distr/js/<chunk>.js`.

## Corrected runtime

- `ui-loader@8dc4a9c40a063a240713f2f6995ac34a0c53c53b`
- `ui-builder@f9aa00ab2c4646262a85b7f61629e17af1f78ba7`
- `ui@fa5e1b94fd5327847ac920d7001c69dca4634080`
- `ui-smart@ab896dc7cd33f151377e3992ffb286769beee7f7`
- `sf-v5.3.2-fa5e1b94-ab896dc7`

## Checks

| Check | Result |
| --- | --- |
| Two clean Framework builds | byte-identical |
| Highlight source tests | PASS |
| Highlight runtime smoke | PASS |
| Docara PHPUnit | 331 tests / 5124 assertions |
| Static verification | 198 pages / 14236 references / 0 broken |
| Browser code blocks | 3 source / 3 highlighted |
| Language panels | 3 |
| Copy controls | 3, targets bound to code IDs |
| Syntax tokens | 71 |
| Themes | light and dark PASS |
| Responsive | desktop and 390 px PASS |
| Browser console | no warnings or errors |

Local deployment:
`https://docara.test/ru/authoring/markdown/`.

Served root:
`/Users/rim/Sites/docara.test/build_production`.

## Publication correction

The original deployment placed the verified build one directory above the
ServBay document root. This left the public URL returning an empty `404` even
though the page existed on disk. The build was republished to the actual Caddy
root and accepted again:

- HTTP: `200`;
- response bytes: `113278`;
- served/build SHA-256:
  `f44e7b4a23f02ffd9b05ffec76fb9cef1ba79f4a429caffef439d91264f09bda`;
- highlighted blocks: `3`;
- language panels: `3`;
- copy controls: `3`;
- browser console warnings/errors: `0`.

Rollback:
`/Users/rim/Sites/docara.test/.docara-backups/highlight-runtime-20260726-024612`.

No Docara-owned highlighter, header, copy implementation, or compatibility
fallback was added.
