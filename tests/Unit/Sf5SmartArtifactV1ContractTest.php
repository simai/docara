<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Simai\Docara\Declarative\Definition\DefinitionRepository;
use Simai\Docara\Smart\Artifact\LegacySmartManifestV1Adapter;
use Simai\Docara\Smart\Artifact\PortableSmartContractException;
use Simai\Docara\Smart\Artifact\Sf5SmartArtifactV1Contract;

final class Sf5SmartArtifactV1ContractTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = dirname(__DIR__) . '/fixtures/smart/portable/fixture.notice';
    }

    public function test_it_accepts_the_source_pinned_portable_manifest_view_and_preset(): void
    {
        $contract = new Sf5SmartArtifactV1Contract;
        $contract->assertManifest($this->json('manifest.json'), 'fixture.notice');
        $contract->assertView($this->json('view/default.json'), 'fixture.notice', 'default');
        $contract->assertPreset($this->json('preset/compact.json'), 'fixture.notice', 'compact');

        self::assertSame([
            'contract' => 'sf5.smart.artifact.v1',
            'source_revision' => 'd6f90bba6a9a2f30ac41075d62cf51f1014b7e78',
        ], $contract->provenance());
    }

    public function test_the_bounded_adapter_projects_current_framework_and_docara_manifests_to_v1(): void
    {
        $repository = new DefinitionRepository;
        $adapter = new LegacySmartManifestV1Adapter;
        $contract = new Sf5SmartArtifactV1Contract;

        foreach (['ui.alert', 'ui.button', 'docara.brand', 'docara.navigation', 'docara.preferences', 'docara.toc'] as $smart) {
            $portable = $adapter->adapt($repository->smartManifest($smart), $smart);
            $contract->assertManifest($portable, $smart);
            self::assertSame($smart, $portable['code']);
            self::assertSame('larena.ui.smart_manifest.v1', $portable['extensions']['docara']['legacySchema']);
        }
    }

    public function test_the_repository_snapshot_is_exactly_source_pinned_and_contains_no_moving_ref(): void
    {
        $root = dirname(__DIR__, 2) . '/resources/contracts/sf5/smart/v1';
        $source = json_decode((string) file_get_contents($root . '/source.json'), true, 512, JSON_THROW_ON_ERROR);
        $contract = json_decode((string) file_get_contents($root . '/contract.json'), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Sf5SmartArtifactV1Contract::SOURCE_REVISION, $source['source_revision']);
        self::assertFalse($source['policy']['moving_refs_allowed']);
        self::assertFalse($source['policy']['external_worktree_files_allowed']);
        self::assertSame('1.0', $contract['schemaVersion']);
        self::assertSame('smart.contract', $contract['kind']);
        self::assertSame(
            ['server-static', 'server-first-hydratable', 'client-owned', 'shadow-dom-owned'],
            $contract['manifest']['renderStrategies'],
        );
        self::assertSame(['preset', 'view', 'invocation'], $contract['propsMerge']);

        $serialized = json_encode([$source, $contract], JSON_THROW_ON_ERROR);
        self::assertStringNotContainsString('"main"', $serialized);
        self::assertStringNotContainsString('"latest"', $serialized);
        self::assertStringNotContainsString('/Users/', $serialized);
    }

    #[DataProvider('invalidManifestProvider')]
    public function test_it_rejects_incompatible_or_incomplete_manifests(string $path, mixed $value): void
    {
        $manifest = $this->json('manifest.json');
        $cursor = &$manifest;
        $segments = explode('.', $path);
        $last = array_pop($segments);
        foreach ($segments as $segment) {
            $cursor = &$cursor[$segment];
        }
        $cursor[$last] = $value;

        $this->expectException(PortableSmartContractException::class);
        $this->expectExceptionMessage('PORTABLE_SMART_CONTRACT_INVALID:fixture.notice:' . $path);
        (new Sf5SmartArtifactV1Contract)->assertManifest($manifest, 'fixture.notice');
    }

    /** @return iterable<string, array{string, mixed}> */
    public static function invalidManifestProvider(): iterable
    {
        yield 'moving schema' => ['schemaVersion', 'latest'];
        yield 'wrong kind' => ['kind', 'component'];
        yield 'component mismatch' => ['code', 'ui.notice'];
        yield 'unknown strategy' => ['render.strategy', 'docara-only'];
        yield 'unsafe template id' => ['render.template', '../default'];
        yield 'static component hydration' => ['render.hydration', 'required'];
        yield 'unknown prop type' => ['props.title.type', 'callback'];
    }

    /** @return array<string, mixed> */
    private function json(string $relative): array
    {
        $payload = json_decode(
            (string) file_get_contents($this->root . '/' . $relative),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        self::assertIsArray($payload);

        return $payload;
    }
}
