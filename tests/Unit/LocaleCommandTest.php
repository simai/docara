<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Simai\Docara\Application\LocaleAddService;
use Simai\Docara\Console\InitCommand;
use Simai\Docara\Console\LocaleCommand;
use Simai\Docara\File\Filesystem;
use Simai\Docara\I18n\LocaleRegistry;
use Simai\Docara\I18n\LocaleRoutingPolicy;
use Simai\Docara\I18n\TranslationStatusService;
use Simai\Docara\PortableSite\PortableProjectInitializer;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\TestCase;

final class LocaleCommandTest extends TestCase
{
    #[Test]
    public function a_locale_is_added_beside_the_default_with_its_folder_and_tracking(): void
    {
        $this->init('en');
        $before = $this->json('docara.json');

        [$status, $result] = $this->locale(['code' => 'ru']);

        self::assertSame(0, $status, json_encode($result));
        self::assertSame('locale.add', $result['operation']);
        self::assertSame('ru', $result['subject']);
        $site = $this->json('docara.json');
        self::assertSame('en', $site['default_locale']);
        self::assertSame([
            'label' => 'Русский',
            'direction' => 'ltr',
            'content_root' => 'content/ru',
            'public_prefix' => 'ru',
            'fallbacks' => [],
        ], $site['locales']['ru']);
        self::assertSame($before['locales']['en'], $site['locales']['en']);
        self::assertSame($before['locale_routing'], $site['locale_routing']);
        self::assertSame(['enabled' => true, 'source_locale' => 'en', 'mode' => 'report', 'lock_file' => 'translations.lock.json'], $site['translation_tracking']);
        // docara.json keeps its two-space indentation and key order.
        self::assertStringStartsWith("{\n  \"schema\": \"docara.site.v1\",\n  \"title\"", (string) file_get_contents($this->tmpPath('docara.json')));
        self::assertSame(array_keys($before), array_slice(array_keys($site), 0, count($before)));

        self::assertFileEquals(dirname(__DIR__, 2) . '/stubs/minimal/ru/content/ru/lang.json', $this->tmpPath('content/ru/lang.json'));
        $home = (string) file_get_contents($this->tmpPath('content/ru/index.md'));
        self::assertStringStartsWith("---\ndraft: true\n", $home);
        self::assertStringContainsString('# My documentation', $home);
        self::assertSame(['schema' => 'docara.translation_lock.v1', 'source_locale' => 'en', 'entries' => [], 'exclusions' => []], $this->json('translations.lock.json'));

        $report = (new TranslationStatusService)->report($this->tmp, 'ru', 'missing', 'page');
        self::assertContains('index.md', array_column($report['items'], 'key'));
        self::assertSame(count($report['items']), $result['data']['translation']['source_pages']);
        self::assertSame('content/ru/index.md', $result['data']['content']['draft_home']);
        self::assertSame([], $result['data']['redirects']['added']);
    }

    #[Test]
    public function a_language_without_a_bundled_lang_file_falls_back_to_the_source_and_reads_right_to_left(): void
    {
        $this->init('en');

        [$status, $result] = $this->locale(['code' => 'AR']);

        self::assertSame(0, $status, json_encode($result));
        $locale = $this->json('docara.json')['locales']['ar'];
        self::assertSame('rtl', $locale['direction']);
        self::assertSame(['en'], $locale['fallbacks']);
        self::assertSame('العربية', $locale['label']);
        self::assertSame(['schema' => 'docara.lang.v1', 'version' => 1], $this->json('content/ar/lang.json'));

        [$status] = $this->locale(['code' => 'fr-ca', '--label' => 'Français (Canada)', '--direction' => 'ltr']);
        self::assertSame(0, $status);
        $locale = $this->json('docara.json')['locales']['fr-CA'];
        self::assertSame(['label' => 'Français (Canada)', 'direction' => 'ltr', 'content_root' => 'content/fr-CA', 'public_prefix' => 'fr-ca', 'fallbacks' => ['en']], $locale);
        self::assertFileExists($this->tmpPath('content/fr-CA/index.md'));
    }

    #[Test]
    public function an_existing_translation_folder_is_registered_without_touching_its_pages(): void
    {
        $this->init('en');
        $this->createSource([
            'content/de/index.md' => "# Dokumentation\n",
            'content/de/lang.json' => "{\"schema\": \"docara.lang.v1\", \"version\": 1}\n",
        ]);

        [$status, $result] = $this->locale(['code' => 'de']);

        self::assertSame(0, $status, json_encode($result));
        self::assertSame("# Dokumentation\n", file_get_contents($this->tmpPath('content/de/index.md')));
        self::assertSame("{\"schema\": \"docara.lang.v1\", \"version\": 1}\n", file_get_contents($this->tmpPath('content/de/lang.json')));
        self::assertNull($result['data']['content']['draft_home']);
        self::assertSame('kept', $result['data']['content']['lang_json']);
        self::assertSame([['action' => 'update', 'path' => 'docara.json'], ['action' => 'create', 'path' => 'translations.lock.json']], $result['data']['changes']);
    }

    #[Test]
    public function a_dry_run_reports_the_changes_and_writes_nothing(): void
    {
        $this->init('en');
        $snapshot = $this->snapshot();

        [$status, $result] = $this->locale(['code' => 'ru', '--dry-run' => true]);

        self::assertSame(0, $status);
        self::assertTrue($result['data']['dry_run']);
        self::assertContains(['action' => 'create', 'path' => 'content/ru/lang.json'], $result['data']['changes']);
        self::assertSame($snapshot, $this->snapshot());
    }

    #[Test]
    public function the_default_moves_from_an_unprefixed_site_and_old_addresses_redirect_to_the_old_language(): void
    {
        $this->init('en');
        $this->createSource([
            'content/ru/index.md' => "# Документация\n",
            'redirects.json' => "{\n  \"schema\": \"docara.redirects.v1\",\n  \"version\": 1,\n  \"redirects\": [\n    {\"from\": \"old-writing\", \"to\": \"writing\"},\n    {\"from\": \"start\", \"to\": \"\"}\n  ]\n}\n",
        ]);

        [$status, $result] = $this->locale(['code' => 'ru', '--default' => true]);

        self::assertSame(0, $status, json_encode($result));
        $site = $this->json('docara.json');
        self::assertSame('ru', $site['default_locale']);
        self::assertSame('content/ru', $site['content_root']);
        self::assertSame('en', $site['locales']['en']['public_prefix']);
        self::assertSame('ru', $site['locales']['ru']['public_prefix']);
        self::assertSame(['strategy' => 'prefixed', 'root' => 'redirect', 'detect_browser_language' => false], $site['locale_routing']);
        self::assertSame('en', $site['translation_tracking']['source_locale']);
        LocaleRoutingPolicy::fromSite($site, LocaleRegistry::fromSite($site));

        $redirects = array_column($this->json('redirects.json')['redirects'], 'to', 'from');
        self::assertSame('en/writing', $redirects['old-writing']);
        self::assertSame('en', $redirects['start']);
        self::assertSame('en/writing', $redirects['writing']);
        self::assertSame('en/getting-started/first-page', $redirects['getting-started/first-page']);
        self::assertArrayNotHasKey('', $redirects);
        self::assertStringContainsString("\n    {\"from\": \"writing\", \"to\": \"en/writing\"}", (string) file_get_contents($this->tmpPath('redirects.json')));
        self::assertSame(['after' => 'prefixed', 'before' => 'default_unprefixed'], $result['data']['routing']);
        self::assertCount(2, $result['data']['redirects']['retargeted']);
        self::assertFileExists($this->tmpPath('content/ru/lang.json'));
    }

    #[Test]
    public function the_default_moves_on_a_prefixed_site_and_generated_legacy_redirects_are_written_down(): void
    {
        $this->init('ru');
        $site = $this->json('docara.json');
        $site['locales']['ru']['public_prefix'] = 'ru';
        $site['locale_routing'] = ['strategy' => 'prefixed', 'root' => 'redirect', 'detect_browser_language' => false, 'legacy_unprefixed_redirects' => true];
        $site['translation_tracking'] = ['enabled' => true, 'source_locale' => 'ru', 'mode' => 'report', 'lock_file' => 'translations.lock.json'];
        $this->filesystem->put($this->tmpPath('docara.json'), json_encode($site, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n");
        $this->createSource([
            'content/en/index.md' => "# Documentation\n",
            'content/ru/draft-page.md' => "---\ndraft: true\n---\n\n# Черновик\n",
        ]);

        [$status, $result] = $this->locale(['code' => 'en', '--default' => true]);

        self::assertSame(0, $status, json_encode($result));
        $site = $this->json('docara.json');
        self::assertSame('en', $site['default_locale']);
        self::assertFalse($site['locale_routing']['legacy_unprefixed_redirects']);
        self::assertSame('ru', $site['locales']['ru']['public_prefix']);
        self::assertSame('ru', $site['translation_tracking']['source_locale']);
        $redirects = array_column($this->json('redirects.json')['redirects'], 'to', 'from');
        self::assertSame('ru/writing', $redirects['writing']);
        self::assertArrayNotHasKey('draft-page', $redirects);
        self::assertArrayNotHasKey('', $redirects);
        self::assertSame('kept', $result['data']['translation']['tracking']);
        // The four-space docara.json stays four-space.
        self::assertStringStartsWith("{\n    \"schema\"", (string) file_get_contents($this->tmpPath('docara.json')));
    }

    #[Test]
    public function a_prefixed_site_without_legacy_redirects_switches_the_default_without_new_redirects(): void
    {
        $this->init('ru');
        $site = $this->json('docara.json');
        $site['locales']['ru']['public_prefix'] = 'ru';
        $site['locale_routing'] = ['strategy' => 'prefixed', 'root' => 'redirect', 'detect_browser_language' => false];
        $this->filesystem->put($this->tmpPath('docara.json'), json_encode($site, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n");
        $this->createSource(['content/en/index.md' => "# Documentation\n"]);
        $redirects = (string) file_get_contents($this->tmpPath('redirects.json'));

        [$status, $result] = $this->locale(['code' => 'en', '--default' => true]);

        self::assertSame(0, $status, json_encode($result));
        self::assertSame('en', $this->json('docara.json')['default_locale']);
        self::assertSame($redirects, file_get_contents($this->tmpPath('redirects.json')));
        self::assertNull($result['data']['redirects']['file']);
    }

    /** @return iterable<string, array{0:string}> */
    public static function invalidCodes(): iterable
    {
        yield 'a word' => ['english'];
        yield 'an underscore' => ['en_US'];
        yield 'a private tag' => ['x-docs'];
        yield 'a retired tag' => ['i-klingon'];
        yield 'a stray character' => ['en/../ru'];
        yield 'empty' => [' '];
    }

    #[Test]
    #[DataProvider('invalidCodes')]
    public function an_unknown_code_is_refused_before_anything_is_written(string $code): void
    {
        $this->init('en');
        $snapshot = $this->snapshot();

        [$status, $result] = $this->locale(['code' => $code]);

        self::assertSame(2, $status);
        self::assertSame('LOCALE_ADD_CODE_INVALID', $result['diagnostics'][0]['code']);
        self::assertNotSame('', $result['diagnostics'][0]['suggestion']);
        self::assertSame($snapshot, $this->snapshot());
    }

    #[Test]
    public function an_existing_locale_is_refused_whatever_its_spelling(): void
    {
        $this->init('en');
        $snapshot = $this->snapshot();

        foreach (['en', 'EN'] as $code) {
            [$status, $result] = $this->locale(['code' => $code, '--default' => true]);
            self::assertSame(2, $status);
            self::assertSame('LOCALE_ADD_EXISTS', $result['diagnostics'][0]['code']);
        }
        self::assertSame($snapshot, $this->snapshot());
    }

    #[Test]
    public function a_site_that_cannot_be_read_is_refused(): void
    {
        $this->init('en');
        $site = (string) file_get_contents($this->tmpPath('docara.json'));

        $this->filesystem->put($this->tmpPath('docara.json'), "{\n  \"schema\": \"docara.site.v1\",\n");
        $snapshot = $this->snapshot();
        [$status, $result] = $this->locale(['code' => 'ru']);
        self::assertSame(2, $status);
        self::assertSame('SDK_PROJECT_CONFIG_INVALID', $result['diagnostics'][0]['code']);
        self::assertSame($snapshot, $this->snapshot());

        $broken = json_decode($site, true);
        $broken['locales']['en']['direction'] = 'sideways';
        $this->filesystem->put($this->tmpPath('docara.json'), json_encode($broken, JSON_PRETTY_PRINT));
        $snapshot = $this->snapshot();
        [$status, $result] = $this->locale(['code' => 'ru']);
        self::assertSame(2, $status);
        self::assertSame('LOCALE_ADD_SITE_INVALID', $result['diagnostics'][0]['code']);
        self::assertStringContainsString('Nothing was written', $result['diagnostics'][0]['message']);
        self::assertSame($snapshot, $this->snapshot());
    }

    #[Test]
    public function the_default_needs_a_published_home_page_of_the_new_locale(): void
    {
        $this->init('en');
        $snapshot = $this->snapshot();
        [$status, $result] = $this->locale(['code' => 'ru', '--default' => true]);
        self::assertSame(2, $status);
        self::assertSame('LOCALE_DEFAULT_HOME_MISSING', $result['diagnostics'][0]['code']);
        self::assertSame($snapshot, $this->snapshot());

        $this->createSource(['content/ru/index.md' => "---\ndraft: true\n---\n\n# Документация\n"]);
        $snapshot = $this->snapshot();
        [$status, $result] = $this->locale(['code' => 'ru', '--default' => true]);
        self::assertSame(2, $status);
        self::assertSame('LOCALE_DEFAULT_HOME_MISSING', $result['diagnostics'][0]['code']);
        self::assertSame($snapshot, $this->snapshot());
    }

    #[Test]
    public function a_flat_content_folder_or_a_file_in_the_way_is_refused(): void
    {
        $this->init('en');
        $site = $this->json('docara.json');
        $this->filesystem->put($this->tmpPath('content/ru'), 'not a folder');
        $snapshot = $this->snapshot();
        [$status, $result] = $this->locale(['code' => 'ru']);
        self::assertSame(2, $status);
        self::assertSame('LOCALE_ADD_CONTENT_ROOT_UNSAFE', $result['diagnostics'][0]['code']);
        self::assertSame($snapshot, $this->snapshot());

        $site['content_root'] = 'content';
        $site['locales']['en']['content_root'] = 'content';
        $this->filesystem->put($this->tmpPath('docara.json'), json_encode($site, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $snapshot = $this->snapshot();
        [$status, $result] = $this->locale(['code' => 'de']);
        self::assertSame(2, $status);
        self::assertSame('LOCALE_ADD_CONTENT_LAYOUT_UNSUPPORTED', $result['diagnostics'][0]['code']);
        self::assertSame($snapshot, $this->snapshot());
    }

    #[Test]
    public function a_write_that_cannot_finish_leaves_no_file_behind(): void
    {
        $this->init('en');
        $site = $this->json('docara.json');
        $site['redirects_file'] = 'missing-folder/redirects.json';
        $this->filesystem->put($this->tmpPath('docara.json'), json_encode($site, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
        $this->createSource(['content/ru/index.md' => "# Документация\n"]);
        $snapshot = $this->snapshot();

        [$status, $result] = $this->locale(['code' => 'ru', '--default' => true]);

        self::assertSame(2, $status);
        self::assertSame('LOCALE_ADD_WRITE_FAILED', $result['diagnostics'][0]['code']);
        self::assertSame($snapshot, $this->snapshot());
    }

    #[Test]
    public function a_portal_site_keeps_its_single_locale_and_an_unknown_action_is_refused(): void
    {
        $this->init('en');
        $site = $this->json('docara.json');
        $site['site_url'] = 'https://example.com/docs/';
        $site['portal'] = ['product' => 'docs', 'home_url' => 'https://example.com/'];
        $this->filesystem->put($this->tmpPath('docara.json'), json_encode($site, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
        $snapshot = $this->snapshot();

        [$status, $result] = $this->locale(['code' => 'ru']);
        self::assertSame(2, $status);
        self::assertSame('LOCALE_ADD_PORTAL_SINGLE_LOCALE', $result['diagnostics'][0]['code']);

        [$status, $result] = $this->locale(['action' => 'remove', 'code' => 'en']);
        self::assertSame(2, $status);
        self::assertSame('LOCALE_ACTION_INVALID', $result['diagnostics'][0]['code']);
        self::assertSame($snapshot, $this->snapshot());
    }

    private function init(string $language): void
    {
        $files = new Filesystem;
        $command = new InitCommand($files, new PortableProjectInitializer($files));
        $command->setApplication(new Application);
        $command->setBase($this->tmp);
        $console = new CommandTester($command);
        self::assertSame(0, $console->execute(['--lang' => $language]), $console->getDisplay());
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{0:int,1:array<string,mixed>}
     */
    private function locale(array $input): array
    {
        $application = new Application;
        $application->addCommand((new LocaleCommand(new LocaleAddService))->setBase($this->tmp));
        $console = new CommandTester($application->find('locale'));
        $status = $console->execute($input + ['action' => 'add', '--json' => true]);

        return [$status, json_decode($console->getDisplay(), true, 512, JSON_THROW_ON_ERROR)];
    }

    /** @return array<string, mixed> */
    private function json(string $relative): array
    {
        return json_decode((string) file_get_contents($this->tmpPath($relative)), true, 512, JSON_THROW_ON_ERROR);
    }

    /** @return array<string, string> every file and folder of the project with its hash */
    private function snapshot(): array
    {
        $entries = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->tmp, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::SELF_FIRST);
        foreach ($iterator as $file) {
            $relative = substr($file->getPathname(), strlen($this->tmp) + 1);
            $entries[$relative] = $file->isDir() ? 'dir' : hash_file('sha256', $file->getPathname());
        }
        ksort($entries);

        return $entries;
    }
}
