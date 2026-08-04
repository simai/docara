<?php

declare(strict_types=1);

namespace Tests\Unit;

require_once dirname(__DIR__, 2) . '/scripts/project-context.php';

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Simai\Docara\Tooling\ProjectContext;
use Tests\TestCase;

final class ProjectContextContractTest extends TestCase
{
    #[Test]
    public function committed_context_and_handoff_match_canonical_graph_state(): void
    {
        self::assertSame([], ProjectContext::check($this->repositoryRoot()));

        $context = ProjectContext::expected($this->repositoryRoot());
        $graph = $this->json($this->repositoryRoot() . '/graph/graph.json');
        $state = $graph['implementation_state'];
        self::assertSame($state['state'], $context['active']['state']);
        self::assertSame($state['current_stage'], $context['active']['stage']);
        self::assertSame($state['current_batch'], $context['active']['batch']);
        self::assertSame($state['next_action'], $context['active']['next_action']);
        self::assertSame($state['next_goal'], $context['roadmap']['next_goal']);
        self::assertSame($state['roadmap_source'], $context['roadmap']['source']);
        self::assertFalse($context['historical_context']['release_baseline']['executable']);
    }

    #[Test]
    public function generation_is_deterministic_for_identical_canonical_inputs(): void
    {
        $root = $this->shadowRoot();
        ProjectContext::generate($root);
        $first = (string) file_get_contents($root . '/graph/generated/ai-context/docara-unified.json');
        ProjectContext::generate($root);

        self::assertSame($first, file_get_contents($root . '/graph/generated/ai-context/docara-unified.json'));
        self::assertSame([], ProjectContext::check($root));
    }

    /** @param scalar $replacement */
    #[Test]
    #[DataProvider('staleCanonicalStateProvider')]
    public function stale_stage_batch_next_evidence_or_candidate_fails_closed(
        string $field,
        mixed $replacement,
        string $canonicalIssue,
    ): void {
        $root = $this->shadowRoot();
        $graphPath = $root . '/graph/graph.json';
        $graph = $this->json($graphPath);
        $graph['implementation_state'][$field] = $replacement;
        $this->writeJson($graphPath, $graph);

        $codes = array_column(ProjectContext::check($root), 'code');
        self::assertContains('derived_context_stale', $codes);
        self::assertContains($canonicalIssue, $codes);
    }

    /** @return iterable<string, array{string, string, string}> */
    public static function staleCanonicalStateProvider(): iterable
    {
        yield 'stage' => [
            'current_stage',
            'docara.stage.r2.production_readiness',
            'canonical_stage_batch_mismatch',
        ];
        yield 'batch' => [
            'current_batch',
            'docara.batch.r2.prepare_deployment',
            'canonical_batch_parent_mismatch',
        ];
        yield 'next action' => [
            'next_action',
            'deploy_docara_test',
            'canonical_next_action_mismatch',
        ];
        yield 'evidence' => [
            'evidence',
            'source/workflow/evidence/2026-08-02-docara-r2-production-readiness/INDEX.md',
            'canonical_evidence_mismatch',
        ];
        yield 'candidate' => [
            'candidate_revision',
            'be0ba2db5254e468c7c014016ade02e8b4f3f16c',
            'canonical_candidate_mismatch',
        ];
    }

    #[Test]
    public function regenerating_context_without_handoff_sync_still_fails(): void
    {
        $root = $this->shadowRoot();
        $graphPath = $root . '/graph/graph.json';
        $graph = $this->json($graphPath);
        $batchPath = $this->specPathForId($root, 'batches', $graph['implementation_state']['current_batch']);
        $batch = $this->json($batchPath);
        $graph['implementation_state']['next_action'] = 'fresh_independent_audit_action';
        $batch['next_action'] = 'fresh_independent_audit_action';
        $this->writeJson($graphPath, $graph);
        $this->writeJson($batchPath, $batch);
        ProjectContext::generate($root);

        $codes = array_column(ProjectContext::check($root), 'code');
        self::assertNotContains('derived_context_stale', $codes);
        self::assertContains('handoff_status_next_action_mismatch', $codes);
        self::assertContains('handoff_semantic_marker_missing', $codes);
    }

    #[Test]
    public function stale_handoff_marker_fails_even_after_context_regeneration(): void
    {
        $root = $this->shadowRoot();
        $startPath = $root . '/source/handoff/docara-unified-architecture/START.md';
        $start = (string) file_get_contents($startPath);
        $graph = $this->json($root . '/graph/graph.json');
        $start = str_replace(
            'Current stage: `' . $graph['implementation_state']['current_stage'] . '`',
            'Current stage: `docara.stage.r2.production_readiness`',
            $start,
        );
        $this->filesystem->put($startPath, $start);
        ProjectContext::generate($root);

        $codes = array_column(ProjectContext::check($root), 'code');
        self::assertNotContains('derived_context_stale', $codes);
        self::assertContains('handoff_semantic_marker_missing', $codes);
    }

    private function repositoryRoot(): string
    {
        return dirname(__DIR__, 2);
    }

    private function shadowRoot(): string
    {
        $root = $this->tmpPath('project-context');
        $files = [
            'graph/graph.json',
            'graph/dna/project-dna.json',
            'graph/generated/ai-context/docara-unified.json',
            'source/handoff/docara-unified-architecture/START.md',
            'source/handoff/docara-unified-architecture/STATUS.yaml',
            'source/handoff/docara-unified-architecture/NEXT.md',
            'source/handoff/docara-unified-architecture/RESULT.md',
            'source/workflow/ACTIVE.md',
            'source/workflow/2026-08-02-docara-extensible-lego-architecture-plan.md',
            'source/workflow/2026-08-04-docara-content-design-settings-track.md',
        ];
        foreach (['goals', 'stages', 'batches'] as $directory) {
            foreach (glob($this->repositoryRoot() . '/graph/specs/' . $directory . '/*.json') ?: [] as $path) {
                $files[] = substr($path, strlen($this->repositoryRoot()) + 1);
            }
        }
        foreach ($files as $relative) {
            $target = $root . '/' . $relative;
            $this->filesystem->ensureDirectoryExists(dirname($target));
            self::assertTrue(copy($this->repositoryRoot() . '/' . $relative, $target), $relative);
        }

        return $root;
    }

    private function specPathForId(string $root, string $directory, string $id): string
    {
        foreach (glob($root . '/graph/specs/' . $directory . '/*.json') ?: [] as $path) {
            if (($this->json($path)['id'] ?? null) === $id) {
                return $path;
            }
        }

        self::fail("Missing graph spec [$directory:$id].");
    }

    /** @return array<string, mixed> */
    private function json(string $path): array
    {
        return json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    }

    /** @param array<string, mixed> $value */
    private function writeJson(string $path, array $value): void
    {
        $this->filesystem->put(
            $path,
            json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . "\n",
        );
    }
}
