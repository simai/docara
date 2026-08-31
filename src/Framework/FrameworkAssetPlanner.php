<?php

declare(strict_types=1);

namespace Simai\Docara\Framework;

use Simai\Docara\Portable\CanonicalJson;

final readonly class FrameworkAssetPlanner
{
    public const DOCARA_SHELL_RUNTIME_TAGS = ['sf-button', 'sf-icon', 'sf-modal'];

    private const INTEGRATION_REVISION = 'simai-framework-asset-plan-v1';

    private const SHELL_CSS_SOURCE = 'declarative-shell.css';

    private const ICON_SUBSET_MANIFEST = 'vendor/docara/icon-subset/50f0603134ce7b70b2d71b686cc13e8b57ccb74c/material-symbols-outlined.995fbf08c43fe8ae9c3b.manifest.json';

    private const ICON_SUBSET_MANIFEST_SHA256 = 'f93668e2b688af2fcdd6e1d78f2ea1abb2c38a3ab49bfab8fae251d9f729bfec';

    private const ICON_SUBSET_PACKET_SHA256 = '1da7a11c755697447b5ed8d556b8ceb6f19c5ee86b805c1893b30a763e57bd54';

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
        return $this->buildPlan($componentKeys, $additionalRuntimeTags, null);
    }

    /**
     * Build the production plan from final HTML. This is the shared Framework
     * planner path; the legacy plan() method remains the no-build fallback.
     *
     * @param  list<string>  $componentKeys
     * @param  list<string>  $additionalRuntimeTags
     */
    public function planForHtml(
        string $html,
        array $componentKeys,
        array $additionalRuntimeTags = [],
    ): FrameworkAssetPlan {
        if (preg_match('//u', $html) !== 1) {
            throw new FrameworkComponentException('FRAMEWORK_ASSET_PLAN_HTML_ENCODING_INVALID');
        }

        return $this->buildPlan($componentKeys, $additionalRuntimeTags, $html);
    }

    /**
     * @param  list<string>  $componentKeys
     * @param  list<string>  $additionalRuntimeTags
     */
    private function buildPlan(
        array $componentKeys,
        array $additionalRuntimeTags,
        ?string $html,
    ): FrameworkAssetPlan {
        $runtime = $this->repository->runtime();
        $uiCommit = (string) $runtime['ui']['commit'];
        $smartCommit = (string) $runtime['ui_smart']['commit'];
        $runtimeProjection = $this->repository->runtimeProjection();
        $iconProjection = $this->repository->iconProjection();
        $iconSubset = $this->iconShellSubset();
        $iconFont = $runtimeProjection === null
            ? 'component/icons/fonts/MaterialSymbols-Outlined.woff2'
            : (string) $runtimeProjection['icon_font'];
        $uiBase = $runtimeProjection === null
            ? 'https://cdn.jsdelivr.net/gh/simai/ui@' . $uiCommit . '/distr'
            : $this->projectedRuntimeBase((string) $runtimeProjection['mount']);
        $typography = $this->repository->typographyProjection();
        $boot = $runtime['boot'];
        $pairId = $this->repository->pairId();
        $projectionFingerprint = substr(
            hash('sha256', CanonicalJson::encode([
                'integration' => self::INTEGRATION_REVISION,
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

        $shell = $html === null
            ? $this->shellPlan($runtime, $cacheVersion)
            : $this->productionHtmlPlan($html, $runtime, $cacheVersion);
        $shell['preload']['icons'] = $iconSubset['receipt'];
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
        ], ...($shell['preload_boot'] === null ? [] : [[
            'key' => 'simai.framework.preloaded',
            'kind' => 'boot',
            'content' => $shell['preload_boot'],
        ]]), ...($typography === null ? [] : $this->typographyFontPreloads($typography)),
            $this->iconSubsetFontPreload($iconSubset), [
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
            ], ...($html === null ? [[
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
            ]] : []), [
                'key' => 'simai.framework.icon_font.css',
                'kind' => 'inline_css',
                'content' => $this->iconSubsetCss($iconSubset),
                'source_revision' => $iconSubset['manifest']['source']['revision'],
                'sha256' => $iconSubset['manifest']['files']['font']['sha256'],
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
                'content' => $this->iconFallbackReadyRuntime(
                    $iconProjection !== null,
                    $iconSubset,
                    $iconProjection === null
                        ? $uiBase . '/' . $iconFont
                        : $this->projectedPublicUrl((string) $iconProjection['files']['outlined']['public']),
                ),
            ], [
                'key' => 'simai.framework.smart_base.js',
                'kind' => 'javascript',
                ...$this->runtimeBootAsset($uiCommit, (string) $boot['smart_base'], $cacheVersion),
            ], ...$shell['dependency_assets'], ...$shell['smart_assets']];

        $tags = [...($shell['runtime_tags'] ?? self::DOCARA_SHELL_RUNTIME_TAGS), ...$additionalRuntimeTags];
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
            if (in_array($tag, $shell['preloaded_tags'] ?? self::DOCARA_SHELL_RUNTIME_TAGS, true)
                && $shell['preload_boot'] !== null
            ) {
                continue;
            }
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

        $assets[] = [
            'key' => 'simai.framework.core.js',
            'kind' => 'javascript',
            ...$this->runtimeBootAsset($uiCommit, (string) $boot['javascript'], $cacheVersion),
        ];

        $this->assertImmutable($assets);

        return new FrameworkAssetPlan(
            $this->repository->pairId(),
            $assets,
            [$shell['generated_asset']],
            $shell['preload'],
            $shell['diagnostics'],
        );
    }

    /**
     * @param  array<string, mixed>  $typography
     * @return list<array<string, mixed>>
     */
    private function typographyFontPreloads(array $typography): array
    {
        $assets = [];
        foreach (['font_49594fb515ba00213fc3', 'font_4f2981d82860061bca3e'] as $key) {
            $record = $typography['files'][$key] ?? null;
            if (! is_array($record)
                || ! is_string($record['public'] ?? null)
                || ! is_string($record['sha256'] ?? null)
            ) {
                throw new FrameworkComponentException('FRAMEWORK_TYPOGRAPHY_PRELOAD_MISSING', $key);
            }
            $assets[] = [
                'key' => 'simai.framework.typography.preload.' . substr($key, 5),
                'kind' => 'font_preload',
                'url' => $this->typographyUrl($record['public']),
                'source_revision' => (string) $typography['distribution']['revision'],
                'sha256' => $record['sha256'],
            ];
        }

        return $assets;
    }

    /**
     * @return array{
     *   manifest:array<string,mixed>,
     *   font_url:string,
     *   receipt:array<string,mixed>
     * }
     */
    private function iconShellSubset(): array
    {
        $manifestBytes = $this->repository->bundledPortableAsset(self::ICON_SUBSET_MANIFEST);
        if (! hash_equals(self::ICON_SUBSET_MANIFEST_SHA256, hash('sha256', $manifestBytes))) {
            throw new FrameworkComponentException('FRAMEWORK_ICON_SUBSET_MANIFEST_HASH_MISMATCH');
        }
        try {
            $manifest = json_decode($manifestBytes, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new FrameworkComponentException('FRAMEWORK_ICON_SUBSET_MANIFEST_INVALID');
        }
        if (! is_array($manifest)
            || ($manifest['schema'] ?? null) !== 'sf.icon_subset.v1'
            || ($manifest['family'] ?? null) !== 'outlined'
            || ! is_array($manifest['icons'] ?? null)
            || ! array_is_list($manifest['icons'])
            || $manifest['icons'] === []
            || ! is_string($manifest['font_family'] ?? null)
            || ! is_array($manifest['files']['font'] ?? null)
            || ! is_array($manifest['files']['css'] ?? null)
            || ! is_string($manifest['files']['css']['path'] ?? null)
            || ! is_string($manifest['files']['css']['sha256'] ?? null)
            || ! is_string($manifest['files']['font']['path'] ?? null)
            || ! is_string($manifest['files']['font']['sha256'] ?? null)
            || ! is_int($manifest['files']['font']['size'] ?? null)
            || ! is_string($manifest['source']['sha256'] ?? null)
            || ! is_string($manifest['packet_sha256'] ?? null)
            || ! hash_equals(self::ICON_SUBSET_PACKET_SHA256, $manifest['packet_sha256'])
        ) {
            throw new FrameworkComponentException('FRAMEWORK_ICON_SUBSET_MANIFEST_INVALID');
        }
        $icons = $manifest['icons'];
        $sortedIcons = $icons;
        sort($sortedIcons, SORT_STRING);
        if ($icons !== $sortedIcons || count($icons) !== count(array_unique($icons))) {
            throw new FrameworkComponentException('FRAMEWORK_ICON_SUBSET_MANIFEST_INVALID');
        }

        $directory = dirname(self::ICON_SUBSET_MANIFEST);
        $cssRelative = $directory . '/' . $manifest['files']['css']['path'];
        $cssBytes = $this->repository->bundledPortableAsset($cssRelative);
        if (! hash_equals($manifest['files']['css']['sha256'], hash('sha256', $cssBytes))) {
            throw new FrameworkComponentException('FRAMEWORK_ICON_SUBSET_CSS_HASH_MISMATCH');
        }
        $fontRelative = $directory . '/' . $manifest['files']['font']['path'];
        $fontBytes = $this->repository->bundledPortableBinaryAsset(
            $fontRelative,
            $manifest['files']['font']['sha256'],
        );
        if (strlen($fontBytes) !== $manifest['files']['font']['size']) {
            throw new FrameworkComponentException('FRAMEWORK_ICON_SUBSET_FONT_SIZE_MISMATCH');
        }
        $public = '_docara/' . $fontRelative;

        return [
            'manifest' => $manifest,
            'font_url' => $this->projectedPublicUrl($public),
            'receipt' => [
                'schema' => 'sf.icon_subset.v1',
                'family' => 'outlined',
                'icons' => $icons,
                'source_sha256' => $manifest['source']['sha256'],
                'font_sha256' => $manifest['files']['font']['sha256'],
                'font_size' => $manifest['files']['font']['size'],
                'font_url' => $this->projectedPublicUrl($public),
                'css_sha256' => $manifest['files']['css']['sha256'],
                'manifest_sha256' => hash('sha256', $manifestBytes),
                'packet_sha256' => $manifest['packet_sha256'],
                'fallback' => 'local_full_font_on_unknown_icon',
            ],
        ];
    }

    /** @param array<string,mixed> $subset @return array<string,mixed> */
    private function iconSubsetFontPreload(array $subset): array
    {
        return [
            'key' => 'simai.framework.icons.preload.outlined_subset',
            'kind' => 'font_preload',
            'url' => $subset['font_url'],
            'source_revision' => $subset['manifest']['source']['revision'],
            'sha256' => $subset['manifest']['files']['font']['sha256'],
        ];
    }

    /**
     * @param  array<string, mixed>  $icons
     * @return list<array<string, mixed>>
     */
    private function iconFontPreloads(array $icons): array
    {
        $record = $icons['files']['outlined'] ?? null;
        if (! is_array($record)
            || ! is_string($record['public'] ?? null)
            || ! is_string($record['sha256'] ?? null)
            || ! is_string($icons['source']['revision'] ?? null)
        ) {
            throw new FrameworkComponentException('FRAMEWORK_ICON_PRELOAD_MISSING', 'outlined');
        }

        return [[
            'key' => 'simai.framework.icons.preload.outlined',
            'kind' => 'font_preload',
            'url' => $this->projectedPublicUrl($record['public']),
            'source_revision' => $icons['source']['revision'],
            'sha256' => $record['sha256'],
        ]];
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

    /**
     * @param  array<string, mixed>  $runtime
     * @return array{
     *   generated_asset:array<string,mixed>,
     *   dependency_assets:list<array<string,mixed>>,
     *   smart_assets:list<array<string,mixed>>,
     *   preload_boot:?string,
     *   preload:array<string,mixed>,
     *   diagnostics:list<array<string,string>>,
     *   runtime_tags:list<string>,
     *   preloaded_tags:list<string>
     * }
     */
    private function shellPlan(array $runtime, string $cacheVersion): array
    {
        $cssInputs = [];
        $dependencyAssets = [];
        $smartAssets = [];
        $modules = [];
        $loadedPlugins = [];
        $diagnostics = [];
        $metadataAvailable = $this->repository->runtimeProjection() !== null;

        $utilityModules = [];
        if ($metadataAvailable) {
            foreach (array_keys($this->repository->runtimeManifest()['files']) as $relativePath) {
                if (! is_string($relativePath)
                    || preg_match('#^utility/(.+)/css/[^/]+\.css$#D', $relativePath, $matches) !== 1
                ) {
                    continue;
                }
                $utilityModules[] = $matches[1];
            }
            $utilityModules = array_values(array_unique($utilityModules));
            sort($utilityModules, SORT_STRING);
            foreach ($utilityModules as $module) {
                $modules[] = $module;
                $loadedPlugins[$module] = ['css' => true, 'js' => false, 'ready' => true];
            }
        }

        $seenDependencies = [];
        $seenSmart = [];
        $addRelation = function (mixed $relation, string $owner) use (
            &$cssInputs,
            &$dependencyAssets,
            &$modules,
            &$loadedPlugins,
            &$seenDependencies,
            $runtime,
            $cacheVersion,
        ): void {
            if (! is_array($relation)
                || ! $this->hasExactKeys($relation, ['plugin', 'type', 'css', 'javascript'])
                || ! is_string($relation['plugin'] ?? null)
                || preg_match('/^[a-z][a-z0-9-]*$/D', $relation['plugin']) !== 1
                || ($relation['type'] ?? null) !== 'component'
                || ! is_string($relation['css'] ?? null)
                || ! is_string($relation['javascript'] ?? null)
            ) {
                throw new FrameworkComponentException('FRAMEWORK_SHELL_PRELOAD_METADATA_INVALID', $owner);
            }
            $relationPlugin = $relation['plugin'];
            if (isset($seenDependencies[$relationPlugin])) {
                return;
            }
            $cssPath = $relation['css'];
            $javascriptPath = $relation['javascript'];
            $cssBytes = $this->repository->bundledRuntimeAsset($cssPath);
            $javascript = $this->runtimeAsset($javascriptPath);
            $cssInputs[] = $this->cssInput('runtime/' . $cssPath, $cssPath, $cssBytes, 'runtime');
            $dependencyAssets[] = [
                'key' => 'simai.framework.preloaded.component.' . str_replace('-', '_', $relationPlugin) . '.js',
                'kind' => 'javascript',
                'url' => $javascript['url'] . '?sf_v=' . rawurlencode($cacheVersion),
                'source_revision' => (string) $runtime['ui']['commit'],
                'sha256' => $javascript['sha256'],
            ];
            $modules[] = $relationPlugin;
            $loadedPlugins[$relationPlugin] = ['css' => true, 'js' => true, 'ready' => true];
            $seenDependencies[$relationPlugin] = true;
        };

        $shellMetadata = $runtime['shell'] ?? null;
        if (! $metadataAvailable
            || ! is_array($shellMetadata)
            || ! $this->hasExactKeys($shellMetadata, ['relations'])
            || ! is_array($shellMetadata['relations'])
            || ! array_is_list($shellMetadata['relations'])
            || $shellMetadata['relations'] === []
        ) {
            $metadataAvailable = false;
        } else {
            foreach ($shellMetadata['relations'] as $relation) {
                $addRelation($relation, 'docara-shell');
            }
        }
        foreach ($this->orderedRuntimeTags(self::DOCARA_SHELL_RUNTIME_TAGS, $runtime) as $tag) {
            $component = $runtime['components'][$tag] ?? null;
            $loader = is_array($component) ? ($component['loader'] ?? null) : null;
            if (! $metadataAvailable
                || ! is_array($loader)
                || ! is_string($loader['plugin'] ?? null)
                || preg_match('/^cl-[a-z][a-z0-9-]*$/D', $loader['plugin']) !== 1
                || ! is_array($loader['relations'] ?? null)
                || ! array_is_list($loader['relations'])
            ) {
                $metadataAvailable = false;
                break;
            }
            $plugin = $loader['plugin'];
            foreach ($loader['relations'] as $relation) {
                $addRelation($relation, $tag);
            }

            if (! isset($seenSmart[$plugin])) {
                if (is_string($component['css'] ?? null) && $component['css'] !== '') {
                    $smartCssPath = (string) $component['css'];
                    if (! str_starts_with($smartCssPath, 'smart/')) {
                        throw new FrameworkComponentException('FRAMEWORK_SMART_ASSET_PATH_INVALID', $smartCssPath);
                    }
                    $smartCssRelative = substr($smartCssPath, strlen('smart/'));
                    $cssInputs[] = $this->cssInput(
                        'smart/' . $smartCssRelative,
                        $smartCssRelative,
                        $this->repository->bundledAsset($smartCssRelative),
                        'smart',
                    );
                }
                $smartJavascript = $this->smartAsset((string) $component['javascript'], $cacheVersion);
                $smartAssets[] = [
                    'key' => 'simai.framework.preloaded.' . str_replace('-', '_', $tag) . '.js',
                    'kind' => 'preloaded_smart_javascript',
                    'tag' => $tag,
                    'url' => $smartJavascript['url'],
                    'source_revision' => (string) $runtime['ui_smart']['commit'],
                    'sha256' => $smartJavascript['sha256'],
                ];
                $modules[] = $plugin;
                $loadedPlugins[$plugin] = ['css' => true, 'js' => true, 'ready' => true];
                $seenSmart[$plugin] = true;
            }
        }

        if (! $metadataAvailable) {
            $cssInputs = [];
            $dependencyAssets = [];
            $smartAssets = [];
            $modules = [];
            $loadedPlugins = [];
            $diagnostics[] = [
                'code' => 'FRAMEWORK_SHELL_PRELOAD_METADATA_MISSING',
                'severity' => 'warning',
                'message' => 'The pinned Framework lock has no exact shell preload metadata; dynamic loading remains active.',
            ];
        }

        $shellBytes = $this->repository->bundledPortableAsset(self::SHELL_CSS_SOURCE);
        $cssInputs[] = $this->cssInput(
            'portable/' . self::SHELL_CSS_SOURCE,
            self::SHELL_CSS_SOURCE,
            $shellBytes,
            'portable',
        );
        $generatedAsset = $this->generatedShellCss($cssInputs);

        if (! $metadataAvailable) {
            return [
                'generated_asset' => $generatedAsset,
                'dependency_assets' => [],
                'smart_assets' => [],
                'preload_boot' => null,
                'preload' => [
                    'schema' => 'docara.framework_preload.v1',
                    'mode' => 'dynamic_fallback',
                    'modules' => [],
                    'loaded_plugins' => [],
                    'asset_order' => [],
                ],
                'diagnostics' => $diagnostics,
                'runtime_tags' => self::DOCARA_SHELL_RUNTIME_TAGS,
                'preloaded_tags' => [],
            ];
        }

        $modules = array_values(array_unique($modules));
        ksort($loadedPlugins, SORT_STRING);
        $preload = [
            'schema' => 'docara.framework_preload.v1',
            'mode' => 'static_shell',
            'modules' => $modules,
            'loaded_plugins' => $loadedPlugins,
            'asset_order' => [
                'simai.framework.smart_base.js',
                ...array_column($dependencyAssets, 'key'),
                ...array_column($smartAssets, 'key'),
                'simai.framework.core.js',
            ],
        ];
        $preloadBoot = 'window.SF_PRELOADED='
            . json_encode(
                ['modules' => $modules, 'loadedPlugins' => $loadedPlugins],
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES,
            )
            . ';';

        return [
            'generated_asset' => $generatedAsset,
            'dependency_assets' => $dependencyAssets,
            'smart_assets' => $smartAssets,
            'preload_boot' => $preloadBoot,
            'preload' => $preload,
            'diagnostics' => [],
            'runtime_tags' => self::DOCARA_SHELL_RUNTIME_TAGS,
            'preloaded_tags' => self::DOCARA_SHELL_RUNTIME_TAGS,
        ];
    }

    /**
     * Resolve the exact first-frame resource closure from the same Loader rule
     * registry used by SIMAI Framework in dynamic mode.
     *
     * @param  array<string, mixed>  $runtime
     * @return array{
     *   generated_asset:array<string,mixed>,
     *   dependency_assets:list<array<string,mixed>>,
     *   smart_assets:list<array<string,mixed>>,
     *   preload_boot:string,
     *   preload:array<string,mixed>,
     *   diagnostics:list<array<string,string>>,
     *   runtime_tags:list<string>,
     *   preloaded_tags:list<string>
     * }
     */
    private function productionHtmlPlan(string $html, array $runtime, string $cacheVersion): array
    {
        $scanHtml = preg_match('~<body\b[^>]*>.*</body\s*>~is', $html, $bodyMatch) === 1
            ? (string) $bodyMatch[0]
            : $html;
        $exampleHtml = '';
        if (preg_match_all(
            '~<iframe\b(?=[^>]*\bdata-docara-example-frame\b)[^>]*\bsrcdoc\s*=\s*(["\'])(.*?)\1[^>]*>~is',
            $scanHtml,
            $exampleMatches,
            PREG_SET_ORDER,
        ) === false) {
            throw new FrameworkComponentException('FRAMEWORK_ASSET_PLAN_HTML_INVALID');
        }
        foreach ($exampleMatches as $exampleMatch) {
            $exampleHtml .= "\n" . html_entity_decode(
                (string) ($exampleMatch[2] ?? ''),
                ENT_QUOTES | ENT_HTML5,
                'UTF-8',
            );
        }
        $scanHtml = preg_replace(
            '/\bsrcdoc\s*=\s*(?:"[^"]*"|\'[^\']*\')/is',
            '',
            $scanHtml,
        );
        if (! is_string($scanHtml)) {
            throw new FrameworkComponentException('FRAMEWORK_ASSET_PLAN_HTML_INVALID');
        }
        $scanHtml .= $exampleHtml;
        $scanHtml = preg_replace(
            '~<(pre|code|script|style)\b[^>]*>.*?</\1\s*>~is',
            '',
            $scanHtml,
        );
        if (! is_string($scanHtml)) {
            throw new FrameworkComponentException('FRAMEWORK_ASSET_PLAN_HTML_INVALID');
        }
        $ruleBytes = $this->repository->bundledRuntimeAsset('rule/rule.json');
        try {
            $rules = json_decode($ruleBytes, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new FrameworkComponentException('FRAMEWORK_RULE_REGISTRY_INVALID');
        }
        if (! is_array($rules) || ! array_is_list($rules) || $rules === []) {
            throw new FrameworkComponentException('FRAMEWORK_RULE_REGISTRY_INVALID');
        }

        $byName = [];
        $selected = [];
        $tagToRule = [];
        foreach ($rules as $order => $rule) {
            $name = is_array($rule) ? ($rule['name'] ?? null) : null;
            if (! is_string($name)
                || preg_match('/^[A-Za-z][A-Za-z0-9-]*(?:\/[A-Za-z0-9-]+)?$/D', $name) !== 1
                || isset($byName[$name])
            ) {
                throw new FrameworkComponentException('FRAMEWORK_RULE_REGISTRY_ENTRY_INVALID', (string) $order);
            }
            $rule['_order'] = $order;
            $byName[$name] = $rule;

            $tags = $rule['tags'] ?? [];
            if (! is_array($tags) || ! array_is_list($tags)) {
                throw new FrameworkComponentException('FRAMEWORK_RULE_REGISTRY_TAGS_INVALID', $name);
            }
            foreach ($tags as $tag) {
                if (! is_string($tag) || preg_match('/^sf-[a-z][a-z0-9-]*$/D', $tag) !== 1) {
                    throw new FrameworkComponentException('FRAMEWORK_RULE_REGISTRY_TAG_INVALID', $name);
                }
                if (isset($tagToRule[$tag]) && $tagToRule[$tag] !== $name) {
                    throw new FrameworkComponentException('FRAMEWORK_RULE_REGISTRY_TAG_CONFLICT', $tag);
                }
                $tagToRule[$tag] = $name;
                if (preg_match('/<\s*' . preg_quote($tag, '/') . '\b/i', $scanHtml) === 1) {
                    $selected[$name] = true;
                }
            }

            $loaderRegex = $rule['regex'] ?? null;
            if ($loaderRegex !== null && $this->loaderRegexMatches($loaderRegex, $name, $scanHtml)) {
                $selected[$name] = true;
            }
        }
        foreach (array_values($byName) as $ownerRule) {
            foreach (($ownerRule['relation'] ?? []) as $relation) {
                $dependency = is_array($relation) ? ($relation['name'] ?? null) : null;
                if (! is_string($dependency) || isset($byName[$dependency])) {
                    continue;
                }
                $mode = $relation['mode'] ?? null;
                $css = $relation['css'] ?? false;
                $javascript = $relation['js'] ?? false;
                if (! in_array($mode, ['utility', 'component'], true)
                    || ! is_bool($css)
                    || ! is_bool($javascript)
                    || (! $css && ! $javascript)
                ) {
                    throw new FrameworkComponentException('FRAMEWORK_RUNTIME_DEPENDENCY_MISSING', $dependency);
                }
                $byName[$dependency] = [
                    'name' => $dependency,
                    'type' => $mode,
                    'css' => $css,
                    'js' => $javascript,
                    'relation' => [],
                    '_synthetic_relation' => true,
                    '_order' => PHP_INT_MAX,
                ];
            }
        }

        if (preg_match_all(
            '/\bdata-sf-require\s*=\s*(?:"([^"]*)"|\'([^\']*)\')/i',
            $scanHtml,
            $matches,
            PREG_SET_ORDER,
        ) === false) {
            throw new FrameworkComponentException('FRAMEWORK_EXPLICIT_REQUIREMENT_INVALID');
        }
        foreach ($matches as $match) {
            $raw = ($match[1] ?? '') !== '' ? (string) $match[1] : (string) ($match[2] ?? '');
            foreach (preg_split('/[\s,]+/', trim($raw)) ?: [] as $token) {
                if ($token === '') {
                    continue;
                }
                if (preg_match('/^[a-z][a-z0-9-]*(?:\.[a-z][a-z0-9-]*)?$/D', $token) !== 1) {
                    throw new FrameworkComponentException('FRAMEWORK_EXPLICIT_REQUIREMENT_INVALID', $token);
                }
                if (str_starts_with($token, 'utility.')) {
                    $family = substr($token, strlen('utility.'));
                    $matched = false;
                    foreach (array_keys($byName) as $candidate) {
                        if (str_starts_with($candidate, $family . '/')) {
                            $selected[$candidate] = true;
                            $matched = true;
                        }
                    }
                    if (! $matched) {
                        throw new FrameworkComponentException('FRAMEWORK_EXPLICIT_REQUIREMENT_UNKNOWN', $token);
                    }

                    continue;
                }
                $candidate = str_starts_with($token, 'smart.')
                    ? 'cl-' . substr($token, strlen('smart.'))
                    : (str_starts_with($token, 'component.')
                        ? substr($token, strlen('component.'))
                        : $token);
                if (! isset($byName[$candidate])) {
                    throw new FrameworkComponentException('FRAMEWORK_EXPLICIT_REQUIREMENT_UNKNOWN', $token);
                }
                $selected[$candidate] = true;
            }
        }

        $ordered = [];
        $visiting = [];
        $visited = [];
        $visit = function (string $name) use (&$visit, &$ordered, &$visiting, &$visited, $byName): void {
            if (isset($visited[$name])) {
                return;
            }
            if (isset($visiting[$name])) {
                // The Framework loader registry contains intentional mutual
                // relations (for example clipboard <-> highlight). They mean
                // "load together", not an invalid executable dependency
                // cycle. The first traversal still emits every member once.
                return;
            }
            $rule = $byName[$name] ?? null;
            if (! is_array($rule)) {
                throw new FrameworkComponentException('FRAMEWORK_RUNTIME_DEPENDENCY_MISSING', $name);
            }
            $visiting[$name] = true;
            $relations = $rule['relation'] ?? [];
            if (! is_array($relations) || ! array_is_list($relations)) {
                throw new FrameworkComponentException('FRAMEWORK_RUNTIME_DEPENDENCY_INVALID', $name);
            }
            foreach ($relations as $relation) {
                $dependency = is_array($relation) ? ($relation['name'] ?? null) : null;
                if (! is_string($dependency)) {
                    throw new FrameworkComponentException('FRAMEWORK_RUNTIME_DEPENDENCY_INVALID', $name);
                }
                $visit($dependency);
            }
            unset($visiting[$name]);
            $visited[$name] = true;
            $ordered[] = $name;
        };
        foreach ($rules as $rule) {
            $name = (string) $rule['name'];
            if (isset($selected[$name])) {
                $visit($name);
            }
        }

        $cssInputs = [];
        $dependencyAssets = [];
        $smartAssets = [];
        $modules = [];
        $loadedPlugins = [];
        $runtimeTags = [];
        $preloadedTags = [];
        $diagnostics = [];
        $seenCss = [];
        $seenJavascript = [];
        $runtimeFiles = $this->repository->runtimeManifest()['files'];
        $smartByPlugin = [];
        foreach (($runtime['components'] ?? []) as $tag => $component) {
            $plugin = is_array($component) ? ($component['loader']['plugin'] ?? null) : null;
            if (is_string($tag) && is_string($plugin)) {
                $smartByPlugin[$plugin] ??= ['tag' => $tag, 'component' => $component];
            }
        }
        foreach ($byName as $name => $rule) {
            if (($rule['type'] ?? null) !== 'smart' || isset($smartByPlugin[$name])) {
                continue;
            }
            foreach (($rule['tags'] ?? []) as $tag) {
                $component = is_string($tag) ? ($runtime['components'][$tag] ?? null) : null;
                if (is_array($component)) {
                    $smartByPlugin[$name] = ['tag' => $tag, 'component' => $component];
                    break;
                }
            }
        }

        foreach ($ordered as $name) {
            $rule = $byName[$name];
            $type = $rule['type'] ?? 'utility';
            $modulePreloaded = true;
            $css = [];
            $javascript = [];
            if ($type === 'utility') {
                $leaf = str_contains($name, '/') ? substr($name, strrpos($name, '/') + 1) : $name;
                $css[] = 'utility/' . $name . '/css/' . $leaf . '.css';
            } elseif ($type === 'component' || $type === 'attribute') {
                if (($rule['css'] ?? false) === true) {
                    $css[] = 'component/' . $name . '/css/' . $name . '.css';
                }
                if (($rule['js'] ?? false) === true) {
                    $javascript[] = 'component/' . $name . '/js/' . $name . '.js';
                }
                $missingEntrypoints = array_values(array_filter(
                    [...$css, ...$javascript],
                    static fn (string $path): bool => ! isset($runtimeFiles[$path]),
                ));
                if ($missingEntrypoints !== []) {
                    $modulePreloaded = false;
                    $css = array_values(array_filter(
                        $css,
                        static fn (string $path): bool => isset($runtimeFiles[$path]),
                    ));
                    $javascript = [];
                    $diagnostics[] = [
                        'code' => 'FRAMEWORK_COMPONENT_DYNAMIC_FALLBACK',
                        'severity' => 'warning',
                        'message' => 'Component [' . $name . '] keeps dynamic behavior because an entrypoint is intentionally outside the bounded runtime projection.',
                    ];
                }
            } elseif ($type === 'smart') {
                $record = $smartByPlugin[$name] ?? null;
                if (! is_array($record) || ! is_array($record['component'] ?? null)) {
                    $diagnostics[] = [
                        'code' => 'FRAMEWORK_SMART_DYNAMIC_FALLBACK',
                        'severity' => 'warning',
                        'message' => 'Smart module [' . $name . '] remains assigned to the dynamic Loader because it is outside the bounded project component catalog.',
                    ];

                    continue;
                }
                $component = $record['component'];
                $tag = (string) $record['tag'];
                $projectedSmartFiles = $this->repository->assetProjection()['files'] ?? [];
                $requiredSmartPaths = [];
                foreach ([$component['css'] ?? null, $component['javascript'] ?? null] as $lockedPath) {
                    if (! is_string($lockedPath) || $lockedPath === '') {
                        continue;
                    }
                    if (! str_starts_with($lockedPath, 'smart/')) {
                        throw new FrameworkComponentException('FRAMEWORK_SMART_ASSET_PATH_INVALID', $lockedPath);
                    }
                    $requiredSmartPaths[] = substr($lockedPath, strlen('smart/'));
                }
                if (array_filter(
                    $requiredSmartPaths,
                    static fn (string $path): bool => ! isset($projectedSmartFiles[$path]),
                ) !== []) {
                    $diagnostics[] = [
                        'code' => 'FRAMEWORK_SMART_DYNAMIC_FALLBACK',
                        'severity' => 'warning',
                        'message' => 'Smart module [' . $name . '] remains assigned to the dynamic Loader; its base component resources are planned statically.',
                    ];

                    continue;
                }
                $runtimeTags[] = $tag;
                $preloadedTags[] = $tag;
                if (is_string($component['css'] ?? null) && $component['css'] !== '') {
                    $smartPath = (string) $component['css'];
                    if (! str_starts_with($smartPath, 'smart/')) {
                        throw new FrameworkComponentException('FRAMEWORK_SMART_ASSET_PATH_INVALID', $smartPath);
                    }
                    $relative = substr($smartPath, strlen('smart/'));
                    if (! isset($seenCss['smart/' . $relative])) {
                        $cssInputs[] = $this->cssInput(
                            'smart/' . $relative,
                            $relative,
                            $this->repository->bundledAsset($relative),
                            'smart',
                        );
                        $seenCss['smart/' . $relative] = true;
                    }
                }
                $smartJavascript = $this->smartAsset((string) $component['javascript'], $cacheVersion);
                if (! isset($seenJavascript['smart:' . $name])) {
                    $smartAssets[] = [
                        'key' => 'simai.framework.preloaded.' . str_replace('-', '_', $tag) . '.js',
                        'kind' => 'preloaded_smart_javascript',
                        'tag' => $tag,
                        'url' => $smartJavascript['url'],
                        'source_revision' => (string) $runtime['ui_smart']['commit'],
                        'sha256' => $smartJavascript['sha256'],
                    ];
                    $seenJavascript['smart:' . $name] = true;
                }
            } else {
                throw new FrameworkComponentException('FRAMEWORK_RULE_REGISTRY_TYPE_INVALID', $name);
            }
            if (($rule['_synthetic_relation'] ?? false) === true) {
                $missingEntrypoints = array_values(array_filter(
                    [...$css, ...$javascript],
                    static fn (string $path): bool => ! isset($runtimeFiles[$path]),
                ));
                if ($missingEntrypoints !== []) {
                    $modulePreloaded = false;
                    $css = [];
                    $javascript = [];
                    $diagnostics[] = [
                        'code' => 'FRAMEWORK_RELATION_DYNAMIC_FALLBACK',
                        'severity' => 'warning',
                        'message' => 'Loader relation [' . $name . '] has no projected entrypoint and remains dynamic.',
                    ];
                }
            }
            foreach (($rule['relation'] ?? []) as $relation) {
                $dependency = is_array($relation) ? ($relation['name'] ?? null) : null;
                if (is_string($dependency)
                    && (($loadedPlugins[$dependency]['ready'] ?? false) !== true)
                ) {
                    $modulePreloaded = false;
                    $diagnostics[] = [
                        'code' => 'FRAMEWORK_RELATION_DYNAMIC_FALLBACK',
                        'severity' => 'warning',
                        'message' => 'Module [' . $name . '] remains dynamic because relation [' . $dependency . '] is not fully preloaded.',
                    ];
                }
            }

            foreach ($css as $path) {
                if (! isset($runtimeFiles[$path])) {
                    throw new FrameworkComponentException('FRAMEWORK_RUNTIME_ASSET_NOT_PROJECTED', $path);
                }
                if (! isset($seenCss['runtime/' . $path])) {
                    $cssInputs[] = $this->cssInput(
                        'runtime/' . $path,
                        $path,
                        $this->repository->bundledRuntimeAsset($path),
                        'runtime',
                    );
                    $seenCss['runtime/' . $path] = true;
                }
            }
            foreach ($javascript as $path) {
                if (! isset($runtimeFiles[$path])) {
                    throw new FrameworkComponentException('FRAMEWORK_RUNTIME_ASSET_NOT_PROJECTED', $path);
                }
                if (! isset($seenJavascript['runtime/' . $path])) {
                    $asset = $this->runtimeAsset($path);
                    $dependencyAssets[] = [
                        'key' => 'simai.framework.preloaded.component.' . str_replace(['/', '-'], '_', $name) . '.js',
                        'kind' => 'javascript',
                        'url' => $asset['url'] . '?sf_v=' . rawurlencode($cacheVersion),
                        'source_revision' => (string) $runtime['ui']['commit'],
                        'sha256' => $asset['sha256'],
                    ];
                    $seenJavascript['runtime/' . $path] = true;
                }
            }
            if ($modulePreloaded) {
                $modules[] = $name;
                $loadedPlugins[$name] = [
                    'css' => $css !== [] || ($type === 'smart' && is_string(($smartByPlugin[$name]['component']['css'] ?? null))),
                    'js' => $javascript !== [] || $type === 'smart',
                    'ready' => true,
                ];
            }
        }

        $shellBytes = $this->repository->bundledPortableAsset(self::SHELL_CSS_SOURCE);
        $cssInputs[] = $this->cssInput(
            'portable/' . self::SHELL_CSS_SOURCE,
            self::SHELL_CSS_SOURCE,
            $shellBytes,
            'portable',
        );
        $generatedAsset = $this->generatedShellCss($cssInputs);
        $modules = array_values(array_unique($modules));
        $runtimeTags = array_values(array_unique($runtimeTags));
        sort($runtimeTags, SORT_STRING);
        $preloadedTags = array_values(array_unique($preloadedTags));
        sort($preloadedTags, SORT_STRING);
        ksort($loadedPlugins, SORT_STRING);
        $preload = [
            'schema' => 'simai.framework.asset_plan.v1',
            'mode' => 'production_exact',
            'rule_registry_sha256' => hash('sha256', $ruleBytes),
            'html_sha256' => hash('sha256', $scanHtml),
            'modules' => $modules,
            'loaded_plugins' => $loadedPlugins,
            'asset_order' => [
                'simai.framework.smart_base.js',
                ...array_column($dependencyAssets, 'key'),
                ...array_column($smartAssets, 'key'),
                'simai.framework.core.js',
            ],
        ];
        $preloadBoot = 'window.SF_PRELOADED=' . json_encode(
            ['modules' => $modules, 'loadedPlugins' => $loadedPlugins],
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES,
        ) . ';';

        return [
            'generated_asset' => $generatedAsset,
            'dependency_assets' => $dependencyAssets,
            'smart_assets' => $smartAssets,
            'preload_boot' => $preloadBoot,
            'preload' => $preload,
            'diagnostics' => $diagnostics,
            'runtime_tags' => $runtimeTags,
            'preloaded_tags' => $preloadedTags,
        ];
    }

    private function loaderRegexMatches(mixed $value, string $name, string $html): bool
    {
        if (! is_string($value) || ! str_starts_with($value, '/')) {
            throw new FrameworkComponentException('FRAMEWORK_RULE_REGISTRY_REGEX_INVALID', $name);
        }
        $end = strrpos($value, '/');
        if ($end === false || $end === 0) {
            throw new FrameworkComponentException('FRAMEWORK_RULE_REGISTRY_REGEX_INVALID', $name);
        }
        $expression = substr($value, 1, $end - 1);
        $flags = substr($value, $end + 1);
        if ($flags !== '' && $flags !== 'i') {
            throw new FrameworkComponentException('FRAMEWORK_RULE_REGISTRY_REGEX_INVALID', $name);
        }
        $pattern = '~' . str_replace('~', '\\~', $expression) . '~' . $flags;
        $result = @preg_match($pattern, $html);
        if ($result === false) {
            throw new FrameworkComponentException('FRAMEWORK_RULE_REGISTRY_REGEX_INVALID', $name);
        }

        return $result === 1;
    }

    /** @param array<string, mixed> $value @param list<string> $expected */
    private function hasExactKeys(array $value, array $expected): bool
    {
        $actual = array_keys($value);
        sort($actual, SORT_STRING);
        sort($expected, SORT_STRING);

        return $actual === $expected;
    }

    /** @return array{source:string,path:string,sha256:string,content:string,root:string} */
    private function cssInput(string $source, string $path, string $content, string $root): array
    {
        if (preg_match('//u', $content) !== 1) {
            throw new FrameworkComponentException('FRAMEWORK_SHELL_CSS_ENCODING_INVALID', $source);
        }

        return [
            'source' => $source,
            'path' => $path,
            'sha256' => hash('sha256', $content),
            'content' => $content,
            'root' => $root,
        ];
    }

    /** @param list<array{source:string,path:string,sha256:string,content:string,root:string}> $inputs */
    private function generatedShellCss(array $inputs): array
    {
        $content = '';
        $receiptInputs = [];
        foreach ($inputs as $input) {
            $content .= '/* docara-shell-source: ' . $input['source'] . ' */' . "\n"
                . $this->rewriteCssUrls($input['content'], $input['path'], $input['root']) . "\n";
            $receiptInputs[] = [
                'source' => $input['source'],
                'sha256' => $input['sha256'],
            ];
        }
        $sha256 = hash('sha256', $content);
        $filename = 'docara-shell.' . $sha256 . '.css';

        return [
            'key' => 'docara.framework.shell.css',
            'kind' => 'shell_css',
            'filename' => $filename,
            'url' => $this->publisherBase() . '/' . $filename,
            'sha256' => $sha256,
            'inputs' => $receiptInputs,
            'content' => $content,
        ];
    }

    private function rewriteCssUrls(string $css, string $sourcePath, string $root): string
    {
        $rewritten = preg_replace_callback(
            '#url\(\s*(?:"([^"]*)"|\'([^\']*)\'|([^)]*))\s*\)#i',
            function (array $matches) use ($sourcePath, $root): string {
                $doubleQuoted = (string) ($matches[1] ?? '');
                $singleQuoted = (string) ($matches[2] ?? '');
                $unquoted = (string) ($matches[3] ?? '');
                $value = trim($doubleQuoted !== '' ? $doubleQuoted : ($singleQuoted !== '' ? $singleQuoted : $unquoted));
                if ($value === '' || str_contains($value, "\0")) {
                    throw new FrameworkComponentException('FRAMEWORK_SHELL_CSS_URL_INVALID', $sourcePath);
                }
                if (str_starts_with($value, 'data:') || str_starts_with($value, '#')) {
                    return 'url("' . str_replace('"', '\\"', $value) . '")';
                }
                if (str_starts_with($value, '/')
                    || str_starts_with($value, '//')
                    || preg_match('/^[a-z][a-z0-9+.-]*:/i', $value) === 1
                ) {
                    throw new FrameworkComponentException('FRAMEWORK_SHELL_CSS_URL_EXTERNAL', $value);
                }
                $parts = preg_split('/([?#])/', $value, 2, PREG_SPLIT_DELIM_CAPTURE);
                $relative = (string) ($parts[0] ?? '');
                $suffix = count($parts) >= 3 ? (string) $parts[1] . (string) $parts[2] : '';
                $resolved = $this->normalizeRelativePath(dirname($sourcePath) . '/' . $relative);
                if ($root === 'runtime') {
                    $this->repository->bundledRuntimeAsset($resolved);
                    $url = $this->runtimeAsset($resolved)['url'];
                } elseif ($root === 'smart') {
                    $this->repository->bundledAsset($resolved);
                    $url = $this->assetBase . '/' . $resolved;
                } else {
                    throw new FrameworkComponentException('FRAMEWORK_SHELL_CSS_URL_UNSUPPORTED', $sourcePath);
                }

                return 'url("' . str_replace('"', '\\"', $url . $suffix) . '")';
            },
            $css,
        );
        if (! is_string($rewritten)) {
            throw new FrameworkComponentException('FRAMEWORK_SHELL_CSS_INVALID', $sourcePath);
        }

        return $rewritten;
    }

    private function normalizeRelativePath(string $path): string
    {
        $segments = [];
        foreach (explode('/', str_replace('\\', '/', $path)) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }
            if ($segment === '..') {
                if ($segments === []) {
                    throw new FrameworkComponentException('FRAMEWORK_SHELL_CSS_URL_TRAVERSAL', $path);
                }
                array_pop($segments);

                continue;
            }
            $segments[] = $segment;
        }
        if ($segments === []) {
            throw new FrameworkComponentException('FRAMEWORK_SHELL_CSS_URL_INVALID', $path);
        }

        return implode('/', $segments);
    }

    private function publisherBase(): string
    {
        $suffix = '/framework';
        if (! str_ends_with($this->assetBase, $suffix)) {
            throw new FrameworkComponentException('FRAMEWORK_SHELL_PUBLIC_PATH_INVALID', $this->assetBase);
        }

        return substr($this->assetBase, 0, -strlen($suffix));
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

    /** @param array<string,mixed> $subset */
    private function iconSubsetCss(array $subset): string
    {
        $family = (string) $subset['manifest']['font_family'];

        return '@font-face{font-family:"' . $family . '";src:url("' . $subset['font_url']
            . '") format("woff2");font-style:normal;font-weight:400;font-display:block}'
            . 'html body .sf-icon:not(.sf-icon-rounded):not(.sf-icon-shape){'
            . '--sf-icon--font-family:"' . $family . '";font-family:"' . $family . '"!important;'
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

    /** @param array<string,mixed> $subset */
    private function iconFallbackReadyRuntime(bool $hasVariants, array $subset, string $fullFontUrl): string
    {
        $variants = $hasVariants
            ? '{rounded:"Material Symbols Rounded",shape:"Material Symbols Sharp"}'
            : '{}';
        $subsetFamily = json_encode(
            $subset['manifest']['font_family'],
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES,
        );
        $subsetIcons = json_encode(
            $subset['manifest']['icons'],
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES,
        );
        $fallbackCss = json_encode(
            $this->iconFallbackCss($fullFontUrl),
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES,
        );

        return '(function(){var variants=' . $variants . ',loaded={},subsetFamily=' . $subsetFamily
            . ',subsetIcons=new Set(' . $subsetIcons . '),fallbackCss=' . $fallbackCss . ',fallbackPending=null;'
            . 'function iconName(icon){return String(icon.getAttribute("icon")||icon.textContent||"").trim()}'
            . 'function ensureFullFont(){if(fallbackPending)return fallbackPending;var style=document.createElement("style");style.dataset.docaraIconFallback="outlined";style.textContent=fallbackCss;document.head.appendChild(style);fallbackPending=document.fonts&&document.fonts.load?document.fonts.load(\'400 24px "Material Symbols Outlined"\'):Promise.resolve([true]);return fallbackPending}'
            . 'function family(icon){if(icon.classList.contains("sf-icon-rounded"))return variants.rounded||null;if(icon.classList.contains("sf-icon-shape"))return variants.shape||null;return subsetIcons.has(iconName(icon))?subsetFamily:"Material Symbols Outlined"}'
            . 'function ready(icon){if(icon.classList.contains("sf-icon-loaded"))return;var name=family(icon);if(!name)return;var promise=loaded[name]||(loaded[name]=document.fonts&&document.fonts.load?document.fonts.load(\'400 24px "\'+name+\'"\'):Promise.resolve([true]));promise.then(function(faces){if(faces&&faces.length)icon.classList.add("sf-icon-loaded")}).catch(function(){})}'
            . 'var originalReady=ready;ready=function(icon){if(family(icon)==="Material Symbols Outlined"){ensureFullFont().then(function(){originalReady(icon)})}else{originalReady(icon)}};'
            . 'function mark(root){if(root.nodeType===1&&root.matches(".sf-icon"))ready(root);if(root.querySelectorAll){root.querySelectorAll(".sf-icon").forEach(ready)}}'
            . 'function watch(){mark(document);if(!document.body)return;new MutationObserver(function(records){records.forEach(function(record){record.addedNodes.forEach(mark)})}).observe(document.body,{childList:true,subtree:true})}'
            . 'function start(){document.documentElement.dataset.docaraIconSubsetReady="true";watch()}'
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
                if (($asset['kind'] ?? null) === 'font_preload'
                    && is_string($asset['source_revision'] ?? null)
                    && preg_match('/^[a-f0-9]{40}$/', $asset['source_revision']) === 1
                    && is_string($asset['sha256'] ?? null)
                    && preg_match('/^[a-f0-9]{64}$/', $asset['sha256']) === 1
                    && preg_match(
                        '#^/(?:[A-Za-z0-9._~-]+/)*_docara/vendor/(?:simai-framework/typography/[a-f0-9]{20}|google/material-symbols/[a-f0-9]{40}/MaterialSymbolsOutlined|docara/icon-subset/[a-f0-9]{40}/material-symbols-outlined\.[a-f0-9]{20})\.woff2$#D',
                        $url,
                    ) === 1
                ) {
                    continue;
                }
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
