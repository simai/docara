<?php

declare(strict_types=1);

namespace Simai\Docara\PortableSite;

use Simai\Docara\Declarative\DeclarativePageResult;
use Simai\Docara\Declarative\Rendering\PublisherChromeRenderer;
use Simai\Docara\Declarative\Rendering\RenderArtifact;
use Simai\Docara\Declarative\Rendering\SmartRenderer;
use Simai\Docara\Declarative\Rendering\TrustedTemplateRegistry;
use Simai\Docara\Declarative\Rendering\View\PortablePageViewModel;
use Simai\Docara\Declarative\Rendering\View\PublisherChromeViewModel;
use Simai\Docara\Declarative\Smart\CompositeSmartPlanResolver;
use Simai\Docara\Framework\FrameworkAssetPlan;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\Preferences\ReaderPreferenceCompiler;
use Simai\Docara\Smart\SmartRegistry;

final readonly class DeclarativePortablePagePublisher implements PortablePagePublisher
{
    private SmartRegistry $smarts;

    private CompositeSmartPlanResolver $composites;

    private SmartRenderer $smartRenderer;

    private ReaderPreferenceCompiler $preferenceCompiler;

    public function __construct(
        private TrustedTemplateRegistry $templates = new TrustedTemplateRegistry,
        ?SmartRegistry $smarts = null,
        private PublisherChromeRenderer $chrome = new PublisherChromeRenderer,
        ?CompositeSmartPlanResolver $composites = null,
        ?SmartRenderer $smartRenderer = null,
        ?ReaderPreferenceCompiler $preferenceCompiler = null,
    ) {
        $this->smarts = $smarts ?? SmartRegistry::bundled();
        $this->composites = $composites ?? new CompositeSmartPlanResolver(smarts: $this->smarts);
        $this->smartRenderer = $smartRenderer ?? new SmartRenderer;
        $this->preferenceCompiler = $preferenceCompiler ?? new ReaderPreferenceCompiler;
    }

    public function id(): string
    {
        return 'docara.declarative_page_publisher.v1';
    }

    public function render(
        array $page,
        array $navigation,
        string $siteTitle,
        FrameworkAssetPlan $assets,
        ?DeclarativePageResult $declarative,
    ): string {
        if (! $declarative instanceof DeclarativePageResult) {
            throw new PortableConfigurationException(
                'DECLARATIVE_PRIMARY_INPUT_REQUIRED',
                "Page [{$page['url']}] has no declarative publication input.",
            );
        }
        $regions = $declarative->artifact->hydration['regions'] ?? null;
        if (! is_array($regions)
            || array_keys($regions) !== ['header', 'sidebar', 'main', 'outline', 'footer']
        ) {
            throw new PortableConfigurationException(
                'DECLARATIVE_PRIMARY_REGIONS_REQUIRED',
                "Page [{$page['url']}] has no complete declarative region projection.",
            );
        }
        if (($page['outline'] ?? []) === []) {
            $regions['outline'] = '';
        }
        $regions['sidebar_mobile'] = $this->mobileClone($regions['sidebar']);
        $regions['outline_mobile'] = $this->mobileOutlineClone($regions['outline']);
        $copy = is_array($page['ui_copy'] ?? null) ? $page['ui_copy'] : [];
        if ($copy === []) {
            throw new PortableConfigurationException(
                'DECLARATIVE_PRIMARY_COPY_REQUIRED',
                "Page [{$page['url']}] has no resolved language-pack copy.",
            );
        }
        $headerNavigation = is_array($page['header_navigation'] ?? null)
            && array_is_list($page['header_navigation'])
            ? $page['header_navigation']
            : [];
        $mobileHeaderNavigation = $this->mobileHeaderNavigation($headerNavigation, $copy, (string) $page['url']);
        $regions['header_navigation_mobile'] = $mobileHeaderNavigation?->html ?? '';
        $branding = is_array($page['branding'] ?? null) ? $page['branding'] : [];
        $brandTitle = (string) ($branding['title'] ?? $siteTitle);
        $configuredTheme = in_array($page['theme'] ?? null, ['system', 'light', 'dark'], true)
            ? (string) $page['theme']
            : 'system';
        $searchEnabled = ($page['search_enabled'] ?? false) === true
            && is_string($page['search_runtime_url'] ?? null)
            && is_string($page['search_index_url'] ?? null);
        $assetBase = rtrim((string) $page['home_url'], '/') . '/_docara';
        if (! str_starts_with($assetBase, '/')) {
            throw new PortableConfigurationException(
                'DECLARATIVE_PRIMARY_ASSET_BASE_INVALID',
                "Page [{$page['url']}] has an unsafe publisher asset base.",
            );
        }
        $escapedCopy = [];
        foreach ($copy as $id => $message) {
            if (is_string($id) && is_string($message)) {
                $escapedCopy[$id] = $this->escape($message);
            }
        }
        $readerPreferences = $this->preferenceCompiler->compile(
            is_array($page['reader_preferences'] ?? null) ? $page['reader_preferences'] : [],
            ['appearance.theme' => $configuredTheme],
            $copy,
            is_string($page['reader_preferences_storage_key'] ?? null)
                ? $page['reader_preferences_storage_key']
                : ReaderPreferenceCompiler::storageKey(['base_url' => '/']),
        );
        $readerPreferencesArtifact = null;
        if ($readerPreferences['enabled']) {
            $readerPreferencesArtifact = $this->smartRenderer->render(
                $this->composites->resolve(
                    'docara.preferences',
                    'reader-preferences-' . substr(hash('sha256', (string) $page['url']), 0, 20),
                    [
                        'position' => ($page['direction'] ?? 'ltr') === 'rtl' ? 'left' : 'right',
                        'title' => (string) $copy['reader.title'],
                        'close_label' => (string) $copy['reader.close'],
                        'reset_label' => (string) $copy['reader.reset'],
                        'groups' => $readerPreferences['groups'],
                        'manifest' => $readerPreferences,
                    ],
                    'side-panel',
                ),
            );
        }
        $breadcrumbs = $this->breadcrumbs(is_array($page['breadcrumbs'] ?? null) ? $page['breadcrumbs'] : []);
        $previous = $this->readingLink(is_array($page['previous'] ?? null) ? $page['previous'] : null);
        $next = $this->readingLink(is_array($page['next'] ?? null) ? $page['next'] : null);
        $languageOptions = $this->languageOptions(is_array($page['language_options'] ?? null) ? $page['language_options'] : []);
        $preset = (string) $page['preset'] === 'landing' ? 'landing' : 'docs';
        $containerMax = $page['container_max'] ?? null;
        if (! is_int($containerMax) || $containerMax < 1 || $containerMax > 8) {
            throw new PortableConfigurationException(
                'DECLARATIVE_CONTAINER_MAX_INVALID',
                "Page [{$page['url']}] must resolve layout.container.max to an integer from 1 through 8.",
            );
        }
        $contentGap = $page['content_gap'] ?? null;
        if (! is_int($contentGap) || $contentGap < 0 || $contentGap > 8) {
            throw new PortableConfigurationException(
                'DECLARATIVE_CONTENT_GAP_INVALID',
                "Page [{$page['url']}] must resolve layout.content.gap to an integer from 0 through 8.",
            );
        }
        $mobileTocState = $regions['outline'] === '' ? 'unavailable' : $this->mobileTocState($page);
        $primaryNavigationEnabled = $regions['header_navigation_mobile'] !== '';
        $documentationNavigationEnabled = $preset === 'docs' && $regions['sidebar_mobile'] !== '';
        $mobileNavigationEnabled = $primaryNavigationEnabled || $documentationNavigationEnabled;
        $chrome = $this->chrome->render(new PublisherChromeViewModel(
            $preset,
            in_array($page['direction'] ?? null, ['ltr', 'rtl'], true) ? (string) $page['direction'] : 'ltr',
            $searchEnabled,
            $searchEnabled ? $this->escape((string) $page['search_runtime_url']) : null,
            $searchEnabled ? $this->escape((string) $page['search_index_url']) : null,
            $regions,
            $mobileTocState === 'shown',
            $breadcrumbs,
            $previous,
            $next,
            $escapedCopy,
            $languageOptions,
            $mobileNavigationEnabled,
            $primaryNavigationEnabled,
            $documentationNavigationEnabled,
            $readerPreferences['enabled'],
            $readerPreferencesArtifact?->html ?? '',
        ));

        $view = new PortablePageViewModel(
            $this->escape((string) $page['locale']),
            in_array($page['direction'] ?? null, ['ltr', 'rtl'], true) ? (string) $page['direction'] : 'ltr',
            $this->escape((string) ($page['documentation_version'] ?? 'current')),
            $this->escape((string) $page['title'] . ' — ' . $brandTitle),
            trim((string) ($page['description'] ?? '')) === ''
                ? null
                : $this->escape((string) $page['description']),
            is_string($branding['favicon'] ?? null)
                ? $this->escape($branding['favicon'])
                : null,
            is_string($branding['favicon_type'] ?? null)
                ? $this->escape($branding['favicon_type'])
                : null,
            $assets->headHtml() . $this->smartAssetHead(
                array_values(array_unique([
                    ...$declarative->artifact->assets,
                    ...($mobileHeaderNavigation?->assets ?? []),
                    ...($readerPreferencesArtifact?->assets ?? []),
                ])),
                $assetBase,
            ),
            $this->preferencesBootstrap($readerPreferences),
            $preset,
            'max-container-' . $containerMax,
            'gap-' . $contentGap,
            $mobileTocState,
            $searchEnabled,
            $searchEnabled ? $this->escape((string) $page['search_runtime_url']) : null,
            $searchEnabled ? $this->escape((string) $page['search_index_url']) : null,
            $this->publisherAssetUrl($assetBase, 'declarative-shell.css'),
            $this->publisherAssetUrl($assetBase, 'declarative-shell.js'),
            $regions,
            $breadcrumbs,
            $previous,
            $next,
            $escapedCopy,
            $this->escape((string) ($page['canonical_url'] ?? $page['url'])),
            $this->alternates(is_array($page['alternates'] ?? null) ? $page['alternates'] : []),
            $languageOptions,
            json_encode($copy, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP),
            $chrome,
        );

        return $this->templates->render('publisher.docara.page', ['view' => $view]);
    }

    private function publisherAssetUrl(string $assetBase, string $name): string
    {
        $path = dirname(__DIR__, 2) . '/resources/portable/' . $name;
        $hash = is_file($path) && ! is_link($path) ? hash_file('sha256', $path) : false;
        if (! is_string($hash) || preg_match('/^[a-f0-9]{64}$/', $hash) !== 1) {
            throw new PortableConfigurationException(
                'DECLARATIVE_PUBLISHER_ASSET_INVALID',
                "Declarative publisher asset [$name] cannot be versioned.",
            );
        }

        return $this->escape($assetBase . '/' . $name . '?docara_v=' . $hash);
    }

    /** @param array<string, mixed> $page */
    private function mobileTocState(array $page): string
    {
        $outline = is_array($page['outline'] ?? null) ? $page['outline'] : [];
        if ($outline === []) {
            return 'unavailable';
        }
        $mode = in_array($page['reading_mobile_toc'] ?? null, ['auto', 'always', 'never'], true)
            ? (string) $page['reading_mobile_toc']
            : 'auto';
        if ($mode !== 'auto') {
            return $mode === 'always' ? 'shown' : 'disabled';
        }
        if (count($outline) >= 4) {
            return 'shown';
        }
        foreach ($outline as $item) {
            if (is_array($item) && (int) ($item['level'] ?? 0) >= 3) {
                return 'shown';
            }
        }

        return 'auto-hidden';
    }

    /** @param list<array<string, mixed>> $items
     * @return list<array{title:string,url:?string,current:bool}>
     */
    private function breadcrumbs(array $items): array
    {
        $result = [];
        $last = count($items) - 1;
        foreach ($items as $index => $item) {
            $result[] = [
                'title' => $this->escape((string) ($item['title'] ?? '')),
                'url' => is_string($item['url'] ?? null) ? $this->escape($item['url']) : null,
                'current' => $index === $last,
            ];
        }

        return $result;
    }

    /** @param array<string, mixed>|null $link
     * @return array{title:string,url:string}|null
     */
    private function readingLink(?array $link): ?array
    {
        if (! is_string($link['url'] ?? null)) {
            return null;
        }

        return [
            'title' => $this->escape((string) ($link['title'] ?? '')),
            'url' => $this->escape($link['url']),
        ];
    }

    /** @param list<array<string, mixed>> $alternates @return list<array{locale:string,url:string}> */
    private function alternates(array $alternates): array
    {
        $resolved = [];
        foreach ($alternates as $alternate) {
            if (is_string($alternate['locale'] ?? null) && is_string($alternate['url'] ?? null)) {
                $resolved[] = [
                    'locale' => $this->escape($alternate['locale']),
                    'url' => $this->escape($alternate['url']),
                ];
            }
        }

        return $resolved;
    }

    /** @param list<array<string, mixed>> $options @return list<array{locale:string,label:string,url:string,current:bool}> */
    private function languageOptions(array $options): array
    {
        $resolved = [];
        foreach ($options as $option) {
            if (is_string($option['locale'] ?? null)
                && is_string($option['label'] ?? null)
                && is_string($option['url'] ?? null)
            ) {
                $resolved[] = [
                    'locale' => $this->escape($option['locale']),
                    'label' => $this->escape($option['label']),
                    'url' => $this->escape($option['url']),
                    'current' => ($option['current'] ?? false) === true,
                ];
            }
        }

        return $resolved;
    }

    /** @param array<string, mixed> $manifest */
    private function preferencesBootstrap(array $manifest): string
    {
        $json = json_encode(
            $manifest,
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT,
        );

        return '<script data-docara-preferences-bootstrap>(function(){var manifest=' . $json
            . ',key=manifest.storage_key,fields={},defaults=manifest.values||{},volatile={};'
            . 'manifest.groups.forEach(function(group){group.fields.forEach(function(field){fields[field.id]=field})});'
            . "function frameworkMemory(){return document.documentElement.dataset.docaraFrameworkStorage==='memory'}"
            . "function clean(values){var result={};if(!values||typeof values!=='object'||Array.isArray(values))return result;Object.keys(values).forEach(function(id){var field=fields[id],value=values[id];if(field&&field.values.indexOf(value)!==-1&&value!==defaults[id])result[id]=value});return result}"
            . 'function stored(){if(!manifest.enabled||frameworkMemory())return{};try{var raw=window.localStorage.getItem(key);if(!raw)return{};var value=JSON.parse(raw);return value&&value.schema===manifest.schema?clean(value.values):{}}catch(error){return{}}}'
            . 'function overrides(){return Object.assign({},stored(),volatile)}'
            . "function current(id){var value=overrides()[id];return typeof value==='string'?value:defaults[id]}"
            . "function applyTheme(mode,source){if(['system','light','dark'].indexOf(mode)===-1)mode='system';var dark=mode==='dark'||(mode==='system'&&window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)').matches);var root=document.documentElement;root.classList.remove('theme-light','theme-dark');root.classList.add(dark?'theme-dark':'theme-light');root.dataset.docaraThemePreference=mode;root.dataset.docaraThemeSource=source}"
            . "var effects={'docara.theme':applyTheme};"
            . 'function applyField(id,source){var field=fields[id],effect=field&&effects[field.effect];if(effect)effect(current(id),source)}'
            . 'function applyAll(source){Object.keys(fields).forEach(function(id){applyField(id,source)})}'
            . 'function write(values){if(frameworkMemory())return false;try{var ids=Object.keys(values);if(ids.length){window.localStorage.setItem(key,JSON.stringify({schema:manifest.schema,values:values}))}else{window.localStorage.removeItem(key)}var verify=stored();return JSON.stringify(verify)===JSON.stringify(clean(values))}catch(error){return false}}'
            . "function set(id,value){var field=fields[id];if(!manifest.enabled||!field||field.values.indexOf(value)===-1)return{applied:false,persisted:false};var values=overrides();if(value===defaults[id])delete values[id];else values[id]=value;var persisted=write(values);volatile=persisted?{}:clean(values);applyField(id,'reader');return{applied:true,persisted:persisted}}"
            . "function reset(){volatile={};if(!frameworkMemory()){try{window.localStorage.removeItem(key)}catch(error){}}applyAll('site')}"
            . "function syncExternal(){volatile={};applyAll(Object.keys(stored()).length?'reader':'site')}"
            . 'function hasOverride(){return Object.keys(overrides()).length>0}'
            . "applyAll(Object.keys(stored()).length?'reader':'site');"
            . 'window.DocaraReaderPreferences={manifest:manifest,key:key,current:current,set:set,reset:reset,syncExternal:syncExternal,hasOverride:hasOverride};'
            . "document.dispatchEvent(new CustomEvent('docara:preferences-ready'));"
            . "var media=window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)');if(media&&media.addEventListener){media.addEventListener('change',function(){if(current('appearance.theme')==='system')applyField('appearance.theme',document.documentElement.dataset.docaraThemeSource||'site')})}"
            . 'window.SF_BOOT_CONFIG=window.SF_BOOT_CONFIG||{};window.SF_BOOT_CONFIG.preloader={enabled:false};})();</script>';
    }

    private function mobileClone(string $html): string
    {
        return preg_replace('/\\s+id="[^"]+"/', '', $html) ?? $html;
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @param  array<string, mixed>  $copy
     */
    private function mobileHeaderNavigation(array $items, array $copy, string $pageUrl): ?RenderArtifact
    {
        if ($items === []) {
            return null;
        }
        $plan = $this->composites->resolve(
            'docara.navigation',
            'header-navigation-mobile-' . substr(hash('sha256', $pageUrl), 0, 20),
            [
                'items' => $items,
                'maximum_depth' => 4,
                'label' => (string) ($copy['navigation.primary'] ?? 'Primary navigation'),
                'expand_label' => (string) ($copy['navigation.expand'] ?? 'Expand: '),
                'collapse_label' => (string) ($copy['navigation.collapse'] ?? 'Collapse: '),
                'contains_current_label' => (string) ($copy['navigation.contains_current'] ?? ', contains the current page'),
            ],
            'compact',
        );

        return $this->smartRenderer->render($plan);
    }

    private function mobileOutlineClone(string $html): string
    {
        if ($html === '') {
            return '';
        }
        $clone = $this->mobileClone($html);
        $count = 0;
        $clone = preg_replace(
            '/(<nav\\b[^>]*data-docara-smart="docara\\.toc"[^>]*>\\s*<p\\b)/',
            '$1 hidden',
            $clone,
            1,
            $count,
        ) ?? $clone;
        if ($count !== 1) {
            throw new PortableConfigurationException(
                'DECLARATIVE_MOBILE_TOC_HEADING_CONTRACT_INVALID',
                'The mobile contents clone must contain exactly one hideable duplicate heading.',
            );
        }

        return $clone;
    }

    /** @param list<string> $assetKeys */
    private function smartAssetHead(array $assetKeys, string $assetBase): string
    {
        $tags = [];
        foreach (array_values(array_unique($assetKeys)) as $key) {
            if (! str_starts_with($key, 'docara.smart.')) {
                continue;
            }
            try {
                $asset = $this->smarts->asset($key);
            } catch (\InvalidArgumentException $exception) {
                throw new PortableConfigurationException(
                    'DECLARATIVE_SMART_ASSET_NOT_REGISTERED',
                    $key,
                    $exception,
                );
            }
            $url = $this->escape(
                $assetBase . '/' . $asset['public'] . '?docara_v=' . $asset['version'],
            );
            $tags[] = $asset['kind'] === 'css'
                ? '<link rel="stylesheet" href="' . $url . '" data-docara-smart-asset="' . $this->escape($key) . '">'
                : '<script defer src="' . $url . '" data-docara-smart-asset="' . $this->escape($key) . '"></script>';
        }

        return $tags === [] ? '' : "\n" . implode("\n", $tags);
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
