---
description: "Why every page has one type and what Docara checks for you."
profile: explanation
---

# Why pages have types

A reader opens a page with one goal: to learn, to do a task, to look up a fact
or to understand. A page that serves one goal is easier to read and to keep up
to date. Replace this page with an explanation your readers need.

## How it works

The type of a page comes from `profile:` in its front matter or from a rule in
`docara.authoring.json`. When neither is set, Docara guesses the type from the
path and the text and shows the guess in `docara validate page`.

## Why it works this way

Each type has a known shape: a how-to guide has numbered steps, a reference
page has a table or a list. Docara can check the shape, so a missing part shows
up before readers find it.

## Limitations

Docara checks the structure of a page, not its meaning. Whether the steps are
right and the text is clear is up to the author and the reviewer.

## See also

- [Page types](/reference/page-types/)
- [Publish the site](/how-to/publish-the-site/)
