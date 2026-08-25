<?php

declare(strict_types=1);

namespace Simai\Docara\Tooling;

use JsonException;
use RuntimeException;
use Throwable;

final class ProjectContext
{
    private const OUTPUT = 'graph/generated/ai-context/docara-unified.json';

    /** @return array<string, mixed> */
    public static function expected(string $root): array
    {
        $root = rtrim($root, '/');
        $graph = self::json($root, 'graph/graph.json');
        $state = self::map($graph, 'implementation_state', 'graph/graph.json');
        $goal = self::objectById($root, 'goals', self::string($state, 'completed_goal', 'implementation_state'));
        $stage = self::objectById($root, 'stages', self::string($state, 'last_completed_stage', 'implementation_state'));
        $batch = self::objectById($root, 'batches', self::string($state, 'last_completed_batch', 'implementation_state'));
        $goalData = $goal['data'];
        $stageData = $stage['data'];
        $batchData = $batch['data'];
        $roadmapSource = self::string($state, 'roadmap_source', 'implementation_state');
        $sources = [
            'graph/graph.json',
            'graph/dna/project-dna.json',
            $goal['path'],
            $stage['path'],
            $batch['path'],
        ];

        return [
            'schema_version' => '1.1.0',
            'context_id' => 'docara.unified.terminal',
            'generated_by' => 'scripts/project-context.php',
            'canonical_sources' => $sources,
            'canonical_sha256' => self::digest($root, $sources),
            'project' => [
                'id' => self::string($graph, 'graph_id', 'graph/graph.json'),
                'title' => self::string($graph, 'title', 'graph/graph.json'),
                'profile' => self::string($graph, 'graph_type', 'graph/graph.json'),
            ],
            'terminal' => [
                'state' => self::string($state, 'state', 'implementation_state'),
                'goal' => self::string($goalData, 'id', $goal['path']),
                'goal_readiness' => self::string($goalData, 'readiness', $goal['path']),
                'goal_lifecycle' => self::string($goalData, 'lifecycle', $goal['path']),
                'active_implementation' => self::boolean($state, 'active_implementation', 'implementation_state'),
                'last_completed_stage' => self::string($stageData, 'id', $stage['path']),
                'last_completed_stage_status' => self::string($stageData, 'status', $stage['path']),
                'last_completed_batch' => self::string($batchData, 'id', $batch['path']),
                'last_completed_batch_status' => self::string($batchData, 'status', $batch['path']),
                'completed_checkpoint' => self::string($state, 'completed_checkpoint', 'implementation_state'),
                'repository_revision' => self::string($state, 'repository_revision', 'implementation_state'),
                'product_baseline_revision' => self::string($state, 'product_baseline_revision', 'implementation_state'),
                'next_action' => self::string($state, 'next_action', 'implementation_state'),
                'evidence' => self::string($state, 'evidence', 'implementation_state'),
            ],
            'roadmap' => [
                'source' => $roadmapSource,
            ],
            'release_boundary' => self::map($state, 'release_boundary', 'implementation_state'),
            'architecture' => self::map($graph, 'architecture_target', 'graph/graph.json'),
            'historical_context' => self::map($graph, 'historical_context', 'graph/graph.json'),
            'forbidden_now' => self::stringList($batchData['forbidden_scope'] ?? null, $batch['path'] . '.forbidden_scope'),
            'read_order' => [
                'source/handoff/2026-08-09-docara-current-main-onboarding/STATUS.yaml',
                'source/workflow/ACTIVE.md',
                $roadmapSource,
                'graph/graph.json',
                $stage['path'],
                $batch['path'],
                self::OUTPUT,
                'source/handoff/2026-08-09-docara-current-main-onboarding/NEXT.md',
                'source/handoff/2026-08-09-docara-current-main-onboarding/RESULT.md',
            ],
        ];
    }

    public static function encoded(string $root): string
    {
        return json_encode(
            self::expected($root),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        ) . "\n";
    }

    public static function generate(string $root): void
    {
        $target = rtrim($root, '/') . '/' . self::OUTPUT;
        $directory = dirname($target);
        if (! is_dir($directory) && ! mkdir($directory, 0777, true) && ! is_dir($directory)) {
            throw new RuntimeException("PROJECT_CONTEXT_DIRECTORY_CREATE_FAILED:$directory");
        }

        $temporary = $target . '.tmp.' . getmypid();
        if (file_put_contents($temporary, self::encoded($root)) === false || ! rename($temporary, $target)) {
            @unlink($temporary);
            throw new RuntimeException("PROJECT_CONTEXT_WRITE_FAILED:$target");
        }
    }

    /** @return list<array{code: string, detail: string}> */
    public static function check(string $root): array
    {
        $issues = [];
        try {
            $expected = self::expected($root);
            $issues = [...$issues, ...self::canonicalConsistency($root, $expected)];
            $target = rtrim($root, '/') . '/' . self::OUTPUT;
            if (! is_file($target)) {
                $issues[] = self::issue('derived_context_missing', self::OUTPUT);
            } elseif ((string) file_get_contents($target) !== self::encoded($root)) {
                $issues[] = self::issue('derived_context_stale', self::OUTPUT . ' does not match canonical graph inputs');
            }
            $issues = [...$issues, ...self::handoffConsistency($root, $expected)];
        } catch (Throwable $throwable) {
            $issues[] = self::issue('project_context_invalid', $throwable->getMessage());
        }

        return $issues;
    }

    /** @param array<string, mixed> $expected
     * @return list<array{code: string, detail: string}>
     */
    private static function canonicalConsistency(string $root, array $expected): array
    {
        $terminal = self::map($expected, 'terminal', 'expected context');
        $graph = self::json($root, 'graph/graph.json');
        $state = self::map($graph, 'implementation_state', 'graph/graph.json');
        $goal = self::objectById($root, 'goals', self::string($state, 'completed_goal', 'implementation_state'))['data'];
        $stage = self::objectById($root, 'stages', self::string($state, 'last_completed_stage', 'implementation_state'))['data'];
        $batch = self::objectById($root, 'batches', self::string($state, 'last_completed_batch', 'implementation_state'))['data'];
        $issues = [];

        if (self::string($state, 'state', 'implementation_state') !== self::string($terminal, 'goal_readiness', 'terminal')) {
            $issues[] = self::issue('canonical_state_goal_readiness_mismatch', 'implementation_state.state must equal current goal readiness');
        }
        if (self::boolean($state, 'active_implementation', 'implementation_state')) {
            $issues[] = self::issue('canonical_terminal_implementation_active', 'terminal state must not expose an active implementation');
        }
        if (($goal['lifecycle'] ?? null) !== 'complete') {
            $issues[] = self::issue('canonical_goal_not_complete', 'terminal goal lifecycle must be complete');
        }
        if (! in_array(self::string($state, 'last_completed_batch', 'implementation_state'), self::stringList($stage['batch_refs'] ?? null, 'stage.batch_refs'), true)) {
            $issues[] = self::issue('canonical_last_stage_batch_mismatch', 'last completed batch is not owned by last completed stage');
        }
        if (($batch['parent_stage_ref'] ?? null) !== ($stage['id'] ?? null)) {
            $issues[] = self::issue('canonical_last_batch_parent_mismatch', 'last completed batch parent does not match last completed stage');
        }
        if (($stage['next_action'] ?? null) !== 'none' || ($batch['next_action'] ?? null) !== 'none') {
            $issues[] = self::issue('canonical_last_work_still_routes', 'last completed stage and batch must not route new implementation');
        }
        $releaseBoundary = self::map($state, 'release_boundary', 'implementation_state');
        if (($releaseBoundary['authorized'] ?? null) !== false
            || self::string($releaseBoundary, 'required_decision', 'release_boundary') !== 'explicit_user_decision'
            || self::string($state, 'next_action', 'implementation_state') !== 'explicit_user_decision') {
            $issues[] = self::issue('canonical_release_boundary_invalid', 'release contour must remain closed pending explicit_user_decision');
        }
        foreach (['repository_revision', 'product_baseline_revision'] as $revisionField) {
            if (preg_match('/^[0-9a-f]{40}$/', self::string($state, $revisionField, 'implementation_state')) !== 1) {
                $issues[] = self::issue('canonical_revision_invalid', "implementation_state.$revisionField must be a full Git revision");
            }
        }
        if (! is_file(rtrim($root, '/') . '/' . self::string($state, 'evidence', 'implementation_state'))) {
            $issues[] = self::issue('canonical_terminal_evidence_missing', 'terminal evidence path must exist');
        }

        return $issues;
    }

    /** @param array<string, mixed> $expected
     * @return list<array{code: string, detail: string}>
     */
    private static function handoffConsistency(string $root, array $expected): array
    {
        $terminal = self::map($expected, 'terminal', 'expected context');
        $state = self::string($terminal, 'state', 'terminal');
        $stage = self::string($terminal, 'last_completed_stage', 'terminal');
        $batch = self::string($terminal, 'last_completed_batch', 'terminal');
        $next = self::string($terminal, 'next_action', 'terminal');
        $evidence = self::string($terminal, 'evidence', 'terminal');
        $goal = self::string($terminal, 'goal', 'terminal');
        $repositoryRevision = self::string($terminal, 'repository_revision', 'terminal');
        $productBaseline = self::string($terminal, 'product_baseline_revision', 'terminal');
        $roadmapSource = self::string(self::map($expected, 'roadmap', 'expected context'), 'source', 'roadmap');
        $issues = [];

        $statusPath = 'source/handoff/2026-08-09-docara-current-main-onboarding/STATUS.yaml';
        $status = self::text($root, $statusPath);
        foreach ([
            'terminal_state' => $state,
            'completed_goal' => $goal,
            'last_completed_stage' => $stage,
            'last_completed_batch' => $batch,
            'next_action' => $next,
            'repository_revision' => $repositoryRevision,
            'product_baseline_revision' => $productBaseline,
            'evidence' => $evidence,
        ] as $key => $value) {
            if (self::yamlScalar($status, $key) !== $value) {
                $issues[] = self::issue("handoff_status_{$key}_mismatch", "$statusPath must expose $key=$value");
            }
        }

        $required = [
            'source/handoff/2026-08-09-docara-current-main-onboarding/START.md' => [
                "Terminal state: `$state`",
                "Completed goal: `$goal`",
                "Last completed stage: `$stage`",
                "Last completed batch: `$batch`",
                "Next action: `$next`",
                "Repository revision: `$repositoryRevision`",
                "Product baseline revision: `$productBaseline`",
            ],
            'source/workflow/ACTIVE.md' => [
                "- terminal state: `$state`;",
                "- completed goal: `$goal`;",
                "- last completed stage: `$stage`;",
                "- last completed batch: `$batch`;",
                "- next action: `$next`;",
                "- repository revision: `$repositoryRevision`;",
                "- product baseline revision: `$productBaseline`;",
            ],
            'source/handoff/2026-08-09-docara-current-main-onboarding/NEXT.md' => [
                "# Next action: `$next`",
                "Terminal state: `$state`",
                'Release authorized: `false`',
            ],
            'source/handoff/2026-08-09-docara-current-main-onboarding/RESULT.md' => [
                "Terminal state: `$state`",
                "Repository revision: `$repositoryRevision`",
                "Product baseline revision: `$productBaseline`",
            ],
            $roadmapSource => [
                "Terminal state: `$state`",
                "Next action: `$next`",
            ],
        ];
        foreach ($required as $relative => $needles) {
            $contents = self::text($root, $relative);
            foreach ($needles as $needle) {
                if (! str_contains($contents, $needle)) {
                    $issues[] = self::issue('handoff_semantic_marker_missing', "$relative missing $needle");
                }
            }
        }

        foreach (['source/handoff/2026-08-09-docara-current-main-onboarding/START.md'] as $relative) {
            $contents = self::text($root, $relative);
            foreach (['Terminal state:', 'Last completed stage:', 'Last completed batch:', 'Next action:'] as $label) {
                if (substr_count($contents, $label) !== 1) {
                    $issues[] = self::issue('active_router_marker_cardinality_invalid', "$relative must contain exactly one [$label] marker");
                }
            }
        }

        return $issues;
    }

    /** @return array{path: string, data: array<string, mixed>} */
    private static function objectById(string $root, string $directory, string $id): array
    {
        $base = rtrim($root, '/') . '/graph/specs/' . $directory;
        foreach (glob($base . '/*.json') ?: [] as $path) {
            $relative = substr($path, strlen(rtrim($root, '/') . '/'));
            $data = self::json($root, $relative);
            if (($data['id'] ?? null) === $id) {
                return ['path' => $relative, 'data' => $data];
            }
        }

        throw new RuntimeException("PROJECT_CONTEXT_OBJECT_NOT_FOUND:$directory:$id");
    }

    /** @param list<string> $sources */
    private static function digest(string $root, array $sources): string
    {
        $context = hash_init('sha256');
        foreach ($sources as $relative) {
            hash_update($context, $relative . "\0" . self::text($root, $relative) . "\0");
        }

        return hash_final($context);
    }

    /** @return array<string, mixed> */
    private static function json(string $root, string $relative): array
    {
        try {
            $value = json_decode(self::text($root, $relative), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException("PROJECT_CONTEXT_JSON_INVALID:$relative", previous: $exception);
        }
        if (! is_array($value)) {
            throw new RuntimeException("PROJECT_CONTEXT_JSON_OBJECT_REQUIRED:$relative");
        }

        return $value;
    }

    private static function text(string $root, string $relative): string
    {
        $path = rtrim($root, '/') . '/' . $relative;
        if (! is_file($path)) {
            throw new RuntimeException("PROJECT_CONTEXT_SOURCE_MISSING:$relative");
        }

        return (string) file_get_contents($path);
    }

    /** @param array<string, mixed> $source
     * @return array<string, mixed>
     */
    private static function map(array $source, string $key, string $owner): array
    {
        $value = $source[$key] ?? null;
        if (! is_array($value)) {
            throw new RuntimeException("PROJECT_CONTEXT_MAP_REQUIRED:$owner:$key");
        }

        return $value;
    }

    /** @param array<string, mixed> $source */
    private static function string(array $source, string $key, string $owner): string
    {
        $value = $source[$key] ?? null;
        if (! is_string($value) || $value === '') {
            throw new RuntimeException("PROJECT_CONTEXT_STRING_REQUIRED:$owner:$key");
        }

        return $value;
    }

    /** @param array<string, mixed> $source */
    private static function boolean(array $source, string $key, string $owner): bool
    {
        $value = $source[$key] ?? null;
        if (! is_bool($value)) {
            throw new RuntimeException("PROJECT_CONTEXT_BOOLEAN_REQUIRED:$owner:$key");
        }

        return $value;
    }

    /** @return list<string> */
    private static function stringList(mixed $value, string $owner): array
    {
        if (! is_array($value) || array_filter($value, static fn (mixed $item): bool => ! is_string($item)) !== []) {
            throw new RuntimeException("PROJECT_CONTEXT_STRING_LIST_REQUIRED:$owner");
        }

        return array_values($value);
    }

    private static function yamlScalar(string $yaml, string $key): ?string
    {
        if (preg_match('/^' . preg_quote($key, '/') . ':\h*(.+)$/m', $yaml, $matches) !== 1) {
            return null;
        }

        return trim($matches[1], " \t\"'");
    }

    /** @return array{code: string, detail: string} */
    private static function issue(string $code, string $detail): array
    {
        return ['code' => $code, 'detail' => $detail];
    }
}

/** @param list<string> $arguments */
function projectContextMain(array $arguments): int
{
    $command = $arguments[0] ?? 'check';
    $root = getcwd() ?: '.';
    foreach ($arguments as $index => $argument) {
        if ($argument === '--root' && isset($arguments[$index + 1])) {
            $root = $arguments[$index + 1];
        } elseif (str_starts_with($argument, '--root=')) {
            $root = substr($argument, 7);
        }
    }

    try {
        if ($command === 'generate') {
            ProjectContext::generate($root);
            $result = ['status' => 'success', 'operation' => 'generate', 'output' => 'graph/generated/ai-context/docara-unified.json'];
        } elseif ($command === 'check') {
            $issues = ProjectContext::check($root);
            $result = ['status' => $issues === [] ? 'success' : 'failed', 'operation' => 'check', 'issues' => $issues];
        } elseif ($command === 'print') {
            echo ProjectContext::encoded($root);

            return 0;
        } else {
            throw new RuntimeException("PROJECT_CONTEXT_COMMAND_UNKNOWN:$command");
        }
        echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . "\n";

        return ($result['status'] ?? null) === 'success' ? 0 : 1;
    } catch (Throwable $throwable) {
        fwrite(STDERR, $throwable->getMessage() . "\n");

        return 1;
    }
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    exit(projectContextMain(array_slice($argv, 1)));
}
