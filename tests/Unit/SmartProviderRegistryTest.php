<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Simai\Docara\Smart\Provider\FrameworkLockSmartProvider;
use Simai\Docara\Smart\Provider\PackageSmartProvider;
use Simai\Docara\Smart\Provider\ProjectSmartProvider;
use Simai\Docara\Smart\Provider\SmartArtifactProvider;
use Simai\Docara\Smart\Provider\SmartProviderException;
use Simai\Docara\Smart\Provider\SmartRegistryCompiler;

final class SmartProviderRegistryTest extends TestCase
{
    /** @var list<string> */
    private array $temporary = [];

    protected function tearDown(): void
    {
        usort($this->temporary, static fn (string $left, string $right): int => strlen($right) <=> strlen($left));
        foreach ($this->temporary as $path) {
            if (is_link($path) || is_file($path)) {
                @unlink($path);
            } elseif (is_dir($path)) {
                @rmdir($path);
            }
        }
    }

    public function test_project_provider_discovers_portable_artifacts_deterministically(): void
    {
        $root = dirname(__DIR__) . '/fixtures/smart/portable';
        $provider = new ProjectSmartProvider('fixture', $root, 'fixture/project', 'fixture-revision');
        $registry = (new SmartRegistryCompiler)->compile([$provider]);

        self::assertSame(['fixture.notice'], $registry->keys());
        $definition = $registry->definition('fixture.notice');
        self::assertSame('project.fixture', $definition->providerId);
        self::assertSame('server-static', $definition->strategy);
        self::assertSame('sf5.smart.artifact.v1', $definition->provenance['contract']);
        self::assertArrayHasKey('smart.fixture.notice.default', $definition->templates);
        self::assertArrayHasKey('default', $definition->views);
        self::assertArrayHasKey('compact', $definition->presets);
        self::assertCount(1, $definition->assets);
        self::assertSame($provider->fingerprint(), $provider->fingerprint());
    }

    public function test_reserved_project_namespaces_fail_closed(): void
    {
        $this->expectException(SmartProviderException::class);
        $this->expectExceptionMessage('SMART_PROJECT_NAMESPACE_RESERVED:ui');
        new ProjectSmartProvider('ui', dirname(__DIR__) . '/fixtures/smart/portable', 'fixture/project', 'x');
    }

    public function test_framework_provider_rejects_a_moving_revision(): void
    {
        $this->expectException(SmartProviderException::class);
        $this->expectExceptionMessage('SMART_FRAMEWORK_REVISION_IMMUTABLE_REQUIRED:latest');
        new FrameworkLockSmartProvider(
            dirname(__DIR__) . '/fixtures/smart/portable',
            'larena/ui',
            'latest',
        );
    }

    public function test_provider_namespace_collision_fails_closed(): void
    {
        $root = dirname(__DIR__) . '/fixtures/smart/portable';
        $this->expectException(SmartProviderException::class);
        $this->expectExceptionMessage('SMART_PROVIDER_NAMESPACE_COLLISION:fixture');
        (new SmartRegistryCompiler)->compile([
            new PackageSmartProvider('one', ['fixture'], $root, 'fixture/one', 'one'),
            new PackageSmartProvider('two', ['fixture'], $root, 'fixture/two', 'two'),
        ]);
    }

    public function test_duplicate_component_from_one_provider_fails_closed(): void
    {
        $source = new ProjectSmartProvider(
            'fixture',
            dirname(__DIR__) . '/fixtures/smart/portable',
            'fixture/project',
            'fixture-revision',
        );
        $descriptor = iterator_to_array($source->descriptors(), false)[0];
        $provider = new class($descriptor) implements SmartArtifactProvider
        {
            public function __construct(private readonly object $descriptor) {}

            public function id(): string
            {
                return 'project.fixture';
            }

            public function priority(): int
            {
                return 100;
            }

            public function namespaces(): array
            {
                return ['fixture'];
            }

            public function descriptors(): iterable
            {
                yield $this->descriptor;
                yield $this->descriptor;
            }

            public function fingerprint(): string
            {
                return 'duplicate';
            }
        };

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('SMART_REGISTRY_DUPLICATE_COMPONENT:fixture.notice');
        (new SmartRegistryCompiler)->compile([$provider]);
    }

    public function test_component_outside_owned_namespace_fails_closed(): void
    {
        $this->expectException(SmartProviderException::class);
        $this->expectExceptionMessage('SMART_PROVIDER_NAMESPACE_NOT_OWNED:package.acme:fixture.notice');
        iterator_to_array((new PackageSmartProvider(
            'acme',
            ['acme'],
            dirname(__DIR__) . '/fixtures/smart/portable',
            'acme/package',
            'x',
        ))->descriptors());
    }

    public function test_symlinked_component_directory_fails_closed(): void
    {
        $root = $this->temporaryDirectory();
        $link = $root . '/fixture.notice';
        symlink(dirname(__DIR__) . '/fixtures/smart/portable/fixture.notice', $link);
        $this->temporary[] = $link;

        $this->expectException(SmartProviderException::class);
        $this->expectExceptionMessage('SMART_PROVIDER_SYMLINK_FORBIDDEN:fixture.notice');
        iterator_to_array((new ProjectSmartProvider('fixture', $root, 'fixture/project', 'x'))->descriptors());
    }

    public function test_symlinked_template_fails_closed(): void
    {
        $root = $this->copiedFixture();
        $template = $root . '/fixture.notice/template/default.php';
        unlink($template);
        symlink(__FILE__, $template);

        $this->expectException(SmartProviderException::class);
        iterator_to_array((new ProjectSmartProvider('fixture', $root, 'fixture/project', 'x'))->descriptors());
    }

    public function test_unsafe_asset_path_fails_closed(): void
    {
        $root = $this->copiedFixture();
        $manifestPath = $root . '/fixture.notice/manifest.json';
        $manifest = json_decode((string) file_get_contents($manifestPath), true, 512, JSON_THROW_ON_ERROR);
        $manifest['assets']['css'] = ['../../outside.css'];
        file_put_contents($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
        $outside = dirname($root) . '/outside.css';
        file_put_contents($outside, 'x');
        $this->temporary[] = $outside;

        $this->expectException(SmartProviderException::class);
        $this->expectExceptionMessage('SMART_PROVIDER_PATH_UNSAFE');
        iterator_to_array((new ProjectSmartProvider('fixture', $root, 'fixture/project', 'x'))->descriptors());
    }

    private function temporaryDirectory(): string
    {
        $root = sys_get_temp_dir() . '/docara-smart-provider-' . bin2hex(random_bytes(8));
        mkdir($root, 0700, true);
        $this->temporary[] = $root;

        return $root;
    }

    private function copiedFixture(): string
    {
        $root = $this->temporaryDirectory();
        $source = dirname(__DIR__) . '/fixtures/smart/portable/fixture.notice';
        $destination = $root . '/fixture.notice';
        mkdir($destination . '/view', 0700, true);
        mkdir($destination . '/preset', 0700, true);
        mkdir($destination . '/template', 0700, true);
        mkdir($destination . '/assets', 0700, true);
        foreach (['manifest.json', 'view/default.json', 'preset/compact.json', 'template/default.php', 'assets/notice.css'] as $relative) {
            copy($source . '/' . $relative, $destination . '/' . $relative);
            $this->temporary[] = $destination . '/' . $relative;
        }
        foreach ([$destination . '/view', $destination . '/preset', $destination . '/template', $destination . '/assets', $destination] as $directory) {
            $this->temporary[] = $directory;
        }

        return $root;
    }
}
