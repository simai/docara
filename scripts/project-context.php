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
        $goal = self::objectById($root, 'goals', self::string($state, 'current_goal', 'implementation_state'));
        $stage = self::objectById($root, 'stages', self::string($state, 'current_stage', 'implementation_state'));
        $batch = self::objectById($root, 'batches', self::string($state, 'current_batch', 'implementation_state'));
        $goalData = $goal['data'];
        $stageData = $stage['data'];
        $batchData = $batch['data'];
        $sources = [
            'graph/graph.json',
            'graph/dna/project-dna.json',
            $goal['path'],
            $stage['path'],
            $batch['path'],
        ];

        return [
            'schema_version' => '1.0.0',
            'context_id' => 'docara.unified.active',
            'generated_by' => 'scripts/project-context.php',
            'canonical_sources' => $sources,
            'canonical_sha256' => self::digest($root, $sources),
            'project' => [
                'id' => self::string($graph, 'graph_id', 'graph/graph.json'),
                'title' => self::string($graph, 'title', 'graph/graph.json'),
                'profile' => self::string($graph, 'graph_type', 'graph/graph.json'),
            ],
            'active' => [
                'state' => self::string($state, 'state', 'implementation_state'),
                'goal' => self::string($goalData, 'id', $goal['path']),
                'goal_readiness' => self::string($goalData, 'readiness', $goal['path']),
                'stage' => self::string($stageData, 'id', $stage['path']),
                'stage_readiness' => self::string($stageData, 'readiness', $stage['path']),
                'stage_status' => self::string($stageData, 'status', $stage['path']),
                'batch' => self::string($batchData, 'id', $batch['path']),
                'batch_readiness' => self::string($batchData, 'readiness', $batch['path']),
                'batch_status' => self::string($batchData, 'status', $batch['path']),
                'completed_checkpoint' => self::string($state, 'completed_checkpoint', 'implementation_state'),
                'candidate_revision' => self::string($state, 'candidate_revision', 'implementation_state'),
                'next_action' => self::string($state, 'next_action', 'implementation_state'),
                'evidence' => self::string($state, 'evidence', 'implementation_state'),
            ],
            'roadmap' => [
                'source' => 'source/workflow/2026-08-02-docara-extensible-lego-architecture-plan.md',
                'next_goal' => self::map($state, 'next_goal', 'implementation_state'),
            ],
            'architecture' => self::map($graph, 'architecture_target', 'graph/graph.json'),
            'historical_context' => self::map($graph, 'historical_context', 'graph/graph.json'),
            'forbidden_now' => self::stringList($batchData['forbidden_scope'] ?? null, $batch['path'] . '.forbidden_scope'),
            'read_order' => [
                'source/handoff/docara-unified-architecture/STATUS.yaml',
                'source/workflow/ACTIVE.md',
                'source/workflow/2026-08-02-docara-extensible-lego-architecture-plan.md',
                'graph/graph.json',
                $stage['path'],
                $batch['path'],
                self::OUTPUT,
                'source/handoff/docara-unified-architecture/NEXT.md',
                'source/handoff/docara-unified-architecture/RESULT.md',
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
        $active = self::map($expected, 'active', 'expected context');
        $graph = self::json($root, 'graph/graph.json');
        $state = self::map($graph, 'implementation_state', 'graph/graph.json');
        $stage = self::objectById($root, 'stages', self::string($state, 'current_stage', 'implementation_state'))['data'];
        $batch = self::objectById($root, 'batches', self::string($state, 'current_batch', 'implementation_state'))['data'];
        $issues = [];

        if (self::string($state, 'state', 'implementation_state') !== self::string($active, 'goal_readiness', 'active')) {
            $issues[] = self::issue('canonical_state_goal_readiness_mismatch', 'implementation_state.state must equal current goal readiness');
        }
        if (! in_array(self::string($state, 'current_batch', 'implementation_state'), self::stringList($stage['batch_refs'] ?? null, 'stage.batch_refs'), true)) {
            $issues[] = self::issue('canonical_stage_batch_mismatch', 'current batch is not owned by current stage');
        }
        if (($batch['parent_stage_ref'] ?? null) !== ($stage['id'] ?? null)) {
            $issues[] = self::issue('canonical_batch_parent_mismatch', 'batch parent_stage_ref does not match current stage');
        }
        if (self::string($state, 'candidate_revision', 'implementation_state') !== ($batch['candidate_revision'] ?? null)) {
            $issues[] = self::issue('canonical_candidate_mismatch', 'implementation candidate differs from current batch candidate');
        }
        if (self::string($state, 'next_action', 'implementation_state') !== ($batch['next_action'] ?? null)) {
            $issues[] = self::issue('canonical_next_action_mismatch', 'implementation next action differs from current batch next action');
        }
        $evidence = self::string($state, 'evidence', 'implementation_state');
        if (! in_array($evidence, self::stringList($stage['evidence_refs'] ?? null, 'stage.evidence_refs'), true)
            || ! in_array($evidence, self::stringList($batch['evidence_refs'] ?? null, 'batch.evidence_refs'), true)) {
            $issues[] = self::issue('canonical_evidence_mismatch', 'implementation evidence is not shared by current stage and batch');
        }

        return $issues;
    }

    /** @param array<string, mixed> $expected
     * @return list<array{code: string, detail: string}>
     */
    private static function handoffConsistency(string $root, array $expected): array
    {
        $active = self::map($expected, 'active', 'expected context');
        $nextGoal = self::map(self::map($expected, 'roadmap', 'expected context'), 'next_goal', 'roadmap');
        $candidate = self::string($active, 'candidate_revision', 'active');
        $state = self::string($active, 'state', 'active');
        $stage = self::string($active, 'stage', 'active');
        $batch = self::string($active, 'batch', 'active');
        $next = self::string($active, 'next_action', 'active');
        $evidence = self::string($active, 'evidence', 'active');
        $goal = self::string($active, 'goal', 'active');
        $nextGoalId = self::string($nextGoal, 'id', 'next_goal');
        $nextGoalStatus = self::string($nextGoal, 'status', 'next_goal');
        $nextGoalAuthorized = ($nextGoal['authorized'] ?? null) === true ? 'true' : 'false';
        $issues = [];

        $statusPath = 'source/handoff/docara-unified-architecture/STATUS.yaml';
        $status = self::text($root, $statusPath);
        foreach ([
            'goal' => $goal,
            'state' => $state,
            'current_stage' => $stage,
            'current_batch' => $batch,
            'next_action' => $next,
            'candidate_revision' => $candidate,
            'evidence' => $evidence,
        ] as $key => $value) {
            if (self::yamlScalar($status, $key) !== $value) {
                $issues[] = self::issue("handoff_status_{$key}_mismatch", "$statusPath must expose $key=$value");
            }
        }

        $required = [
            'source/handoff/docara-unified-architecture/START.md' => [
                "Current state: `$state`",
                "Current goal: `$goal`",
                "Current stage: `$stage`",
                "Current batch: `$batch`",
                "Current next action: `$next`",
                "Current evidence: `$evidence`",
                "Current candidate: `$candidate`",
                "Next roadmap goal: `$nextGoalId`",
                "Next roadmap status: `$nextGoalStatus`",
                "Next roadmap authorized: `$nextGoalAuthorized`",
            ],
            'source/workflow/ACTIVE.md' => [
                "- state: `$state`;",
                "- goal: `$goal`;",
                "- stage: `$stage`;",
                "- batch: `$batch`;",
                "- next action: `$next`;",
                "- candidate: `$candidate`;",
                "- fresh evidence: `$evidence`;",
            ],
            'source/handoff/docara-unified-architecture/NEXT.md' => [
                "# Next action: `$next`",
                "Current state: `$state`",
                "Current candidate: `$candidate`",
                "Current evidence: `$evidence`",
            ],
            'source/handoff/docara-unified-architecture/RESULT.md' => [
                "Current state: `$state`",
                "Current candidate: `$candidate`",
                "Current evidence: `$evidence`",
            ],
            'source/workflow/2026-08-02-docara-extensible-lego-architecture-plan.md' => [
                "Status: `$state`",
                "Current stage: `$stage`",
                "Current batch: `$batch`",
                "Current next action: `$next`",
                "Next roadmap goal: `$nextGoalId` (`$nextGoalStatus`, authorized=`$nextGoalAuthorized`)",
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

        foreach (['source/handoff/docara-unified-architecture/START.md'] as $relative) {
            $contents = self::text($root, $relative);
            foreach (['Current state:', 'Current stage:', 'Current batch:', 'Current next action:'] as $label) {
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
