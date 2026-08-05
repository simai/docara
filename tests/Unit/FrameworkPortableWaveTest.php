<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Simai\Docara\Application\DesignAtlasService;
use Simai\Docara\Application\DiscoveryService;
use Simai\Docara\Application\ProjectRuntime;
use Simai\Docara\Declarative\Document\SmartCallNode;
use Simai\Docara\Declarative\Document\SourceSpan;
use Simai\Docara\Declarative\Rendering\SmartRenderer;
use Simai\Docara\Declarative\Rendering\TrustedTemplateRegistry;
use Simai\Docara\Declarative\Smart\SmartComponentGateway;
use Simai\Docara\File\Filesystem;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\PortableSite\PortableMarkdownRenderer;
use Simai\Docara\PortableSite\PortablePublisherAssetPublisher;
use Simai\Docara\PortableSite\PortableSiteBuilder;
use Simai\Docara\Preview\PreviewKernel;
use Simai\Docara\Preview\PreviewTarget;
use Simai\Docara\Smart\Artifact\Sf5SmartArtifactV1Contract;
use Simai\Docara\Smart\SmartRegistry;
use Tests\TestCase;

final class FrameworkPortableWaveTest extends TestCase
{
    private const FORM_PACKET = '83551f972ad0b1a6e2037f61583769e32a4a78081e01ed0a0fe888b1187baca1';

    private const LIST_PACKET = '7dbcb161e8bb48c342a385c3f28f7dc8628eecdf0c09758ab3113eb8dc2107db';

    protected function setUp(): void
    {
        parent::setUp();
        $this->filesystem->copyDirectory(dirname(__DIR__, 2) . '/stubs/portable', $this->tmp);
    }

    #[Test]
    public function exact_accepted_artifacts_share_one_framework_provider_and_neutral_abi(): void
    {
        $registry = SmartRegistry::bundled();
        $expected = [
            'ui.checkbox' => ['7e0b87187ceb1f89fad730094bcc4aada3e4f3f2', self::FORM_PACKET, []],
            'ui.dropdown' => ['7e0b87187ceb1f89fad730094bcc4aada3e4f3f2', self::FORM_PACKET, ['ui.list-item']],
            'ui.input' => ['7e0b87187ceb1f89fad730094bcc4aada3e4f3f2', self::FORM_PACKET, []],
            'ui.list-item' => ['639d7b67833cfdf1e2c349c5f83669ba0e34fe05', self::LIST_PACKET, []],
        ];

        foreach ($expected as $id => [$revision, $packet, $dependencies]) {
            $definition = $registry->definition($id);
            self::assertSame('framework.lock', $definition->providerId);
            self::assertSame('simai/bx-simai.main', $definition->ownerPackage);
            self::assertSame(Sf5SmartArtifactV1Contract::CONTRACT_ID, $definition->provenance['contract_id']);
            self::assertSame(Sf5SmartArtifactV1Contract::SCHEMA_VERSION, $definition->provenance['contract_schema_version']);
            self::assertSame(Sf5SmartArtifactV1Contract::COMPATIBILITY_ID, $definition->provenance['contract_compatibility_id']);
            self::assertSame($revision, $definition->provenance['provider_revision']);
            self::assertSame($packet, $definition->provenance['owner_packet_content_sha256']);
            self::assertSame($dependencies, $definition->provenance['dependencies']);
            self::assertSame('portable.manifest.direct', $definition->provenance['provider_adapter']);
            self::assertSame('sf5.smart.template.v1', $definition->provenance['template_abi']);
        }
    }

    #[Test]
    public function populated_dropdown_is_resolved_and_rendered_recursively_by_the_same_gateway_and_renderer(): void
    {
        $runtime = ProjectRuntime::load($this->tmp);
        $gateway = SmartComponentGateway::withProject(
            $runtime->smarts,
            'project.project',
            $runtime->site['framework'] ?? $this->json(dirname(__DIR__, 2) . '/stubs/portable/simai-framework.lock.json'),
        );
        $plan = $gateway->resolve(new SmartCallNode(
            'project-configurator-test',
            'project.product-configurator',
            'default',
            ['title' => 'Test', 'base_price' => 1000, 'currency' => '₽'],
            1,
            new SourceSpan('content/ru/project-demos.md', 1, 1),
        ));
        $artifact = (new SmartRenderer(new TrustedTemplateRegistry(smarts: $runtime->smarts)))->render($plan);

        self::assertStringContainsString('<sf-dropdown', $artifact->html);
        self::assertSame(3, substr_count($artifact->html, '<sf-list-item'));
        self::assertSame(3, substr_count($artifact->html, 'type="text"'));
        self::assertGreaterThanOrEqual(3, substr_count($artifact->html, '<sf-checkbox'));
        self::assertSame(['ui.dropdown', 'ui.checkbox', 'ui.checkbox', 'ui.checkbox'], array_column($plan->provenance['dependency_trace'], 'smart'));
        self::assertSame(['ui.list-item', 'ui.list-item', 'ui.list-item'], array_column($plan->children[0]->provenance['dependency_trace'], 'smart'));
    }

    #[Test]
    public function list_item_is_fail_closed_outside_dropdown_and_for_unaccepted_related_types(): void
    {
        $lock = $this->json(dirname(__DIR__, 2) . '/stubs/portable/simai-framework.lock.json');
        $gateway = SmartComponentGateway::bundled($lock);

        try {
            $gateway->resolve(new SmartCallNode(
                'standalone-list-item',
                'ui.list-item',
                'default',
                ['type' => 'text', 'text' => 'Standalone'],
                1,
                new SourceSpan('content/test.md', 1, 1),
            ));
            self::fail('A standalone ui.list-item unexpectedly passed admission.');
        } catch (PortableConfigurationException $exception) {
            self::assertStringContainsString('PORTABLE_SMART_PARENT_FORBIDDEN', $exception->getMessage());
        }

        $view = $this->json($this->tmpPath('smart/project.product-configurator/view/default.json'));
        $view['children']['product']['children']['starter']['props']['type'] = 'icon';
        file_put_contents(
            $this->tmpPath('smart/project.product-configurator/view/default.json'),
            json_encode($view, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n",
        );
        $runtime = ProjectRuntime::load($this->tmp);
        $gateway = SmartComponentGateway::withProject(
            $runtime->smarts,
            'project.project',
            $this->json(dirname(__DIR__, 2) . '/stubs/portable/simai-framework.lock.json'),
        );
        $this->expectException(PortableConfigurationException::class);
        $this->expectExceptionMessage('PORTABLE_SMART_PROP_POLICY_FORBIDDEN');
        $gateway->resolve(new SmartCallNode(
            'invalid-list-item-type',
            'project.product-configurator',
            'default',
            ['title' => 'Test', 'base_price' => 1000, 'currency' => '₽'],
            1,
            new SourceSpan('content/test.md', 1, 1),
        ));
    }

    #[Test]
    public function atlas_inspect_and_preview_expose_dependency_and_exact_provider_provenance(): void
    {
        $atlas = (new DesignAtlasService)->atlas($this->tmp)->data['entries'];
        $dropdown = array_values(array_filter($atlas, static fn (array $entry): bool => $entry['kind'] === 'smart' && $entry['id'] === 'ui.dropdown'))[0];
        self::assertSame(['ui.list-item'], $dropdown['container_contract']['allowed_children']);
        self::assertSame(['options'], $dropdown['container_contract']['slots']);
        self::assertSame(self::FORM_PACKET, $dropdown['provenance']['owner_packet_content_sha256']);

        $inspect = (new DiscoveryService)->inspect($this->tmp, 'smart', 'ui.list-item');
        self::assertSame('accepted_for_ui.dropdown_text_options_only', $inspect->data['provenance']['support_status']);
        self::assertSame(['icons', 'avatars', 'tags', 'standalone_form_control'], $inspect->data['provenance']['nonclaims']);

        $files = new Filesystem;
        $preview = (new PreviewKernel(new PortableSiteBuilder($files, new PortableMarkdownRenderer), $files))
            ->render($this->tmp, '/ru/project-demos/', PreviewTarget::Smart, 'ui.dropdown');
        self::assertStringContainsString('<sf-dropdown', $preview->html);
        self::assertContains('@package-tree:resources/framework/portable-smart/ui.dropdown', $preview->dependencies);
        self::assertContains('@package-file:resources/framework/portable-smart-lock.json', $preview->dependencies);
    }

    #[Test]
    public function exact_framework_assets_are_published_only_when_the_page_uses_the_portable_wave(): void
    {
        $build = $this->tmpPath('unused-assets');
        (new PortablePublisherAssetPublisher(new Filesystem, SmartRegistry::bundled()))->publish($build, []);
        self::assertFileDoesNotExist($build . '/_docara/vendor/simai/ui/inputs/js/inputs.js');

        (new PortablePublisherAssetPublisher(new Filesystem, SmartRegistry::bundled()))->publish(
            $build,
            ['framework.portable.ui.input.js'],
        );
        self::assertFileExists($build . '/_docara/vendor/simai/ui/inputs/js/inputs.js');
        self::assertSame(
            '344464ac91ce3997dce7bf46ff0635da49f21087f055987bcc24d84f1d3de123',
            hash_file('sha256', $build . '/_docara/vendor/simai/ui/inputs/js/inputs.js'),
        );
        self::assertFileDoesNotExist($build . '/_docara/vendor/simai/ui/dropdown/js/dropdown.js');
    }

    /** @return array<string, mixed> */
    private function json(string $path): array
    {
        $value = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        return is_array($value) ? $value : throw new \RuntimeException('JSON object expected.');
    }
}
