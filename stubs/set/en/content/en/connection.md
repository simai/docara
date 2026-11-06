---
description: How to connect the {set} set with one line or from your own copy; versions and compatibility with SIMAI Framework.
---

# Connection

A page connects SIMAI Framework and names the sets it uses. The Loader finds
the set, loads its rules and loads only the components the page has.

## One line

```html
<script src="https://cdn.jsdelivr.net/npm/@simai-studio/ui/distr/core/js/core.min.js" data-sf-sets="{set}"></script>
```

The `data-sf-sets` attribute lists sets separated by spaces. A version of a
set follows `@`, for example `data-sf-sets="{set}@1.0.0"`. Without a
version the Loader picks the newest release of the set that fits the
connected Core. On a production site, pin both the Framework version in the
address and the set version.

## From your own copy

When a page serves the set's build itself, it gives the address before Core
starts:

```html
<script>
  window.SF_BOOT_CONFIG = {
    packages: {
      '{set}': {base: '/assets/{set}/smart', version: '1.0.0'}
    }
  };
</script>
<script src="/assets/ui/distr/core/js/core.min.js"></script>
```

`base` is the `smart/` folder of the set's build: it holds `rule.js` and the
component folders.

## Versions and compatibility

Replace the table row with your set's releases, newest first.

| Set | Built over SIMAI Framework | Needs Core |
|:---|:---|:---|
| 1.0.0 | the Framework release the set is built over | the oldest `core.runtime` version |

## See also

- [Components](../components/) — component pages with examples and parameters.
- [Changes](../changes/) — what changed from release to release.
