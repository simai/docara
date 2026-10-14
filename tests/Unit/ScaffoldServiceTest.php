<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Simai\Docara\Application\ProjectRuntime;
use Simai\Docara\Application\ScaffoldService;
use Simai\Docara\Design\Artifact\DesignArtifactKind;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\Smart\Provider\SmartProviderException;
use Tests\TestCase;

final class ScaffoldServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->filesystem->copyDirectory(dirname(__DIR__, 2) . '/stubs/portable', $this->tmp);
    }

    #[Test]
    public function smart_plan_is_deterministic_hash_bound_and_registry_ready_after_apply(): void
    {
        $service = new ScaffoldService;
        $first = $service->plan($this->tmp, 'smart', 'project.card')->toArray();
        $second = $service->plan($this->tmp, 'smart', 'project.card')->toArray();
        self::assertSame($first['data'], $second['data']);
        self::assertSame('absent', $first['data']['input_hashes']['smart/project.card/manifest.json']);

        $applied = $service->apply($this->tmp, $first['data']['plan_id'])->toArray();
        self::assertSame('registry_reload_passed', $applied['data']['validation']);
        self::assertSame('project.card', ProjectRuntime::load($this->tmp)->smarts->definition('project.card')->key);
        self::assertFileExists($this->tmpPath('smart/project.card/template/default.php'));
    }

    #[Test]
    public function design_bundle_is_data_only_and_registry_ready(): void
    {
        $service = new ScaffoldService;
        $plan = $service->plan($this->tmp, 'design', 'project.marketing')->toArray();
        self::assertCount(5, $plan['data']['diff']);
        $service->apply($this->tmp, $plan['data']['plan_id']);

        self::assertSame('project.marketing', ProjectRuntime::load($this->tmp)->designs->get(
            DesignArtifactKind::Layout,
            'project.marketing',
        )->id);
    }

    #[Test]
    public function changed_input_and_tampered_plan_fail_closed_without_source_writes(): void
    {
        $service = new ScaffoldService;
        $plan = $service->plan($this->tmp, 'smart', 'project.card')->toArray();
        $this->filesystem->append($this->tmpPath('docara.json'), "\n");
        try {
            $service->apply($this->tmp, $plan['data']['plan_id']);
            self::fail('Stale plan was accepted.');
        } catch (PortableConfigurationException $exception) {
            self::assertSame('SCAFFOLD_PLAN_STALE', $exception->errorCode);
        }
        self::assertDirectoryDoesNotExist($this->tmpPath('smart/project.card'));

        $this->filesystem->put($this->tmpPath('docara.json'), rtrim((string) file_get_contents(dirname(__DIR__, 2) . '/stubs/portable/docara.json')) . "\n");
        $plan = $service->plan($this->tmp, 'smart', 'project.card')->toArray();
        $path = $this->tmpPath($plan['data']['plan_path']);
        $decoded = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        $decoded['id'] = 'project.tampered';
        $this->filesystem->put($path, json_encode($decoded, JSON_THROW_ON_ERROR));
        $this->expectException(PortableConfigurationException::class);
        $this->expectExceptionMessage('plan contents do not match');
        $service->apply($this->tmp, $plan['data']['plan_id']);
    }

    #[Test]
    public function traversal_reserved_namespace_duplicate_symlink_and_hardlink_are_rejected(): void
    {
        $service = new ScaffoldService;
        foreach (['../evil.card', 'ui.card', 'project/card'] as $id) {
            try {
                $service->plan($this->tmp, 'smart', $id);
                self::fail("Unsafe id [$id] was accepted.");
            } catch (PortableConfigurationException $exception) {
                self::assertSame('SCAFFOLD_NAMESPACE_FORBIDDEN', $exception->errorCode);
            }
        }

        $plan = $service->plan($this->tmp, 'smart', 'project.card')->toArray();
        $service->apply($this->tmp, $plan['data']['plan_id']);
        try {
            $service->plan($this->tmp, 'smart', 'project.card');
            self::fail('Duplicate target was accepted.');
        } catch (PortableConfigurationException $exception) {
            self::assertSame('SCAFFOLD_TARGET_EXISTS', $exception->errorCode);
        }
        $this->filesystem->deleteDirectory($this->tmpPath('smart/project.card'));
        $this->filesystem->deleteDirectory($this->tmpPath('smart'));

        symlink(sys_get_temp_dir(), $this->tmpPath('smart'));
        try {
            $service->plan($this->tmp, 'smart', 'project.card');
            self::fail('Symlink parent was accepted.');
        } catch (PortableConfigurationException $exception) {
            self::assertSame('SDK_PROJECT_OWNED_ROOT_UNSAFE', $exception->errorCode);
        }
        unlink($this->tmpPath('smart'));

        $this->filesystem->copyDirectory(dirname(__DIR__) . '/fixtures/smart/portable/fixture.notice', $this->tmpPath('smart/project.card'));
        $manifest = json_decode((string) file_get_contents($this->tmpPath('smart/project.card/manifest.json')), true, 512, JSON_THROW_ON_ERROR);
        $manifest['code'] = 'project.card';
        $manifest['meta']['ownerPackage'] = 'project/project';
        $this->filesystem->put($this->tmpPath('outside.json'), json_encode($manifest, JSON_THROW_ON_ERROR));
        $this->filesystem->delete($this->tmpPath('smart/project.card/manifest.json'));
        link($this->tmpPath('outside.json'), $this->tmpPath('smart/project.card/manifest.json'));
        try {
            $service->plan($this->tmp, 'smart', 'project.card');
            self::fail('Hard-linked target was accepted.');
        } catch (SmartProviderException $exception) {
            self::assertStringStartsWith('SMART_PROVIDER_PATH_UNSAFE:', $exception->getMessage());
        }
    }
}
