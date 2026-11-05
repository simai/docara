---
description: A site in several languages — any number of locales by BCP 47 tags, each with its own address and writing direction, translations, their freshness and interface labels.
tags: [i18n, l10n, translation, translations, localization, multilingual, language, languages]
---

# Multilingual site

Docara does not limit a project to one or two languages. A site declares any
number of locales by BCP 47 tags, for example `ar`, `zh-Hans`, `fr-CA` or
`sr-Latn-RS`. Each locale has its own Markdown tree, a public address prefix,
a writing direction and an explicit fallback chain for shared interface
labels. One command builds all declared locales.

In the example below English is the main language of the site and is
published under `/en/`. Texts are written in Russian (`/ru/`) and translated
from it into English and Arabic (`/ar/`). `/` steadily redirects to the main
locale.

## 1. Create the same content structure

```text
content/
  en/
    index.md
    guide/
      install.md
  ru/
    index.md
    guide/
      install.md
  ar/
    index.md
    guide/
      install.md
```

A minimal page, `content/en/index.md`:

```markdown
# Documentation

Start with the installation guide.
```

Create the pages of the other languages with the same relative paths. It is
the matching `index.md` and `guide/install.md` that link the pages for
`hreflang` and the language switcher; Docara does not guess a missing
translation.

Every language needs its own interface label file
`content/<language>/lang.json`: "Search", "Skip to content", "Copy" and
others. Without it the build stops with `MESSAGE_NOT_FOUND`. For English and
Russian take the ready files from the package:

```bash
cp vendor/simai/docara/stubs/minimal/en/content/en/lang.json content/en/lang.json
cp vendor/simai/docara/stubs/minimal/ru/content/ru/lang.json content/ru/lang.json
```

For another language translate one of them. The file may be incomplete if
the language has a fallback language: missing required strings come from
there, and for the rest Docara uses its built-in text (see step 2).

## 2. Declare the locales

Replace `docara.json` with a complete working registry:

```json
{
  "schema": "docara.site.v1",
  "title": "Acme Docs",
  "preset": "docs",
  "framework_lock": "simai-framework.lock.json",
  "content_root": "content/en",
  "default_locale": "en",
  "documentation_version": "current",
  "base_url": "/",
  "locales": {
    "missing_page_policy": "skip",
    "en": {
      "label": "English",
      "direction": "ltr",
      "content_root": "content/en",
      "public_prefix": "en",
      "fallbacks": []
    },
    "ru": {
      "label": "Русский",
      "direction": "ltr",
      "content_root": "content/ru",
      "public_prefix": "ru",
      "fallbacks": []
    },
    "ar": {
      "label": "العربية",
      "direction": "rtl",
      "content_root": "content/ar",
      "public_prefix": "ar",
      "fallbacks": ["en"]
    }
  },
  "locale_routing": {
    "strategy": "prefixed",
    "root": "redirect",
    "detect_browser_language": false,
    "legacy_unprefixed_redirects": true
  },
  "translation_tracking": {
    "enabled": true,
    "source_locale": "ru",
    "mode": "report",
    "lock_file": "translations.lock.json"
  },
  "branding": {
    "title": "Acme",
    "label": "Docs",
    "mode": "text",
    "size": "medium"
  },
  "search": {
    "enabled": true,
    "indexed": true
  }
}
```

Every locale in `fallbacks` must be in the same registry. The Arabic
`content/ar/lang.json` may be incomplete: missing required labels come from
the English `content/en/lang.json`. But the fallback mechanism does not
translate Markdown content. For RTL set `"direction": "rtl"` explicitly.

Each locale can also have its own top menu. For example,
`content/ru/section.json` holds Russian labels:

```json
{
  "schema": "docara.section.v1",
  "header_navigation": {
    "enabled": true,
    "items": [
      {"id": "start", "label": "Быстрый старт", "href": "/ru/start/quick-start/"},
      {"id": "github", "label": "GitHub", "href": "https://github.com/simai/docara"}
    ]
  }
}
```

And `content/en/section.json` sets different items on its own:

```json
{
  "schema": "docara.section.v1",
  "header_navigation": {
    "enabled": true,
    "items": [
      {"id": "docs", "label": "Documentation", "href": "/en/"},
      {"id": "components", "label": "Components", "href": "/en/components/"},
      {"id": "github", "label": "GitHub", "href": "https://github.com/simai/docara"}
    ]
  }
}
```

Docara does not require the same number of items and does not translate the
labels automatically.

## 3. Keep translations current

`translation_tracking` does not change routing, fallback or
`missing_page_policy`. It compares the source Markdown and `lang.json` with
the source language (`source_locale`, Russian here) and builds a report without stopping the build:

```bash
php vendor/bin/docara translations status
php vendor/bin/docara translations status --locale=en
php vendor/bin/docara translations status --status=stale --json
```

Pages with the same relative path are linked automatically. If the paths
differ, give both pages the same `translation_key`. Docara does not link
pages by similar titles. A deliberate exclusion is stored in
`translations.lock.json` together with a required reason.

A translation must keep the shape of the page: heading levels, `:::` blocks
and their parameters, links, tables, lists and code blocks. If the shape
differs, the status is `structure_mismatch`. What a translation may change:

- the text of headings, labels, paragraphs and links;
- the language part of an internal link and the anchor: `/ru/start/#шаги`
  and `/en/start/#steps` count as one link, and so do `#шаги` and `#steps`;
- the address of an external link on the same site: `https://semver.org/lang/ru/`
  and `https://semver.org/` count as one link, so a translation can send the
  reader to the version of the outside page in their language;
- in the parameters of a `:::` block, the reader-facing text (`label`,
  `title`, `alt`, `caption`, `text`), the file size (`file_size`) and the
  selected tab (`default`), and the language part of an address in `src` and
  `href`: `/ru/demo/` and `/en/demo/` count as one address, as do
  `inputs.json` and `inputs.en.json`;
- the content of code blocks — comments and sample text. Only the number of
  blocks and their language are compared.

After reviewing a translation, first create a hash-bound plan, then apply
exactly its identifier:

```bash
php vendor/bin/docara translations accept \
  --locale=en --key=guide/install.md --review=ai_verified --dry-run --json
php vendor/bin/docara translations accept --apply=<plan-sha256> --json
```

A reviewed set is accepted with one plan. Repeat `--key`, name a section by
a key prefix, or accept the result of a filtered report:

```bash
# two keys
php vendor/bin/docara translations accept --locale=en \
  --key=guide/install.md --key=guide/update.md --review=human_reviewed --dry-run --json
# the whole guide/ section
php vendor/bin/docara translations accept --locale=en --prefix=guide/ --dry-run --json
# everything translations status --locale=en --status=unverified shows
php vendor/bin/docara translations accept --locale=en --status=unverified --dry-run --json
# lang.json strings
php vendor/bin/docara translations accept --locale=en --kind=lang --status=stale --dry-run --json
```

A set plan takes only the `unverified` and `stale` items of the selection,
listed in `accepted`. Items in other statuses (`orphan`, `missing`,
`structure_mismatch` and so on) do not change and are listed in `skipped`.
The plan is bound to the hashes of all files it read and is applied with the
same `--apply=<plan-sha256>` command. An exclusion (`--exclude-reason`) is
still set for one key.

If the Markdown, `lang.json`, the configuration or the lock file changed
between the commands, applying stops. `ai_verified` means a translation
checked by AI both technically and for meaning; `human_reviewed` is set after
a separate editorial review. The builder does not call AI or the network and
does not change the sources.

### Parts created automatically

Your script may build some parts of a page, such as a version line or a code
reference. They change with every release even though the text stayed the
same. To keep them from making a translation stale, put markers around such a
part, each on its own line:

```markdown
<!-- docara:generated -->
Current version: 2.4.1
<!-- /docara:generated -->
```

Nothing between the markers affects translation freshness or the comparison of
page structure. Your script must build this part for each language: Docara does
not translate or create it. The markers do not reach the site.

## Strict publish-current mode

In `report` mode the build publishes all translations as they are. To keep a
stale or unreviewed translation away from readers, turn on strict mode:

```json
{
  "translation_tracking": {
    "enabled": true,
    "source_locale": "ru",
    "mode": "publish-current",
    "lock_file": "translations.lock.json"
  }
}
```

The rule for each page of the target locale:

| Translation status | What is published at the translation's address |
|---|---|
| `current`, `excluded` | the translation as it is |
| `stale`, `unverified`, `structure_mismatch` | the source page with a notice in the translation's language |
| `orphan` | nothing: the page has no source |
| `missing` | as before, by `locales.missing_page_policy` |
| `duplicate_key` | the build stops: it is unclear which page to publish |

Why the source page and not a gap: the translation's address may already be
in links, bookmarks and the language switcher, and the source text is more
accurate than a stale translation. A missing page (`missing`) stays missing:
the `missing_page_policy` rule does not change.

A page with the source text:

- is built in the menu, theme and interface of the target locale
  (`lang="en"`) and takes its content and title from the source page;
- marks the source text with the original's language: `<article>` and the
  page outline items get the `lang` of the source locale (and `dir` if the
  writing direction differs), so a screen reader reads them in that
  language's voice; the notice and the interface stay in the target locale's
  language;
- starts with a notice that links to the original (the text is below);
- gets `<meta name="robots" content="noindex">` and a `canonical` to the
  source page, and takes no part in `hreflang` (neither its own nor the
  source page's), in site search or in `sitemap.xml`.

The default notice text is "This page is being translated; the original is
shown (Русский)." for English and a similar one for Russian. You can set it
in `content/<locale>/lang.json` with the `language.fallback_notice` key,
where `{source}` is replaced with a link to the source page:

```json
{
  "schema": "docara.lang.v1",
  "version": 1,
  "language": {
    "fallback_notice": "This page is being translated; showing the Russian original ({source})."
  }
}
```

Interface strings from `lang.json` in the `stale`, `unverified` or
`structure_mismatch` status are replaced with the source locale's string. A
missing string is still taken by the locale's rules.

The build prints what was held back:

```text
Translations held back (mode publish-current): 2 page(s) show the source text, 1 page(s) withheld, 1 string(s) show the source text.
- source_fallback  stale        guide/install.md [en] content/en/guide/install.md
- source_fallback  unverified   components.md [en] content/en/components.md
- withheld         orphan       legacy.md [en] content/en/legacy.md
- source_string    lang:search.placeholder [en]
```

The same list is kept in the
`build_<environment>.docara/translation-publication.json` receipt. `report`
mode works exactly as before.

## 4. Build and check all languages

```bash
php vendor/bin/docara build production
php vendor/bin/docara verify-static build_production
php vendor/bin/docara serve production --host=127.0.0.1 --port=8000 --no-build
```

Check the `/` redirect, then `/ru/`, `/en/` and `/ar/`. On linked pages the
language switcher (a button with an icon), the locale menu and `hreflang`
must work; the root HTML of the Arabic page must have `lang="ar"` and
`dir="rtl"`. Search stores the locale of each record and does not mix the
same routes of different languages.

## When there is no translation

With `missing_page_policy: "skip"`, if `content/ar/guide/install.md` is
missing, the Arabic installation page is not created and the switcher does
not lead to a made-up URL. For strict symmetry choose `"error"`: the build
stops with the exact expected path. Do not copy the Russian Markdown as a
hidden fallback. If an interface label really comes from `fallbacks`, the
translation report shows the fallback locale separately and still counts the
own translation as missing.

## How languages work

### Separation of data

- `docara.json`, `section.json`, `*.page.json` and the component catalogue
  hold the structure, IDs, parameters and links;
- Markdown holds the translatable content of each locale;
- `content/<locale>/lang.json` holds only the repeated interface labels; page
  text and component parameters do not move there;
- PHP, JavaScript and templates use semantic message IDs and do not check for
  specific `ru` or `en` values.

So adding a language needs no new generator branch and no copied PHP code.

### Locale registry and addresses

```json
{
  "default_locale": "en",
  "locales": {
    "missing_page_policy": "skip",
    "en": {
      "label": "English",
      "direction": "ltr",
      "content_root": "content/en",
      "public_prefix": "en",
      "fallbacks": []
    },
    "ru": {
      "label": "Русский",
      "direction": "ltr",
      "content_root": "content/ru",
      "public_prefix": "ru",
      "fallbacks": []
    },
    "ar": {
      "label": "العربية",
      "direction": "rtl",
      "content_root": "content/ar",
      "public_prefix": "ar",
      "fallbacks": ["en"]
    },
    "fr-CA": {
      "label": "Français (Canada)",
      "direction": "ltr",
      "content_root": "content/fr-CA",
      "public_prefix": "fr-ca",
      "fallbacks": ["en"]
    }
  },
  "locale_routing": {
    "strategy": "prefixed",
    "root": "redirect",
    "detect_browser_language": false,
    "legacy_unprefixed_redirects": true
  }
}
```

The locale key is a canonical BCP 47 tag. `public_prefix` only sets the
address and does not have to repeat the tag. The `content_root` of different
locales must not overlap. The `locales` registry, `default_locale` and
`locale_routing` are set only in `docara.json`.

`locale_routing.strategy: "prefixed"` turns on symmetric addresses: every
locale needs a prefix, including the main one — English is published under
`/en/`, Russian under `/ru/`. `root: "redirect"` creates a static `/` page
that leads to `default_locale`. Automatic browser language detection is off
on purpose: one address has one stable language identity. With
`legacy_unprefixed_redirects: true` the build creates a redirect for every
former address without a prefix and records them in
`build_<environment>.docara/locale-routes.json`. Redirects of the project's
own addresses are set separately, in [`redirects.json`](/en/authoring/redirects/).

Existing projects need not move to prefixes at once. The
`strategy: "default_unprefixed"` mode keeps an empty prefix for the main
locale and publishes it at the root, as the earlier Docara did; the
`minimal` starter uses it too. Both modes pass the same check, but their
rules cannot be mixed in one configuration.

`locales.missing_page_policy: "skip"` publishes only the existing pages. The
`"error"` value requires the same set of addresses in all locales and stops
the build with `LOCALE_PAGE_MISSING` and the expected file path. No mode
copies page text from another locale.

### Interface labels and fallback

The shared labels of each locale are in `content/<locale>/lang.json`. For
some labels Docara ships default values: the copy and line-wrap buttons in
code, the labels of the responsive example preview, font size and page width,
the "Settings" heading and the "Reset settings" button in the settings panel,
the "Reading mode" and "Hide page contents" buttons, and the pause button of
the hero background animation (`effects.pause`, `effects.play`). The built-in
values exist in English and Russian. The language is chosen by the main part
of the locale tag: `ru` and `ru-RU` get Russian labels, locales without their
own set get English ones.

A key from `lang.json` always wins over a built-in value, so any label can be
overridden in the project. The main labels of the header, search, navigation
and settings panel are required: a locale without its own file stops the
build instead of silently taking labels from another language. The file may
be incomplete: Docara looks up a missing required string along the
explicitly declared `fallbacks` chain, and for strings with built-in text it
takes the built-in text. A fallback is never picked by guessing from the
language name and never replaces a Markdown page. All keys of the file are
listed in the [lang.json interface labels](/en/reference/lang-json/)
reference.

### Build result

Each locale gets an isolated address tree, its own `lang`, `dir`, navigation
and a localized component catalogue. Linked pages get `hreflang` and a
language switcher. Each content page gets a self-canonical, and
`hreflang="x-default"` points to the stable root redirect. For an RTL
language set `"direction": "rtl"` explicitly: Docara adds `dir="rtl"` to the
root HTML element and does not infer the direction from a list of known
languages.

If a page has no counterpart in another tree, the switcher does not invent a
translation: pages are linked only by the same relative content path or the
same `translation_key`.

### Translation statuses

`translation_tracking` is an independent reporting loop. It does not change
fallback, addresses or `missing_page_policy`, and shows the statuses
`current`, `stale`, `missing`, `unverified`, `orphan`, `duplicate_key`,
`structure_mismatch` and `excluded`. The `report` mode (the default) only
reports statuses; the `publish-current` mode is described
[above](#strict-publish-current-mode).

## How to add another language

1. Add the locale to `locales`.
2. Create its `content_root` and a Markdown tree with the same relative paths.
3. Create `content/<locale>/lang.json` with the shared interface labels.
4. Set explicit `fallbacks` if the file is incomplete.
5. Run `build` and `verify-static`.

## See also

- [lang.json interface labels](/en/reference/lang-json/) — all keys of the file
- [Redirects](/en/authoring/redirects/)
- [Menu and sections](/en/authoring/navigation/) — the top menu of each locale
