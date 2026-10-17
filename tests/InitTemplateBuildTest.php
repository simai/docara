<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * Runs the real command line the way a new user does: init, build, then
 * verify-static, with no Framework checkout and no Docara or Framework
 * environment variables.
 */
final class InitTemplateBuildTest extends TestCase
{
    /** @return array<string, array{string, string, list<string>}> */
    public static function starters(): array
    {
        return [
            'minimal, English' => ['minimal', 'en', ['index.html', 'writing/index.html', 'components/index.html']],
            'minimal, Russian' => ['minimal', 'ru', ['index.html', 'writing/index.html', 'components/index.html']],
            'showcase, Russian' => ['showcase', 'ru', ['index.html', 'guides/getting-started/index.html', 'components/index.html']],
        ];
    }

    /** @param list<string> $pages */
    #[Test]
    #[DataProvider('starters')]
    public function every_starter_builds_and_verifies_without_a_checkout(string $template, string $language, array $pages): void
    {
        $site = $this->tmpPath("site-{$template}-{$language}");
        $this->filesystem->ensureDirectoryExists($site);

        $this->docara([
            'init', "--template={$template}", "--lang={$language}"], $site);
        $this->docara(['build', 'production'], $site);
        $verification = json_decode(
            $this->docara(['verify-static', 'build_production', '--no-ansi'], $site, jsonOutput: true),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        self::assertSame([], $verification['broken']);
        self::assertGreaterThanOrEqual(count($pages), $verification['html_pages']);
        foreach ($pages as $page) {
            self::assertFileExists($site . '/build_production/' . $page);
        }
        if ($language === 'en') {
            foreach ($pages as $page) {
                $html = (string) file_get_contents($site . '/build_production/' . $page);
                self::assertStringContainsString('<html lang="en"', $html);
                self::assertDoesNotMatchRegularExpression(
                    '/[А-Яа-яЁё]/u',
                    $this->visibleText($html),
                    "The English starter page [$page] shows Russian text.",
                );
            }
        }
    }

    #[Test]
    public function a_site_pinning_the_lock_docara_2_18_shipped_builds_and_verifies(): void
    {
        $site = $this->tmpPath('site-2-18-lock');
        $this->filesystem->ensureDirectoryExists($site);
        $this->docara(['init', '--template=showcase', '--lang=ru'], $site);
        copy(__DIR__ . '/fixtures/framework/simai-framework-2.18.2.lock.json', $site . '/simai-framework.lock.json');

        $this->docara(['build', 'production'], $site);
        $verification = json_decode(
            $this->docara(['verify-static', 'build_production', '--no-ansi'], $site, jsonOutput: true),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        self::assertSame([], $verification['broken']);
        $catalog = json_decode((string) file_get_contents($site . '/build_production/_docara/component-catalog.json'), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('ui-45384c434d3f', $catalog['framework_pair'], 'The site is served the bundled release.');
    }

    /** @param list<string> $arguments */
    private function docara(array $arguments, string $cwd, bool $jsonOutput = false): string
    {
        $environment = [
            'TZ' => 'UTC',
            'PATH' => dirname(PHP_BINARY) . ':/usr/bin:/bin:/usr/sbin:/sbin',
            // Process inherits the parent environment; unset what could point the
            // build at a Framework checkout.
            'SIMAI_UI_ROOT' => false,
            'DOCARA_SIMAI_UI_ROOT' => false,
            'SIMAI_UI_SMART_ROOT' => false,
        ];
        $node = getenv('DOCARA_NODE_BINARY') ?: (new ExecutableFinder)->find('node');
        self::assertIsString($node, 'Building a site requires Node.js.');
        $environment['DOCARA_NODE_BINARY'] = $node;

        $process = new Process(
            array_merge([PHP_BINARY, dirname(__DIR__) . '/docara'], $arguments, ['--no-interaction']),
            $cwd,
            $environment,
            null,
            600,
        );
        $process->run();
        self::assertSame(0, $process->getExitCode(), implode(' ', $arguments) . "\n" . $process->getErrorOutput() . $process->getOutput());
        $output = $process->getOutput();
        if ($jsonOutput) {
            $start = strpos($output, '{');
            self::assertNotFalse($start, $output);
            $output = substr($output, $start);
        }

        return $output;
    }

    private function visibleText(string $html): string
    {
        $html = (string) preg_replace('#<(script|style|template)\b[^>]*>.*?</\1>#is', ' ', $html);

        return html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
