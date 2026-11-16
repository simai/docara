---
description: How to make a Docara site visible in search and in AI assistant answers — what the build does itself, what to set and what to check before publishing.
tags: [seo, geo, search, ai, llms, sitemap, robots, json-ld, share-card, share_image]
---

# SEO and GEO

SEO helps people find the site in search engines. GEO (generative engine
optimization) helps AI assistants — ChatGPT, Claude, Perplexity and search
with AI answers — read the documentation correctly and cite it. Docara does
almost all of it itself once the site has a public address,
[`site_url`](/en/settings/site-address/#public-address-site-url).

## What Docara does itself

A build with `site_url` gives the site:

- **A sitemap**, `sitemap.xml`, with every indexable page, its translations
  and the date of its last change (`lastmod`).
- **`robots.txt`**, which allows crawling and names the sitemap.
- **A canonical address** and links to translations (`hreflang`) on every
  page.
- **A page description** for search results and for the share card.
- **A share card for social networks and messengers**: Open Graph and
  Twitter card with the title, description and picture.
- **Structured data** from schema.org as JSON-LD: the website, the article
  with its language and date of change, and the trail through the sections
  (breadcrumbs).
- **Files for AI:**
  - `llms.txt` — the site's table of contents after the
    [llmstxt.org](https://llmstxt.org/) convention;
  - `llms-full.txt` — the whole text of the default language's pages in one
    file;
  - a text copy next to each page: `index.md` next to `index.html`; the page
    links to it with `<link rel="alternate" type="text/markdown">`.
- **A redirect from the site root** to the default language, with a full
  canonical address and `noindex`.

Without `site_url` none of this appears: the build for local preview stays
as it was.

## What you set

1. **`site_url`** in `docara.json` — the public address of the site. It is
   easiest to set it in CI at the publishing step, as described in
   [Site address](/en/settings/site-address/).
2. **A description for every page** — the `description` field in front
   matter. One sentence about what the reader gets from the page, up to 160
   characters. Without the field Docara takes the first paragraph, but a
   written description is almost always better.
3. **A share picture** — `branding.share_image`. It is optional: by default
   Docara draws one picture per language with the site title, description
   and address. For a picture of your own, set a 1200×630 PNG or JPEG file.
   Docara also accepts WebP, but some social networks and messengers do not
   show such a picture. `false` removes the picture. A section or a page can
   replace the value in `section.json` or `<page>.page.json`. Each language
   has its own content folder, so the picture can easily be in the page's
   language.
4. **A site description for AI** — `discovery.description`: one or two
   sentences about the product. Without it `llms.txt` takes the home page
   description.
5. **Access for AI crawlers** — `discovery.ai_crawlers`:

   | Value | What it does |
   |---|---|
   | `allow` (default) | AI crawlers read the site, and AI assistants can cite it. |
   | `deny` | `robots.txt` closes the site to GPTBot, ClaudeBot, Google-Extended, PerplexityBot, CCBot and other crawlers that gather text for AI. Regular search engines still see the site. |

```json
{
  "site_url": "https://docs.example.com/",
  "discovery": {
    "description": "Documentation for Example: installation, settings and API.",
    "ai_crawlers": "allow"
  },
  "branding": {
    "share_image": "assets/share.png"
  }
}
```

## Writing pages that AI understands

An AI assistant reads a page in parts and quotes single paragraphs. So:

- **One first-level heading** and clear subheadings that lead to an answer.
- **The answer in the first sentence of a section**, details after it.
- **The same terms on every page.** Explain a new term where it first
  appears, and add it to the glossary.
- **Whole code and commands**, with the language on the code block: such a
  block is copied without edits.
- **Links to neighbouring pages** with words that say where the link leads,
  not "here".

The [quality report](/en/authoring/documentation-quality/) checks these
rules: the length and presence of descriptions, headings, long sentences and
terms.

## The site root

Docara builds the main address of the site (`https://docs.example.com/`) as
a page that redirects to the default language. Search engines understand
it, but a server redirect (code 301) is more reliable. If the hosting allows
it, add one — examples for popular servers are in
[Hosting](/en/build/hosting/#redirect-from-the-site-root).

## Dates of change

A page's date is the time of the last Git commit that changed its source
file. It goes into `sitemap.xml` (`lastmod`), into the structured data
(`dateModified`) and into `_docara/page-metadata.json`.

A CI build has to see the Git history. In GitHub Actions set
`fetch-depth: 0` on the `actions/checkout` step, otherwise every page gets
the date of the last commit. If the sources are outside Git, there are no
dates, and the build passes without them.

## Checks before publishing

1. Build the site with `site_url` and run `verify-static`: it checks
   `sitemap.xml`, `robots.txt`, `llms.txt` and every link in them.
2. Run `docara qa report` and fix the descriptions the report calls empty or
   too long.
3. Open `llms.txt` and `llms-full.txt` in the browser and read them as an AI
   would: is it clear what the site is about?
4. Check the link card in any messenger or in a
   [card validator](https://www.opengraph.xyz/), and the structured data in
   the [Rich Results Test](https://search.google.com/test/rich-results).
5. Submit `sitemap.xml` to Google Search Console and Yandex Webmaster.

## See also

- [Site address and search engines](/en/settings/site-address/) — the exact
  contents of `sitemap.xml`, `robots.txt` and `llms.txt`
- [Brand and theme](/en/settings/branding-and-theme/) — the logo and the
  share picture
- [docara.json fields](/en/reference/docara-json/#address-and-publication)
