# Матрица сценариев

| ID | Сценарий | Статус |
|---|---|---|
| S01 | Clean source install | PASS |
| S02 | Clean Composer distribution install | PASS WITH BLOCKER: archive input is state-dependent |
| S03 | New portable project init from current directory | PASS |
| S04 | Update preserves user content/config | PASS |
| S05 | Build and verify | PASS |
| S06 | Deterministic repeat build | PASS |
| S07 | Negative/path-confinement tests | PASS |
| S08 | Documentation links/assets | PASS |
| S09 | Desktop navigation/search/theme | PASS |
| S10 | Mobile navigation/search/theme | PASS |
| S11 | Legacy-runtime absence in product candidate | PASS |
| S12 | Skill/workflow consistency | FAIL |
| S13 | Documented `init [path]` command | FAIL |
| S14 | Composer archive reproducible from one SHA | FAIL |
| S15 | Local `https://docara.test/` equals exact candidate | FAIL |
