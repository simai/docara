<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Simai\Docara\Declarative\Definition\DefinitionRepository;
use Simai\Docara\Smart\SmartManifestValidationException;
use Simai\Docara\Smart\SmartManifestValidator;
use Simai\Docara\Smart\SmartPropsValidationException;
use Simai\Docara\Smart\SmartPropsValidator;
use Simai\Docara\Smart\SmartRegistry;

final class SmartRegistryTest extends TestCase
{
    public function test_bundled_registry_exposes_canonical_components_and_deprecated_aliases(): void
    {
        $registry = SmartRegistry::bundled();

        self::assertSame(
            ['docara.brand', 'docara.navigation', 'docara.toc', 'ui.alert', 'ui.button'],
            $registry->keys(),
        );
        self::assertSame('docara.brand', $registry->canonicalKey('docara.header'));
        self::assertSame('docara.toc', $registry->canonicalKey('docara.outline'));
        self::assertTrue($registry->resolution('docara.header')['deprecated']);
        self::assertFalse($registry->resolution('docara.brand')['deprecated']);
    }

    public function test_one_validator_accepts_framework_and_product_manifests(): void
    {
        $repository = new DefinitionRepository;
        $validator = new SmartManifestValidator;

        foreach (['ui.alert', 'ui.button', 'docara.brand', 'docara.navigation', 'docara.toc'] as $key) {
            $manifest = $repository->smartManifest($key);
            $validator->assertValid($key, $manifest);
            self::assertSame('larena.ui.smart_manifest.v1', $manifest['schema']);
        }
    }

    public function test_common_validator_fails_closed_when_readiness_is_incomplete(): void
    {
        $manifest = (new DefinitionRepository)->smartManifest('docara.brand');
        unset($manifest['atlas']['readiness']['safe_to_render']);

        $this->expectException(SmartManifestValidationException::class);
        $this->expectExceptionMessage('SMART_MANIFEST_INVALID:docara.brand:atlas.readiness');

        (new SmartManifestValidator)->assertValid('docara.brand', $manifest);
    }

    public function test_product_item_props_fail_closed_when_the_shape_is_unknown(): void
    {
        $manifest = (new DefinitionRepository)->smartManifest('docara.toc');

        $this->expectException(SmartPropsValidationException::class);
        $this->expectExceptionMessage('SMART_PROPS_INVALID:docara.toc:props.items.0.unknown');

        (new SmartPropsValidator)->assertValid('docara.toc', $manifest, [
            'label' => 'On this page',
            'items' => [[
                'id' => 'install',
                'level' => 2,
                'text' => 'Install',
                'unknown' => true,
            ]],
        ]);
    }

    public function test_shared_smart_contract_has_no_laravel_runtime_dependency(): void
    {
        $files = glob(dirname(__DIR__, 2) . '/src/Smart/*.php') ?: [];
        self::assertNotEmpty($files);

        foreach ($files as $file) {
            $source = (string) file_get_contents($file);
            self::assertStringNotContainsString('Illuminate\\', $source, basename($file));
            self::assertStringNotContainsString('Laravel\\', $source, basename($file));
        }
    }
}
