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
            ['docara.brand', 'docara.breadcrumbs', 'docara.navigation', 'docara.pager', 'docara.preferences', 'docara.search', 'docara.toc', 'ui.alert', 'ui.button'],
            $registry->keys(),
        );
        self::assertSame('docara.brand', $registry->canonicalKey('docara.header'));
        self::assertSame('docara.toc', $registry->canonicalKey('docara.outline'));
        self::assertTrue($registry->resolution('docara.header')['deprecated']);
        self::assertFalse($registry->resolution('docara.brand')['deprecated']);

        foreach ($registry->keys() as $key) {
            self::assertContains($registry->definition($key)->providerId, ['docara.package', 'framework.lock']);
            self::assertNotSame('legacy.contribution', $registry->definition($key)->providerId);
            self::assertSame($key, $registry->definition($key)->portableManifest['code']);
        }
    }

    public function test_gateway_dispatches_by_provider_ownership_not_component_namespace(): void
    {
        $source = (string) file_get_contents(
            dirname(__DIR__, 2) . '/src/Declarative/Smart/SmartComponentGateway.php',
        );

        self::assertStringNotContainsString('str_starts_with($call->smart', $source);
        self::assertStringNotContainsString("'ui.'", $source);
        self::assertStringNotContainsString("'docara.'", $source);
        self::assertStringContainsString('definition($call->smart)', $source);
        self::assertStringContainsString('providerId', $source);
    }

    public function test_manual_contribution_surface_is_removed(): void
    {
        $root = dirname(__DIR__, 2);
        foreach (['SmartContribution.php', 'DocaraSmartContribution.php', 'FrameworkSmartContribution.php'] as $file) {
            self::assertFileDoesNotExist($root . '/src/Smart/' . $file);
        }
    }

    public function test_replaceable_chrome_leaves_are_not_trusted_application_templates(): void
    {
        $root = dirname(__DIR__, 2);
        $source = (string) file_get_contents($root . '/src/Declarative/Rendering/TrustedTemplateRegistry.php');
        $publisher = (string) file_get_contents($root . '/src/Declarative/Rendering/PublisherChromeRenderer.php');

        foreach (['breadcrumbs', 'pager', 'search-dialog'] as $leaf) {
            self::assertStringNotContainsString("publisher.docara.$leaf", $source);
            self::assertFileDoesNotExist($root . '/resources/publisher/components/' . $leaf . '.php');
        }
        foreach (['docara.breadcrumbs', 'docara.pager', 'docara.search'] as $smart) {
            self::assertSame($smart, SmartRegistry::bundled()->definition($smart)->key);
        }
        self::assertStringContainsString('SmartComponentGateway', $publisher);
        self::assertStringNotContainsString('CompositeSmartPlanResolver', $publisher);
    }

    public function test_one_validator_accepts_framework_and_product_manifests(): void
    {
        $repository = new DefinitionRepository;
        $validator = new SmartManifestValidator;

        foreach (['ui.alert', 'ui.button', 'docara.brand', 'docara.breadcrumbs', 'docara.navigation', 'docara.pager', 'docara.preferences', 'docara.search', 'docara.toc'] as $key) {
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
