<?php

declare(strict_types=1);

namespace Simai\Docara\Application;

use Simai\Docara\Portable\CanonicalJson;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\Portable\SchemaRepository;
use Throwable;

final readonly class ScaffoldService
{
    public function plan(string $root, string $kind, string $id): OperationResult
    {
        $runtime = ProjectRuntime::load($root);
        $this->assertIdentity($runtime, $kind, $id);
        $files = $kind === 'smart' ? $this->smartFiles($id) : $this->designFiles($id);
        $inputHashes = ['docara.json' => hash_file('sha256', $runtime->root . '/docara.json') ?: 'absent'];
        foreach (array_keys($files) as $path) {
            $absolute = $runtime->root . '/' . $path;
            $this->assertExistingParentsSafe($runtime->root, $path);
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
            'namespace' => (string) $runtime->namespace,
            'input_hashes' => $inputHashes,
            'files' => $records,
        ];
        $planId = hash('sha256', CanonicalJson::encode($core));
        $plan = ['plan_id' => $planId] + $core;
        (new SchemaRepository)->assertValid($plan, 'scaffold-plan.schema.json');
        $planDirectory = $runtime->root . '/.docara/sdk-plans';
        $this->ensureSafeDirectory($runtime->root, '.docara');
        $this->ensureSafeDirectory($runtime->root, '.docara/sdk-plans');
        $planPath = $planDirectory . '/' . $planId . '.json';
        $this->writeExclusiveOrIdentical($planPath, CanonicalJson::encodePretty($plan));

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
        $planPath = $runtime->root . '/.docara/sdk-plans/' . $planId . '.json';
        if (! is_file($planPath) || is_link($planPath) || (lstat($planPath)['nlink'] ?? 1) !== 1) {
            throw new PortableConfigurationException('SCAFFOLD_PLAN_MISSING', 'The exact dry-run plan is missing or unsafe.');
        }
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
            $absolute = $this->safeTarget($runtime->root, (string) $path, true);
            $actual = is_file($absolute) && ! is_link($absolute) ? (hash_file('sha256', $absolute) ?: 'absent') : 'absent';
            if ($actual !== $expected) {
                throw new PortableConfigurationException('SCAFFOLD_PLAN_STALE', "Input [$path] changed after dry-run.");
            }
        }
        $created = [];
        try {
            foreach ($plan['files'] as $file) {
                $path = (string) $file['path'];
                $target = $this->safeTarget($runtime->root, $path, true);
                if (file_exists($target) || is_link($target)) {
                    throw new PortableConfigurationException('SCAFFOLD_TARGET_EXISTS', "Scaffold target [$path] already exists.");
                }
                $content = base64_decode((string) $file['content_base64'], true);
                if (! is_string($content) || hash('sha256', $content) !== $file['sha256']) {
                    throw new PortableConfigurationException('SCAFFOLD_CONTENT_HASH_MISMATCH', "Scaffold content [$path] is invalid.");
                }
                $relativeDirectory = dirname($path);
                $this->ensureSafeDirectory($runtime->root, $relativeDirectory);
                $temporary = $target . '.docara-' . substr($planId, 0, 12) . '.tmp';
                if (file_put_contents($temporary, $content, LOCK_EX) !== strlen($content) || ! rename($temporary, $target)) {
                    @unlink($temporary);
                    throw new PortableConfigurationException('SCAFFOLD_APPLY_WRITE_FAILED', "Scaffold target [$path] could not be written atomically.");
                }
                $created[] = $target;
            }
            ProjectRuntime::load($runtime->root);
        } catch (Throwable $exception) {
            foreach (array_reverse($created) as $createdPath) {
                @unlink($createdPath);
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

    private function assertIdentity(ProjectRuntime $runtime, string $kind, string $id): void
    {
        if (! in_array($kind, ['smart', 'design'], true)) {
            throw new PortableConfigurationException('SCAFFOLD_KIND_INVALID', 'Scaffold kind must be smart or design.');
        }
        if ($runtime->namespace === null || ! str_starts_with($id, $runtime->namespace . '.')
            || preg_match('/^[a-z][a-z0-9-]*(?:\.[a-z][a-z0-9_-]*)+$/D', $id) !== 1) {
            throw new PortableConfigurationException('SCAFFOLD_NAMESPACE_FORBIDDEN', 'Scaffold id must belong to smart.namespace from docara.json.');
        }
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
            'ai' => ['summary' => 'Project-local portable Smart scaffold.', 'when_to_use' => ['Use for project-owned content with a title and text.']],
        ];
        $view = ['schemaVersion' => '1.0', 'kind' => 'smart.view', 'smart' => $id, 'code' => 'default', 'template' => 'default', 'props' => []];
        $template = <<<'PHP'
<?php

declare(strict_types=1);

$title = htmlspecialchars((string) ($props['title'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$text = htmlspecialchars((string) ($props['text'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
echo '<aside data-project-smart="__ID__"><strong>' . $title . '</strong><p>' . $text . '</p></aside>';
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

        return [
            'design/layouts/' . $layout . '.json' => CanonicalJson::encodePretty(['schema' => 'docara.layout.v1', 'key' => $layout, 'default' => false, 'view' => $layoutView, 'configuration' => ['container' => ['max' => 7], 'scrollbar' => ['preset' => 'overlay'], 'content' => ['gap' => 0]], 'document' => ['region' => 'main', 'section' => $section, 'slot' => 'content', 'block' => $block], 'regions' => ['main' => ['required' => true, 'default_enabled' => true, 'default_sections' => [], 'section_types' => ['content']]], 'assets' => []]),
            'design/views/' . $layoutView . '.json' => CanonicalJson::encodePretty(['schema' => 'docara.view_tree.v1', 'key' => $layoutView, 'tree' => ['kind' => 'region', 'region' => 'main', 'tag' => 'main']]),
            'design/sections/' . $section . '.json' => CanonicalJson::encodePretty(['schema' => 'docara.section.v1', 'key' => $section, 'type' => 'content', 'view' => $sectionView, 'allowed_regions' => ['main'], 'slots' => ['content'], 'allowed_blocks' => [$block, 'content.markdown', 'content.smart'], 'blocks' => []]),
            'design/views/' . $sectionView . '.json' => CanonicalJson::encodePretty(['schema' => 'docara.view_tree.v1', 'key' => $sectionView, 'tree' => ['kind' => 'element', 'tag' => 'section', 'identity' => 'section', 'children' => [['kind' => 'slot', 'slot' => 'content']]]]),
            'design/blocks/' . $block . '.json' => CanonicalJson::encodePretty(['schema' => 'docara.block.v1', 'key' => $block, 'kind' => 'content', 'renderer' => 'block.document']),
        ];
    }

    private function ensureSafeDirectory(string $root, string $relative): void
    {
        $current = $root;
        foreach (explode('/', str_replace('\\', '/', trim($relative, '/'))) as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..' || preg_match('/^[a-zA-Z0-9._-]+$/D', $segment) !== 1) {
                throw new PortableConfigurationException('SCAFFOLD_PATH_FORBIDDEN', 'Scaffold directory contains an unsafe segment.');
            }
            $current .= '/' . $segment;
            if (file_exists($current) || is_link($current)) {
                $stat = @lstat($current);
                if (is_link($current) || ! is_dir($current) || ($stat['nlink'] ?? 1) < 1) {
                    throw new PortableConfigurationException('SCAFFOLD_PATH_UNSAFE', "Scaffold directory [$relative] is unsafe.");
                }

                continue;
            }
            if (! mkdir($current, 0755)) {
                throw new PortableConfigurationException('SCAFFOLD_DIRECTORY_CREATE_FAILED', "Scaffold directory [$relative] could not be created.");
            }
        }
    }

    private function safeTarget(string $root, string $relative, bool $allowConfig = false): string
    {
        $relative = str_replace('\\', '/', $relative);
        if ($relative === 'docara.json' && $allowConfig) {
            return $root . '/docara.json';
        }
        if (str_contains($relative, "\0") || str_starts_with($relative, '/') || str_contains('/' . $relative . '/', '/../')
            || preg_match('~^(?:smart|design)/[a-zA-Z0-9._/-]+$~D', $relative) !== 1) {
            throw new PortableConfigurationException('SCAFFOLD_PATH_FORBIDDEN', "Scaffold path [$relative] is outside project-owned roots.");
        }

        return $root . '/' . $relative;
    }

    private function assertExistingParentsSafe(string $root, string $relative): void
    {
        $segments = explode('/', str_replace('\\', '/', $relative));
        array_pop($segments);
        $current = $root;
        foreach ($segments as $segment) {
            $current .= '/' . $segment;
            if (! file_exists($current) && ! is_link($current)) {
                continue;
            }
            $stat = @lstat($current);
            if (is_link($current) || ! is_dir($current) || ! is_array($stat)) {
                throw new PortableConfigurationException('SCAFFOLD_PATH_UNSAFE', "Scaffold parent for [$relative] is unsafe.");
            }
        }
    }

    private function writeExclusiveOrIdentical(string $path, string $contents): void
    {
        if (is_file($path)) {
            if (! hash_equals(hash('sha256', (string) file_get_contents($path)), hash('sha256', $contents))) {
                throw new PortableConfigurationException('SCAFFOLD_PLAN_COLLISION', 'A different plan exists at the deterministic plan path.');
            }

            return;
        }
        $handle = @fopen($path, 'x');
        if ($handle === false) {
            throw new PortableConfigurationException('SCAFFOLD_PLAN_WRITE_FAILED', 'The dry-run plan could not be written safely.');
        }
        try {
            if (fwrite($handle, $contents) !== strlen($contents)) {
                throw new PortableConfigurationException('SCAFFOLD_PLAN_WRITE_FAILED', 'The dry-run plan could not be written completely.');
            }
        } finally {
            fclose($handle);
        }
    }
}
