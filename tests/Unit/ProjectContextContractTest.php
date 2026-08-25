<?php

declare(strict_types=1);

namespace Tests\Unit;

require_once dirname(__DIR__, 2) . '/scripts/project-context.php';

use PHPUnit\Framework\Attributes\Test;
use Simai\Docara\Tooling\ProjectContext;
use Tests\TestCase;

final class ProjectContextContractTest extends TestCase
{
    #[Test]
    public function committed_context_and_handoff_match_canonical_terminal_state(): void
    {
        self::assertSame([], ProjectContext::check($this->repositoryRoot()));

        $context = ProjectContext::expected($this->repositoryRoot());
        $state = $this->json($this->repositoryRoot() . '/graph/graph.json')['implementation_state'];
        self::assertSame('docara.unified.terminal', $context['context_id']);
        self::assertSame($state['state'], $context['terminal']['state']);
        self::assertSame($state['active_implementation'], $context['terminal']['active_implementation']);
        self::assertSame($state['completed_goal'], $context['terminal']['goal']);
        self::assertSame($state['last_completed_stage'], $context['terminal']['last_completed_stage']);
        self::assertSame($state['last_completed_batch'], $context['terminal']['last_completed_batch']);
        self::assertSame($state['repository_revision'], $context['terminal']['repository_revision']);
        self::assertSame($state['product_baseline_revision'], $context['terminal']['product_baseline_revision']);
        self::assertSame($state['next_action'], $context['terminal']['next_action']);
        self::assertSame($state['release_boundary'], $context['release_boundary']);
        self::assertFalse($context['release_boundary']['authorized']);
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

    #[Test]
    public function terminal_state_fails_closed_when_implementation_is_reactivated(): void
    {
        $root = $this->shadowRoot();
        $graphPath = $root . '/graph/graph.json';
        $graph = $this->json($graphPath);
        $graph['implementation_state']['active_implementation'] = true;
        $this->writeJson($graphPath, $graph);

        $codes = array_column(ProjectContext::check($root), 'code');
        self::assertContains('derived_context_stale', $codes);
        self::assertContains('canonical_terminal_implementation_active', $codes);
    }

    #[Test]
    public function terminal_state_fails_closed_when_goal_is_not_complete(): void
    {
        $root = $this->shadowRoot();
        $goalPath = $root . '/graph/specs/goals/unified-docara.json';
        $goal = $this->json($goalPath);
        $goal['lifecycle'] = 'active';
        $this->writeJson($goalPath, $goal);

        $codes = array_column(ProjectContext::check($root), 'code');
        self::assertContains('derived_context_stale', $codes);
        self::assertContains('canonical_goal_not_complete', $codes);
    }

    #[Test]
    public function terminal_state_fails_closed_when_last_stage_and_batch_do_not_match(): void
    {
        $root = $this->shadowRoot();
        $graphPath = $root . '/graph/graph.json';
        $graph = $this->json($graphPath);
        $graph['implementation_state']['last_completed_batch'] = 'docara.batch.r2.prepare_deployment';
        $this->writeJson($graphPath, $graph);

        $codes = array_column(ProjectContext::check($root), 'code');
        self::assertContains('derived_context_stale', $codes);
        self::assertContains('canonical_last_stage_batch_mismatch', $codes);
        self::assertContains('canonical_last_batch_parent_mismatch', $codes);
    }

    #[Test]
    public function terminal_state_fails_closed_when_release_boundary_is_bypassed(): void
    {
        $root = $this->shadowRoot();
        $graphPath = $root . '/graph/graph.json';
        $graph = $this->json($graphPath);
        $graph['implementation_state']['next_action'] = 'tag_release';
        $graph['implementation_state']['release_boundary']['authorized'] = true;
        $this->writeJson($graphPath, $graph);

        $codes = array_column(ProjectContext::check($root), 'code');
        self::assertContains('derived_context_stale', $codes);
        self::assertContains('canonical_release_boundary_invalid', $codes);
    }

    #[Test]
    public function terminal_state_requires_full_repository_and_product_revisions(): void
    {
        $root = $this->shadowRoot();
        $graphPath = $root . '/graph/graph.json';
        $graph = $this->json($graphPath);
        $graph['implementation_state']['repository_revision'] = 'd514c536';
        $this->writeJson($graphPath, $graph);

        $codes = array_column(ProjectContext::check($root), 'code');
        self::assertContains('derived_context_stale', $codes);
        self::assertContains('canonical_revision_invalid', $codes);
    }

    #[Test]
    public function regenerating_context_without_handoff_sync_still_fails(): void
    {
        $root = $this->shadowRoot();
        $graphPath = $root . '/graph/graph.json';
        $graph = $this->json($graphPath);
        $graph['implementation_state']['repository_revision'] = str_repeat('a', 40);
        $this->writeJson($graphPath, $graph);
        ProjectContext::generate($root);

        $codes = array_column(ProjectContext::check($root), 'code');
        self::assertNotContains('derived_context_stale', $codes);
        self::assertContains('handoff_status_repository_revision_mismatch', $codes);
        self::assertContains('handoff_semantic_marker_missing', $codes);
    }

    #[Test]
    public function stale_terminal_handoff_marker_fails_even_after_context_regeneration(): void
    {
        $root = $this->shadowRoot();
        $startPath = $root . '/source/handoff/2026-08-09-docara-current-main-onboarding/START.md';
        $start = (string) file_get_contents($startPath);
        $state = $this->json($root . '/graph/graph.json')['implementation_state'];
        $start = str_replace(
            'Last completed stage: `' . $state['last_completed_stage'] . '`',
            'Last completed stage: `docara.stage.r2.production_readiness`',
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
        $state = $this->json($this->repositoryRoot() . '/graph/graph.json')['implementation_state'];
        $roadmapSource = $state['roadmap_source'] ?? null;
        $terminalEvidence = $state['evidence'] ?? null;
        self::assertIsString($roadmapSource);
        self::assertIsString($terminalEvidence);
        $files = [
            'graph/graph.json',
            'graph/dna/project-dna.json',
            'graph/generated/ai-context/docara-unified.json',
            'source/handoff/2026-08-09-docara-current-main-onboarding/START.md',
            'source/handoff/2026-08-09-docara-current-main-onboarding/STATUS.yaml',
            'source/handoff/2026-08-09-docara-current-main-onboarding/NEXT.md',
            'source/handoff/2026-08-09-docara-current-main-onboarding/RESULT.md',
            'source/workflow/ACTIVE.md',
            $roadmapSource,
            $terminalEvidence,
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
