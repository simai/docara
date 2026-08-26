<?php

declare(strict_types=1);

namespace Simai\Docara\Application;

use Simai\Docara\Authoring\AuthoringProfileRegistry;
use Simai\Docara\File\ProjectFilesystemGuard;
use Simai\Docara\I18n\LocaleRegistry;
use Simai\Docara\Portable\CanonicalJson;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\Portable\SchemaRepository;
use Throwable;

final readonly class ScaffoldService
{
    public function __construct(private ProjectFilesystemGuard $writes = new ProjectFilesystemGuard) {}

    /** @param array<string, mixed> $options */
    public function plan(string $root, string $kind, string $id, array $options = []): OperationResult
    {
        $runtime = ProjectRuntime::load($root);
        $this->assertIdentity($runtime, $kind, $id, $options);
        $files = match ($kind) {
            'smart' => $this->smartFiles($id),
            'design' => $this->designFiles($id),
            'page' => $this->pageFiles($runtime, $id, $options),
        };
        $inputHashes = ['docara.json' => hash_file('sha256', $runtime->root . '/docara.json') ?: 'absent'];
        foreach (array_keys($files) as $path) {
            $absolute = $kind === 'page'
                ? $this->writes->authoringPath($runtime->root, $path, LocaleRegistry::fromSite($runtime->site)->get((string) $options['locale'])->contentRoot)
                : $this->writes->writablePath($runtime->root, $path);
            if (file_exists($absolute) || is_link($absolute)) {
                throw new PortableConfigurationException('SCAFFOLD_TARGET_EXISTS', "Scaffold target [$path] already exists.");
            }
            $inputHashes[$path] = is_file($absolute) && ! is_link($absolute) ? (hash_file('sha256', $absolute) ?: 'absent') : 'absent';
        }
        ksort($inputHashes, SORT_STRING);
        $records = [];
        foreach ($files as $path => $content) {
            $records[] = ['path' => $path, 'sha256' => hash('sha256', $content), 'content_base64' => base64_encode($content)];
        }
        $core = [
            'schema' => 'docara.scaffold_plan.v1',
            'kind' => $kind,
            'id' => $id,
            'namespace' => $runtime->namespace,
            'input_hashes' => $inputHashes,
            'files' => $records,
        ];
        if ($kind === 'page') {
            $core['options'] = ['locale' => (string) ($options['locale'] ?? ''), 'title' => (string) ($options['title'] ?? ''), 'profile' => (string) ($options['profile'] ?? '')];
        }
        $planId = hash('sha256', CanonicalJson::encode($core));
        $plan = ['plan_id' => $planId] + $core;
        (new SchemaRepository)->assertValid($plan, 'scaffold-plan.schema.json');
        $this->writes->putNewOrIdentical(
            $runtime->root,
            '.docara/sdk-plans/' . $planId . '.json',
            CanonicalJson::encodePretty($plan),
            'SCAFFOLD_PLAN_COLLISION',
        );

        return OperationResult::success('scaffold.plan', $id, [
            'plan_id' => $planId,
            'plan_path' => '.docara/sdk-plans/' . $planId . '.json',
            'input_hashes' => $inputHashes,
            'diff' => array_map(static fn (array $file): array => ['path' => $file['path'], 'action' => 'create', 'sha256' => $file['sha256']], $records),
        ], $runtime->provenance());
    }

    public function apply(string $root, string $planId): OperationResult
    {
        $runtime = ProjectRuntime::load($root);
        if (preg_match('/^[a-f0-9]{64}$/D', $planId) !== 1) {
            throw new PortableConfigurationException('SCAFFOLD_PLAN_ID_INVALID', 'Apply requires the exact SHA-256 plan id returned by dry-run.');
        }
        $planPath = $this->writes->regularFile($runtime->root, '.docara/sdk-plans/' . $planId . '.json');
        $plan = json_decode((string) file_get_contents($planPath), true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($plan)) {
            throw new PortableConfigurationException('SCAFFOLD_PLAN_INVALID', 'The scaffold plan must be a JSON object.');
        }
        (new SchemaRepository)->assertValid($plan, 'scaffold-plan.schema.json');
        $core = $plan;
        unset($core['plan_id']);
        if (($plan['plan_id'] ?? null) !== $planId || hash('sha256', CanonicalJson::encode($core)) !== $planId) {
            throw new PortableConfigurationException('SCAFFOLD_PLAN_HASH_MISMATCH', 'The scaffold plan contents do not match its plan id.');
        }
        if (($plan['namespace'] ?? null) !== $runtime->namespace) {
            throw new PortableConfigurationException('SCAFFOLD_PLAN_STALE', 'The project namespace changed after dry-run.');
        }
        foreach ($plan['input_hashes'] as $path => $expected) {
            $absolute = $path === 'docara.json'
                ? $runtime->root . '/docara.json'
                : (($plan['kind'] ?? null) === 'page'
                    ? $this->writes->authoringPath($runtime->root, (string) $path, LocaleRegistry::fromSite($runtime->site)->get((string) $plan['options']['locale'])->contentRoot)
                    : $this->writes->writablePath($runtime->root, (string) $path));
            $actual = is_file($absolute) && ! is_link($absolute) ? (hash_file('sha256', $absolute) ?: 'absent') : 'absent';
            if ($actual !== $expected) {
                throw new PortableConfigurationException('SCAFFOLD_PLAN_STALE', "Input [$path] changed after dry-run.");
            }
        }
        $created = [];
        try {
            foreach ($plan['files'] as $file) {
                $path = (string) $file['path'];
                $contentRoot = ($plan['kind'] ?? null) === 'page'
                    ? LocaleRegistry::fromSite($runtime->site)->get((string) $plan['options']['locale'])->contentRoot
                    : null;
                $target = $contentRoot === null ? $this->writes->writablePath($runtime->root, $path) : $this->writes->authoringPath($runtime->root, $path, $contentRoot);
                if (file_exists($target) || is_link($target)) {
                    throw new PortableConfigurationException('SCAFFOLD_TARGET_EXISTS', "Scaffold target [$path] already exists.");
                }
                $content = base64_decode((string) $file['content_base64'], true);
                if (! is_string($content) || hash('sha256', $content) !== $file['sha256']) {
                    throw new PortableConfigurationException('SCAFFOLD_CONTENT_HASH_MISMATCH', "Scaffold content [$path] is invalid.");
                }
                if ($contentRoot === null) {
                    $this->writes->putNew($runtime->root, $path, $content, 'SCAFFOLD_TARGET_EXISTS');
                } else {
                    $this->writes->putNewAuthoring($runtime->root, $path, $contentRoot, $content, 'SCAFFOLD_TARGET_EXISTS');
                }
                $created[] = $path;
            }
            ProjectRuntime::load($runtime->root);
            if (($plan['kind'] ?? null) === 'page') {
                $locale = (string) ($plan['options']['locale'] ?? '');
                $route = (string) ($plan['id'] ?? '');
                $definition = LocaleRegistry::fromSite($runtime->site)->get($locale);
                $public = '/' . implode('/', array_filter([$definition->publicPrefix, trim($route, '/')])) . '/';
                (new PageInspectionService)->inspect($runtime->root, preg_replace('#/+#', '/', $public) ?: '/');
            }
        } catch (Throwable $exception) {
            foreach (array_reverse($created) as $createdPath) {
                if (($plan['kind'] ?? null) === 'page') {
                    $this->writes->deleteAuthoringFile($runtime->root, $createdPath, LocaleRegistry::fromSite($runtime->site)->get((string) $plan['options']['locale'])->contentRoot);
                } else {
                    $this->writes->deleteFile($runtime->root, $createdPath);
                }
            }
            throw $exception;
        }

        return OperationResult::success('scaffold.apply', (string) $plan['id'], [
            'plan_id' => $planId,
            'created' => array_column($plan['files'], 'path'),
            'validation' => 'registry_reload_passed',
            'preview_ready' => true,
        ], $runtime->provenance());
    }

    /** @param array<string, mixed> $options */
    private function assertIdentity(ProjectRuntime $runtime, string $kind, string $id, array $options): void
    {
        if (! in_array($kind, ['smart', 'design', 'page'], true)) {
            throw new PortableConfigurationException('SCAFFOLD_KIND_INVALID', 'Scaffold kind must be smart, design or page.');
        }
        if ($kind === 'page') {
            if (preg_match('#^(?!/)(?!.*(?:^|/)\.\.(?:/|$))[a-z0-9][a-z0-9_-]*(?:/[a-z0-9][a-z0-9_-]*)*$#D', $id) !== 1) {
                throw new PortableConfigurationException('SCAFFOLD_PAGE_ROUTE_INVALID', 'Page route must be a safe lowercase locale-relative route.');
            }
            $locale = $options['locale'] ?? null;
            $title = $options['title'] ?? null;
            $profile = $options['profile'] ?? null;
            if (! is_string($locale) || ! is_string($title) || trim($title) === '') {
                throw new PortableConfigurationException('SCAFFOLD_PAGE_ARGUMENT_REQUIRED', 'Page scaffold requires locale and title.');
            }
            LocaleRegistry::fromSite($runtime->site)->get($locale);
            if (! is_string($profile) || ! in_array($profile, AuthoringProfileRegistry::IDS, true)) {
                throw new PortableConfigurationException('SCAFFOLD_PAGE_PROFILE_INVALID', 'Page scaffold requires a built-in profile.');
            }

            return;
        }
        if ($runtime->namespace === null || ! str_starts_with($id, $runtime->namespace . '.')
            || preg_match('/^[a-z][a-z0-9-]*(?:\.[a-z][a-z0-9_-]*)+$/D', $id) !== 1) {
            throw new PortableConfigurationException('SCAFFOLD_NAMESPACE_FORBIDDEN', 'Scaffold id must belong to smart.namespace from docara.json.');
        }
    }

    /** @param array<string, mixed> $options @return array<string, string> */
    private function pageFiles(ProjectRuntime $runtime, string $id, array $options): array
    {
        $locale = (string) $options['locale'];
        $title = trim((string) $options['title']);
        $profile = (string) $options['profile'];
        $contentRoot = LocaleRegistry::fromSite($runtime->site)->get($locale)->contentRoot;
        $path = $contentRoot . '/' . trim($id, '/') . '.md';
        $quotedTitle = json_encode($title, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return [$path => "---\ntitle: {$quotedTitle}\ndraft: true\nprofile: {$profile}\n---\n\n# {$title}\n\nDraft.\n"];
    }

    /** @return array<string, string> */
    private function smartFiles(string $id): array
    {
        $escaped = htmlspecialchars($id, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $manifest = [
            'schemaVersion' => '1.0', 'kind' => 'smart', 'code' => $id, 'title' => ucfirst(str_replace(['.', '_'], ' ', $id)),
            'render' => ['mode' => 'server-first', 'strategy' => 'server-static', 'template' => 'default', 'hydration' => 'none', 'domStrategy' => 'none', 'updateStrategy' => 'none', 'initialHtml' => 'complete', 'frontendOwnership' => 'none'],
            'props' => ['title' => ['type' => 'string', 'required' => true], 'text' => ['type' => 'string', 'required' => true]],
            'slots' => [], 'assets' => ['css' => [], 'js' => [], 'depends' => ['simai.ui']],
            'meta' => ['ownerPackage' => 'project/' . explode('.', $id)[0], 'version' => '1.0.0'],
            'ai' => [
                'summary' => 'Project-local portable Smart scaffold.',
                'when_to_use' => ['Use for project-owned content with a title and text.'],
                'accessibility' => ['role' => 'region', 'accessible_name_prop' => 'title'],
                'fixtures' => ['default' => ['props' => ['title' => 'Example title', 'text' => 'Example text']]],
                'states' => ['default' => ['fixture' => 'default']],
            ],
        ];
        $view = ['schemaVersion' => '1.0', 'kind' => 'smart.view', 'smart' => $id, 'code' => 'default', 'template' => 'default', 'props' => []];
        $template = <<<'PHP'
<?php

declare(strict_types=1);

$title = htmlspecialchars((string) ($props['title'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$text = htmlspecialchars((string) ($props['text'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
echo '<aside data-docara-smart="__ID__"><strong>' . $title . '</strong><p>' . $text . '</p></aside>';
PHP;
        $template = str_replace('__ID__', $escaped, $template) . "\n";
        $base = 'smart/' . $id;

        return [
            $base . '/manifest.json' => CanonicalJson::encodePretty($manifest),
            $base . '/view/default.json' => CanonicalJson::encodePretty($view),
            $base . '/template/default.php' => $template,
        ];
    }

    /** @return array<string, string> */
    private function designFiles(string $id): array
    {
        $namespace = explode('.', $id)[0];
        $name = implode('.', array_slice(explode('.', $id), 1));
        $layout = $id;
        $section = $namespace . '.' . $name . '_section';
        $block = $namespace . '.' . $name . '_block';
        $layoutView = 'layout.' . $layout;
        $sectionView = 'section.' . $section;
        $regions = [
            'header' => ['required' => false, 'default_enabled' => true, 'default_sections' => [['id' => 'site-header', 'section' => 'docara.header']], 'section_types' => ['navigation', 'shell'], 'capabilities' => ['shell.brand', 'shell.primary-navigation']],
            'sidebar' => ['required' => false, 'default_enabled' => true, 'default_sections' => [['id' => 'docs-navigation', 'section' => 'docara.navigation']], 'section_types' => ['navigation', 'shell'], 'capabilities' => ['shell.secondary-navigation', 'shell.content-before']],
            'main' => ['required' => true, 'default_enabled' => true, 'default_sections' => [], 'section_types' => ['content'], 'capabilities' => ['content.document']],
            'outline' => ['required' => false, 'default_enabled' => true, 'default_sections' => [['id' => 'page-outline', 'section' => 'docara.outline']], 'section_types' => ['navigation', 'shell'], 'capabilities' => ['shell.outline']],
            'footer' => ['required' => false, 'default_enabled' => false, 'default_sections' => [], 'section_types' => ['shell'], 'capabilities' => ['shell.content-after', 'shell.footer']],
        ];
        $layoutTree = [
            'kind' => 'element', 'tag' => 'article', 'identity' => 'page',
            'utilities' => ['flex', 'flex-col', 'min-h-screen', 'w-full'],
            'children' => [
                ['kind' => 'region', 'region' => 'header', 'tag' => 'header'],
                ['kind' => 'element', 'tag' => 'div', 'utilities' => ['flex', 'flex-col', 'lg:flex-row', 'w-full'], 'children' => [
                    ['kind' => 'region', 'region' => 'sidebar', 'tag' => 'aside'],
                    ['kind' => 'region', 'region' => 'main', 'tag' => 'main'],
                    ['kind' => 'region', 'region' => 'outline', 'tag' => 'aside'],
                ]],
                ['kind' => 'region', 'region' => 'footer', 'tag' => 'footer'],
            ],
        ];

        return [
            'design/layouts/' . $layout . '.json' => json_encode(['schema' => 'docara.layout.v1', 'key' => $layout, 'default' => false, 'view' => $layoutView, 'configuration' => ['container' => ['max' => 7], 'scrollbar' => ['preset' => 'overlay'], 'content' => ['gap' => 0]], 'document' => ['region' => 'main', 'section' => $section, 'slot' => 'content', 'block' => $block], 'regions' => $regions, 'assets' => ['simai.framework', 'docara.reader']], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n",
            'design/views/' . $layoutView . '.json' => CanonicalJson::encodePretty(['schema' => 'docara.view_tree.v1', 'key' => $layoutView, 'tree' => $layoutTree]),
            'design/sections/' . $section . '.json' => CanonicalJson::encodePretty(['schema' => 'docara.section.v1', 'key' => $section, 'type' => 'content', 'view' => $sectionView, 'allowed_regions' => ['main'], 'capabilities' => ['content.document'], 'slots' => ['content'], 'allowed_blocks' => [$block, 'content.markdown', 'content.smart'], 'blocks' => []]),
            'design/views/' . $sectionView . '.json' => CanonicalJson::encodePretty(['schema' => 'docara.view_tree.v1', 'key' => $sectionView, 'tree' => ['kind' => 'element', 'tag' => 'section', 'identity' => 'section', 'children' => [['kind' => 'slot', 'slot' => 'content']]]]),
            'design/blocks/' . $block . '.json' => CanonicalJson::encodePretty(['schema' => 'docara.block.v1', 'key' => $block, 'kind' => 'content', 'renderer' => 'block.document']),
        ];
    }
}
