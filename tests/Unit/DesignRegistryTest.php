<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Simai\Docara\Design\Artifact\DesignArtifactKind;
use Simai\Docara\Design\Provider\BuiltinDesignProvider;
use Simai\Docara\Design\Provider\FilesystemDesignProvider;
use Simai\Docara\Design\Provider\ProjectDesignProvider;
use Simai\Docara\Design\Registry\DesignRegistry;
use Simai\Docara\Portable\CanonicalJson;
use Simai\Docara\Portable\PortableConfigurationException;
use Tests\TestCase;

final class DesignRegistryTest extends TestCase
{
    #[Test]
    public function bundled_artifacts_are_discovered_deterministically_with_provenance(): void
    {
        $first = new DesignRegistry([new BuiltinDesignProvider]);
        $second = new DesignRegistry([new BuiltinDesignProvider]);

        self::assertSame($first->fingerprint(), $second->fingerprint());
        self::assertSame('docara.builtin', $first->get(DesignArtifactKind::Layout, 'docara.docs')->provider);
        self::assertSame('layouts/docara.docs.json', $first->get(DesignArtifactKind::Layout, 'docara.docs')->relativePath);
        self::assertSame(
            ['content' => 'docara.builtin', 'docara' => 'docara.builtin', 'shell' => 'docara.builtin'],
            $first->namespaceOwners(),
        );
        self::assertCount(15, $first->all());
        self::assertStringNotContainsString(
            'private const DEFINITIONS',
            (string) file_get_contents(dirname(__DIR__, 2) . '/src/Declarative/Definition/DefinitionRepository.php'),
        );
    }

    #[Test]
    public function project_layout_view_section_and_block_require_only_artifacts(): void
    {
        $this->projectArtifacts('acme');
        $registry = new DesignRegistry([
            new BuiltinDesignProvider,
            new ProjectDesignProvider($this->tmp, 'acme', 'fixture-v1'),
        ]);

        self::assertSame('project.acme', $registry->get(DesignArtifactKind::Layout, 'acme.docs')->provider);
        self::assertSame('acme', $registry->get(DesignArtifactKind::View, 'layout.acme.docs')->ownerNamespace);
        self::assertSame('fixture-v1', $registry->get(DesignArtifactKind::Section, 'acme.hero')->providerRevision);
        self::assertSame('blocks/acme.notice.json', $registry->get(DesignArtifactKind::Block, 'acme.notice')->relativePath);
    }

    #[Test]
    public function project_cannot_claim_reserved_or_foreign_namespace(): void
    {
        $this->expectException(PortableConfigurationException::class);
        $this->expectExceptionMessage('reserved');

        new ProjectDesignProvider($this->tmp, 'docara', 'fixture-v1');
    }

    #[Test]
    public function namespace_collision_fails_before_precedence_can_shadow(): void
    {
        $this->filesystem->ensureDirectoryExists($this->tmpPath('one'));
        $this->filesystem->ensureDirectoryExists($this->tmpPath('two'));

        $this->expectException(PortableConfigurationException::class);
        $this->expectExceptionMessage('claimed by');

        new DesignRegistry([
            new FilesystemDesignProvider('package.one', 'one', $this->tmpPath('one'), ['acme'], 200),
            new FilesystemDesignProvider('package.two', 'two', $this->tmpPath('two'), ['acme'], 201),
        ]);
    }

    #[Test]
    public function symlinked_artifact_fails_closed(): void
    {
        $outside = $this->tmpPath('outside.json');
        $this->filesystem->put($outside, CanonicalJson::encodePretty($this->block('acme.notice')));
        $directory = $this->tmpPath('design/blocks');
        $this->filesystem->ensureDirectoryExists($directory);
        self::assertTrue(symlink($outside, $directory . '/acme.notice.json'));

        $this->expectException(PortableConfigurationException::class);
        $this->expectExceptionMessage('invalid artifact path');

        (new ProjectDesignProvider($this->tmp, 'acme', 'fixture-v1'))->descriptors();
    }

    #[Test]
    public function schema_and_namespace_are_validated_before_registration(): void
    {
        $this->artifact('blocks', 'foreign.notice.json', $this->block('foreign.notice'));

        $this->expectException(PortableConfigurationException::class);
        $this->expectExceptionMessage('cannot own');

        new DesignRegistry([new ProjectDesignProvider($this->tmp, 'acme', 'fixture-v1')]);
    }

    #[Test]
    public function invalid_view_tree_is_rejected_before_registration(): void
    {
        $this->projectArtifacts('acme');
        $this->artifact('views', 'layout.acme.docs.json', [
            'schema' => 'docara.view_tree.v1',
            'key' => 'layout.acme.docs',
            'tree' => ['kind' => 'bogus', 'evil' => 'yes'],
        ]);

        $this->expectException(PortableConfigurationException::class);
        $this->expectExceptionMessage('must match exactly one supported schema branch');

        new DesignRegistry([
            new BuiltinDesignProvider,
            new ProjectDesignProvider($this->tmp, 'acme', 'fixture-v1'),
        ]);
    }

    #[Test]
    public function descriptor_owned_dynamic_region_is_validated_without_a_central_enum(): void
    {
        $this->projectArtifacts('acme', 'stage');
        $registry = new DesignRegistry([
            new BuiltinDesignProvider,
            new ProjectDesignProvider($this->tmp, 'acme', 'fixture-v1'),
        ]);

        self::assertSame(
            ['stage'],
            array_keys($registry->get(DesignArtifactKind::Layout, 'acme.docs')->definition['regions']),
        );
    }

    #[Test]
    public function layout_region_and_section_slot_must_match_their_view_trees(): void
    {
        $this->projectArtifacts('acme');
        $this->artifact('views', 'layout.acme.docs.json', [
            'schema' => 'docara.view_tree.v1',
            'key' => 'layout.acme.docs',
            'tree' => ['kind' => 'region', 'region' => 'other', 'tag' => 'main'],
        ]);

        $this->expectException(PortableConfigurationException::class);
        $this->expectExceptionMessage('regions do not match');

        new DesignRegistry([
            new BuiltinDesignProvider,
            new ProjectDesignProvider($this->tmp, 'acme', 'fixture-v1'),
        ]);
    }

    private function projectArtifacts(string $namespace, string $region = 'main'): void
    {
        $this->artifact('layouts', "$namespace.docs.json", [
            'schema' => 'docara.layout.v1',
            'key' => "$namespace.docs",
            'default' => false,
            'view' => "layout.$namespace.docs",
            'configuration' => [
                'container' => ['max' => 7],
                'scrollbar' => ['preset' => 'overlay'],
                'content' => ['gap' => 0],
            ],
            'document' => [
                'region' => $region,
                'section' => "$namespace.hero",
                'slot' => 'content',
                'block' => "$namespace.notice",
            ],
            'regions' => [
                $region => ['required' => true, 'default_enabled' => true, 'default_sections' => [], 'section_types' => ['content']],
            ],
            'assets' => [],
        ]);
        $this->artifact('views', "layout.$namespace.docs.json", [
            'schema' => 'docara.view_tree.v1',
            'key' => "layout.$namespace.docs",
            'tree' => ['kind' => 'region', 'region' => $region, 'tag' => 'main'],
        ]);
        $this->artifact('sections', "$namespace.hero.json", [
            'schema' => 'docara.section.v1',
            'key' => "$namespace.hero",
            'type' => 'content',
            'view' => "section.$namespace.hero",
            'allowed_regions' => [$region],
            'slots' => ['content'],
            'allowed_blocks' => ["$namespace.notice"],
            'blocks' => [],
        ]);
        $this->artifact('views', "section.$namespace.hero.json", [
            'schema' => 'docara.view_tree.v1',
            'key' => "section.$namespace.hero",
            'tree' => ['kind' => 'slot', 'slot' => 'content'],
        ]);
        $this->artifact('blocks', "$namespace.notice.json", $this->block("$namespace.notice"));
    }

    /** @return array<string, mixed> */
    private function block(string $key): array
    {
        return [
            'schema' => 'docara.block.v1',
            'key' => $key,
            'kind' => 'element',
            'renderer' => 'block.element',
        ];
    }

    /** @param array<string, mixed> $payload */
    private function artifact(string $directory, string $filename, array $payload): void
    {
        $path = $this->tmpPath("design/$directory/$filename");
        $this->filesystem->ensureDirectoryExists(dirname($path));
        $this->filesystem->put($path, CanonicalJson::encodePretty($payload));
    }
}
