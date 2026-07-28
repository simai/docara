# Browser acceptance

Browser: native Chrome controlled through the Browser integration.

## Desktop, 1440 x 1000

- `/authoring/regions/`: sidebar and outline visible, active menu is
  `Области макета`, `/examples/` is present, no horizontal overflow.
- `/examples/`: 7 cards and 7 detail links, active menu is `Примеры макетов`,
  no horizontal overflow.
- `/examples/regions-disabled/`: one iframe, three exact source blocks and
  visible Markdown source. Embedded result has no sidebar, outline or mobile
  navigation trigger; its layout declares `data-sidebar=false`.
- `/examples/smart-button/`: embedded `sf-button` hydrates to the visible
  native button `Продолжить`; browser error log is empty after correction.

## Mobile, 390 x 844

- `/examples/`: 7 cards form one 350px column, desktop sidebar is hidden,
  mobile menu trigger is visible and the document has no horizontal overflow.
- `/examples/regions-disabled/`: iframe width is 349px inside a 350px main
  column; source blocks scroll internally and the document has no horizontal
  overflow.

Viewport override was reset after acceptance. The generated catalogue was left
open as the user-facing deliverable.
