# Composite Smart Component terminology

The canonical English term is **Composite Smart Component**; the Russian term,
used on the documentation site, is **составной смарт-компонент**. A composite
Smart component combines child components and coordinates their settings,
events and state. Children may also be composite. Merely placing several
components next to each other does not make them composite. The normal Smart
component connection mechanism applies.

Manifests mark such components with `kind: composite`. The terms "complex"
and "комплексный/сложный" are older names for the same thing, not separate
categories. Blocks are a separate concept and are not covered here.
