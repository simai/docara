# Legacy reference block map

| Block ID | Reference | Target | Mandatory invariant | Allowed adaptation | State |
| --- | --- | --- | --- | --- | --- |
| REF-HEADER | legacy `/en/` header | portable header | brand, discoverable search/settings, compact mobile entry | modal controls and fewer secondary icons | implemented |
| REF-NAV | legacy left menu | portable left menu | hierarchy, current location, keyboard/mobile access | four-level active trail and task grouping | implemented |
| REF-MAIN | legacy article | portable article | readable content-first canvas and anchors | portable components and wider line-height | implemented |
| REF-TOC | legacy right navigation | portable outline | headings remain discoverable and usable | responsive disclosure and generated headings | implemented |
| REF-CODE | legacy code block | portable code block | readable/copyable code with local overflow | one pinned Framework-owned highlight surface | implemented |
| REF-SEARCH | legacy inline search | portable search dialog | relevant results and direct navigation | accessible modal and local deterministic index | retained; regression pending |
| REF-SETTINGS | legacy reader controls | portable theme dialog | useful preferences remain discoverable | theme-only default; width/text size are optional |
| REF-MOBILE | legacy off-canvas navigation | portable responsive shell | navigation access without displacing reading task | native dialog with current semantic hierarchy | implemented |
| REF-MIGRATION | legacy source/docs | portable migration docs | no silent feature loss | explicit replace/retire/defer decisions |
| REF-ROUTES | legacy URL corpus | portable generated routes | required old links do not silently break | safe static redirect pages |

Block-level screenshot/DOM evidence will be generated only for the final exact
candidate; this map defines the comparison boundary before implementation.
