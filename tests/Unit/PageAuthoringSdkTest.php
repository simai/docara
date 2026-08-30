<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Simai\Docara\Application\DiscoveryService;
use Simai\Docara\Application\ScaffoldService;
use Simai\Docara\Application\ValidationService;
use Simai\Docara\Authoring\AuthoringProfileRegistry;
use Simai\Docara\Console\BuildCommand;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\PortableSite\PortableMarkdownRenderer;
use Simai\Docara\PortableSite\PortableSiteBuilder;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\TestCase;

final class PageAuthoringSdkTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->filesystem->copyDirectory(dirname(__DIR__, 2) . '/stubs/portable', $this->tmp);
        $this->filesystem->put($this->tmpPath('docara.authoring.json'), json_encode([
            'schema' => 'docara.authoring.v1',
            'audiences' => ['reader'],
            'default_profile' => 'article',
            'rules' => [['match' => 'guide/**', 'profile' => 'tutorial']],
        ], JSON_THROW_ON_ERROR));
    }

    #[Test]
    public function page_list_inspection_schema_and_validation_share_the_contract(): void
    {
        $discovery = new DiscoveryService;
        $items = $discovery->list($this->tmp, 'page')->toArray()['data']['items'];
        self::assertNotEmpty($items);
        $route = $items[0]['route'];
        $inspection = $discovery->inspect($this->tmp, 'page', $route)->toArray();
        self::assertSame('docara.page_inspection.v1', $inspection['data']['schema']);
        self::assertSame('article', $inspection['data']['profile']['id']);
        self::assertSame('simai-framework.lock.json', $inspection['data']['locks']['framework']['path']);
        self::assertArrayHasKey('peers', $inspection['data']['translation']);
        self::assertMatchesRegularExpression('/^[a-f0-9]{40}$/', (string) $inspection['data']['revisions']['repository_head']);
        self::assertSame('authoring.schema.json', $discovery->schema($this->tmp, 'authoring')->toArray()['data']['schema_id']);
        self::assertSame(0, (new ValidationService)->validate($this->tmp, 'page', $route)->exitCode);
    }

    #[Test]
    public function page_scaffold_is_create_only_and_hash_bound(): void
    {
        $service = new ScaffoldService;
        $plan = $service->plan($this->tmp, 'page', 'guide/first-task', [
            'locale' => 'ru', 'title' => 'First task', 'profile' => 'how_to',
        ])->toArray();
        self::assertSame('create', $plan['data']['diff'][0]['action']);
        $service->apply($this->tmp, $plan['data']['plan_id']);
        self::assertFileExists($this->tmpPath('content/ru/guide/first-task.md'));
        self::assertStringContainsString('profile: how_to', (string) file_get_contents($this->tmpPath('content/ru/guide/first-task.md')));

        $this->expectException(PortableConfigurationException::class);
        $this->expectExceptionMessage('already exists');
        $service->plan($this->tmp, 'page', 'guide/first-task', [
            'locale' => 'ru', 'title' => 'First task', 'profile' => 'how_to',
        ]);
    }

    #[Test]
    public function conflicting_rules_fail_deterministically(): void
    {
        $this->filesystem->put($this->tmpPath('docara.authoring.json'), json_encode([
            'rules' => [
                ['match' => '**', 'profile' => 'article'],
                ['match' => '**', 'profile' => 'reference'],
            ],
        ], JSON_THROW_ON_ERROR));
        $this->expectException(PortableConfigurationException::class);
        $this->expectExceptionMessage('conflicting profiles');
        (new DiscoveryService)->list($this->tmp, 'page');
    }

    #[Test]
    public function all_profiles_are_built_in_and_front_matter_overrides_rules(): void
    {
        $ids = array_keys((new AuthoringProfileRegistry)->all());
        sort($ids, SORT_STRING);
        self::assertSame(['article', 'explanation', 'how_to', 'landing', 'reference', 'tutorial'], $ids);
        $this->filesystem->put($this->tmpPath('docara.authoring.json'), json_encode([
            'default_profile' => 'article',
            'rules' => [['match' => 'index.md', 'profile' => 'landing']],
        ], JSON_THROW_ON_ERROR));
        $source = $this->tmpPath('content/ru/index.md');
        $this->filesystem->put($source, "---\nprofile: reference\n---\n\n" . (string) file_get_contents($source));
        $inspection = (new DiscoveryService)->inspect($this->tmp, 'page', '/ru/')->toArray()['data'];
        self::assertSame('reference', $inspection['profile']['id']);
        self::assertSame('front_matter', $inspection['profile']['source']);
    }

    #[Test]
    public function absent_authoring_contract_preserves_unprofiled_page_discovery(): void
    {
        $this->filesystem->delete($this->tmpPath('docara.authoring.json'));
        $item = (new DiscoveryService)->list($this->tmp, 'page')->toArray()['data']['items'][0];
        self::assertNull($item['profile']);
        self::assertSame('none', $item['profile_source']);
    }

    #[Test]
    public function page_scaffold_rejects_stale_plan_unknown_inputs_and_traversal(): void
    {
        $service = new ScaffoldService;
        $options = ['locale' => 'ru', 'title' => 'Safe page', 'profile' => 'article'];
        $plan = $service->plan($this->tmp, 'page', 'safe/page', $options)->toArray();
        $this->filesystem->append($this->tmpPath('docara.json'), "\n");
        try {
            $service->apply($this->tmp, $plan['data']['plan_id']);
            self::fail('Stale page plan was accepted.');
        } catch (PortableConfigurationException $exception) {
            self::assertSame('SCAFFOLD_PLAN_STALE', $exception->errorCode);
        }
        foreach ([
            ['../escape', $options],
            ['safe/locale', array_replace($options, ['locale' => 'zz'])],
            ['safe/profile', array_replace($options, ['profile' => 'unknown'])],
        ] as [$route, $invalid]) {
            try {
                $service->plan($this->tmp, 'page', $route, $invalid);
                self::fail("Invalid page scaffold [$route] was accepted.");
            } catch (PortableConfigurationException) {
                self::addToAssertionCount(1);
            }
        }
    }

    #[Test]
    public function page_scaffold_rejects_case_collisions_symlinks_and_hardlinks(): void
    {
        $service = new ScaffoldService;
        $options = ['locale' => 'ru', 'title' => 'Guarded page', 'profile' => 'article'];

        mkdir($this->tmpPath('content/ru/CaseOnly'));
        symlink($this->tmpPath('content/ru'), $this->tmpPath('content/ru/symlinked'));
        link($this->tmpPath('content/ru/index.md'), $this->tmpPath('content/ru/hardlink.md'));

        foreach (['caseonly/page', 'symlinked/page', 'hardlink'] as $route) {
            try {
                $service->plan($this->tmp, 'page', $route, $options);
                self::fail("Unsafe page scaffold [$route] was accepted.");
            } catch (PortableConfigurationException) {
                self::addToAssertionCount(1);
            }
        }
    }

    #[Test]
    public function invalid_authoring_schema_fails_validation_but_only_warns_during_build(): void
    {
        $this->filesystem->put($this->tmpPath('docara.authoring.json'), "{\"versions\":[]}\n");
        try {
            (new ValidationService)->validate($this->tmp, 'project');
            self::fail('Invalid authoring schema passed validation.');
        } catch (PortableConfigurationException $exception) {
            self::assertSame('SCHEMA_VALIDATION_FAILED', $exception->errorCode);
        }

        $command = (new BuildCommand(new PortableSiteBuilder($this->filesystem, new PortableMarkdownRenderer)))->setBase($this->tmp);
        $command->setApplication(new Application);
        $tester = new CommandTester($command);
        self::assertSame(0, $tester->execute(['environment' => 'authoring-invalid']));
        self::assertStringContainsString('Page authoring contract warning:', $tester->getDisplay());
        self::assertDirectoryExists($this->tmpPath('build_authoring-invalid'));
    }

    #[Test]
    public function page_validation_uses_the_project_aware_markdown_compiler(): void
    {
        $this->filesystem->put($this->tmpPath('content/ru/index.md'), "# Invalid page\n\n:::unknown_component\n:::\n");

        $this->expectException(PortableConfigurationException::class);
        (new ValidationService)->validate($this->tmp, 'page', '/ru/');
    }

    #[Test]
    public function page_validation_resolves_absolute_locale_assets_from_the_locale_content_root(): void
    {
        $this->filesystem->ensureDirectoryExists($this->tmpPath('content/ru/assets/reference'));
        $this->filesystem->put($this->tmpPath('content/ru/assets/reference/example.png'), 'png');
        $this->filesystem->put(
            $this->tmpPath('content/ru/index.md'),
            "# Asset page\n\n![Example](/ru/assets/reference/example.png)\n",
        );

        self::assertSame(0, (new ValidationService)->validate($this->tmp, 'page', '/ru/')->exitCode);
    }
}
