---
description: "The six page types and the parts each type needs."
profile: reference
---

# Page types

Every page has one type. `docara validate page` checks that the page has the
parts its type needs. Replace this page with the reference your readers look up.

## Types

| Type | Use it for | Required parts |
| --- | --- | --- |
| `landing` | An entry page that sends the reader on | Title, introduction, two sections, a link |
| `article` | A page that fits no other type | Title, introduction, a section |
| `tutorial` | A guided first lesson | Title, numbered steps |
| `how_to` | Steps for one practical task | Title, numbered steps |
| `reference` | Exact facts to look up | Title, introduction, a table or a list |
| `explanation` | Reasons, design and limits | Title, introduction, a section |

## Front matter

| Field | What it sets |
| --- | --- |
| `title` | The page title in the menu and the browser tab. |
| `description` | One sentence for search results and link previews. |
| `profile` | The page type. |
| `draft` | `true` keeps the page out of the build. |

## See also

- [Why pages have types](/explanation/page-types/)
- [Create your first page](/getting-started/first-page/)
