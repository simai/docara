# Publisher shell audit

Status: focused static and rendering tests PASS.

`resources/publisher/templates/page.php` contains document metadata, layout
region hosts, asset hosts and pre-rendered chrome fragment slots. It contains:

- no `docara.brand`, `docara.navigation` or `docara.toc` branch;
- no product Smart markup;
- no button or dialog implementation;
- no product component CSS or JavaScript.

Publisher chrome is split into eight registered application templates under
`resources/publisher/components`. This chrome is application shell UI, not a
product Smart contract. Product component templates and their CSS/JavaScript
live under `resources/smart` and are published from `SmartRegistry`.

`DeclarativePageRenderer` now propagates component hydration records to the
page artifact. Each record names `asset_owner` and `hydration_owner`.
