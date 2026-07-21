<?php

declare(strict_types=1);

namespace Simai\Docara\PortableSite;

use Simai\Docara\Declarative\DeclarativePageResult;
use Simai\Docara\Declarative\Rendering\PublisherChromeRenderer;
use Simai\Docara\Declarative\Rendering\TrustedTemplateRegistry;
use Simai\Docara\Declarative\Rendering\View\PortablePageViewModel;
use Simai\Docara\Declarative\Rendering\View\PublisherChromeViewModel;
use Simai\Docara\Framework\FrameworkAssetPlan;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\Smart\SmartRegistry;

final readonly class DeclarativePortablePagePublisher implements PortablePagePublisher
{
    private SmartRegistry $smarts;

    public function __construct(
        private TrustedTemplateRegistry $templates = new TrustedTemplateRegistry,
        ?SmartRegistry $smarts = null,
        private PublisherChromeRenderer $chrome = new PublisherChromeRenderer,
    ) {
        $this->smarts = $smarts ?? SmartRegistry::bundled();
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
        $regions['outline_mobile'] = $this->mobileClone($regions['outline']);
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
        $copy = is_array($page['ui_copy'] ?? null) ? $page['ui_copy'] : [];
        if ($copy === []) {
            throw new PortableConfigurationException(
                'DECLARATIVE_PRIMARY_COPY_REQUIRED',
                "Page [{$page['url']}] has no resolved language-pack copy.",
            );
        }
        $escapedCopy = [];
        foreach ($copy as $id => $message) {
            if (is_string($id) && is_string($message)) {
                $escapedCopy[$id] = $this->escape($message);
            }
        }
        $breadcrumbs = $this->breadcrumbs(is_array($page['breadcrumbs'] ?? null) ? $page['breadcrumbs'] : []);
        $previous = $this->readingLink(is_array($page['previous'] ?? null) ? $page['previous'] : null);
        $next = $this->readingLink(is_array($page['next'] ?? null) ? $page['next'] : null);
        $themeOptions = $this->themeOptions($configuredTheme, $escapedCopy);
        $languageOptions = $this->languageOptions(is_array($page['language_options'] ?? null) ? $page['language_options'] : []);
        $preset = (string) $page['preset'] === 'landing' ? 'landing' : 'docs';
        $chrome = $this->chrome->render(new PublisherChromeViewModel(
            $preset,
            $searchEnabled,
            $searchEnabled ? $this->escape((string) $page['search_runtime_url']) : null,
            $searchEnabled ? $this->escape((string) $page['search_index_url']) : null,
            $regions,
            $breadcrumbs,
            $previous,
            $next,
            $themeOptions,
            $this->escape($configuredTheme),
            $escapedCopy,
            $languageOptions,
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
                $declarative->artifact->assets,
                $assetBase,
            ),
            $this->themeBootstrap($configuredTheme),
            $preset,
            $this->escape((string) $page['max_width']),
            $searchEnabled,
            $searchEnabled ? $this->escape((string) $page['search_runtime_url']) : null,
            $searchEnabled ? $this->escape((string) $page['search_index_url']) : null,
            $this->escape($assetBase . '/declarative-shell.css'),
            $this->escape($assetBase . '/declarative-shell.js'),
            $regions,
            $breadcrumbs,
            $previous,
            $next,
            $themeOptions,
            $this->escape($configuredTheme),
            $escapedCopy,
            $this->escape((string) ($page['canonical_url'] ?? $page['url'])),
            $this->alternates(is_array($page['alternates'] ?? null) ? $page['alternates'] : []),
            $languageOptions,
            json_encode($copy, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP),
            $chrome,
        );

        return $this->templates->render('publisher.docara.page', ['view' => $view]);
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

    /** @return list<array{value:string,title:string,description:string,checked:bool}> */
    private function themeOptions(string $configured, array $copy): array
    {
        return [
            [
                'value' => 'system',
                'title' => $copy['reader.theme_system'],
                'description' => $copy['reader.theme_system_description'],
                'checked' => $configured === 'system',
            ],
            [
                'value' => 'light',
                'title' => $copy['reader.theme_light'],
                'description' => $copy['reader.theme_light_description'],
                'checked' => $configured === 'light',
            ],
            [
                'value' => 'dark',
                'title' => $copy['reader.theme_dark'],
                'description' => $copy['reader.theme_dark_description'],
                'checked' => $configured === 'dark',
            ],
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

    private function themeBootstrap(string $configured): string
    {
        $json = json_encode($configured, JSON_THROW_ON_ERROR);

        return '<script data-docara-theme-bootstrap>(function(){var configured=' . $json
            . ",key='docara.reader.theme.v1',valid=/^(system|light|dark)$/,volatile='';"
            . "function frameworkMemory(){return document.documentElement.dataset.docaraFrameworkStorage==='memory'}"
            . "function stored(){if(frameworkMemory())return'';try{var value=window.localStorage.getItem(key)||'';return valid.test(value)?value:''}catch(error){return''}}"
            . "function legacy(){var value=(document.cookie.split('; ').find(function(item){return item.indexOf('sf-theme=')===0})||'').split('=')[1]||'';return /^(light|dark)$/.test(value)?value:''}"
            . "function clearLegacy(){document.cookie='sf-theme=; Path=/; Max-Age=0; SameSite=Lax'}"
            . "function projectLegacy(mode){if(/^(light|dark)$/.test(mode)){document.cookie='sf-theme='+mode+'; Path=/; Max-Age=31536000; SameSite=Lax';return legacy()===mode}clearLegacy();return legacy()===''}"
            . "function apply(mode,source){if(!valid.test(mode)){mode='system'}var dark=mode==='dark'||(mode==='system'&&window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)').matches);var root=document.documentElement;root.classList.remove('theme-light','theme-dark');root.classList.add(dark?'theme-dark':'theme-light');root.dataset.docaraThemePreference=mode;root.dataset.docaraThemeSource=source;return mode}"
            . "function preference(){var reader=volatile||stored(),old=reader?'':legacy();return{mode:reader||old||configured,source:reader?'reader':(old?'legacy':'site')}}"
            . "function set(mode){if(!valid.test(mode)){return{applied:false,persisted:false}}var persisted=false;if(!frameworkMemory()){try{window.localStorage.setItem(key,mode);persisted=window.localStorage.getItem(key)===mode}catch(error){}}var cookiePersisted=projectLegacy(mode);if(/^(light|dark)$/.test(mode)){persisted=persisted||cookiePersisted}volatile=persisted?'':mode;apply(mode,'reader');return{applied:true,persisted:persisted}}"
            . "function reset(){volatile='';if(!frameworkMemory()){try{window.localStorage.removeItem(key)}catch(error){}}clearLegacy();apply(configured,'site')}"
            . "var initial=preference();apply(initial.mode,initial.source);window.DocaraReaderTheme={configured:configured,key:key,apply:apply,preference:preference,set:set,reset:reset,syncExternal:function(){volatile='';return preference()},hasOverride:function(){return volatile!==''||stored()!==''||legacy()!==''}};"
            . 'window.SF_BOOT_CONFIG=window.SF_BOOT_CONFIG||{};window.SF_BOOT_CONFIG.preloader={enabled:false};})();</script>';
    }

    private function mobileClone(string $html): string
    {
        return preg_replace('/\\s+id="[^"]+"/', '', $html) ?? $html;
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
            $url = $this->escape($assetBase . '/' . $asset['public']);
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
