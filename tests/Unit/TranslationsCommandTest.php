<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Simai\Docara\Console\TranslationsCommand;
use Simai\Docara\I18n\TranslationStatusService;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\TestCase;

final class TranslationsCommandTest extends TestCase
{
    #[Test]
    public function status_and_hash_bound_accept_are_available_through_the_cli(): void
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
                    'en' => ['label' => 'English', 'direction' => 'ltr', 'content_root' => 'content/en', 'public_prefix' => 'en'],
                ],
                'translation_tracking' => ['enabled' => true, 'source_locale' => 'ru', 'mode' => 'report', 'lock_file' => 'translations.lock.json'],
            ], JSON_THROW_ON_ERROR),
            'content/ru/index.md' => "# Начало\n",
            'content/en/index.md' => "# Start\n",
            'content/ru/lang.json' => '{}',
            'content/en/lang.json' => '{}',
        ]);
        $application = new Application;
        $application->addCommand((new TranslationsCommand(new TranslationStatusService))->setBase($this->tmp));
        $status = new CommandTester($application->find('translations'));
        self::assertSame(0, $status->execute(['action' => 'status', '--locale' => 'en', '--status' => 'unverified', '--json' => true]));
        $report = $this->jsonOutput($status->getDisplay());
        self::assertSame('docara.translation_status.v1', $report['schema']);
        self::assertSame(1, $report['summary']['unverified']);

        $dryRun = new CommandTester($application->find('translations'));
        self::assertSame(0, $dryRun->execute([
            'action' => 'accept',
            '--locale' => 'en',
            '--key' => 'index.md',
            '--review' => 'ai_verified',
            '--dry-run' => true,
            '--json' => true,
        ]));
        $plan = $this->jsonOutput($dryRun->getDisplay());
        self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $plan['plan_id']);

        $apply = new CommandTester($application->find('translations'));
        self::assertSame(0, $apply->execute(['action' => 'accept', '--apply' => $plan['plan_id'], '--json' => true]));
        self::assertFileExists($this->tmpPath('translations.lock.json'));
    }

    /** @return array<string,mixed> */
    private function jsonOutput(string $display): array
    {
        $offset = strpos($display, '{');
        self::assertNotFalse($offset, $display);

        return json_decode(substr($display, $offset), true, 512, JSON_THROW_ON_ERROR);
    }
}
