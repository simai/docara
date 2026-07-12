<?php

declare(strict_types=1);

namespace Larena\Docara\Tests\Feature;

use InvalidArgumentException;
use Larena\Admin\Assets\AdminProductAssetManifest;
use Larena\Admin\Http\Controllers\Product\AdminUiLabController;
use Larena\Docara\Admin\DocumentationPageFormPresenter;
use Larena\Docara\Tests\TestCase;
use Larena\Docara\Ui\DocaraSmartContribution;
use Larena\Ui\Developer\SmartAiCatalogProjection;
use Larena\Ui\Developer\SmartCatalogProjection;
use Larena\Ui\Developer\SmartInvocationExampleBuilder;
use Larena\Ui\Frontend\FrontendRuntimeLock;
use Larena\Ui\Registry\SmartRegistry;
use Larena\Ui\Runtime\SmartManager;
use Larena\Ui\Smart;

final class SmartRegistryContributionTest extends TestCase
{
    public function test_docara_contributes_manifest_and_renders_full_artifact(): void
    {
        $registry = $this->app->make(SmartRegistry::class);
        self::assertContains('docara.smart_components', $registry->contributionIds());
        $manifest = $registry->manifest('docara.page_title_field');
        self::assertSame('larena/docara', $manifest->ownerPackage);
        self::assertSame('sf-input', $manifest->frontendTag);
        self::assertTrue($manifest->isCanonical());

        $artifact = $this->app->make(SmartManager::class)->render(
            'docara.page_title_field',
            $manifest->atlas['example_props'],
            AdminProductAssetManifest::activation(),
        );
        self::assertTrue($artifact->isRenderable());
        self::assertStringContainsString('<sf-input', $artifact->html());
        self::assertSame('docara.page_title_field', $artifact->toArray()['diagnostics']['component_key']);
        self::assertFalse($artifact->toArray()['diagnostics']['production_ready']);
    }

    public function test_page_title_and_ui_lab_use_the_same_contributed_manifest(): void
    {
        $artifact = $this->app->make(DocumentationPageFormPresenter::class)
            ->titleField('Title', 'Larena', '');
        self::assertSame('docara.page_title_field', $artifact->toArray()['diagnostics']['component_key']);

        require __DIR__ . '/../../../admin/routes/admin/product.php';
        $html = $this->app->make(AdminUiLabController::class)->atlas()->render();
        self::assertStringContainsString('registry-docara-page-title-field', $html);
        self::assertStringContainsString('Docara page title field', $html);
        self::assertStringContainsString('data-manifest="docara.page_title_field"', $html);
        self::assertStringContainsString('<sf-input', $html);
        self::assertStringContainsString('data-production-ready="false"', $html);
        self::assertStringContainsString('data-all-packages-ready="false"', $html);
    }

    public function test_cross_package_catalog_ai_projection_and_render_stay_source_backed(): void
    {
        $registry = SmartRegistry::withDefaults();
        $registry->registerContribution(new DocaraSmartContribution());
        $catalog = new SmartCatalogProjection($registry, new SmartInvocationExampleBuilder());
        $manager = new SmartManager($registry);

        $manifest = $registry->manifest('docara.page_title_field');
        $canonicalInput = $registry->manifest('ui.input');
        $english = $catalog->component('docara.page_title_field', 'en');
        $russian = $catalog->component('docara.page_title_field', 'ru');

        self::assertSame('Docara page title field', $english->title);
        self::assertSame('Поле заголовка страницы Docara', $russian->title);
        self::assertSame(
            array_column($english->controls, 'key'),
            array_column($russian->controls, 'key'),
        );
        self::assertSame('Label', $english->controls[0]['label']);
        self::assertSame('Подпись', $russian->controls[0]['label']);
        self::assertSame('fields', $english->category);
        self::assertSame('developer_testable', $english->status);
        self::assertSame([
            'safe_to_suggest' => true,
            'safe_to_render' => true,
            'safe_to_bind_data' => false,
            'safe_to_execute_effect' => false,
        ], $english->readiness);
        self::assertSame(
            array_column($canonicalInput->toArray()['assets'], 'key'),
            array_column($manifest->toArray()['assets'], 'key'),
        );
        self::assertSame(
            array_map(static fn (array $asset): array => [
                $asset['key'],
                $asset['kind'],
                $asset['critical'],
            ], $canonicalInput->toArray()['assets']),
            array_map(static fn (array $asset): array => [
                $asset['key'],
                $asset['kind'],
                $asset['critical'],
            ], $manifest->toArray()['assets']),
        );
        foreach (['runtime_lock', 'upstream_component', 'upstream_revision', 'reference_status'] as $key) {
            self::assertSame($canonicalInput->provenance[$key], $manifest->provenance[$key]);
        }
        self::assertSame('resources/smart/page-title-field/manifest.json', $manifest->provenance['manifest_path']);
        self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $manifest->provenance['manifest_sha256']);
        foreach ($manifest->eventSchema as $event) {
            self::assertSame('dom', $event['kind']);
            self::assertFalse($event['backend_handler_binding']);
        }

        $activation = AdminProductAssetManifest::activation();
        self::assertSame(FrontendRuntimeLock::bundled()->pairId(), $activation['runtime_pair']);
        $artifact = $manager->render(
            'docara.page_title_field',
            $english->exampleProps,
            $activation,
        );
        self::assertTrue($artifact->isRenderable());
        self::assertStringContainsString('<sf-input', $artifact->html());
        self::assertStringContainsString('name="title"', $artifact->html());
        self::assertSame('manifest_verified', $artifact->toArray()['diagnostics']['asset_contract_mode']);

        $expectedAssetKeys = array_map(
            static fn ($asset): string => $asset->assetKey,
            Smart::assetGraph('sf-input')->requirements,
        );
        self::assertSame($expectedAssetKeys, array_column($manifest->toArray()['assets'], 'key'));

        $ai = new SmartAiCatalogProjection($catalog);
        $englishProjection = $ai->toArray('en');
        $russianProjection = $ai->toArray('ru');
        self::assertSame($englishProjection, $ai->toArray('en'));
        self::assertContains('docara.page_title_field', array_column($englishProjection['components'], 'key'));
        self::assertContains('docara.page_title_field', array_column($russianProjection['components'], 'key'));
        self::assertSame([], $englishProjection['recipes']);
        $englishJson = $ai->toJson('en');
        self::assertSame($englishJson, $ai->toJson('en'));
        self::assertStringNotContainsString('/Users/', $englishJson);
        self::assertStringNotContainsString('file://', $englishJson);
    }

    public function test_cross_package_registry_catalog_and_manager_fail_closed_for_unknown_key(): void
    {
        $registry = SmartRegistry::withDefaults();
        $registry->registerContribution(new DocaraSmartContribution());
        $catalog = new SmartCatalogProjection($registry, new SmartInvocationExampleBuilder());
        $manager = new SmartManager($registry);
        $unknown = 'docara.unknown';

        foreach ([
            static fn () => $registry->manifest($unknown),
            static fn () => $catalog->component($unknown),
            static fn () => $manager->render($unknown, [], AdminProductAssetManifest::activation()),
        ] as $operation) {
            try {
                $operation();
                self::fail('Unknown Smart component must fail closed.');
            } catch (InvalidArgumentException $exception) {
                self::assertSame('ui_smart_manifest_unknown:' . $unknown, $exception->getMessage());
            }
        }
    }
}
