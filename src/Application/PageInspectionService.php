<?php

declare(strict_types=1);

namespace Simai\Docara\Application;

use Simai\Docara\Authoring\AuthoringContract;
use Simai\Docara\Authoring\AuthoringProfileRegistry;
use Simai\Docara\Content\FrontMatterParser;
use Simai\Docara\Content\PageSource;
use Simai\Docara\Content\PageSourceLocator;
use Simai\Docara\Declarative\Smart\SmartComponentGateway;
use Simai\Docara\Document\MarkdownCompiler;
use Simai\Docara\Documentation\DocumentationStatusService;
use Simai\Docara\Framework\FrameworkLock;
use Simai\Docara\I18n\LocaleRegistry;
use Simai\Docara\I18n\LocaleRoutingPolicy;
use Simai\Docara\I18n\LocaleUrlProjector;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\Portable\PortableConfigurationLoader;
use Simai\Docara\PortableSite\PortableMarkdownRenderer;
use Simai\Docara\PortableSite\ProjectExampleRepository;
use Simai\Docara\Smart\Runtime\ProjectSmartRuntime;

final readonly class PageInspectionService
{
    /** @return list<array<string, mixed>> */
    public function list(string $root): array
    {
        $runtime = ProjectRuntime::load($root);
        $contract = AuthoringContract::load($runtime->root);
        $items = [];
        foreach ($this->sources($runtime) as $source) {
            $raw = (string) file_get_contents($runtime->root . '/' . $source->path);
            $document = (new FrontMatterParser)->parse($raw, $source->path);
            $contentRoot = LocaleRegistry::fromSite($runtime->site)->get($source->locale)->contentRoot;
            $relative = substr($source->path, strlen($contentRoot) + 1);
            $resolution = $contract->resolve(
                $relative,
                is_string($document->metadata['profile'] ?? null) ? $document->metadata['profile'] : null,
            );
            $diagnostics = $this->profileDiagnostics(
                $resolution['profile'],
                $this->facts($document->markdown),
                $contract->present || isset($document->metadata['profile']),
            );
            $route = $this->publicRoute($runtime, $source);
            $items[] = [
                'id' => $route,
                'kind' => 'page',
                'source' => $source->path,
                'locale' => $source->locale,
                'route' => $route,
                'profile' => $resolution['profile'],
                'profile_source' => $resolution['source'],
                'status' => $diagnostics === [] ? 'current' : 'needs_attention',
            ];
        }

        return $items;
    }

    /** @return array<string, mixed> */
    public function inspect(string $root, string $route): array
    {
        $runtime = ProjectRuntime::load($root);
        $contract = AuthoringContract::load($runtime->root);
        foreach ($this->sources($runtime) as $source) {
            $public = $this->publicRoute($runtime, $source);
            if ($route === $public || trim($route, '/') === trim($public, '/')) {
                return $this->inspectSource($runtime, $contract, $source, true);
            }
        }
        throw new PortableConfigurationException('PAGE_ROUTE_UNKNOWN', "No Markdown page owns route [$route].");
    }

    /** @return list<array<string, mixed>> */
    public function validateAll(string $root): array
    {
        $runtime = ProjectRuntime::load($root);
        $contract = AuthoringContract::load($runtime->root);
        $checks = [];
        $sources = $this->sources($runtime);
        $routeSet = [];
        foreach ($sources as $candidate) {
            $routeSet[$this->publicRoute($runtime, $candidate)] = true;
        }
        $frameworkPath = (string) ($runtime->site['framework_lock'] ?? '');
        if ($frameworkPath === '') {
            throw new PortableConfigurationException('FRAMEWORK_LOCK_PATH_INVALID', 'Page validation requires the configured Framework lock.');
        }
        $framework = FrameworkLock::fromJsonFile($runtime->root . '/' . ltrim($frameworkPath, '/'))->toArray();
        $project = ProjectSmartRuntime::fromSite($runtime->root, $runtime->site, $framework);
        $compiler = new MarkdownCompiler(smarts: $project?->gateway ?? SmartComponentGateway::bundled($framework));
        $exampleRepository = new ProjectExampleRepository($runtime->root, (string) ($runtime->site['base_url'] ?? '/'));
        foreach ($sources as $source) {
            $raw = (string) file_get_contents($runtime->root . '/' . $source->path);
            $document = (new FrontMatterParser)->parse($raw, $source->path);
            $contentRoot = LocaleRegistry::fromSite($runtime->site)->get($source->locale)->contentRoot;
            $relative = substr($source->path, strlen($contentRoot) + 1);
            $resolution = $contract->resolve(
                $relative,
                is_string($document->metadata['profile'] ?? null) ? $document->metadata['profile'] : null,
            );
            $diagnostics = $this->profileDiagnostics(
                $resolution['profile'],
                $this->facts($document->markdown),
                $contract->present || isset($document->metadata['profile']),
            );
            $compiler->compile($document->markdown, $source->path);
            foreach ($this->examples($runtime, $source, $document->markdown) as $example) {
                if ($example['mode'] === 'reusable') {
                    $exampleRepository->load($example['id'], $source->path);
                }
            }
            array_push($diagnostics, ...$this->technicalDiagnostics(
                $runtime,
                $source,
                $document->markdown,
                $this->links($document->markdown),
                $routeSet,
            ));
            $route = $this->publicRoute($runtime, $source);
            foreach ($diagnostics as $diagnostic) {
                $checks[] = $diagnostic + ['subject' => $route];
            }
            $checks[] = ['code' => 'PAGE_MARKDOWN_VALID', 'status' => 'pass', 'subject' => $route];
        }

        return $checks;
    }

    /** @return list<PageSource> */
    private function sources(ProjectRuntime $runtime): array
    {
        $locales = LocaleRegistry::fromSite($runtime->site);
        $locator = new PageSourceLocator($runtime->root, $locales);
        $sources = [];
        foreach ($locales->all() as $locale => $_definition) {
            array_push($sources, ...$locator->forLocale($locale));
        }
        usort($sources, fn (PageSource $a, PageSource $b): int => strcmp($this->publicRoute($runtime, $a), $this->publicRoute($runtime, $b)));

        return $sources;
    }

    /** @return array<string, mixed> */
    /** @param array<string, true>|null $routeSet */
    private function inspectSource(
        ProjectRuntime $runtime,
        AuthoringContract $contract,
        PageSource $source,
        bool $full,
        ?array $routeSet = null,
        bool $resolveSettings = true,
        bool $includeRelations = true,
    ): array {
        $absolute = $runtime->root . '/' . $source->path;
        $raw = (string) file_get_contents($absolute);
        $document = (new FrontMatterParser)->parse($raw, $source->path);
        $relative = substr($source->path, strlen(LocaleRegistry::fromSite($runtime->site)->get($source->locale)->contentRoot) + 1);
        $resolution = $contract->resolve($relative, is_string($document->metadata['profile'] ?? null) ? $document->metadata['profile'] : null);
        $facts = $this->facts($document->markdown);
        $diagnostics = $this->profileDiagnostics($resolution['profile'], $facts, $contract->present || isset($document->metadata['profile']));
        $route = $this->publicRoute($runtime, $source);
        $components = $this->components($document->markdown);
        $examples = $this->examples($runtime, $source, $document->markdown);
        $links = $this->links($document->markdown);
        if ($full) {
            $this->compileMarkdown($runtime, $source, $document->markdown);
            foreach ($examples as $example) {
                if ($example['mode'] === 'reusable') {
                    (new ProjectExampleRepository($runtime->root, (string) ($runtime->site['base_url'] ?? '/')))->load($example['id'], $source->path);
                }
            }
            array_push($diagnostics, ...$this->technicalDiagnostics($runtime, $source, $document->markdown, $links, $routeSet));
        }
        $profile = $resolution['profile'] === null ? null : (new AuthoringProfileRegistry)->all()[$resolution['profile']];
        $resolved = $resolveSettings ? (new PortableConfigurationLoader($runtime->root))->resolve($source->path) : null;
        $translation = $full && $includeRelations
            ? $this->translationRelation($runtime, $source, $document->metadata['translation_key'] ?? null)
            : ['key' => $document->metadata['translation_key'] ?? null, 'source_locale' => $this->translationSourceLocale($runtime, $source), 'peers' => []];
        $data = [
            'schema' => 'docara.page_inspection.v1',
            'source' => ['path' => $source->path, 'sha256' => hash('sha256', str_replace(["\r\n", "\r"], "\n", $raw))],
            'locale' => $source->locale,
            'route' => $route,
            'front_matter' => $document->metadata,
            'effective_settings' => ['configuration' => $resolved?->configuration, 'page_sidecar' => $this->sidecar($runtime->root, $source->path)],
            'profile' => ['id' => $resolution['profile'], 'source' => $resolution['source'], 'definition' => $profile, 'review_status' => $profile === null ? 'not_configured' : 'review_required'],
            'components' => $components,
            'examples' => $examples,
            'links' => $links,
            'translation' => $translation,
            'locks' => $this->locks($runtime),
            'revisions' => ['repository_head' => $this->gitHead($runtime->root), 'engine' => $runtime->provenance()['engine_revision']],
            'hashes' => ['markdown' => hash('sha256', $document->markdown), 'authoring_contract' => $contract->sha256],
            'provenance' => ['profile_rule_matches' => $resolution['matches'], 'content_root_relative_path' => $relative, 'effective_settings' => $resolved?->provenance ?? []],
            'diagnostics' => $diagnostics,
        ];
        $data['documentation'] = $this->documentationRelation($runtime, $route, $full && $includeRelations);
        if (! $full) {
            unset($data['effective_settings']['configuration']);
        }

        return $data;
    }

    private function publicRoute(ProjectRuntime $runtime, PageSource $source): string
    {
        $locales = LocaleRegistry::fromSite($runtime->site);
        $policy = LocaleRoutingPolicy::fromSite($runtime->site, $locales);

        return (new LocaleUrlProjector((string) ($runtime->site['base_url'] ?? '/'), $locales, $policy))->page($source->locale, $source->route)['url'];
    }

    /** @return array<string, int|bool> */
    private function facts(string $markdown): array
    {
        $structural = preg_replace('/^```[^\n]*\n.*?^```\s*$/ms', '', $markdown) ?? $markdown;
        preg_match_all('/^#\s+\S.*$/m', $structural, $h1);
        preg_match_all('/^##\s+\S.*$/m', $structural, $h2);
        preg_match_all('/^\s*\d+[.)]\s+\S/m', $structural, $ordered);
        preg_match_all('/^\s*[-*+]\s+\S/m', $structural, $unordered);
        preg_match_all('/^\|.*\|\s*$/m', $structural, $tables);
        preg_match_all('/^```/m', $markdown, $fences);
        preg_match_all('/^:::[a-z_][a-z0-9_]*/mi', $structural, $directives);
        preg_match_all('/\[[^]]+](?:\([^)]*\)|\[[^]]*])/', $structural, $links);
        $body = preg_replace('/^#.*$/m', '', $structural) ?? $structural;

        return [
            'h1' => count($h1[0]), 'h2' => count($h2[0]), 'ordered_steps' => count($ordered[0]),
            'lists' => count($ordered[0]) + count($unordered[0]), 'tables' => count($tables[0]),
            'code_blocks' => intdiv(count($fences[0]), 2), 'directives' => count($directives[0]),
            'links' => count($links[0]), 'has_introduction' => trim($body) !== '',
        ];
    }

    /** @param array<string, int|bool> $facts @return list<array<string, mixed>> */
    private function profileDiagnostics(?string $profile, array $facts, bool $enabled): array
    {
        if (! $enabled || $profile === null) {
            return [];
        }
        $requirements = [
            'one_h1' => $facts['h1'] === 1,
            'introduction' => $facts['has_introduction'] === true,
            'multiple_sections' => $facts['h2'] >= 2,
            'main_material' => $facts['h2'] >= 1,
            'ordered_steps' => $facts['ordered_steps'] >= 2,
            'structured_reference' => $facts['tables'] + $facts['lists'] + $facts['code_blocks'] + $facts['directives'] >= 1,
            'action' => $facts['links'] + $facts['directives'] >= 1,
        ];
        $diagnostics = [];
        foreach ((new AuthoringProfileRegistry)->all()[$profile]['structural_signals'] as $signal) {
            if (($requirements[$signal] ?? true) !== true) {
                $diagnostics[] = ['code' => 'PAGE_PROFILE_' . strtoupper($signal) . '_MISSING', 'status' => 'not_declared', 'severity' => 'warning'];
            }
        }
        $diagnostics[] = ['code' => 'PAGE_EDITORIAL_REVIEW_REQUIRED', 'status' => 'review_required', 'severity' => 'advisory'];

        return $diagnostics;
    }

    /** @return list<array{id:string,docs_ref:?string}> */
    private function components(string $markdown): array
    {
        $markdown = $this->withoutFencedCode($markdown);
        preg_match_all('/^:::([a-z_][a-z0-9_]*)/mi', $markdown, $matches);
        $ids = array_values(array_unique(array_map(static fn (string $name): string => 'docara.' . $name, $matches[1] ?? [])));
        sort($ids, SORT_STRING);

        return array_map(fn (string $id): array => ['id' => $id, 'docs_ref' => $this->docsRef($id)], $ids);
    }

    private function docsRef(string $id): ?string
    {
        foreach (glob(dirname(__DIR__, 2) . '/resources/component-catalog/*/*.json') ?: [] as $path) {
            $record = json_decode((string) file_get_contents($path), true);
            if (($record['id'] ?? null) === $id) {
                return is_string($record['docs_ref'] ?? null) ? $record['docs_ref'] : null;
            }
        }

        return null;
    }

    /** @return list<array{id:string,mode:string,requested_preview:string,resolved_preview:string,reason:string}> */
    private function examples(ProjectRuntime $runtime, PageSource $source, string $markdown): array
    {
        return (new PortableMarkdownRenderer(
            projectExamples: new ProjectExampleRepository(
                $runtime->root,
                (string) ($runtime->site['base_url'] ?? '/'),
            ),
        ))->examplePreviews(
            $markdown,
            $runtime->root,
            $runtime->root . '/' . $source->path,
        );
    }

    /** @return list<array{url:string}> */
    private function links(string $markdown): array
    {
        $markdown = $this->withoutFencedCode($markdown);
        preg_match_all('/\[[^]]+]\(([^)]+)\)/', $markdown, $matches);

        return array_map(static fn (string $url): array => ['url' => $url], array_values(array_unique($matches[1] ?? [])));
    }

    private function withoutFencedCode(string $markdown): string
    {
        return preg_replace('/^(?:`{3,}|~{3,}).*?^(?:`{3,}|~{3,})\h*$/ms', '', $markdown) ?? $markdown;
    }

    /** @param list<array{url:string}> $links @return list<array<string, mixed>> */
    private function technicalDiagnostics(ProjectRuntime $runtime, PageSource $source, string $markdown, array $links, ?array $routeSet = null): array
    {
        $diagnostics = [];
        if ($routeSet === null) {
            $routeSet = [];
            foreach ($this->sources($runtime) as $candidate) {
                $routeSet[$this->publicRoute($runtime, $candidate)] = true;
            }
        }
        $locale = LocaleRegistry::fromSite($runtime->site)->get($source->locale);
        foreach ($links as $link) {
            $url = $link['url'];
            if (! str_starts_with($url, '/') || str_starts_with($url, '//')) {
                continue;
            }
            $path = preg_replace('/[?#].*$/', '', $url) ?: '/';
            $localized = '/' . implode('/', array_filter([$locale->publicPrefix, trim($path, '/')])) . '/';
            $localized = preg_replace('#/+#', '/', $localized) ?: '/';
            $publicPrefix = trim($locale->publicPrefix, '/');
            $pathRelative = ltrim($path, '/');
            $localeRelative = $publicPrefix !== '' && str_starts_with($pathRelative, $publicPrefix . '/')
                ? substr($pathRelative, strlen($publicPrefix) + 1)
                : null;
            $localeAsset = is_string($localeRelative)
                ? $runtime->root . '/' . trim($locale->contentRoot, '/') . '/' . $localeRelative
                : null;
            if (! isset($routeSet[$path])
                && ! isset($routeSet[$localized])
                && ! is_file($runtime->root . '/' . $pathRelative)
                && (! is_string($localeAsset) || ! is_file($localeAsset))
            ) {
                $diagnostics[] = ['code' => 'PAGE_LINK_TARGET_MISSING', 'status' => 'error', 'severity' => 'error', 'url' => $url];
            }
        }
        preg_match_all('/!\[[^]]*]\(([^)]+)\)/', $this->withoutFencedCode($markdown), $images);
        foreach ($images[1] ?? [] as $asset) {
            if (str_contains($asset, '://') || str_starts_with($asset, 'data:') || str_starts_with($asset, '/')) {
                continue;
            }
            $asset = preg_replace('/[?#].*$/', '', $asset) ?: '';
            $path = dirname($runtime->root . '/' . $source->path) . '/' . $asset;
            $publicAsset = $runtime->root . '/' . (preg_replace('#^(?:\.\./)+#', '', $asset) ?: $asset);
            if ($asset !== '' && ! is_file($path) && ! is_file($publicAsset)) {
                $diagnostics[] = ['code' => 'PAGE_ASSET_MISSING', 'status' => 'error', 'severity' => 'error', 'path' => $asset];
            }
        }

        return $diagnostics;
    }

    private function compileMarkdown(ProjectRuntime $runtime, PageSource $source, string $markdown): void
    {
        $frameworkPath = (string) ($runtime->site['framework_lock'] ?? '');
        if ($frameworkPath === '') {
            throw new PortableConfigurationException('FRAMEWORK_LOCK_PATH_INVALID', 'Page validation requires the configured Framework lock.');
        }
        $framework = FrameworkLock::fromJsonFile($runtime->root . '/' . ltrim($frameworkPath, '/'))->toArray();
        $project = ProjectSmartRuntime::fromSite($runtime->root, $runtime->site, $framework);
        $gateway = $project?->gateway ?? SmartComponentGateway::bundled($framework);
        (new MarkdownCompiler(smarts: $gateway))->compile($markdown, $source->path);
    }

    /** @return array{path:string,sha256:string}|null */
    private function sidecar(string $root, string $source): ?array
    {
        $path = preg_replace('/(?:\/index)?\.md$/', '.page.json', $source) ?: '';
        if ($path === '' || ! is_file($root . '/' . $path)) {
            return null;
        }

        return ['path' => $path, 'sha256' => hash_file('sha256', $root . '/' . $path) ?: 'unavailable'];
    }

    /** @return array{key:mixed,source_locale:string,peers:list<array{locale:string,path:string,route:string}>} */
    private function translationRelation(ProjectRuntime $runtime, PageSource $source, mixed $key): array
    {
        $sourceLocale = $this->translationSourceLocale($runtime, $source);
        $currentRelative = $this->contentRelativePath($runtime, $source);
        $peers = [];
        foreach ($this->sources($runtime) as $candidate) {
            if ($candidate->path === $source->path) {
                continue;
            }
            $candidateRaw = (string) file_get_contents($runtime->root . '/' . $candidate->path);
            $candidateDocument = (new FrontMatterParser)->parse($candidateRaw, $candidate->path);
            $candidateKey = $candidateDocument->metadata['translation_key'] ?? null;
            $sameKey = is_string($key) && $key !== '' && $candidateKey === $key;
            if (! $sameKey && $this->contentRelativePath($runtime, $candidate) !== $currentRelative) {
                continue;
            }
            $peers[] = ['locale' => $candidate->locale, 'path' => $candidate->path, 'route' => $this->publicRoute($runtime, $candidate)];
        }
        usort($peers, static fn (array $a, array $b): int => strcmp($a['locale'] . $a['path'], $b['locale'] . $b['path']));

        return ['key' => $key, 'source_locale' => $sourceLocale, 'peers' => $peers];
    }

    private function translationSourceLocale(ProjectRuntime $runtime, PageSource $source): string
    {
        $tracking = is_array($runtime->site['translation_tracking'] ?? null) ? $runtime->site['translation_tracking'] : [];

        return (string) ($tracking['source_locale'] ?? $runtime->site['default_locale'] ?? $source->locale);
    }

    private function contentRelativePath(ProjectRuntime $runtime, PageSource $source): string
    {
        $contentRoot = LocaleRegistry::fromSite($runtime->site)->get($source->locale)->contentRoot;

        return substr($source->path, strlen($contentRoot) + 1);
    }

    /** @return array<string, array{path:string,sha256:string}|null> */
    private function locks(ProjectRuntime $runtime): array
    {
        $translationTracking = is_array($runtime->site['translation_tracking'] ?? null) ? $runtime->site['translation_tracking'] : [];
        $documentationTracking = is_array($runtime->site['documentation_tracking'] ?? null) ? $runtime->site['documentation_tracking'] : [];

        return [
            'framework' => $this->lockDescriptor($runtime->root, (string) ($runtime->site['framework_lock'] ?? '')),
            'translations' => $this->lockDescriptor($runtime->root, (string) ($translationTracking['lock_file'] ?? '')),
            'documentation' => $this->lockDescriptor($runtime->root, (string) ($documentationTracking['lock_file'] ?? '')),
            'composer' => $this->lockDescriptor($runtime->root, 'composer.lock'),
        ];
    }

    /** @return array{enabled:bool,source_locale:?string,relations:list<array<string,mixed>>} */
    private function documentationRelation(ProjectRuntime $runtime, string $route, bool $full): array
    {
        $tracking = is_array($runtime->site['documentation_tracking'] ?? null) ? $runtime->site['documentation_tracking'] : [];
        if (($tracking['enabled'] ?? false) !== true || ! $full) {
            return ['enabled' => ($tracking['enabled'] ?? false) === true, 'source_locale' => $tracking['source_locale'] ?? null, 'relations' => []];
        }
        $relations = array_values(array_filter(
            (new DocumentationStatusService)->report($runtime->root)['items'],
            static fn (array $item): bool => isset($item['route']) && trim((string) $item['route'], '/') === trim($route, '/'),
        ));

        return ['enabled' => true, 'source_locale' => $tracking['source_locale'], 'relations' => $relations];
    }

    /** @return array{path:string,sha256:string}|null */
    private function lockDescriptor(string $root, string $relative): ?array
    {
        if ($relative === '' || str_starts_with($relative, '/') || str_contains($relative, '..')) {
            return null;
        }
        $absolute = $root . '/' . $relative;
        if (! is_file($absolute) || is_link($absolute)) {
            return null;
        }

        return ['path' => $relative, 'sha256' => hash_file('sha256', $absolute) ?: 'unavailable'];
    }

    private function gitHead(string $root): ?string
    {
        $directory = realpath($root);
        while (is_string($directory)) {
            $git = $directory . '/.git';
            if (is_dir($git)) {
                return $this->gitHeadFromDirectory($git);
            }
            if (is_file($git) && ! is_link($git)) {
                $pointer = trim((string) @file_get_contents($git));
                if (str_starts_with($pointer, 'gitdir: ')) {
                    $target = trim(substr($pointer, 8));
                    $gitDirectory = realpath(str_starts_with($target, '/') ? $target : $directory . '/' . $target);
                    if (is_string($gitDirectory) && is_dir($gitDirectory)) {
                        return $this->gitHeadFromDirectory($gitDirectory);
                    }
                }
            }
            $parent = dirname($directory);
            if ($parent === $directory) {
                break;
            }
            $directory = $parent;
        }

        return null;
    }

    private function gitHeadFromDirectory(string $git): ?string
    {
        $head = @file_get_contents($git . '/HEAD');
        if (! is_string($head)) {
            return null;
        }
        $head = trim($head);
        if (str_starts_with($head, 'ref: ')) {
            $ref = trim(substr($head, 5));
            $gitDirectories = [$git];
            $commonDirectory = @file_get_contents($git . '/commondir');
            if (is_string($commonDirectory)) {
                $common = realpath($git . '/' . trim($commonDirectory));
                if (is_string($common) && is_dir($common)) {
                    $gitDirectories[] = $common;
                }
            }
            foreach ($gitDirectories as $directory) {
                $value = @file_get_contents($directory . '/' . $ref);
                if (is_string($value) && preg_match('/^[a-f0-9]{40}$/D', trim($value)) === 1) {
                    return trim($value);
                }
                $packed = @file_get_contents($directory . '/packed-refs');
                if (is_string($packed) && preg_match('/^([a-f0-9]{40})\s+' . preg_quote($ref, '/') . '$/m', $packed, $match) === 1) {
                    return $match[1];
                }
            }

            return null;
        }

        return preg_match('/^[a-f0-9]{40}$/D', $head) === 1 ? $head : null;
    }
}
