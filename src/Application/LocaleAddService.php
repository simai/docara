<?php

declare(strict_types=1);

namespace Simai\Docara\Application;

use JsonException;
use Simai\Docara\Content\FrontMatterParser;
use Simai\Docara\Content\PageSource;
use Simai\Docara\Content\PageSourceLocator;
use Simai\Docara\I18n\LocaleDefinition;
use Simai\Docara\I18n\LocaleRegistry;
use Simai\Docara\I18n\LocaleRoutingPolicy;
use Simai\Docara\I18n\LocaleTag;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\Portable\PortableConfigurationLoader;
use Simai\Docara\Portable\SchemaRepository;
use stdClass;
use Throwable;

/**
 * `docara locale add <code> [--default]`: registers one more locale in
 * docara.json, creates its content root with a lang.json, and registers it
 * for translation tracking against the source locale. With --default it also
 * becomes default_locale, and every address the old default locale had keeps
 * working through redirects.json.
 *
 * Everything is computed and validated in memory first; the files are then
 * written together, and a failed write puts back what was already written.
 */
final readonly class LocaleAddService
{
    /** Languages written right to left; a script subtag decides first. */
    private const RTL_LANGUAGES = ['ar', 'arc', 'ckb', 'dv', 'fa', 'he', 'ks', 'ku', 'ps', 'sd', 'syr', 'ug', 'ur', 'yi'];

    private const RTL_SCRIPTS = ['Adlm', 'Arab', 'Hebr', 'Mand', 'Nkoo', 'Rohg', 'Syrc', 'Thaa'];

    /** Languages Docara ships a complete lang.json for. */
    private const BUNDLED_LANG = ['en', 'ru'];

    private const DEFAULT_LOCK_FILE = 'translations.lock.json';

    private const DEFAULT_REDIRECTS_FILE = 'redirects.json';

    /**
     * @param  array{default?:bool,label?:?string,direction?:?string,dry_run?:bool}  $options
     */
    public function add(string $root, string $code, array $options = []): OperationResult
    {
        $runtime = ProjectRuntime::load($root);
        $root = $runtime->root;
        $makeDefault = (bool) ($options['default'] ?? false);
        $dryRun = (bool) ($options['dry_run'] ?? false);

        $siteBytes = $this->read($root, 'docara.json', 'LOCALE_ADD_SITE_INVALID');
        $site = $runtime->site;
        try {
            (new SchemaRepository)->assertValid($site, 'site.schema.json');
            $registry = LocaleRegistry::fromSite($site);
            $routing = LocaleRoutingPolicy::fromSite($site, $registry);
            $document = json_decode($siteBytes, false, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $exception) {
            throw new PortableConfigurationException(
                'LOCALE_ADD_SITE_INVALID',
                'docara.json cannot be read as a site configuration (' . $this->describe($exception) . '). Nothing was written.',
                $exception,
            );
        }
        if (! $document instanceof stdClass) {
            throw new PortableConfigurationException('LOCALE_ADD_SITE_INVALID', 'docara.json must contain a JSON object. Nothing was written.');
        }

        $tag = $this->tag($code);
        foreach ($registry->all() as $existing => $_definition) {
            if ($existing === $tag) {
                throw new PortableConfigurationException('LOCALE_ADD_EXISTS', "Locale [$tag] is already configured in docara.json. Nothing was written.");
            }
        }
        if (isset($site['portal'])) {
            throw new PortableConfigurationException(
                'LOCALE_ADD_PORTAL_SINGLE_LOCALE',
                'A portal build publishes exactly one locale, so this site cannot get another one. Nothing was written.',
            );
        }

        $oldDefault = $registry->default();
        $trackingBefore = is_array($site['translation_tracking'] ?? null) ? $site['translation_tracking'] : null;
        $sourceLocale = is_array($trackingBefore) ? (string) $trackingBefore['source_locale'] : $oldDefault->tag->value();
        $contentRoot = $this->contentRoot($registry, $oldDefault, $tag);
        $prefix = strtolower($tag);
        $label = $this->label($tag, $options['label'] ?? null);
        $direction = $this->direction($tag, $options['direction'] ?? null);
        $this->assertNoRouteCollision($root, $registry, $routing, $prefix);

        $contentState = $this->contentState($root, $contentRoot);
        $language = LocaleTag::from($tag)->language();
        $bundledLang = in_array($language, self::BUNDLED_LANG, true);
        $fallbacks = $bundledLang ? [] : [$sourceLocale];

        // docara.json: the new locale, tracking, and for --default the switch.
        $locales = $document->locales ?? null;
        if (! $locales instanceof stdClass || get_object_vars($locales) === []) {
            // No explicit registry yet: write down the one Docara derived.
            $locales = new stdClass;
            $locales->{$oldDefault->tag->value()} = (object) [
                'label' => $oldDefault->label,
                'direction' => $oldDefault->direction,
                'content_root' => $oldDefault->contentRoot,
                'public_prefix' => $oldDefault->publicPrefix,
                'fallbacks' => [],
            ];
            $document->locales = $locales;
        }
        $locales->{$tag} = (object) [
            'label' => $label,
            'direction' => $direction,
            'content_root' => $contentRoot,
            'public_prefix' => $prefix,
            'fallbacks' => $fallbacks,
        ];

        $trackingAction = 'kept';
        $lockFile = self::DEFAULT_LOCK_FILE;
        $diagnostics = [];
        if ($trackingBefore === null) {
            $document->translation_tracking = (object) [
                'enabled' => true,
                'source_locale' => $sourceLocale,
                'mode' => 'report',
                'lock_file' => $lockFile,
            ];
            $trackingAction = 'added';
        } else {
            $lockFile = (string) $trackingBefore['lock_file'];
            if (($trackingBefore['enabled'] ?? false) !== true) {
                $trackingAction = 'disabled';
                $diagnostics[] = $this->warning(
                    'LOCALE_ADD_TRACKING_DISABLED',
                    "translation_tracking is switched off, so docara translations status does not report [$tag].",
                    'docara.json',
                    '/translation_tracking/enabled',
                    'Set translation_tracking.enabled to true to see which pages still need a translation.',
                );
            }
        }

        $redirects = ['file' => null, 'added' => [], 'retargeted' => []];
        $routingAfter = $routing->strategy;
        if ($makeDefault) {
            if (! $contentState['home']) {
                throw new PortableConfigurationException(
                    'LOCALE_DEFAULT_HOME_MISSING',
                    "The default locale needs a published home page, and [$contentRoot/index.md] is missing or a draft. Nothing was written.",
                );
            }
            $redirects = $this->switchDefault($root, $site, $document, $registry, $routing, $oldDefault, $tag, $contentRoot);
            $routingAfter = LocaleRoutingPolicy::STRATEGY_PREFIXED;
        }

        $newSite = $this->assoc($document);
        (new SchemaRepository)->assertValid($newSite, 'site.schema.json');
        $newRegistry = LocaleRegistry::fromSite($newSite);
        LocaleRoutingPolicy::fromSite($newSite, $newRegistry);

        $writes = [];
        $writes[] = ['path' => 'docara.json', 'action' => 'update', 'contents' => $this->encodeLike($document, $siteBytes), 'expected' => $siteBytes];
        $langPath = $contentRoot . '/lang.json';
        if (! $contentState['lang']) {
            $writes[] = ['path' => $langPath, 'action' => 'create', 'contents' => $this->langJson($language), 'expected' => null];
        }
        // The build needs at least one page per locale. The new locale gets
        // the source home page as a draft to translate in place: a draft is
        // never published, so no untranslated text reaches the site, and the
        // translation report lists the page as missing until draft is removed.
        $draftHome = null;
        if ($contentState['pages'] === 0) {
            $draftHome = $contentRoot . '/index.md';
            $sourceHome = $registry->get($sourceLocale)->contentRoot . '/index.md';
            $writes[] = ['path' => $draftHome, 'action' => 'create', 'contents' => $this->draftHome($root, $sourceHome, $label), 'expected' => null];
        }
        $lockExists = file_exists($root . '/' . $lockFile) || is_link($root . '/' . $lockFile);
        if ($trackingAction !== 'disabled' && ! $lockExists) {
            $lock = ['schema' => 'docara.translation_lock.v1', 'source_locale' => $sourceLocale, 'entries' => [], 'exclusions' => []];
            (new SchemaRepository)->assertValid($lock, 'translation-lock.schema.json');
            $writes[] = ['path' => $lockFile, 'action' => 'create', 'contents' => $this->encodePretty($lock, 4), 'expected' => null];
        }
        if (is_array($redirects['document'] ?? null)) {
            $writes[] = $redirects['document'];
        }

        $sourcePages = count((new PageSourceLocator($root, $newRegistry))->forLocale($sourceLocale));
        $missingPolicy = (string) ($site['locales']['missing_page_policy'] ?? 'skip');
        if ($missingPolicy === 'error' && $contentState['pages'] < $sourcePages) {
            $diagnostics[] = $this->warning(
                'LOCALE_ADD_PAGES_MISSING',
                "locales.missing_page_policy is error, so the build stops until [$contentRoot] has every page of [$sourceLocale].",
                'docara.json',
                '/locales/missing_page_policy',
                'Translate the pages first, or set locales.missing_page_policy to skip while the translation is in progress.',
            );
        }

        $changes = [];
        if (! $contentState['exists']) {
            $changes[] = ['path' => $contentRoot . '/', 'action' => 'create'];
        }
        foreach ($writes as $write) {
            $changes[] = ['path' => $write['path'], 'action' => $write['action']];
        }

        if (! $dryRun) {
            $this->commit($root, $contentRoot, $contentState['exists'], $writes);
        }

        $data = [
            'dry_run' => $dryRun,
            'locale' => $tag,
            'label' => $label,
            'direction' => $direction,
            'content_root' => $contentRoot,
            'public_prefix' => $prefix,
            'fallbacks' => $fallbacks,
            'default_locale' => ['before' => $oldDefault->tag->value(), 'after' => $makeDefault ? $tag : $oldDefault->tag->value()],
            'routing' => ['before' => $routing->strategy, 'after' => $routingAfter],
            'content' => [
                'existing_pages' => $contentState['pages'],
                'draft_home' => $draftHome,
                'lang_json' => $contentState['lang'] ? 'kept' : ($bundledLang ? 'bundled' : 'minimal'),
            ],
            'translation' => [
                'tracking' => $trackingAction,
                'source_locale' => $sourceLocale,
                'lock_file' => $lockFile,
                'source_pages' => $sourcePages,
            ],
            'redirects' => [
                'file' => $redirects['file'],
                'added' => $redirects['added'],
                'retargeted' => $redirects['retargeted'],
            ],
            'changes' => $changes,
            'next' => [
                'php vendor/bin/docara translations status --locale=' . $tag,
                'php vendor/bin/docara build production',
            ],
        ];

        return new OperationResult('locale.add', 'success', 0, $tag, $data, $diagnostics, $runtime->provenance());
    }

    private function tag(string $code): string
    {
        $code = trim($code);
        $invalid = static fn (): PortableConfigurationException => new PortableConfigurationException(
            'LOCALE_ADD_CODE_INVALID',
            "[$code] is not a BCP 47 language code such as en, de or fr-CA. Nothing was written.",
        );
        // A real language subtag has two or three letters; longer ones
        // ("english") are reserved, x- and i- tags are private or retired.
        if (! LocaleTag::isWellFormed($code) || preg_match('/^[A-Za-z]{2,3}(?:-[A-Za-z0-9]{1,8})*$/D', $code) !== 1) {
            throw $invalid();
        }
        try {
            return LocaleTag::from($code)->value();
        } catch (PortableConfigurationException) {
            throw $invalid();
        }
    }

    private function contentRoot(LocaleRegistry $registry, LocaleDefinition $default, string $tag): string
    {
        // Locales sit side by side: content/ru beside content/en.
        $parent = dirname($default->contentRoot);
        if ($parent === '.' || $parent === '') {
            throw new PortableConfigurationException(
                'LOCALE_ADD_CONTENT_LAYOUT_UNSUPPORTED',
                "Locale [{$default->tag}] keeps its pages in [{$default->contentRoot}], not in a folder of its own such as content/{$default->tag}, so a new locale has no place beside it. Nothing was written.",
            );
        }
        $contentRoot = $parent . '/' . $tag;
        foreach ($registry->all() as $definition) {
            $other = rtrim($definition->contentRoot, '/');
            if ($other === $contentRoot
                || str_starts_with($contentRoot . '/', $other . '/')
                || str_starts_with($other . '/', $contentRoot . '/')
            ) {
                throw new PortableConfigurationException(
                    'LOCALE_ADD_CONTENT_LAYOUT_UNSUPPORTED',
                    "Locale [{$definition->tag}] keeps its pages in [$other], so a new locale has no folder of its own beside it. Nothing was written.",
                );
            }
        }

        return $contentRoot;
    }

    private function label(string $tag, mixed $label): string
    {
        if (is_string($label) && trim($label) !== '') {
            return trim($label);
        }
        $name = \Locale::getDisplayName($tag, $tag);
        if (! is_string($name) || $name === '' || strcasecmp($name, $tag) === 0) {
            return $tag;
        }

        return mb_strtoupper(mb_substr($name, 0, 1)) . mb_substr($name, 1);
    }

    private function direction(string $tag, mixed $direction): string
    {
        if (is_string($direction) && $direction !== '') {
            if (! in_array($direction, ['ltr', 'rtl'], true)) {
                throw new PortableConfigurationException('LOCALE_ADD_DIRECTION_INVALID', "Direction [$direction] must be ltr or rtl. Nothing was written.");
            }

            return $direction;
        }
        foreach (array_slice(explode('-', $tag), 1) as $subtag) {
            if (strlen($subtag) === 4 && ctype_alpha($subtag)) {
                return in_array($subtag, self::RTL_SCRIPTS, true) ? 'rtl' : 'ltr';
            }
        }

        return in_array(LocaleTag::from($tag)->language(), self::RTL_LANGUAGES, true) ? 'rtl' : 'ltr';
    }

    /** A page of an unprefixed locale must not already sit where the new prefix goes. */
    private function assertNoRouteCollision(string $root, LocaleRegistry $registry, LocaleRoutingPolicy $routing, string $prefix): void
    {
        if ($routing->isSymmetric()) {
            return;
        }
        foreach ($registry->all() as $locale => $definition) {
            if ($definition->publicPrefix !== '') {
                continue;
            }
            foreach ((new PageSourceLocator($root, $registry))->forLocale($locale) as $source) {
                if ($source->route === $prefix || str_starts_with($source->route, $prefix . '/')) {
                    throw new PortableConfigurationException(
                        'LOCALE_ADD_ROUTE_COLLISION',
                        "Page [{$source->path}] is published at [/{$source->route}/], where locale addresses with the prefix [$prefix] would go. Nothing was written.",
                    );
                }
            }
        }
    }

    /** @return array{exists:bool,home:bool,lang:bool,pages:int} */
    private function contentState(string $root, string $contentRoot): array
    {
        $current = $root;
        foreach (explode('/', $contentRoot) as $segment) {
            if (preg_match('/^[A-Za-z0-9._-]+$/D', $segment) !== 1 || $segment === '.' || $segment === '..') {
                throw new PortableConfigurationException('LOCALE_ADD_CONTENT_ROOT_UNSAFE', "Content root [$contentRoot] is not a safe project path. Nothing was written.");
            }
            $current .= '/' . $segment;
            if (is_link($current) || (file_exists($current) && ! is_dir($current))) {
                throw new PortableConfigurationException(
                    'LOCALE_ADD_CONTENT_ROOT_UNSAFE',
                    "[$contentRoot] exists but is not an ordinary folder (a file or a symbolic link is in the way). Nothing was written.",
                );
            }
        }
        if (! is_dir($current)) {
            return ['exists' => false, 'home' => false, 'lang' => false, 'pages' => 0];
        }
        $lang = $current . '/lang.json';
        if (is_link($lang) || (file_exists($lang) && ! is_file($lang))) {
            throw new PortableConfigurationException('LOCALE_ADD_CONTENT_ROOT_UNSAFE', "[$contentRoot/lang.json] is not an ordinary file. Nothing was written.");
        }
        $pages = 0;
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($current, \FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if ($file->isLink()) {
                throw new PortableConfigurationException('LOCALE_ADD_CONTENT_ROOT_UNSAFE', "[$contentRoot] contains a symbolic link. Nothing was written.");
            }
            if ($file->isFile() && $file->getExtension() === 'md') {
                $pages++;
            }
        }

        $home = false;
        $index = $current . '/index.md';
        if (is_file($index) && ! is_link($index)) {
            $document = (new FrontMatterParser)->parse((string) file_get_contents($index), $contentRoot . '/index.md');
            $home = ($document->metadata['draft'] ?? false) !== true;
        }

        return ['exists' => true, 'home' => $home, 'lang' => is_file($lang), 'pages' => $pages];
    }

    /** The source home page with `draft: true`, or a bare draft title when there is none. */
    private function draftHome(string $root, string $sourceHome, string $label): string
    {
        $path = $root . '/' . $sourceHome;
        $markdown = is_file($path) && ! is_link($path) ? file_get_contents($path) : false;
        if (! is_string($markdown) || $markdown === '') {
            return "---\ndraft: true\n---\n\n# " . $label . "\n";
        }
        $lines = preg_split('/\R/u', $markdown) ?: [];
        if (($lines[0] ?? null) === '---') {
            $kept = ['---', 'draft: true'];
            $inFrontMatter = true;
            foreach (array_slice($lines, 1) as $line) {
                if ($inFrontMatter && $line === '---') {
                    $inFrontMatter = false;
                } elseif ($inFrontMatter && preg_match('/^draft:/', $line) === 1) {
                    continue;
                }
                $kept[] = $line;
            }

            return implode("\n", $kept);
        }

        return "---\ndraft: true\n---\n\n" . $markdown;
    }

    /**
     * Makes $tag the default locale. URLs become prefixed for every locale
     * (the root redirects to the default), and each published page of the old
     * default locale that had an address without a prefix keeps it as a
     * redirect in redirects.json to the same page under its prefix.
     *
     * @param  array<string,mixed>  $site
     * @return array{file:?string,added:list<array{from:string,to:string}>,retargeted:list<array{from:string,before:string,after:string}>,document?:array<string,mixed>}
     */
    private function switchDefault(
        string $root,
        array $site,
        stdClass $document,
        LocaleRegistry $registry,
        LocaleRoutingPolicy $routing,
        LocaleDefinition $oldDefault,
        string $tag,
        string $contentRoot,
    ): array {
        $oldTag = $oldDefault->tag->value();
        $document->default_locale = $tag;
        if (($document->content_root ?? null) === $oldDefault->contentRoot) {
            $document->content_root = $contentRoot;
        }

        $wasUnprefixed = ! $routing->isSymmetric();
        $freezeLegacy = $routing->isSymmetric() && $routing->legacyUnprefixedRedirects;
        $oldPrefix = $wasUnprefixed ? strtolower($oldTag) : trim($oldDefault->publicPrefix, '/');
        if ($wasUnprefixed) {
            foreach (get_object_vars($document->locales) as $key => $record) {
                if ($record instanceof stdClass && LocaleTag::isWellFormed((string) $key) && LocaleTag::from((string) $key)->value() === $oldTag) {
                    $record->public_prefix = $oldPrefix;
                }
            }
            $previous = $document->locale_routing ?? null;
            $document->locale_routing = (object) [
                'strategy' => LocaleRoutingPolicy::STRATEGY_PREFIXED,
                'root' => LocaleRoutingPolicy::ROOT_REDIRECT,
                'detect_browser_language' => $previous instanceof stdClass ? (bool) ($previous->detect_browser_language ?? false) : false,
            ];
        } elseif ($freezeLegacy) {
            // The generated redirects follow the default locale; written down,
            // they keep pointing at the pages they pointed at before.
            $document->locale_routing->legacy_unprefixed_redirects = false;
        }
        if (! $wasUnprefixed && ! $freezeLegacy) {
            return ['file' => null, 'added' => [], 'retargeted' => []];
        }

        $routes = $this->publishedRoutes($root, $site, $registry, $oldDefault);
        $file = is_string($site['redirects_file'] ?? null) ? $site['redirects_file'] : self::DEFAULT_REDIRECTS_FILE;
        $existingBytes = null;
        $entries = [];
        $redirectDocument = ['schema' => 'docara.redirects.v1', 'version' => 1, 'redirects' => []];
        $absolute = $root . '/' . $file;
        if (is_link($absolute) || (file_exists($absolute) && ! is_file($absolute))) {
            throw new PortableConfigurationException('LOCALE_DEFAULT_REDIRECTS_INVALID', "[$file] is not an ordinary file. Nothing was written.");
        }
        if (is_file($absolute)) {
            $existingBytes = $this->read($root, $file, 'LOCALE_DEFAULT_REDIRECTS_INVALID');
            try {
                $redirectDocument = json_decode($existingBytes, true, 512, JSON_THROW_ON_ERROR);
                (new SchemaRepository)->assertValid($redirectDocument, 'redirects.schema.json');
            } catch (Throwable $exception) {
                throw new PortableConfigurationException(
                    'LOCALE_DEFAULT_REDIRECTS_INVALID',
                    "[$file] cannot be read as a redirect list (" . $this->describe($exception) . '). Nothing was written.',
                    $exception,
                );
            }
            $entries = $redirectDocument['redirects'];
        }
        if (! isset($site['redirects_file'])) {
            $document->redirects_file = $file;
        }

        $retargeted = [];
        if ($wasUnprefixed) {
            // A redirect to a page of the old default locale follows the page
            // to its new prefixed address; no redirect may target a redirect.
            $known = array_fill_keys($routes, true);
            foreach ($entries as $index => $entry) {
                $to = (string) $entry['to'];
                if ($to === '' || isset($known[$to])) {
                    $after = $to === '' ? $oldPrefix : $oldPrefix . '/' . $to;
                    $retargeted[] = ['from' => (string) $entry['from'], 'before' => $to, 'after' => $after];
                    $entries[$index]['to'] = $after;
                }
            }
        }

        $byFrom = [];
        foreach ($entries as $entry) {
            $byFrom[(string) $entry['from']] = (string) $entry['to'];
        }
        $added = [];
        foreach ($routes as $route) {
            $to = $oldPrefix . '/' . $route;
            if (isset($byFrom[$route])) {
                if ($byFrom[$route] === $to) {
                    continue;
                }
                throw new PortableConfigurationException(
                    'LOCALE_DEFAULT_REDIRECT_CONFLICT',
                    "[$file] already sends [$route] to [{$byFrom[$route]}], but the page of [$oldTag] at that address moves to [$to]. Nothing was written.",
                );
            }
            $added[] = ['from' => $route, 'to' => $to];
            $entries[] = ['from' => $route, 'to' => $to];
        }
        $redirectDocument['redirects'] = array_values($entries);
        (new SchemaRepository)->assertValid($redirectDocument, 'redirects.schema.json');

        $result = ['file' => $file, 'added' => $added, 'retargeted' => $retargeted];
        if ($added !== [] || $retargeted !== []) {
            $result['document'] = [
                'path' => $file,
                'action' => $existingBytes === null ? 'create' : 'update',
                'contents' => $this->redirectsJson($redirectDocument, $this->indent($existingBytes ?? '', 2)),
                'expected' => $existingBytes,
            ];
        }

        return $result;
    }

    /**
     * Routes (without the locale prefix) of the pages the old default locale
     * publishes, as the build gives them: from the file path or `slug`, without
     * drafts, the home page and the site's 404 page.
     *
     * @param  array<string,mixed>  $site
     * @return list<string>
     */
    private function publishedRoutes(string $root, array $site, LocaleRegistry $registry, LocaleDefinition $locale): array
    {
        $loader = new PortableConfigurationLoader($root);
        $notFound = ($site['not_found']['enabled'] ?? null) !== false ? rtrim($locale->contentRoot, '/') . '/404.md' : null;
        $routes = [];
        foreach ((new PageSourceLocator($root, $registry))->forLocale($locale->tag->value()) as $source) {
            /** @var PageSource $source */
            if ($source->path === $notFound) {
                continue;
            }
            $plan = $loader->resolve($source->path);
            if (($plan->frontMatter['draft'] ?? false) === true) {
                continue;
            }
            $slug = $plan->configuration['slug'] ?? null;
            $route = is_string($slug) && trim($slug, '/') !== '' ? trim(str_replace('\\', '/', $slug), '/') : $source->route;
            if ($route !== '') {
                $routes[$route] = $route;
            }
        }
        ksort($routes, SORT_STRING);

        return array_values($routes);
    }

    private function langJson(string $language): string
    {
        if (in_array($language, self::BUNDLED_LANG, true)) {
            $stub = dirname(__DIR__, 2) . '/stubs/minimal/' . $language . '/content/' . $language . '/lang.json';
            $bytes = is_file($stub) ? file_get_contents($stub) : false;
            if (is_string($bytes) && $bytes !== '') {
                return $bytes;
            }
        }
        $lang = ['schema' => 'docara.lang.v1', 'version' => 1];
        (new SchemaRepository)->assertValid($lang, 'lang.schema.json');

        return $this->encodePretty($lang, 4);
    }

    /**
     * @param  list<array{path:string,action:string,contents:string,expected:?string}>  $writes
     */
    private function commit(string $root, string $contentRoot, bool $contentExists, array $writes): void
    {
        $createdDirectories = [];
        $temporaries = [];
        $done = [];
        try {
            if (! $contentExists) {
                $current = $root;
                foreach (explode('/', $contentRoot) as $segment) {
                    $current .= '/' . $segment;
                    if (is_dir($current) && ! is_link($current)) {
                        continue;
                    }
                    if (! @mkdir($current, 0755)) {
                        throw new PortableConfigurationException('LOCALE_ADD_WRITE_FAILED', "Folder [$contentRoot] could not be created.");
                    }
                    $createdDirectories[] = $current;
                }
            }
            foreach ($writes as $index => $write) {
                $target = $root . '/' . $write['path'];
                $now = file_exists($target) || is_link($target) ? $this->read($root, $write['path'], 'LOCALE_ADD_WRITE_FAILED') : null;
                if ($now !== $write['expected']) {
                    throw new PortableConfigurationException(
                        'LOCALE_ADD_CHANGED_DURING_WRITE',
                        "[{$write['path']}] changed while the locale was being added.",
                    );
                }
                $temporary = dirname($target) . '/.docara-locale-' . bin2hex(random_bytes(8));
                if (@file_put_contents($temporary, $write['contents'], LOCK_EX) !== strlen($write['contents'])) {
                    @unlink($temporary);
                    throw new PortableConfigurationException('LOCALE_ADD_WRITE_FAILED', "[{$write['path']}] could not be written.");
                }
                $temporaries[$index] = $temporary;
            }
            foreach ($writes as $index => $write) {
                if (! @rename($temporaries[$index], $root . '/' . $write['path'])) {
                    throw new PortableConfigurationException('LOCALE_ADD_WRITE_FAILED', "[{$write['path']}] could not be written.");
                }
                unset($temporaries[$index]);
                $done[] = $write;
            }
        } catch (Throwable $exception) {
            foreach ($temporaries as $temporary) {
                @unlink($temporary);
            }
            foreach (array_reverse($done) as $write) {
                $target = $root . '/' . $write['path'];
                if ($write['expected'] === null) {
                    @unlink($target);
                } else {
                    @file_put_contents($target, $write['expected']);
                }
            }
            foreach (array_reverse($createdDirectories) as $directory) {
                @rmdir($directory);
            }

            throw $exception;
        }
    }

    private function read(string $root, string $relative, string $code): string
    {
        $path = $root . '/' . $relative;
        $stat = @lstat($path);
        if (! is_array($stat) || is_link($path) || ! is_file($path) || ($stat['nlink'] ?? 1) !== 1) {
            throw new PortableConfigurationException($code, "[$relative] is not an ordinary file. Nothing was written.");
        }
        $bytes = file_get_contents($path);
        if (! is_string($bytes)) {
            throw new PortableConfigurationException($code, "[$relative] could not be read. Nothing was written.");
        }

        return $bytes;
    }

    /** @return array<string,mixed> */
    private function assoc(stdClass $document): array
    {
        return json_decode(json_encode($document, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), true, 512, JSON_THROW_ON_ERROR);
    }

    /** docara.json keeps its key order, indentation and final newline. */
    private function encodeLike(stdClass $document, string $original): string
    {
        $json = $this->encodePretty($document, $this->indent($original, 4));

        return str_ends_with($original, "\n") ? $json : rtrim($json, "\n");
    }

    private function encodePretty(mixed $value, int|string $indent): string
    {
        $json = json_encode($value, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);
        $unit = is_int($indent) ? str_repeat(' ', $indent) : $indent;
        if ($unit !== '    ') {
            $json = preg_replace_callback('/^(?: {4})+/m', static fn (array $match): string => str_repeat($unit, intdiv(strlen($match[0]), 4)), $json) ?? $json;
        }

        return $json . "\n";
    }

    /** One redirect per line, the way people write the file by hand. */
    private function redirectsJson(array $document, int|string $indent): string
    {
        $unit = is_int($indent) ? str_repeat(' ', $indent) : $indent;
        $flags = JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
        $lines = [];
        foreach ($document['redirects'] as $redirect) {
            $lines[] = $unit . $unit . '{"from": ' . json_encode((string) $redirect['from'], $flags) . ', "to": ' . json_encode((string) $redirect['to'], $flags) . '}';
        }
        $head = $document;
        unset($head['redirects']);
        $out = "{\n";
        foreach ($head as $key => $value) {
            $out .= $unit . json_encode((string) $key, $flags) . ': ' . json_encode($value, $flags) . ",\n";
        }
        $out .= $unit . '"redirects": ' . ($lines === [] ? '[]' : "[\n" . implode(",\n", $lines) . "\n" . $unit . ']') . "\n}\n";

        return $out;
    }

    /** The indentation unit of a JSON file, or $default when it has none. */
    private function indent(string $json, int $default): int|string
    {
        if (preg_match('/^([ \t]+)\S/m', $json, $match) === 1) {
            return str_starts_with($match[1], "\t") ? "\t" : strlen($match[1]);
        }

        return $default;
    }

    private function warning(string $code, string $message, string $path, string $pointer, string $suggestion): Diagnostic
    {
        return new Diagnostic($code, 'warning', $message, $path, $pointer, owner: 'docara.application', suggestion: $suggestion);
    }

    private function describe(Throwable $exception): string
    {
        // A Docara exception message already starts with its [CODE].
        return ($exception instanceof JsonException ? 'JSON: ' : '') . $exception->getMessage();
    }
}
