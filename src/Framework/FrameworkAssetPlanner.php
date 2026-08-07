<?php

declare(strict_types=1);

namespace Simai\Docara\Framework;

use Simai\Docara\Portable\CanonicalJson;

final readonly class FrameworkAssetPlanner
{
    public const DOCARA_SHELL_RUNTIME_TAGS = ['sf-button', 'sf-icon', 'sf-modal'];

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

    /**
     * @param  list<string>  $componentKeys
     * @param  list<string>  $additionalRuntimeTags
     */
    public function plan(
        array $componentKeys,
        array $additionalRuntimeTags = [],
    ): FrameworkAssetPlan {
        $runtime = $this->repository->runtime();
        $uiCommit = (string) $runtime['ui']['commit'];
        $smartCommit = (string) $runtime['ui_smart']['commit'];
        $runtimeProjection = $this->repository->runtimeProjection();
        $iconFont = $runtimeProjection === null
            ? 'component/icons/fonts/MaterialSymbols-Outlined.woff2'
            : (string) $runtimeProjection['icon_font'];
        $uiBase = $runtimeProjection === null
            ? 'https://cdn.jsdelivr.net/gh/simai/ui@' . $uiCommit . '/distr'
            : $this->projectedRuntimeBase((string) $runtimeProjection['mount']);
        $typography = $this->repository->typographyProjection();
        $iconProjection = $this->repository->iconProjection();
        $boot = $runtime['boot'];
        $pairId = $this->repository->pairId();
        $projectionFingerprint = substr(
            hash('sha256', CanonicalJson::encode([
                'smart' => $this->repository->assetProjection(),
                'runtime' => $runtimeProjection,
                'typography' => $typography,
                'icons' => $iconProjection,
            ])),
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
            'url' => $typography === null
                ? $this->uiUrl($uiCommit, (string) $boot['css'])
                : $this->typographyUrl((string) $typography['files']['core']['public'])
                    . '?sf_v=' . rawurlencode($cacheVersion),
            'source_revision' => $typography === null
                ? $uiCommit
                : (string) $typography['distribution']['revision'],
            'sha256' => $typography === null
                ? null
                : (string) $typography['files']['core']['sha256'],
        ], [
            'key' => 'simai.framework.utility.full.css',
            'kind' => 'css',
            'url' => $typography === null
                ? $uiBase . '/core/css/utility.full.css'
                : $this->typographyUrl((string) $typography['files']['utility']['public'])
                    . '?sf_v=' . rawurlencode($cacheVersion),
            'source_revision' => $typography === null
                ? $uiCommit
                : (string) $typography['distribution']['revision'],
            'sha256' => $typography === null
                ? null
                : (string) $typography['files']['utility']['sha256'],
        ], [
            'key' => 'simai.framework.icon_font.css',
            'kind' => 'inline_css',
            'content' => $this->iconFallbackCss($uiBase . '/' . $iconFont),
            'source_revision' => $uiCommit,
            'sha256' => $runtimeProjection === null
                ? null
                : $this->runtimeAsset($iconFont)['sha256'],
        ], ...($iconProjection === null ? [] : [[
            'key' => 'simai.framework.icon_variant_fonts.css',
            'kind' => 'inline_css',
            'content' => $this->iconVariantCss(
                $this->projectedPublicUrl((string) $iconProjection['files']['rounded']['public']),
                $this->projectedPublicUrl((string) $iconProjection['files']['sharp']['public']),
            ),
            'source_revision' => $iconProjection['source']['revision'],
            'sha256' => $iconProjection['packet_sha256'],
        ]]), [
            'key' => 'simai.framework.icon_font.ready',
            'kind' => 'boot',
            'content' => $this->iconFallbackReadyRuntime($iconProjection !== null),
        ], [
            'key' => 'simai.framework.smart_base.js',
            'kind' => 'javascript',
            ...$this->runtimeBootAsset($uiCommit, (string) $boot['smart_base'], $cacheVersion),
        ], [
            'key' => 'simai.framework.core.js',
            'kind' => 'javascript',
            ...$this->runtimeBootAsset($uiCommit, (string) $boot['javascript'], $cacheVersion),
        ]];

        $tags = $additionalRuntimeTags;
        foreach (array_values(array_unique($componentKeys)) as $key) {
            $manifest = $this->repository->get($key);
            $tag = $manifest['frontend']['tag'];
            if (! is_string($tag)) {
                throw new FrameworkComponentException('FRAMEWORK_COMPONENT_TAG_INVALID', $key);
            }
            $tags[] = $tag;
        }
        sort($tags, SORT_STRING);

        foreach ($this->orderedRuntimeTags($tags, $runtime) as $tag) {
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
                'tag' => $tag,
                'url' => $projectedAsset['url'],
                'source_revision' => $smartCommit,
                'sha256' => $projectedAsset['sha256'],
            ];
        }

        $this->assertImmutable($assets);

        return new FrameworkAssetPlan($this->repository->pairId(), $assets);
    }

    /**
     * @param  list<string>  $componentKeys
     * @param  list<string>  $additionalRuntimeTags
     */
    public function assertExactProjection(
        array $componentKeys,
        array $additionalRuntimeTags = [],
    ): FrameworkAssetPlan {
        $plan = $this->plan($componentKeys, $additionalRuntimeTags);
        $expected = array_keys($this->repository->assetProjection()['files']);
        sort($expected, SORT_STRING);

        // Validate every locked file before an output directory can be
        // cleaned, including a file that is not reachable from any admitted
        // component. The exact closure check below then rejects such extras.
        foreach ($expected as $relativePath) {
            $this->repository->bundledAsset($relativePath);
        }

        $actual = [];
        $prefix = rtrim($this->assetBase, '/') . '/';
        foreach ($plan->assets as $asset) {
            $url = $asset['url'] ?? null;
            if (! is_string($url)) {
                continue;
            }
            $path = parse_url($url, PHP_URL_PATH);
            if (! is_string($path) || ! str_starts_with($path, $prefix)) {
                continue;
            }
            $relativePath = rawurldecode(substr($path, strlen($prefix)));
            if ($relativePath === '' || str_contains($relativePath, "\0")) {
                throw new FrameworkComponentException(
                    'FRAMEWORK_ASSET_PROJECTION_CLOSURE_MISMATCH',
                    $relativePath,
                );
            }
            $actual[] = $relativePath;
        }
        $actual = array_values(array_unique($actual));
        sort($actual, SORT_STRING);
        if ($actual !== $expected) {
            throw new FrameworkComponentException(
                'FRAMEWORK_ASSET_PROJECTION_CLOSURE_MISMATCH',
                implode(',', array_values(array_diff($expected, $actual))),
            );
        }

        return $plan;
    }

    /**
     * @param  list<string>  $tags
     * @param  array<string, mixed>  $runtime
     * @return list<string>
     */
    private function orderedRuntimeTags(array $tags, array $runtime): array
    {
        $ordered = [];
        $visiting = [];
        $visited = [];
        foreach ($tags as $tag) {
            $this->visitRuntimeTag($tag, $runtime, $ordered, $visiting, $visited);
        }

        return $ordered;
    }

    /**
     * @param  array<string, mixed>  $runtime
     * @param  list<string>  $ordered
     * @param  array<string, true>  $visiting
     * @param  array<string, true>  $visited
     */
    private function visitRuntimeTag(
        string $tag,
        array $runtime,
        array &$ordered,
        array &$visiting,
        array &$visited,
    ): void {
        if (isset($visited[$tag])) {
            return;
        }
        if (isset($visiting[$tag])) {
            throw new FrameworkComponentException('FRAMEWORK_RUNTIME_DEPENDENCY_CYCLE', $tag);
        }

        $component = $runtime['components'][$tag] ?? null;
        if (! is_array($component)) {
            throw new FrameworkComponentException('FRAMEWORK_RUNTIME_COMPONENT_MISSING', $tag);
        }
        $requires = $component['requires'] ?? [];
        if (! is_array($requires) || ! array_is_list($requires)) {
            throw new FrameworkComponentException('FRAMEWORK_RUNTIME_DEPENDENCY_INVALID', $tag);
        }
        foreach ($requires as $dependency) {
            if (! is_string($dependency) || preg_match('/^sf-[a-z][a-z0-9-]*$/D', $dependency) !== 1) {
                throw new FrameworkComponentException('FRAMEWORK_RUNTIME_DEPENDENCY_INVALID', $tag);
            }
        }
        $requires = array_values(array_unique($requires));
        sort($requires, SORT_STRING);

        $visiting[$tag] = true;
        foreach ($requires as $dependency) {
            $this->visitRuntimeTag($dependency, $runtime, $ordered, $visiting, $visited);
        }
        unset($visiting[$tag]);
        $visited[$tag] = true;
        $ordered[] = $tag;
    }

    private function uiUrl(string $commit, string $lockedPath): string
    {
        $prefix = 'ui/distr/';
        if (! str_starts_with($lockedPath, $prefix)) {
            throw new FrameworkComponentException('FRAMEWORK_UI_ASSET_PATH_INVALID', $lockedPath);
        }

        return 'https://cdn.jsdelivr.net/gh/simai/ui@' . $commit . '/distr/' . substr($lockedPath, strlen($prefix));
    }

    private function typographyUrl(string $publicPath): string
    {
        return $this->projectedPublicUrl($publicPath);
    }

    private function projectedPublicUrl(string $publicPath): string
    {
        $prefix = '_docara/';
        $frameworkSuffix = '/framework';
        if (! str_starts_with($publicPath, $prefix)
            || ! str_ends_with($this->assetBase, $frameworkSuffix)
        ) {
            throw new FrameworkComponentException('FRAMEWORK_TYPOGRAPHY_PUBLIC_PATH_INVALID', $publicPath);
        }

        $publisherBase = substr($this->assetBase, 0, -strlen($frameworkSuffix));

        return $publisherBase . '/' . substr($publicPath, strlen($prefix));
    }

    private function projectedRuntimeBase(string $mount): string
    {
        $prefix = '_docara/';
        $frameworkSuffix = '/framework';
        if (! str_starts_with($mount, $prefix)
            || ! str_ends_with($this->assetBase, $frameworkSuffix)
        ) {
            throw new FrameworkComponentException('FRAMEWORK_RUNTIME_PUBLIC_PATH_INVALID', $mount);
        }
        $publisherBase = substr($this->assetBase, 0, -strlen($frameworkSuffix));

        return $publisherBase . '/' . substr($mount, strlen($prefix));
    }

    /** @return array{url: string, source_revision?: string, sha256?: string} */
    private function runtimeBootAsset(string $commit, string $lockedPath, string $cacheVersion): array
    {
        if ($this->repository->runtimeProjection() === null) {
            return ['url' => $this->uiUrl($commit, $lockedPath)];
        }
        $prefix = 'ui/distr/';
        if (! str_starts_with($lockedPath, $prefix)) {
            throw new FrameworkComponentException('FRAMEWORK_UI_ASSET_PATH_INVALID', $lockedPath);
        }
        $projected = $this->runtimeAsset(substr($lockedPath, strlen($prefix)));

        return [
            'url' => $projected['url'] . '?sf_v=' . rawurlencode($cacheVersion),
            'source_revision' => $commit,
            'sha256' => $projected['sha256'],
        ];
    }

    /** @return array{url: string, sha256: string} */
    private function runtimeAsset(string $relativePath): array
    {
        $projection = $this->repository->runtimeProjection();
        if (! is_array($projection)) {
            throw new FrameworkComponentException('FRAMEWORK_RUNTIME_ASSET_NOT_PROJECTED', $relativePath);
        }
        $record = $this->repository->runtimeAssetRecord($relativePath);
        $this->repository->bundledRuntimeAsset($relativePath);

        return [
            'url' => $this->projectedRuntimeBase((string) $projection['mount']) . '/' . $relativePath,
            'sha256' => $record['sha256'],
        ];
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
        return '@font-face{font-family:"Material Symbols Outlined";src:url("' . $fontUrl
            . '") format("woff2");font-style:normal;font-weight:100 700;font-display:block}'
            . 'html body .sf-icon:not(.sf-icon-rounded):not(.sf-icon-shape){'
            . '--sf-icon--font-family:"Material Symbols Outlined";font-family:"Material Symbols Outlined"!important;'
            . 'font-feature-settings:"liga"!important;font-variation-settings:"FILL" var(--sf-icon--fill,0),'
            . '"wght" var(--sf-icon--weight,400),"GRAD" var(--sf-icon--grade,0),'
            . '"opsz" var(--sf-icon--optical-size,24)}';
    }

    private function iconVariantCss(string $roundedUrl, string $sharpUrl): string
    {
        $settings = 'font-feature-settings:"liga"!important;font-variation-settings:"FILL" var(--sf-icon--fill,0),'
            . '"wght" var(--sf-icon--weight,400),"GRAD" var(--sf-icon--grade,0),'
            . '"opsz" var(--sf-icon--optical-size,24)}';

        return '@font-face{font-family:"Material Symbols Rounded";src:url("' . $roundedUrl
            . '") format("woff2");font-style:normal;font-weight:100 700;font-display:block}'
            . '@font-face{font-family:"Material Symbols Sharp";src:url("' . $sharpUrl
            . '") format("woff2");font-style:normal;font-weight:100 700;font-display:block}'
            . 'html body .sf-icon.sf-icon-rounded{--sf-icon--font-family:"Material Symbols Rounded";'
            . 'font-family:"Material Symbols Rounded"!important;' . $settings
            . 'html body .sf-icon.sf-icon-shape{--sf-icon--font-family:"Material Symbols Sharp";'
            . 'font-family:"Material Symbols Sharp"!important;' . $settings;
    }

    private function iconFallbackReadyRuntime(bool $hasVariants): string
    {
        $selector = $hasVariants
            ? '.sf-icon:not(.sf-icon-loaded)'
            : '.sf-icon:not(.sf-icon-rounded):not(.sf-icon-shape):not(.sf-icon-loaded)';
        $loads = $hasVariants
            ? '["Material Symbols Outlined","Material Symbols Rounded","Material Symbols Sharp"].map(function(family){return document.fonts.load(\'400 24px "\'+family+\'"\')})'
            : '[document.fonts.load(\'400 24px "Material Symbols Outlined"\')]';

        return '(function(){var selector=' . json_encode($selector, JSON_THROW_ON_ERROR) . ';'
            . 'function mark(root){if(root.nodeType===1&&root.matches(selector)){root.classList.add("sf-icon-loaded")}if(root.querySelectorAll){root.querySelectorAll(selector).forEach(function(icon){icon.classList.add("sf-icon-loaded")})}}'
            . 'function watch(){mark(document);if(!document.body)return;new MutationObserver(function(records){records.forEach(function(record){record.addedNodes.forEach(mark)})}).observe(document.body,{childList:true,subtree:true})}'
            . 'function start(){var ready=document.fonts&&document.fonts.load?Promise.all(' . $loads . '):Promise.resolve([[true]]);ready.then(function(faces){if(faces&&faces.every(function(face){return face&&face.length})){document.documentElement.dataset.docaraFullFontReady="true";watch()}}).catch(function(){})}'
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
                        || (! str_contains((string) ($asset['content'] ?? ''), '@' . $revision . '/')
                            && ! str_contains((string) ($asset['content'] ?? ''), '/' . $revision . '/'))
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
