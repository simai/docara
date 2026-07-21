# Requirements matrix

| Requirement | Evidence | Verdict |
| --- | --- | --- |
| Branded header recipe | `/examples/region-header/`; `site-brand` + `header-action`; `docara.header` + `ui.button` | PASS |
| Sidebar recipe | `/examples/region-sidebar/`; navigation Smart + safe element note/link | PASS |
| Aside recipe | `/examples/region-aside/`; outline Smart + `ui.alert` | PASS |
| Footer recipe | `/examples/region-footer/`; enabled footer + two safe elements | PASS |
| Element and Smart blocks | `shell.element`, `shell.smart`, `docara.shell`; focused tests and generated HTML | PASS |
| Site/section/page inheritance | `/examples/composition-inheritance/`; `docara.json`, `section.json`, `page.page.json` | PASS |
| Override and reset | section replaces sidebar and disables outline; page `$reset` rebuilds outline and enables footer | PASS |
| Exact Markdown/JSON sources | demonstrator receipt binds every source path and SHA-256; static verifier PASS | PASS |
| Primary generator | all result and demonstrator pages use `docara.declarative_page_publisher.v1` | PASS |
| Simai Framework | pinned compatibility `sf-v5.3.2-7e836d8a-dd786bba`; exact utility projection; `sf-button` and `sf-alert` rendered | PASS |
| Adaptive behavior | 1440 px and 390 px browser checks; no horizontal overflow; mobile sidebar hidden and sheet triggers visible | PASS |
| Light and dark themes | browser switched system/light/dark contract; `theme-dark` and Framework colors verified | PASS |
| Fail-closed safety | schemas plus runtime reject unknown Smart, executable/unknown element keys, unsafe tags/utilities and arbitrary templates | PASS |
| Deterministic build | `build_a`, `build_b` and served output digest `68475469…92d3` | PASS |
| Static and full regression | 152 HTML pages, 15,489 references, zero broken; 595 tests and 4,947 assertions | PASS |
| Local publication with rollback | staged verification, atomic local swap and retained timestamped backup | PASS |
| No external readiness/release action | no push, merge, tag, public release or production deployment | PASS |
