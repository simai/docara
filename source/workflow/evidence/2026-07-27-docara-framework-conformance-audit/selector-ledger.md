# Selector ledger

Machine-readable source: `selector-ledger.json`. One row equals one physical CSS rule; comma-separated selector groups remain one atomic rule.

## keep_product_contract (30)

| Source | Line | Selector | Target | Batch |
|---|---:|---|---|---|
| `resources/portable/declarative-shell.css` | 1 | `html` | Docara shell/Smart semantic contract | B0 |
| `resources/portable/declarative-shell.css` | 2 | `.theme-light` | Docara shell/Smart semantic contract | B0 |
| `resources/portable/declarative-shell.css` | 3 | `.theme-dark` | Docara shell/Smart semantic contract | B0 |
| `resources/portable/declarative-shell.css` | 4 | `body` | Docara shell/Smart semantic contract | B0 |
| `resources/portable/declarative-shell.css` | 5 | `.docara-skip-link` | Docara shell/Smart semantic contract | B0 |
| `resources/portable/declarative-shell.css` | 6 | `.docara-skip-link:focus` | Docara shell/Smart semantic contract | B0 |
| `resources/portable/declarative-shell.css` | 10 | `.docara-docs-layout` | Docara shell/Smart semantic contract | B0 |
| `resources/portable/declarative-shell.css` | 11 | `.docara-docs-layout[data-outline="true"]` | Docara shell/Smart semantic contract | B0 |
| `resources/portable/declarative-shell.css` | 12 | `.docara-docs-layout[data-sidebar="false"]` | Docara shell/Smart semantic contract | B0 |
| `resources/portable/declarative-shell.css` | 13 | `.docara-docs-layout[data-sidebar="false"][data-outline="true"]` | Docara shell/Smart semantic contract | B0 |
| `resources/portable/declarative-shell.css` | 20 | `html[dir="ltr"] .docara-outline-scroll>.sf-scrollbar__viewport>[data-docara-section]>*` | Docara shell/Smart semantic contract | B0 |
| `resources/portable/declarative-shell.css` | 21 | `html[dir="rtl"] .docara-outline-scroll>.sf-scrollbar__viewport>[data-docara-section]>*` | Docara shell/Smart semantic contract | B0 |
| `resources/portable/declarative-shell.css` | 44 | `.docara-landing .docara-content>[data-docara-section][data-docara-region-owner="main"]>[data-docara-width="full"]` | Docara shell/Smart semantic contract | B0 |
| `resources/portable/declarative-shell.css` | 53 | `.docara-example-grid` | Docara shell/Smart semantic contract | B0 |
| `resources/portable/declarative-shell.css` | 58 | `.docara-example-source` | Docara shell/Smart semantic contract | B0 |
| `resources/portable/declarative-shell.css` | 63 | `.docara-docs-layout[data-outline="true"]` | Docara shell/Smart semantic contract | B0 |
| `resources/portable/declarative-shell.css` | 64 | `.docara-docs-layout[data-sidebar="false"],.docara-docs-layout[data-sidebar="false"][data-outline="true"]` | Docara shell/Smart semantic contract | B0 |
| `resources/portable/declarative-shell.css` | 71 | `.docara-docs-layout,.docara-docs-layout[data-outline="true"]` | Docara shell/Smart semantic contract | B0 |
| `resources/portable/declarative-shell.css` | 82 | `*,*::before,*::after` | Docara shell/Smart semantic contract | B0 |
| `resources/portable/declarative-shell.css` | 90 | `.docara-search-result-item:not(:last-child)` | Docara shell/Smart semantic contract | B0 |
| `resources/smart/assets/brand.css` | 1 | `.docara-brand` | Docara shell/Smart semantic contract | B0 |
| `resources/smart/assets/brand.css` | 2 | `.docara-brand--size-small` | docara.brand public view/size contract | B0 |
| `resources/smart/assets/brand.css` | 3 | `.docara-brand--size-large` | docara.brand public view/size contract | B0 |
| `resources/smart/assets/brand.css` | 6 | `.docara-brand-logo--dark` | docara.brand view state | B0 |
| `resources/smart/assets/brand.css` | 7 | `.theme-dark .docara-brand-logo--light:has(+.docara-brand-logo--dark)` | docara.brand view state | B0 |
| `resources/smart/assets/brand.css` | 8 | `.theme-dark .docara-brand-logo--dark` | docara.brand view state | B0 |
| `resources/smart/assets/brand.css` | 10 | `.docara-brand-title,.docara-brand-label` | docara.brand public view/size contract | B0 |
| `resources/smart/assets/brand.css` | 13 | `.docara-brand--compact .docara-brand-label` | docara.brand view state | B0 |
| `resources/smart/assets/brand.css` | 15 | `.docara-brand-label` | docara.brand view state | B0 |
| `resources/smart/assets/navigation.css` | 23 | `.docara-header-navigation .docara-header-navigation-link` | Docara shell/Smart semantic contract | B0 |

## replace_with_utility (68)

| Source | Line | Selector | Target | Batch |
|---|---:|---|---|---|
| `resources/portable/declarative-shell.css` | 7 | `.docara-header` | size utilities | B1 |
| `resources/portable/declarative-shell.css` | 8 | `.docara-mobile-navigation-trigger,.docara-outline-mobile` | layout utilities | B1 |
| `resources/portable/declarative-shell.css` | 9 | `.docara-header-row>[data-docara-section="docara.header"]` | layout utilities + size utilities | B1 |
| `resources/portable/declarative-shell.css` | 14 | `.docara-sidebar` | layout utilities + border/radius utilities | B1 |
| `resources/portable/declarative-shell.css` | 15 | `.docara-sidebar-scroll` | size utilities + position utilities | B1 |
| `resources/portable/declarative-shell.css` | 16 | `.docara-sidebar-scroll>.sf-scrollbar__viewport>[data-docara-section]` | spacing utilities | B1 |
| `resources/portable/declarative-shell.css` | 17 | `.docara-outline-rail` | layout utilities + position utilities | B1 |
| `resources/portable/declarative-shell.css` | 18 | `.docara-outline-scroll` | size utilities + position utilities | B1 |
| `resources/portable/declarative-shell.css` | 19 | `.docara-outline-scroll>.sf-scrollbar__viewport>[data-docara-section]` | spacing utilities | B1 |
| `resources/portable/declarative-shell.css` | 22 | `.docara-content` | spacing utilities + surface/color utilities | B1 |
| `resources/portable/declarative-shell.css` | 25 | `.docara-document-link` | typography utilities + overflow/scroll utilities | B1 |
| `resources/portable/declarative-shell.css` | 26 | `.docara-previous-next` | spacing utilities | B1 |
| `resources/portable/declarative-shell.css` | 27 | `.docara-document-link--next` | typography utilities | B1 |
| `resources/portable/declarative-shell.css` | 28 | `.docara-prose h1[id],.docara-prose h2[id],.docara-prose h3[id],.docara-prose h4[id],.docara-prose h5[id],.docara-prose h6[id]` | spacing utilities | B1 |
| `resources/portable/declarative-shell.css` | 29 | `.docara-prose p,.docara-prose li` | size utilities | B1 |
| `resources/portable/declarative-shell.css` | 40 | `.docara-prose sf-alert,.docara-prose sf-button` | spacing utilities | B1 |
| `resources/portable/declarative-shell.css` | 41 | `.docara-landing` | layout utilities + size utilities | B1 |
| `resources/portable/declarative-shell.css` | 42 | `.docara-landing .docara-content` | size utilities | B1 |
| `resources/portable/declarative-shell.css` | 43 | `.docara-landing .docara-content>[data-docara-section][data-docara-region-owner="main"]` | layout utilities + spacing utilities + size utilities + border/radius utilities | B1 |
| `resources/portable/declarative-shell.css` | 45 | `.docara-landing [data-docara-container]` | layout utilities + size utilities + border/radius utilities | B1 |
| `resources/portable/declarative-shell.css` | 46 | `.docara-landing .docara-content>[data-docara-section][data-docara-region-owner="main"]>h1:first-child,.docara-landing .docara-content>[data-docara-section][data-docara-region-owner="main"]>p:first-of-type` | size utilities | B1 |
| `resources/portable/declarative-shell.css` | 47 | `[data-docara-media]` | layout utilities + size utilities | B1 |
| `resources/portable/declarative-shell.css` | 48 | `[data-docara-media="hero"],[data-docara-media="promo"]` | aspect/object utilities | B1 |
| `resources/portable/declarative-shell.css` | 49 | `[data-docara-media="showcase"]` | aspect/object utilities | B1 |
| `resources/portable/declarative-shell.css` | 50 | `[data-docara-media="feature-icon"]` | size utilities + aspect/object utilities | B1 |
| `resources/portable/declarative-shell.css` | 51 | `[data-docara-media="card"]` | layout utilities + border/radius utilities + aspect/object utilities | B1 |
| `resources/portable/declarative-shell.css` | 52 | `[data-docara-media="logo"]` | size utilities + aspect/object utilities | B1 |
| `resources/portable/declarative-shell.css` | 54 | `.docara-example-grid .sf-button` | layout utilities + spacing utilities | B1 |
| `resources/portable/declarative-shell.css` | 55 | `.docara-example-preview iframe` | layout utilities + size utilities + border/radius utilities + surface/color utilities | B1 |
| `resources/portable/declarative-shell.css` | 56 | `.docara-example-preview[data-preview-size="compact"] iframe` | size utilities | B1 |
| `resources/portable/declarative-shell.css` | 57 | `.docara-example-preview[data-preview-size="tall"] iframe` | size utilities | B1 |
| `resources/portable/declarative-shell.css` | 61 | `.docara-header-navigation` | layout utilities | B1 |
| `resources/portable/declarative-shell.css` | 62 | `.docara-mobile-navigation-trigger--primary` | layout utilities | B1 |
| `resources/portable/declarative-shell.css` | 65 | `.docara-outline-rail` | layout utilities | B1 |
| `resources/portable/declarative-shell.css` | 66 | `.docara-outline-mobile` | layout utilities | B1 |
| `resources/portable/declarative-shell.css` | 69 | `.docara-header` | size utilities | B1 |
| `resources/portable/declarative-shell.css` | 70 | `.docara-mobile-navigation-trigger` | layout utilities | B1 |
| `resources/portable/declarative-shell.css` | 72 | `.docara-sidebar` | layout utilities | B1 |
| `resources/portable/declarative-shell.css` | 73 | `.docara-reading-column` | spacing utilities | B1 |
| `resources/portable/declarative-shell.css` | 74 | `.docara-content` | spacing utilities | B1 |
| `resources/portable/declarative-shell.css` | 75 | `.docara-prose h1[id],.docara-prose h2[id],.docara-prose h3[id],.docara-prose h4[id],.docara-prose h5[id],.docara-prose h6[id]` | spacing utilities | B1 |
| `resources/portable/declarative-shell.css` | 76 | `.docara-landing [data-docara-container]` | spacing utilities | B1 |
| `resources/portable/declarative-shell.css` | 79 | `.docara-previous-next` | layout utilities | B1 |
| `resources/portable/declarative-shell.css` | 80 | `.docara-document-link--next` | layout utilities + typography utilities | B1 |
| `resources/portable/declarative-shell.css` | 89 | `.docara-search-results` | size utilities + overflow/scroll utilities | B1 |
| `resources/portable/declarative-shell.css` | 91 | `.docara-search-result` | typography utilities + overflow/scroll utilities | B1 |
| `resources/portable/declarative-shell.css` | 92 | `.docara-search-result:hover` | surface/color utilities | B1 |
| `resources/portable/declarative-shell.css` | 94 | `.docara-search-result-context,.docara-search-result-summary` | size utilities + typography utilities | B1 |
| `resources/portable/declarative-shell.css` | 95 | `.docara-search-mark` | layout utilities + spacing utilities + border/radius utilities + surface/color utilities | B1 |
| `resources/portable/declarative-shell.css` | 97 | `.docara-search-status[data-state="error"]` | surface/color utilities | B1 |
| `resources/portable/declarative-shell.css` | 99 | `.docara-search-trigger>.sf-button-text-container,.docara-search-shortcut` | layout utilities | B1 |
| `resources/portable/declarative-shell.css` | 99 | `.docara-search-help` | layout utilities | B1 |
| `resources/portable/declarative-shell.css` | 99 | `.docara-search-results` | size utilities | B1 |
| `resources/smart/assets/brand.css` | 4 | `.docara-brand-mark` | layout utilities + size utilities | B1 |
| `resources/smart/assets/brand.css` | 5 | `.docara-brand-logo` | layout utilities + size utilities + aspect/object utilities | B1 |
| `resources/smart/assets/brand.css` | 9 | `.docara-brand-copy` | size utilities | B1 |
| `resources/smart/assets/brand.css` | 11 | `.docara-brand--size-small .docara-brand-title` | size utilities + typography utilities | B1 |
| `resources/smart/assets/brand.css` | 12 | `.docara-brand--size-large .docara-brand-title` | size utilities + typography utilities | B1 |
| `resources/smart/assets/navigation.css` | 7 | `.docara-navigation-link,.docara-navigation-label` | size utilities + surface/color utilities | B1 |
| `resources/smart/assets/navigation.css` | 8 | `.docara-navigation-link` | layout utilities + spacing utilities | B1 |
| `resources/smart/assets/navigation.css` | 20 | `.docara-header-navigation` | layout utilities + size utilities | B1 |
| `resources/smart/assets/navigation.css` | 21 | `.docara-header-navigation-list` | layout utilities + typography utilities | B1 |
| `resources/smart/assets/navigation.css` | 22 | `.docara-header-navigation-item` | layout utilities | B1 |
| `resources/smart/assets/navigation.css` | 24 | `.docara-header-navigation .docara-header-navigation-link[aria-current="page"]` | surface/color utilities | B1 |
| `resources/smart/assets/preferences.css` | 11 | `.docara-preferences-field` | size utilities | B1 |
| `resources/smart/assets/toc.css` | 1 | `.docara-outline-list` | existing utility family | B1 |
| `resources/smart/assets/toc.css` | 2 | `.docara-outline-item` | position utilities | B1 |
| `resources/smart/assets/toc.css` | 5 | `.docara-outline-link` | typography utilities + overflow/scroll utilities | B1 |

## replace_with_component (15)

| Source | Line | Selector | Target | Batch |
|---|---:|---|---|---|
| `resources/portable/declarative-shell.css` | 30 | `.docara-mobile-sheet` | smart.modal with side-panel preset | B1 |
| `resources/portable/declarative-shell.css` | 31 | `.docara-mobile-sheet:not([open])` | smart.modal with side-panel preset | B1 |
| `resources/portable/declarative-shell.css` | 32 | `.docara-mobile-sheet::backdrop` | smart.modal with side-panel preset | B1 |
| `resources/portable/declarative-shell.css` | 33 | `.docara-mobile-sheet-header` | smart.modal with side-panel preset | B1 |
| `resources/portable/declarative-shell.css` | 34 | `.docara-mobile-sheet-content` | smart.modal with side-panel preset | B1 |
| `resources/portable/declarative-shell.css` | 35 | `.docara-outline-dialog` | smart.modal with side-panel preset | B1 |
| `resources/portable/declarative-shell.css` | 84 | `.docara-search-modal-surface` | smart.modal + smart.buttons/input + shadow utilities | B1 |
| `resources/portable/declarative-shell.css` | 85 | `.docara-search-trigger` | smart.modal + smart.buttons/input + shadow utilities | B1 |
| `resources/portable/declarative-shell.css` | 86 | `.docara-search-modal-body,.docara-search-modal-content` | smart.modal + smart.buttons/input + shadow utilities | B1 |
| `resources/portable/declarative-shell.css` | 87 | `.docara-search-query` | smart.modal + smart.buttons/input + shadow utilities | B1 |
| `resources/portable/declarative-shell.css` | 88 | `.docara-search-results-surface` | smart.modal + smart.buttons/input + shadow utilities | B1 |
| `resources/smart/assets/preferences.css` | 1 | `.docara-preferences-panel` | smart.modal side-panel parameters and utility classes | B1 |
| `resources/smart/assets/preferences.css` | 2 | `.docara-preferences-surface` | smart.modal side-panel parameters and utility classes | B1 |
| `resources/smart/assets/preferences.css` | 10 | `.docara-preferences-body,.docara-preferences-content` | smart.modal side-panel parameters and utility classes | B1 |
| `resources/smart/assets/preferences.css` | 14 | `.docara-preferences-panel` | smart.modal side-panel parameters and utility classes | B1 |

## promote_framework (34)

| Source | Line | Selector | Target | Batch |
|---|---:|---|---|---|
| `resources/portable/declarative-shell.css` | 23 | `.sf-breadcrumbs` | component.breadcrumbs / smart.breadcrumbs | B2 |
| `resources/portable/declarative-shell.css` | 24 | `.sf-breadcrumbs-item[hidden]` | component.breadcrumbs / smart.breadcrumbs | B2 |
| `resources/portable/declarative-shell.css` | 36 | `.docara-code-block>.sf--highlight-head` | smart.code candidate | B2 |
| `resources/portable/declarative-shell.css` | 37 | `.docara-code-scroll` | smart.code candidate + component.scrollbar | B3 |
| `resources/portable/declarative-shell.css` | 38 | `.docara-code-scroll code` | smart.code candidate + component.scrollbar | B3 |
| `resources/portable/declarative-shell.css` | 39 | `sf-alert,sf-button` | Smart host display contract | B2 |
| `resources/portable/declarative-shell.css` | 59 | `.docara-skip-link:focus-visible,.docara-document-link:focus-visible,[data-docara-component-details-summary]:focus-visible` | shared focus-visible utility/recipe | B2 |
| `resources/portable/declarative-shell.css` | 93 | `.docara-search-result:focus-visible` | shared focus-visible utility/recipe | B2 |
| `resources/portable/declarative-shell.css` | 96 | `.docara-search-help kbd` | component.kbd candidate | B3 |
| `resources/portable/declarative-shell.css` | 98 | `.sf-button.sf-button--outline` | component.buttons / smart.buttons | B2 |
| `resources/smart/assets/brand.css` | 14 | `.docara-brand:focus-visible` | shared focus-visible utility/recipe | B2 |
| `resources/smart/assets/navigation.css` | 1 | `.docara-navigation .sf-menu` | smart.menu documentation-tree view/preset | B2 |
| `resources/smart/assets/navigation.css` | 2 | `.docara-navigation .sf-menu-element` | smart.menu documentation-tree view/preset | B2 |
| `resources/smart/assets/navigation.css` | 3 | `.docara-navigation .sf-menu-element--level-1` | smart.menu documentation-tree view/preset | B2 |
| `resources/smart/assets/navigation.css` | 4 | `.docara-navigation .sf-menu-element--level-2` | smart.menu documentation-tree view/preset | B2 |
| `resources/smart/assets/navigation.css` | 5 | `.docara-navigation .sf-menu-element--level-3` | smart.menu documentation-tree view/preset | B2 |
| `resources/smart/assets/navigation.css` | 6 | `.docara-navigation .sf-menu-element--level-4` | smart.menu documentation-tree view/preset | B2 |
| `resources/smart/assets/navigation.css` | 9 | `.docara-navigation-link .sf-menu-element-text,.docara-navigation-label .sf-menu-element-text` | smart.menu documentation-tree view/preset | B2 |
| `resources/smart/assets/navigation.css` | 10 | `.docara-navigation [data-docara-disclosure]` | smart.menu documentation-tree view/preset | B2 |
| `resources/smart/assets/navigation.css` | 11 | `.docara-navigation [data-docara-active-role="ancestor"]>.sf-menu-element,.docara-navigation [data-docara-active-role="section"]>.sf-menu-element` | smart.menu documentation-tree view/preset | B2 |
| `resources/smart/assets/navigation.css` | 12 | `.docara-navigation [data-docara-active-role="page"]>.sf-menu-element` | smart.menu documentation-tree view/preset | B2 |
| `resources/smart/assets/navigation.css` | 13 | `.docara-navigation [data-docara-active-role="page"]>.sf-menu-element>.docara-navigation-link,.docara-navigation [data-docara-active-role="page"]>.sf-menu-element>.docara-navigation-label` | smart.menu documentation-tree view/preset | B2 |
| `resources/smart/assets/navigation.css` | 14 | `.docara-navigation .sf-menu-element:not(.disabled):hover` | smart.menu documentation-tree view/preset | B2 |
| `resources/smart/assets/navigation.css` | 15 | `.docara-navigation .sf-menu-element:not(.disabled):hover [data-docara-disclosure] .sf-icon` | smart.menu documentation-tree view/preset | B2 |
| `resources/smart/assets/navigation.css` | 16 | `.docara-navigation--compact .sf-menu-element` | smart.menu documentation-tree view/preset | B2 |
| `resources/smart/assets/navigation.css` | 17 | `.docara-navigation--compact .sf-menu-element-wrap` | smart.menu documentation-tree view/preset | B2 |
| `resources/smart/assets/navigation.css` | 18 | `.docara-navigation-link:focus-visible,.docara-header-navigation-link:focus-visible,[data-docara-disclosure]:focus-visible` | smart.menu documentation-tree view/preset | B2 |
| `resources/smart/assets/navigation.css` | 19 | `.docara-navigation [data-docara-disclosure]:focus` | smart.menu documentation-tree view/preset | B2 |
| `resources/smart/assets/preferences.css` | 12 | `.docara-preferences-field>.sf-radio-button:hover` | radio hover/state contract | B2 |
| `resources/smart/assets/toc.css` | 3 | `.docara-outline-item[data-docara-outline-level="3"]` | outline/scrollspy presentation preset | B3 |
| `resources/smart/assets/toc.css` | 4 | `.docara-outline-item[data-docara-outline-level="4"],.docara-outline-item[data-docara-outline-level="5"],.docara-outline-item[data-docara-outline-level="6"]` | outline/scrollspy presentation preset | B3 |
| `resources/smart/assets/toc.css` | 6 | `.docara-outline-link[aria-current="location"]` | outline/scrollspy presentation preset | B3 |
| `resources/smart/assets/toc.css` | 7 | `.docara-outline-rail .docara-outline-item[data-docara-active]::before` | outline/scrollspy presentation preset | B3 |
| `resources/smart/assets/toc.css` | 8 | `.docara-outline-link:focus-visible` | shared focus-visible utility/recipe | B2 |

## prototype_only (64)

These selectors belong only to the visual prototype and are not production implementation contracts.

- `.docara-prototype-alert-stack`
- `.docara-prototype-banner`
- `.docara-prototype-brand`
- `.docara-prototype-card`
- `.docara-prototype-card--media`
- `.docara-prototype-card--plain`
- `.docara-prototype-code`
- `.docara-prototype-component`
- `.docara-prototype-component-head`
- `.docara-prototype-details`
- `.docara-prototype-diagram`
- `.docara-prototype-download`
- `.docara-prototype-faq`
- `.docara-prototype-faq--surface`
- `.docara-prototype-feature-card`
- `.docara-prototype-feature-icon`
- `.docara-prototype-feature-title`
- `.docara-prototype-figure`
- `.docara-prototype-figure--contain`
- `.docara-prototype-figure-frame`
- `.docara-prototype-formula`
- `.docara-prototype-grid`
- `.docara-prototype-grid-2`
- `.docara-prototype-grid-3`
- `.docara-prototype-grid-4`
- `.docara-prototype-grid-span-all`
- `.docara-prototype-group`
- `.docara-prototype-group-heading`
- `.docara-prototype-header`
- `.docara-prototype-header-inner`
- `.docara-prototype-heading-sample`
- `.docara-prototype-hero`
- `.docara-prototype-inline`
- `.docara-prototype-intro`
- `.docara-prototype-kbd`
- `.docara-prototype-kbd-example`
- `.docara-prototype-kicker`
- `.docara-prototype-layout`
- `.docara-prototype-logo-word`
- `.docara-prototype-logos`
- `.docara-prototype-main`
- `.docara-prototype-mark`
- `.docara-prototype-media`
- `.docara-prototype-media-copy`
- `.docara-prototype-media-icon`
- `.docara-prototype-nav`
- `.docara-prototype-node`
- `.docara-prototype-note`
- `.docara-prototype-shell`
- `.docara-prototype-stage`
- `.docara-prototype-step`
- `.docara-prototype-step--complete`
- `.docara-prototype-step--current`
- `.docara-prototype-step-title`
- `.docara-prototype-steps`
- `.docara-prototype-syntax`
- `.docara-prototype-tabs`
- `.docara-prototype-theme`
- `.docara-prototype-tree`
- `.docara-prototype-tree-row`
- `.docara-prototype-tree-spacer`
- `.docara-prototype-variant-label`
- `.docara-prototype-variant-separated`
- `.docara-prototype-warning`
