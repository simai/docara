<?php

declare(strict_types=1);

namespace Simai\Docara\Framework;

use Simai\Docara\Portable\CanonicalJson;

final readonly class FrameworkAssetPlanner
{
    public function __construct(
        private FrameworkManifestRepository $repository,
        private string $assetBase,
    ) {
        $segments = explode('/', ltrim($assetBase, '/'));
        if (preg_match('#^/(?:[A-Za-z0-9._~-]+/)*[A-Za-z0-9._~-]+$#', $assetBase) !== 1
            || in_array('.', $segments, true)
            || in_array('..', $segments, true)
        ) {
            throw new FrameworkComponentException('FRAMEWORK_ASSET_BASE_INVALID', $assetBase);
        }
    }

    /** @param list<string> $componentKeys */
    public function plan(array $componentKeys): FrameworkAssetPlan
    {
        $runtime = $this->repository->runtime();
        $uiCommit = (string) $runtime['ui']['commit'];
        $smartCommit = (string) $runtime['ui_smart']['commit'];
        $uiBase = 'https://cdn.jsdelivr.net/gh/simai/ui@' . $uiCommit . '/distr';
        $boot = $runtime['boot'];
        $pairId = $this->repository->pairId();
        $projectionFingerprint = substr(
            hash('sha256', CanonicalJson::encode($this->repository->assetProjection())),
            0,
            16,
        );
        $cacheVersion = $pairId . '-' . $projectionFingerprint;
        $bootConfiguration = [
            'cacheVersion' => $cacheVersion,
            'pluginListVersion' => $pairId,
            // Docara resolves the author default and the reader's tri-state
            // preference before Core loads. Disable Core's binary OS/cookie
            // bootstrap so it cannot overwrite that resolved theme.
            'theme' => false,
            'icons' => [
                'enabled' => false,
                'accumulate' => false,
            ],
            'smart' => [
                'base' => true,
            ],
        ];

        $assets = [[
            'key' => 'docara.framework.storage.compatibility',
            'kind' => 'boot',
            'content' => $this->storageFallbackRuntime(),
        ], [
            'key' => 'simai.framework.boot',
            'kind' => 'boot',
            // Core webpack chunks are concatenated onto sfPath, so the
            // immutable distribution base must keep its trailing slash.
            'content' => 'window.SF_BOOT_CONFIG=Object.assign({},window.SF_BOOT_CONFIG||{},'
                . json_encode($bootConfiguration, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES)
                . ');window.sfPath=' . json_encode($uiBase . '/', JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES)
                . ';window.sfSmartPath=' . json_encode($this->assetBase, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES) . ';',
        ], [
            'key' => 'simai.framework.core.css',
            'kind' => 'css',
            'url' => $this->uiUrl($uiCommit, (string) $boot['css']),
        ], [
            'key' => 'simai.framework.utility.full.css',
            'kind' => 'css',
            'url' => $uiBase . '/core/css/utility.full.css',
        ], [
            'key' => 'simai.framework.icon_font.css',
            'kind' => 'inline_css',
            'content' => $this->iconFallbackCss($uiBase . '/fonts/MaterialSymbols-Outlined.woff2'),
            'source_revision' => $uiCommit,
        ], [
            'key' => 'simai.framework.icon_font.ready',
            'kind' => 'boot',
            'content' => $this->iconFallbackReadyRuntime(),
        ], [
            'key' => 'simai.framework.smart_base.js',
            'kind' => 'javascript',
            'url' => $this->uiUrl($uiCommit, (string) $boot['smart_base']),
        ], [
            'key' => 'simai.framework.core.js',
            'kind' => 'javascript',
            'url' => $this->uiUrl($uiCommit, (string) $boot['javascript']),
        ]];

        $tags = [];
        foreach (array_values(array_unique($componentKeys)) as $key) {
            $manifest = $this->repository->get($key);
            $tag = $manifest['frontend']['tag'];
            if (! is_string($tag)) {
                throw new FrameworkComponentException('FRAMEWORK_COMPONENT_TAG_INVALID', $key);
            }
            $tags[] = $tag;
        }
        sort($tags, SORT_STRING);

        $orderedTags = [];
        if (in_array('sf-alert', $tags, true) || in_array('sf-button', $tags, true)) {
            $orderedTags[] = 'sf-icon';
        }
        foreach ($tags as $tag) {
            $orderedTags[] = $tag;
        }

        foreach (array_values(array_unique($orderedTags)) as $tag) {
            $component = $runtime['components'][$tag] ?? null;
            if (! is_array($component) || ! is_string($component['javascript'] ?? null)) {
                throw new FrameworkComponentException('FRAMEWORK_RUNTIME_COMPONENT_MISSING', $tag);
            }
            if (is_string($component['css'] ?? null) && $component['css'] !== '') {
                $projectedAsset = $this->smartAsset($component['css'], $cacheVersion);
                $assets[] = [
                    'key' => 'simai.framework.' . str_replace('-', '_', $tag) . '.css',
                    'kind' => 'css',
                    'url' => $projectedAsset['url'],
                    'source_revision' => $smartCommit,
                    'sha256' => $projectedAsset['sha256'],
                ];
            }
            $projectedAsset = $this->smartAsset($component['javascript'], $cacheVersion);
            $assets[] = [
                'key' => 'simai.framework.' . str_replace('-', '_', $tag) . '.js',
                'kind' => 'smart_javascript',
                'url' => $projectedAsset['url'],
                'source_revision' => $smartCommit,
                'sha256' => $projectedAsset['sha256'],
            ];
        }

        $this->assertImmutable($assets);

        return new FrameworkAssetPlan($this->repository->pairId(), $assets);
    }

    private function uiUrl(string $commit, string $lockedPath): string
    {
        $prefix = 'ui/distr/';
        if (! str_starts_with($lockedPath, $prefix)) {
            throw new FrameworkComponentException('FRAMEWORK_UI_ASSET_PATH_INVALID', $lockedPath);
        }

        return 'https://cdn.jsdelivr.net/gh/simai/ui@' . $commit . '/distr/' . substr($lockedPath, strlen($prefix));
    }

    /** @return array{url: string, sha256: string} */
    private function smartAsset(string $lockedPath, string $cacheVersion): array
    {
        $prefix = 'smart/';
        if (! str_starts_with($lockedPath, $prefix)) {
            throw new FrameworkComponentException('FRAMEWORK_SMART_ASSET_PATH_INVALID', $lockedPath);
        }

        $relativePath = substr($lockedPath, strlen($prefix));
        $sha256 = hash('sha256', $this->repository->bundledAsset($relativePath));

        return [
            'url' => $this->assetBase . '/' . $relativePath . '?sf_v=' . rawurlencode($cacheVersion),
            'sha256' => $sha256,
        ];
    }

    private function iconFallbackCss(string $fontUrl): string
    {
        return '@font-face{font-family:"Docara Material Symbols";src:url("' . $fontUrl
            . '") format("woff2");font-style:normal;font-weight:100 700;font-display:block}'
            . 'html body sf-icon > i.sf-icon{font-family:"Docara Material Symbols"!important;'
            . 'font-feature-settings:"liga"!important;font-variation-settings:"FILL" var(--sf-icon--fill,0),'
            . '"wght" var(--sf-icon--weight,400),"GRAD" var(--sf-icon--grade,0),'
            . '"opsz" var(--sf-icon--optical-size,24)}';
    }

    private function iconFallbackReadyRuntime(): string
    {
        return '(function(){var selector="sf-icon > i.sf-icon:not(.sf-icon-loaded)";'
            . 'function mark(root){if(root.nodeType===1&&root.matches(selector)){root.classList.add("sf-icon-loaded")}if(root.querySelectorAll){root.querySelectorAll(selector).forEach(function(icon){icon.classList.add("sf-icon-loaded")})}}'
            . 'function watch(){mark(document);if(!document.body)return;new MutationObserver(function(records){records.forEach(function(record){record.addedNodes.forEach(mark)})}).observe(document.body,{childList:true,subtree:true})}'
            . 'function start(){var ready=document.fonts&&document.fonts.load?document.fonts.load("400 24px \\"Docara Material Symbols\\""):Promise.resolve([true]);ready.then(function(faces){if(faces&&faces.length){document.documentElement.dataset.docaraFullFontReady="true";watch()}}).catch(function(){})}'
            . 'if(document.readyState==="loading"){document.addEventListener("DOMContentLoaded",start,{once:true})}else{start()}})();';
    }

    private function storageFallbackRuntime(): string
    {
        return "(function(){function nativeStorage(){var storage,probe='__docara_sf_probe_'+Math.random().toString(36).slice(2);try{storage=window.localStorage;if(!storage)return null;storage.setItem(probe,'1');return storage.getItem(probe)==='1'?storage:null}catch(error){return null}finally{if(storage){try{storage.removeItem(probe)}catch(error){}}}}"
            . "if(nativeStorage())return;var values=Object.create(null),keys=[];var storage={key:function(index){index=Number(index);return Number.isInteger(index)&&index>=0&&index<keys.length?keys[index]:null},getItem:function(key){key=String(key);return Object.prototype.hasOwnProperty.call(values,key)?values[key]:null},setItem:function(key,value){key=String(key);if(!Object.prototype.hasOwnProperty.call(values,key)){keys.push(key)}values[key]=String(value)},removeItem:function(key){key=String(key);if(!Object.prototype.hasOwnProperty.call(values,key))return;delete values[key];keys.splice(keys.indexOf(key),1)},clear:function(){values=Object.create(null);keys=[]}};Object.defineProperty(storage,'length',{enumerable:true,get:function(){return keys.length}});"
            . "try{Object.defineProperty(window,'localStorage',{configurable:true,enumerable:true,value:storage})}catch(error){try{window.localStorage=storage}catch(ignored){}}try{if(window.localStorage===storage){document.documentElement.dataset.docaraFrameworkStorage='memory'}}catch(error){}})();";
    }

    /** @param list<array<string, mixed>> $assets */
    private function assertImmutable(array $assets): void
    {
        foreach ($assets as $asset) {
            $haystack = strtolower((string) ($asset['url'] ?? '') . ' ' . (string) ($asset['content'] ?? ''));
            if (preg_match('~@(?:main|master|latest)(?:/|$)|/(?:main|master|latest)(?:/|$)~', $haystack) === 1) {
                throw new FrameworkComponentException('FRAMEWORK_ASSET_MOVING_REFERENCE_FORBIDDEN', (string) $asset['key']);
            }
            if (! isset($asset['url'])) {
                if (($asset['kind'] ?? null) === 'inline_css') {
                    $revision = $asset['source_revision'] ?? null;
                    if (! is_string($revision)
                        || preg_match('/^[a-f0-9]{40}$/', $revision) !== 1
                        || ! str_contains((string) ($asset['content'] ?? ''), '@' . $revision . '/')
                    ) {
                        throw new FrameworkComponentException(
                            'FRAMEWORK_ASSET_SOURCE_REVISION_REQUIRED',
                            (string) $asset['key'],
                        );
                    }
                }

                continue;
            }

            $url = (string) $asset['url'];
            if (str_starts_with($url, '/')) {
                if (! is_string($asset['source_revision'] ?? null)
                    || preg_match('/^[a-f0-9]{40}$/', $asset['source_revision']) !== 1
                    || ! is_string($asset['sha256'] ?? null)
                    || preg_match('/^[a-f0-9]{64}$/', $asset['sha256']) !== 1
                    || preg_match('/\?sf_v=sf-v[0-9.]+-[a-f0-9]{8}-[a-f0-9]{8}-[a-f0-9]{16}$/', $url) !== 1
                ) {
                    throw new FrameworkComponentException('FRAMEWORK_ASSET_SOURCE_REVISION_REQUIRED', (string) $asset['key']);
                }

                continue;
            }
            if (preg_match('/@[a-f0-9]{40}(?:\/|$)/', $url) !== 1) {
                throw new FrameworkComponentException('FRAMEWORK_ASSET_COMMIT_REQUIRED', (string) $asset['key']);
            }
        }
    }
}
