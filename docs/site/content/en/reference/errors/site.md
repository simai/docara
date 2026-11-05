---
description: Site settings error codes — redirects, languages and translations, lang.json strings, search, background effects, the 404 page, the site address, the theme, the brand and the portal — with the cause and the fix.
---

# Site settings errors

These errors concern `docara.json`, `redirects.json`, `lang.json` and the
settings that apply to the whole site. The fields are described in the
[site settings](/en/reference/docara-json/) reference.

## Redirects

Redirects are set in the file from the `redirects_file` field, usually
`redirects.json`. Each record is `{"from": "old/address", "to": "new/address"}`
without a leading `/`. More in [Redirects](/authoring/redirects/).

| Code | Cause and what to do |
| --- | --- |
| `PORTABLE_REDIRECT_SOURCE_INVALID` | The path in `redirects_file` is unsafe: absolute or with `..`. **What to do:** Give a file inside the project, for example `redirects.json`. |
| `PORTABLE_REDIRECT_SOURCE_UNSAFE`, `PORTABLE_REDIRECT_SOURCE_READ_FAILED` | The file was not found or cannot be read. **What to do:** Check the file name and the read permissions. |
| `PORTABLE_REDIRECT_SOURCE_SYMLINK_FORBIDDEN` | The path to the file goes through a symbolic link. **What to do:** Replace the link with a real file. |
| `PORTABLE_REDIRECT_JSON_INVALID` | The file is not valid JSON. **What to do:** Fix the syntax. |
| `PORTABLE_REDIRECT_DOCUMENT_INVALID` | The file does not hold an object. **What to do:** Start the file with `{"schema": "docara.redirects.v1", "version": 1, "redirects": [...]}`. |
| `PORTABLE_REDIRECT_RECORD_INVALID` | A record in `redirects` is not an object. **What to do:** Write each record as `{"from": "...", "to": "..."}`. |
| `PORTABLE_REDIRECT_SOURCE_DUPLICATED` | One old address is given twice. **What to do:** Keep one record. |
| `PORTABLE_REDIRECT_CHAIN_FORBIDDEN` | A redirect leads to itself or to an address that is itself redirected. **What to do:** Point each redirect straight at the final page. |
| `PORTABLE_REDIRECT_TARGET_NOT_FOUND` | The new address matches no page of the site. **What to do:** Give the address of an existing page, for example `ru/start/quick-start`. For languages with a prefix, the prefix is part of the address. |
| `PORTABLE_REDIRECT_TARGET_IDENTITY_INVALID` | The new address looks like a page but does not match it exactly. **What to do:** Copy the page address from the built site without the `/` at the start and end. |
| `PORTABLE_REDIRECT_PAGE_COLLISION` | A page still exists at the old address. **What to do:** Delete or rename the old page, or remove the redirect. |
| `PORTABLE_REDIRECT_ASSET_COLLISION` | The old address matches a file from `assets/` or the content. **What to do:** Choose another address or move the file. |
| `PORTABLE_REDIRECT_PUBLICATION_FAILED`, `PORTABLE_REDIRECT_RECEIPT_PUBLICATION_FAILED` | The build could not write the redirect page. **What to do:** Check the disk space and the permissions on the build folder, then build again. |
| `PORTABLE_REDIRECT_PAGE_IDENTITY_INVALID` | An internal build error: pages have no exact addresses when redirects are built. **What to do:** Rebuild the whole site. If the error repeats, report it in the Docara repository. |

## Languages

Languages are set in `locales`, and language addresses in `locale_routing`.
More in [Multilingual site](/en/authoring/multilingual-site/).

| Code | Cause and what to do |
| --- | --- |
| `LOCALE_TAG_INVALID` | The language code is written incorrectly. **What to do:** Use a BCP 47 code: `ru`, `en`, `fr-CA`. |
| `LOCALE_REGISTRY_EMPTY` | `locales` is empty. **What to do:** Add at least one language. |
| `LOCALE_DEFINITION_INVALID` | A language description is not an object. **What to do:** Write the language as an object with `label`, `direction` and `content_root`. |
| `LOCALE_NOT_CONFIGURED` | A page or command refers to a language that is not in `locales`. **What to do:** Add the language to `locales` or fix the language code. |
| `LOCALE_DUPLICATE` | One language is given twice in different spellings, for example `en-us` and `en-US`. **What to do:** Keep one key. |
| `LOCALE_DIRECTION_INVALID` | `direction` is not `ltr` or `rtl`. **What to do:** Give `ltr` or `rtl`. |
| `LOCALE_CONTENT_ROOT_INVALID` | The language has an empty `content_root`. **What to do:** Give a folder, for example `content/en`. |
| `LOCALE_CONTENT_ROOT_OVERLAP` | The folders of two languages are nested in each other. **What to do:** Give each language its own folder, for example `content/en` and `content/ru`. |
| `LOCALE_PUBLIC_PREFIX_INVALID` | The address prefix contains forbidden characters. **What to do:** Use lowercase Latin letters, digits and a hyphen. |
| `LOCALE_PUBLIC_PREFIX_DUPLICATE` | Two languages have the same prefix. **What to do:** Make the prefixes different. |
| `LOCALE_FALLBACK_INVALID` | A fallback language is not written as a string. **What to do:** List the language codes as strings: `["en"]`. |
| `LOCALE_FALLBACK_DUPLICATE` | A fallback language is given twice. **What to do:** Remove the repeat. |
| `LOCALE_FALLBACK_NOT_CONFIGURED` | The fallback language is not in `locales`. **What to do:** Add it to `locales` or remove it from `fallbacks`. |
| `LOCALE_FALLBACK_CYCLE` | Languages refer to each other in a loop, for example `en` → `ru` → `en`. **What to do:** Break the loop: one language must have empty `fallbacks`. |
| `DEFAULT_LOCALE_NOT_CONFIGURED` | `default_locale` is not in `locales`. **What to do:** Add that language to `locales` or fix `default_locale`. |
| `DEFAULT_LOCALE_MUST_BE_UNPREFIXED` | With `strategy: default_unprefixed` the main language has a prefix. Without an explicit `public_prefix` the language code becomes the prefix. **What to do:** Give the main language `"public_prefix": ""`. |
| `DEFAULT_LOCALE_ROOT_COLLISION` | `root: default_locale`, but the main language does not open from the root. **What to do:** Give the main language `"public_prefix": ""`, or use `root: redirect`. |
| `LOCALE_PREFIX_REQUIRED` | With `strategy: prefixed` a language has no prefix. **What to do:** Set `public_prefix` for every language. |
| `LOCALE_ROOT_REDIRECT_REQUIRED` | With `strategy: prefixed`, `root: redirect` is not given. **What to do:** Give `"root": "redirect"`. |
| `LOCALE_ROOT_REDIRECT_TARGET_INVALID` | `root: redirect`, but the main language has an empty prefix. **What to do:** Give the main language a prefix. |
| `LOCALE_ROUTING_INVALID`, `LOCALE_ROUTING_STRATEGY_INVALID`, `LOCALE_ROOT_ROUTE_INVALID` | `locale_routing` is not written as an object or holds an unknown value. **What to do:** Compare the fields with the [site settings](/en/reference/docara-json/#languages-and-translations). |
| `LEGACY_LOCALE_REDIRECT_COLLISION` | An old unprefixed address that needs a redirect is taken by a page. **What to do:** Rename the page or turn off `legacy_unprefixed_redirects`. |
| `LEGACY_UNPREFIXED_REDIRECTS_REQUIRE_PREFIXED_ROUTING` | `legacy_unprefixed_redirects: true` without `strategy: prefixed`. **What to do:** Turn these redirects on only with `prefixed`, or remove the field. |
| `LOCALE_MISSING_PAGE_POLICY_INVALID` | `locales.missing_page_policy` is not `skip` or `error`. **What to do:** Give `skip` or `error`. |
| `LOCALE_PAGE_MISSING` | A page is missing in one of the languages, and `missing_page_policy` is `error`. The message has the expected path. **What to do:** Create a page at that path or give `missing_page_policy: skip`. |
| `PAGE_OUTSIDE_LOCALE_CONTENT_ROOTS` | A page lies outside the folders of all languages. **What to do:** Move the page into a language folder. |
| `PAGE_LOCALE_CONTENT_ROOT_MISMATCH` | `locale` in the page or section settings does not match the language of the folder. **What to do:** Remove `locale` or fix it. |
| `PORTABLE_LOCALE_CONTENT_EMPTY` | The language folder has no Markdown pages. **What to do:** Add pages or remove the language from `locales`. |
| `LOCALE_CANONICAL_URL_MISMATCH` | The page address does not belong to its language. Usually this follows from another language settings error. **What to do:** Check the `public_prefix` and `content_root` of the languages. |
| `LOCALE_REDIRECT_OUTPUT_DUPLICATE`, `LOCALE_REDIRECT_PUBLICATION_FAILED`, `LOCALE_REDIRECT_RECEIPT_PUBLICATION_FAILED` | The build could not write the redirects between languages: two redirects to one place, or a write error. **What to do:** Check that the language addresses do not overlap with pages, and build again. |

## `lang.json` interface strings

The list of all strings and their defaults is on
[lang.json interface labels](/en/reference/lang-json/#all-interface-strings).

| Code | Cause and what to do |
| --- | --- |
| `MESSAGE_NOT_FOUND` | The `lang.json` of the language and its fallback languages lack a required string. The message has its name, for example `search.placeholder`. **What to do:** Add the string to `lang.json`, or put a fallback language with this string in `fallbacks`. |
| `MESSAGE_ID_INVALID`, `MESSAGE_PARAMETER_INVALID` | An internal error in a string request. **What to do:** Report it in the Docara repository. |
| `CONTENT_LANGUAGE_INVALID` | `lang.json` is not valid JSON or not an object. **What to do:** Fix the syntax. |
| `CONTENT_LANGUAGE_SOURCE_INVALID` | `lang.json` is a symbolic link or lies outside the language folder. **What to do:** Put a real file at `content/<language>/lang.json`. |
| `TARGET_LANG_NAMESPACE_FORBIDDEN` | `lang.json` has an unknown group, for example `pages`. **What to do:** Keep only the interface groups from the list on [lang.json interface labels](/en/reference/lang-json/). |
| `TARGET_LANG_VALUE_INVALID` | A string is empty, longer than 280 characters, multi-line or contains HTML. **What to do:** Shorten the string and remove the tags. |
| `READER_PREFERENCES_COPY_MISSING` | There is no label for a setting in the reader panel. **What to do:** Add the `reader` group strings to `lang.json` or to a fallback language. |

## Translations

These codes come from the `docara translations` commands and from the build
with `translation_tracking` turned on.

| Code | Cause and what to do |
| --- | --- |
| `TRANSLATION_TRACKING_DISABLED` | Translation tracking is off. **What to do:** Turn on `translation_tracking.enabled` in `docara.json`. |
| `TRANSLATION_CONFIG_INVALID` | `docara.json` cannot be read as JSON. **What to do:** Fix the syntax. |
| `TRANSLATION_PROJECT_ROOT_INVALID` | The command was not run in the project folder, or the folder is a link. **What to do:** Run the command from the site folder. |
| `TRANSLATION_KIND_INVALID` | `--kind` is not `page` or `lang`. **What to do:** Give `--kind=page` or `--kind=lang`. |
| `TRANSLATION_STATUS_FILTER_INVALID` | An unknown status in the filter. **What to do:** Use the statuses from the output of `docara translations status`. |
| `TRANSLATION_TARGET_LOCALE_INVALID` | The translation language is the same as the source language. **What to do:** Give another language. |
| `TRANSLATION_REVIEW_INVALID` | The review level is not `ai_verified` or `human_reviewed`. **What to do:** Give one of the two values. |
| `TRANSLATION_ACCEPT_SELECTION_REQUIRED` | It is not said which translations to accept. **What to do:** Name the keys, a key prefix or a status. |
| `TRANSLATION_ACCEPT_SELECTION_EMPTY` | The selection holds no unverified or stale translation. **What to do:** Check the selection: the translations may already be accepted. |
| `TRANSLATION_ACCEPT_TARGET_INVALID` | There is no translation with this key, or the key is ambiguous. **What to do:** Check the key in the `docara translations status` report. |
| `TRANSLATION_EXCLUSION_REASON_REQUIRED` | A translation is excluded without a reason. **What to do:** Give the reason. |
| `TRANSLATION_LOCK_PATH_INVALID` | `translation_tracking.lock_file` is not a file name in the project root. **What to do:** Give a file name, for example `translations.lock.json`. |
| `TRANSLATION_LOCK_INVALID` | The file of accepted translations is damaged. **What to do:** Restore the file from version control. |
| `TRANSLATION_LOCK_SOURCE_LOCALE_MISMATCH` | The source language in the file of accepted translations differs from `docara.json`. **What to do:** Restore the former `source_locale` or accept the translations again. |
| `TRANSLATION_LANG_INVALID`, `TRANSLATION_PAGE_SOURCE_INVALID` | `lang.json` or a page cannot be read as UTF-8 or lies outside the project. **What to do:** Save the file as UTF-8 inside the language folder. |
| `TRANSLATION_GENERATED_REGION_INVALID` | The marks of an automatically generated part of a page do not form a pair or are nested in each other. **What to do:** Keep `<!-- docara:generated -->` and `<!-- /docara:generated -->` pairs on the page, each mark on its own line. |
| `TRANSLATION_PLAN_ID_INVALID`, `TRANSLATION_PLAN_INVALID`, `TRANSLATION_PLAN_HASH_MISMATCH` | The plan identifier is not the one `--dry-run` gave, or the plan was changed. **What to do:** Run `--dry-run` again and apply the identifier it gives. |
| `TRANSLATION_PLAN_STALE` | Files changed after `--dry-run`. **What to do:** Run `--dry-run` again. |
| `TRANSLATION_PLAN_PATH_UNSAFE`, `TRANSLATION_PLAN_WRITE_FAILED`, `TRANSLATION_WRITE_FAILED` | The command could not write a file: the folder is a link, or there are no permissions. **What to do:** Check the permissions on the project folder and `.docara/`. |
| `TRANSLATION_WRITE_COLLISION` | The translation file already exists. **What to do:** Delete or rename the existing file. |
| `TRANSLATION_GATE_REPORT_INVALID` | The `publish-current` mode is on without a translation report. **What to do:** Turn on `translation_tracking.enabled`. |
| `TRANSLATION_DUPLICATE_KEY_BLOCKS_PUBLICATION` | Several pages of one language share a `translation_key`. **What to do:** Give each page its own key. |

## Search

| Code | Cause and what to do |
| --- | --- |
| `SEARCH_INDEX_LOCALE_EMPTY` | Search is on, but all pages in this language are excluded from search. **What to do:** Turn on `search.indexed` for at least one page or turn search off for the language. |
| `SEARCH_DOCUMENT_OUTSIDE_BASE` | The page address lies outside `base_url`. **What to do:** Check `base_url` and the `slug` of the page. |
| `SEARCH_TEXT_INVALID_UTF8`, `SEARCH_TEXT_PARSE_FAILED` | The page text cannot be parsed for search. **What to do:** Save the page as UTF-8 and fix the markup error near the place from the message. |
| `SEARCH_RUNTIME_MISSING`, `SEARCH_RUNTIME_INVALID_UTF8` | The search file in the Docara package is damaged or missing. **What to do:** Reinstall Docara: `composer install`. |
| `SEARCH_COMPONENT_CALL_INVALID` | The internal component data for search is damaged. **What to do:** Report the error in the Docara repository. |

## Background effects

Effects are connected with the `effects` field in `docara.json` and the
`effect` parameter of the [hero](/components/hero/) block.

| Code | Cause and what to do |
| --- | --- |
| `EFFECTS_INVALID` | `effects` is not an object. **What to do:** Write `"effects": {"catalogue": "https://.../"}`. |
| `EFFECTS_CATALOGUE_INVALID` | The catalogue address is not `https://`, does not end with `/` or is not pinned to a commit. **What to do:** Give an address like `https://cdn.jsdelivr.net/gh/simai/ui-effects@<commit>/`. |
| `EFFECTS_CATALOGUE_REQUIRED` | A page uses an effect, but `effects.catalogue` is not set. **What to do:** Add `effects.catalogue` to `docara.json`. |
| `EFFECTS_OUTSIDE_CATALOGUE` | An effect is given by an address outside the catalogue from `effects.catalogue`. **What to do:** Take effects only from the connected catalogue. |
| `EFFECTS_INDEX_INVALID` | `effects.index` is not a relative path to a `.json` file. **What to do:** Give a path like `catalogue/index.json`. |
| `EFFECTS_INDEX_SHA256_INVALID` | `effects.index_sha256` is not 64 lowercase hexadecimal characters. **What to do:** Copy the whole SHA-256 checksum of the file. |
| `EFFECTS_BOOT_INVALID` | The effects start script was changed after the build. **What to do:** Rebuild the site and do not edit files in the build folder. |

Codes for the effect parameters in the block itself are described in
[Hero](/reference/errors/content/#hero).

## The 404 page

Docara creates `404.html` in the site root if `not_found.enabled` is not
`false`. The text comes from `404.md` of the main language or from the
`error.not_found.*` strings in `lang.json`.

| Code | Cause and what to do |
| --- | --- |
| `PORTAL_NOT_FOUND_PAGE_FORBIDDEN` | A portal build sets `not_found.enabled: true`. The portal owns the 404 page. **What to do:** Remove `not_found` from `docara.json`. |
| `PORTAL_FILE_COLLISION` | A portal build publishes a file the portal owns: `404.html`, `robots.txt`, `sitemap.xml`, `CNAME`, `.nojekyll`. **What to do:** Remove this file from the content. |

## Site address and `llms.txt`

| Code | Cause and what to do |
| --- | --- |
| `SITE_URL_INVALID` | `site_url` is not a full `http(s)://` address, or contains `?`, `#`, a login or `..`. **What to do:** Give an address like `https://example.com/docs/`. |
| `SITE_URL_BASE_MISMATCH` | The path in `site_url` does not match `base_url`. **What to do:** Set both values from one publication address. |
| `DISCOVERY_FILE_COLLISION` | The content already publishes `sitemap.xml`, `robots.txt` or `llms.txt`. **What to do:** Remove your file or remove `site_url`. |
| `DISCOVERY_LINK_INVALID` | A path in `discovery.llms_links` is unsafe. **What to do:** Give a path relative to `base_url` without `..`. |
| `DISCOVERY_URL_OUTSIDE_BASE` | An address in `llms.txt` came out outside `base_url`. **What to do:** Check `base_url`, `site_url` and the link paths. |

## Theme and brand

| Code | Cause and what to do |
| --- | --- |
| `THEME_INVALID` | `theme` is not an object with `color`. **What to do:** Write `"theme": {"color": "#0a7d4f"}`. |
| `THEME_COLOR_INVALID` | The colour is not in the form `#rgb` or `#rrggbb`. **What to do:** Give the colour as a hexadecimal code. Colour names are not accepted. |
| `THEME_HUE_INVALID`, `THEME_CHROMA_INVALID` | `hue` is outside 0–360 or `chroma` outside 0–150. **What to do:** Give a whole number in the range or remove the field. |
| `THEME_FRAMEWORK_UNSUPPORTED` | The pinned SIMAI Framework version cannot build a palette. **What to do:** Update `simai-framework.lock.json` to a version with the theme generator, or remove `theme`. |
| `THEME_PALETTE_FAILED`, `THEME_PALETTE_RESULT_INVALID`, `THEME_PALETTE_RUNTIME_UNAVAILABLE` | The palette generator did not start or returned an unexpected result. **What to do:** Check that Node.js is available to the build, and build again. |
| `BRANDING_CREDIT_SITE_ONLY` | `branding.credit` is given in `section.json` or `<page>.page.json`. **What to do:** Move it to `docara.json`. |
| `BRAND_ASSET_NOT_FOUND` | The logo, icon or social image file was not found. **What to do:** Check the path from the project root. |
| `BRAND_ASSET_PATH_INVALID`, `BRAND_ASSET_SYMLINK_FORBIDDEN` | The path is unsafe or goes through a link. **What to do:** Put the file inside the project and give a relative path. |
| `BRAND_ASSET_TYPE_FORBIDDEN` | The file format is not supported. **What to do:** Use SVG, PNG, JPEG, WebP or ICO. |
| `BRAND_SHARE_IMAGE_TYPE_FORBIDDEN` | The social image is not PNG, JPEG or WebP. **What to do:** Save it in one of these formats. |
| `BRAND_ASSET_TOO_LARGE` | The file is larger than 2 MB. **What to do:** Make the file smaller. |
| `BRAND_DARK_LOGO_REQUIRES_LOGO` | `logo_dark` is set without `logo`. **What to do:** Add the main `logo`. |
| `BRAND_ASSET_READ_FAILED`, `BRAND_ASSET_PUBLICATION_FAILED` | The file could not be read or copied into the build. **What to do:** Check the file permissions and the disk space. |

## Header menu and reader panel

| Code | Cause and what to do |
| --- | --- |
| `DECLARATIVE_HEADER_NAVIGATION_ITEMS_REQUIRED` | `header_navigation.enabled: true` without `items`. **What to do:** Add links to `items` or turn the menu off. |
| `DECLARATIVE_HEADER_NAVIGATION_LIMIT_EXCEEDED` | There are more than eight links. **What to do:** Keep no more than eight. |
| `DECLARATIVE_HEADER_NAVIGATION_INVALID` | A link is written with an error, or an `id` repeats. **What to do:** Check the `id`, `label` and `href` of each link. |
| `READER_PREFERENCES_FIELD_UNKNOWN`, `READER_PREFERENCES_FIELD_INVALID` | An unknown reader panel setting. **What to do:** Use `appearance.theme`, `appearance.font_size` or `appearance.content_width`. |
| `READER_PREFERENCES_FIELD_DUPLICATE`, `READER_PREFERENCES_GROUP_DUPLICATE` | A setting or group is given twice. **What to do:** Remove the repeat. |
| `READER_PREFERENCES_FIELD_GROUP_MISMATCH` | A setting is not in its own group. **What to do:** Move it to the `appearance` group. |
| `READER_PREFERENCES_FIELD_HIDDEN_CONFLICT` | A setting is both in a group and in `hidden_fields`. **What to do:** Keep it in one place. |
| `READER_PREFERENCES_GROUP_INVALID`, `READER_PREFERENCES_HIDDEN_FIELDS_INVALID`, `READER_PREFERENCES_VIEW_INVALID` | A group, the list of hidden settings or `view` is written incorrectly. **What to do:** Compare `reader_preferences` with the [site settings](/en/reference/docara-json/#menu-reading-and-search). |
| `READER_PREFERENCES_AUTHORED_VALUE_INVALID` | The default value of a reader setting is not in the list of allowed values. **What to do:** Give one of the values of this setting. |

## Building for a portal

More in [Building for a portal](/build/portal/).

| Code | Cause and what to do |
| --- | --- |
| `PORTAL_INVALID` | `portal` is not an object. **What to do:** Write `portal` as an object with `product` and `home_url`. |
| `PORTAL_PRODUCT_INVALID` | `portal.product` is written incorrectly. **What to do:** Use lowercase Latin letters, digits and a hyphen, starting with a letter. |
| `PORTAL_HOME_URL_INVALID`, `PORTAL_HOME_INVALID` | `home_url` is not an `https://` address or contains `?` and `#`. **What to do:** Give an address like `https://simai.io/ru/`. |
| `PORTAL_HOME_LABEL_INVALID` | `home_label` is empty or longer than 80 characters. **What to do:** Shorten the label. |
| `PORTAL_SITE_URL_REQUIRED` | There is no `site_url`. **What to do:** Give the full address of the site on the portal. |
| `PORTAL_SINGLE_LOCALE_REQUIRED` | A portal build has more than one language. **What to do:** Build each language separately. |
| `PORTAL_UNPREFIXED_ROUTING_REQUIRED` | `locale_routing.strategy` is not `default_unprefixed`. **What to do:** Give `default_unprefixed`: the language is part of the portal path. |
| `PORTAL_PROFILE_MISSING`, `PORTAL_SITE_MISSING` | `docara portal-artifact` was run for a site without `portal` or without `docara.json`. **What to do:** Run the command in the site folder with the `portal` setting. |
| `PORTAL_BUILD_MISSING`, `PORTAL_INDEX_MISSING` | There is no build folder, or it has no `index.html`. **What to do:** Run `docara build` first. |
| `PORTAL_BUILD_LINK`, `PORTAL_BUILD_UNREADABLE`, `PORTAL_PATH_TOO_LONG` | The build has a link, an unreadable file or a name that is too long. **What to do:** Rebuild the site; shorten long paths. |
| `PORTAL_PAGE_INVALID`, `PORTAL_FRAMEWORK_PAIR_MISSING` | A build page does not look like one Docara built for this portal. **What to do:** Rebuild the site and do not edit the build files. |
| `PORTAL_OUTPUT_INVALID`, `PORTAL_RELEASE_INVALID`, `PORTAL_DOCARA_VERSION_MISSING` | The archive could not be created, or the Docara installation is damaged. **What to do:** Check the archive folder and reinstall Docara. |
