# Browser acceptance

Browser: real Chrome against `https://docara.test/`.

## Desktop

- declarative shell CSS and JS loaded from `_docara`;
- active catalogue page recognized;
- four-level navigation rendered;
- desktop and mobile outline projections present;
- no duplicate IDs;
- no horizontal overflow at 1282 px;
- search dialog opens, focuses input and returns 11 results for
  `наследование`;
- light theme applies, persists across reload and resets to system;
- landing contains CTA and features and omits docs-only navigation/search;
- console warnings/errors: none.

## Mobile 390 x 844

- desktop sidebar hidden and mobile trigger visible;
- no document horizontal overflow;
- four-level active page and two ancestor levels visible in opened menu;
- menu dialog has no horizontal overflow;
- catalogue filter fits viewport;
- desktop outline hidden and mobile outline trigger visible;
- mobile outline lists four expected headings;
- outline dialog has no horizontal overflow;
- duplicate IDs: none;
- console warnings/errors: none.

Temporary viewport override and reader-theme override were reset after testing.
