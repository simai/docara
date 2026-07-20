# Capability parity

| Capability | Result | Evidence |
| --- | --- | --- |
| URL/output routing | pass | declarative and rollback builders return identical URL keys and HTML inventories |
| metadata and document shell | pass | registered `publisher.docara.page` template and static shell assertions |
| docs and landing presets | pass | docs and `/landing/` browser acceptance |
| branding/favicon | pass | generated HTML and asset checks |
| four-level navigation | pass | recursive registered item template, active page/ancestor browser checks |
| search | pass | 11 results for `наследование`; index/runtime static checks |
| breadcrumbs | pass | DOM and static tests |
| desktop/mobile outline | pass | two outline projections, mobile dialog acceptance |
| previous/next | pass | DOM and full suite |
| reader settings/theme | pass | light selection persisted across reload and reset to system |
| generated catalogue | pass | index/detail exact trusted projection and browser catalogue |
| Framework assets | pass | immutable lock projection and 11,320 local-reference checks |
| redirects/content assets | pass | full suite and static verifier |
| diagnostics | pass | publisher ID, HTML SHA-256 and full resolved plan per page |
| rollback | pass | explicit legacy publisher, immutable renderer hash and equal URL/HTML inventory |

Primary authoring Smart slice: `ui.alert`, `ui.button`.
Unsupported Smart input fails closed; readiness is not expanded implicitly.
