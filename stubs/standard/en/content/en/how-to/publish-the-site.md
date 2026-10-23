---
description: "Build the site, check it and copy the result to a static host."
profile: how_to
---

# Publish the site

Use this guide when the pages are ready and the site should go online.
Replace it with the tasks your readers solve most often.

## Before you start

- Every page you want to publish has no `draft: true` line.

## Steps

1. Build the site: `php vendor/bin/docara build production`.
2. Check the result: `php vendor/bin/docara verify-static build_production`.
3. Copy the `build_production/` folder to any static host.

## Result

The site opens at the address of the host, with working links and search.

## See also

- [Create your first page](/getting-started/first-page/)
- [Why pages have types](/explanation/page-types/)
