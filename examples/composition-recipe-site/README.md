# Composition Recipe page example

This directory contains one complete Recipe page for a portable Docara site.
Copy `content/` and `composition/` into a newly initialized site. The page
appears at `/ru/catalogue/`; ordinary Docara pages remain Markdown.

Use the exact generated `ui` distribution accepted for the Recipe contract:

```bash
export DOCARA_SIMAI_UI_ROOT=/path/to/exact/ui
php vendor/bin/docara build production
```

The initial setting selects the compact header. To see the expanded variant,
change `headerMode.value` to `expanded` in `composition/inputs.json` and update
its `valueDigest` for the canonical JSON string. The other content and template
files stay reusable. A failed Recipe leaves the previous site build intact.
Each variant supplies the page's single `H1`.

This example is a checked input, not generated HTML. The accepted Framework
contract and renderer live in `ui-source`; Docara executes their packaged copy
from `ui`.

For a local smoke check, this example can be copied into `stubs/portable`. That
stub currently locks an older Framework shell while the Recipe compiler uses
the candidate selected by `DOCARA_SIMAI_UI_ROOT`. The smoke check proves page
assembly and Docara publication behavior; it does not certify a unified
Core/Smart package pair. Before adopting the page in a real site, pin the site
shell and Recipe compiler to one verified Framework delivery and rerun its
browser and static checks.
