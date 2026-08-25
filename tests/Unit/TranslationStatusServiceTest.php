<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
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
