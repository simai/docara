<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Simai\Docara\I18n\ContentLanguageRepository;
use Simai\Docara\I18n\LocaleRegistry;
use Simai\Docara\I18n\TranslationPublicationGate;
use Simai\Docara\I18n\TranslationStatusService;
use Simai\Docara\Portable\PortableConfigurationException;
use Tests\TestCase;

final class TranslationStatusServiceTest extends TestCase
{
    #[Test]
    public function it_reports_pages_and_language_keys_and_accepts_a_hash_bound_translation(): void
    {
        $this->project();
        $service = new TranslationStatusService;
        $report = $service->report($this->tmp, 'en');

        self::assertSame('docara.translation_status.v1', $report['schema']);
        self::assertSame(2, $report['summary']['unverified']);
        self::assertSame(2, $report['summary']['missing']);
        self::assertSame(['lang', 'lang', 'page', 'page'], array_column($report['items'], 'kind'));
        self::assertSame('ru', $this->item($report, 'page', 'only.md')['fallback_locale']);
        self::assertSame('ru', $this->item($report, 'lang', 'common.continue')['fallback_locale']);

        $plan = $service->planAccept($this->tmp, 'en', 'guide.start', 'ai_verified');
        self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $plan['plan_id']);
        $result = $service->apply($this->tmp, $plan['plan_id']);
        self::assertSame('applied', $result['status']);
        self::assertSame('current', $this->item($service->report($this->tmp, 'en'), 'page', 'guide.start')['status']);

        file_put_contents($this->tmpPath('content/ru/index.md'), str_replace('Начало', 'Новое начало', (string) file_get_contents($this->tmpPath('content/ru/index.md'))));
        self::assertSame('stale', $this->item($service->report($this->tmp, 'en'), 'page', 'guide.start')['status']);
    }

    #[Test]
    public function target_changes_become_unverified_and_structure_changes_are_reported_separately(): void
    {
        $this->project();
        $service = new TranslationStatusService;
        $plan = $service->planAccept($this->tmp, 'en', 'guide.start', 'human_reviewed');
        $service->apply($this->tmp, $plan['plan_id']);
        file_put_contents($this->tmpPath('content/en/index.md'), str_replace('Start', 'Getting started', (string) file_get_contents($this->tmpPath('content/en/index.md'))));
        self::assertSame('unverified', $this->item($service->report($this->tmp, 'en'), 'page', 'guide.start')['status']);
        file_put_contents($this->tmpPath('content/en/index.md'), "---\ntranslation_key: guide.start\n---\n## Start\n");
        self::assertSame('structure_mismatch', $this->item($service->report($this->tmp, 'en'), 'page', 'guide.start')['status']);
        $this->expectException(PortableConfigurationException::class);
        $service->planAccept($this->tmp, 'en', 'guide.start', 'ai_verified');
    }

    #[Test]
    public function stale_plans_and_ambiguous_keys_fail_closed(): void
    {
        $this->project();
        $service = new TranslationStatusService;
        $plan = $service->planAccept($this->tmp, 'en', 'guide.start', 'ai_verified');
        file_put_contents($this->tmpPath('content/en/index.md'), (string) file_get_contents($this->tmpPath('content/en/index.md')) . "\nChanged.\n");
        try {
            $service->apply($this->tmp, $plan['plan_id']);
            self::fail('A stale translation plan was applied.');
        } catch (PortableConfigurationException $exception) {
            self::assertSame('TRANSLATION_PLAN_STALE', $exception->errorCode);
        }

        file_put_contents($this->tmpPath('content/en/duplicate.md'), "---\ntranslation_key: guide.start\n---\n# Duplicate\n");
        self::assertSame('duplicate_key', $this->item($service->report($this->tmp, 'en'), 'page', 'guide.start')['status']);
    }

    #[Test]
    public function translation_control_front_matter_does_not_change_the_page_hash(): void
    {
        $this->project();
        $service = new TranslationStatusService;
        $before = $this->item($service->report($this->tmp, 'en'), 'page', 'guide.start');
        foreach (['ru', 'en'] as $locale) {
            $path = $this->tmpPath("content/$locale/index.md");
            file_put_contents($path, str_replace("translation_key: guide.start\n", '', (string) file_get_contents($path)));
        }
        $after = $this->item($service->report($this->tmp, 'en'), 'page', 'index.md');
        self::assertSame($before['source_sha256'], $after['source_sha256']);
        self::assertSame($before['source_structure_sha256'], $after['source_structure_sha256']);
    }

    #[Test]
    public function a_reviewed_set_is_accepted_through_one_hash_bound_plan(): void
    {
        $this->project();
        $this->createSource([
            'content/ru/guide/a.md' => "# А\n",
            'content/ru/guide/b.md' => "# Б\n",
            'content/en/guide/a.md' => "# A\n",
            'content/en/guide/b.md' => "# B\n",
            'content/en/orphan.md' => "# Orphan\n",
        ]);
        $service = new TranslationStatusService;

        $plan = $service->planAcceptSet($this->tmp, 'en', 'human_reviewed', prefix: 'guide/');
        self::assertSame(['guide/a.md', 'guide/b.md'], array_column($plan['accepted'], 'key'));
        self::assertSame([], $plan['skipped']);
        self::assertSame('guide/', $plan['selection']['prefix']);
        self::assertArrayHasKey('content/en/guide/a.md', $plan['input_hashes']);
        self::assertArrayHasKey('content/ru/guide/b.md', $plan['input_hashes']);
        self::assertSame('applied', $service->apply($this->tmp, $plan['plan_id'])['status']);
        $report = $service->report($this->tmp, 'en');
        self::assertSame('current', $this->item($report, 'page', 'guide/a.md')['status']);
        self::assertSame('human_reviewed', $this->item($report, 'page', 'guide/b.md')['review']);
        self::assertSame('unverified', $this->item($report, 'page', 'guide.start')['status']);

        // The result of a filtered status: every unverified page, with the
        // keys that cannot be accepted listed, not hidden.
        $plan = $service->planAcceptSet($this->tmp, 'en', 'ai_verified', keys: ['guide.start', 'orphan.md']);
        self::assertSame(['guide.start'], array_column($plan['accepted'], 'key'));
        self::assertSame([['key' => 'orphan.md', 'status' => 'orphan']], $plan['skipped']);
        $plan = $service->planAcceptSet($this->tmp, 'en', 'ai_verified', status: 'unverified', kind: 'lang');
        self::assertSame(['common.search'], array_column($plan['accepted'], 'key'));

        // A plan is bound to every file it read.
        $plan = $service->planAcceptSet($this->tmp, 'en', 'ai_verified', status: 'unverified');
        file_put_contents($this->tmpPath('content/en/index.md'), "---\ntranslation_key: guide.start\n---\n# Started\n");
        try {
            $service->apply($this->tmp, $plan['plan_id']);
            self::fail('A stale set plan was applied.');
        } catch (PortableConfigurationException $exception) {
            self::assertSame('TRANSLATION_PLAN_STALE', $exception->errorCode);
        }
        foreach ([
            [[], null, null, 'TRANSLATION_ACCEPT_SELECTION_REQUIRED'],
            [['orphan.md'], null, null, 'TRANSLATION_ACCEPT_SELECTION_EMPTY'],
            [['no-such.md'], null, null, 'TRANSLATION_ACCEPT_TARGET_INVALID'],
        ] as [$keys, $prefix, $status, $code]) {
            try {
                $service->planAcceptSet($this->tmp, 'en', 'ai_verified', $keys, $prefix, $status);
                self::fail("Selection did not fail with [$code].");
            } catch (PortableConfigurationException $exception) {
                self::assertSame($code, $exception->errorCode);
            }
        }
    }

    #[Test]
    public function publish_current_mode_holds_back_what_is_not_current(): void
    {
        $this->project();
        $site = json_decode((string) file_get_contents($this->tmpPath('docara.json')), true, 512, JSON_THROW_ON_ERROR);
        self::assertNull(TranslationPublicationGate::fromSite($this->tmp, $site), 'Report mode builds as before.');
        $site['translation_tracking']['mode'] = 'publish-current';
        file_put_contents($this->tmpPath('docara.json'), json_encode($site, JSON_THROW_ON_ERROR));
        $this->createSource([
            'content/ru/current.md' => "# Текущая\n",
            'content/en/current.md' => "# Current\n",
            'content/en/orphan.md' => "# Orphan\n",
            'content/ru/excluded.md' => "# Исключённая\n",
            'content/en/excluded.md' => "# Excluded\n",
        ]);
        $service = new TranslationStatusService;
        $service->apply($this->tmp, $service->planAccept($this->tmp, 'en', 'current.md', 'ai_verified')['plan_id']);
        $service->apply($this->tmp, $service->planAccept($this->tmp, 'en', 'excluded.md', 'ai_verified', 'English page kept as written')['plan_id']);
        self::assertSame('publish-current', $service->report($this->tmp)['mode']);

        $gate = TranslationPublicationGate::fromSite($this->tmp, $site);
        self::assertInstanceOf(TranslationPublicationGate::class, $gate);
        self::assertNull($gate->decision('content/en/current.md'));
        self::assertNull($gate->decision('content/en/excluded.md'));
        self::assertNull($gate->decision('content/ru/index.md'));
        self::assertSame([
            'action' => 'source_fallback',
            'status' => 'unverified',
            'key' => 'guide.start',
            'locale' => 'en',
            'source_path' => 'content/ru/index.md',
            'target_path' => 'content/en/index.md',
        ], $gate->decision('content/en/index.md'));
        self::assertSame('withheld', $gate->decision('content/en/orphan.md')['action']);
        self::assertSame(['en' => ['common.search']], $gate->languageKeys());
        $receipt = $gate->receipt();
        self::assertSame(['source_fallback' => 1, 'withheld' => 1, 'source_strings' => 1], $receipt['summary']);

        // Stale or unverified interface strings show the source string; a
        // missing one is left to the locale's own rules.
        $registry = LocaleRegistry::fromSite($site);
        $languages = (new ContentLanguageRepository($this->tmp))->withSourceStrings($registry->get('ru'), $gate->languageKeys());
        self::assertSame(['common.search' => 'Поиск'], $languages->messages($registry->get('en')));
        self::assertSame('Поиск', $languages->messages($registry->get('ru'))['common.search']);
        self::assertSame(['common.search' => 'Search'], (new ContentLanguageRepository($this->tmp))->messages($registry->get('en')));

        file_put_contents($this->tmpPath('content/en/duplicate.md'), "---\ntranslation_key: guide.start\n---\n# Duplicate\n");
        try {
            TranslationPublicationGate::fromSite($this->tmp, $site);
            self::fail('A duplicate key was published.');
        } catch (PortableConfigurationException $exception) {
            self::assertSame('TRANSLATION_DUPLICATE_KEY_BLOCKS_PUBLICATION', $exception->errorCode);
        }
    }

    private function project(): void
    {
        $this->createSource([
            'docara.json' => json_encode([
                'schema' => 'docara.site.v1',
                'preset' => 'docs',
                'framework_lock' => 'framework.lock.json',
                'default_locale' => 'ru',
                'locales' => [
                    'missing_page_policy' => 'skip',
                    'ru' => ['label' => 'Русский', 'direction' => 'ltr', 'content_root' => 'content/ru', 'public_prefix' => 'ru'],
                    'en' => ['label' => 'English', 'direction' => 'ltr', 'content_root' => 'content/en', 'public_prefix' => 'en', 'fallbacks' => ['ru']],
                ],
                'translation_tracking' => ['enabled' => true, 'source_locale' => 'ru', 'mode' => 'report', 'lock_file' => 'translations.lock.json'],
            ], JSON_THROW_ON_ERROR),
            'content/ru/index.md' => "---\ntranslation_key: guide.start\n---\n# Начало\n",
            'content/en/index.md' => "---\ntranslation_key: guide.start\n---\n# Start\n",
            'content/ru/only.md' => "# Только русский\n",
            'content/ru/lang.json' => json_encode(['schema' => 'docara.lang.v1', 'version' => 1, 'common' => ['search' => 'Поиск', 'continue' => 'Продолжить']], JSON_THROW_ON_ERROR),
            'content/en/lang.json' => json_encode(['schema' => 'docara.lang.v1', 'version' => 1, 'common' => ['search' => 'Search']], JSON_THROW_ON_ERROR),
        ]);
    }

    /** @return array<string,mixed> */
    private function item(array $report, string $kind, string $key): array
    {
        $matches = array_values(array_filter($report['items'], static fn (array $item): bool => $item['kind'] === $kind && $item['key'] === $key));
        self::assertCount(1, $matches);

        return $matches[0];
    }
}
