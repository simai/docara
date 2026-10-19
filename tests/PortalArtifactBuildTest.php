<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * A one-locale site built for a portal path (docara.json `portal`) and packed
 * with `docara portal-artifact`, through the real command line.
 */
final class PortalArtifactBuildTest extends TestCase
{
    private const SITE_URL = 'https://portal.example/ru/product/docs/';

    #[Test]
    public function a_portal_build_carries_portal_addresses_and_packs_reproducibly(): void
    {
        $site = $this->tmpPath('site');
        $this->filesystem->ensureDirectoryExists($site);
        $this->docara(['init', '--template=minimal', '--lang=ru'], $site);
        $configuration = $this->readJson($site . '/docara.json');
        $configuration['base_url'] = '/ru/product/docs/';
        $configuration['site_url'] = self::SITE_URL;
        $configuration['portal'] = [
            'product' => 'product-docs',
            'home_url' => 'https://portal.example/ru/',
            'home_label' => 'Портал',
        ];
        $this->writeJson($site . '/docara.json', $configuration);
        // Content written for a prefixed site names its pages with the locale.
        file_put_contents(
            $site . '/content/ru/index.md',
            (string) file_get_contents($site . '/content/ru/index.md')
                . "\n[Как писать с префиксом](/ru/writing/), [компоненты от корня](/components/)"
                . " и [главная](/ru/).\n\n![Схема](/assets/scheme.png)\n",
        );
        $this->filesystem->ensureDirectoryExists($site . '/content/ru/assets');
        file_put_contents($site . '/content/ru/assets/scheme.png', $this->png());

        $this->docara(['build', 'portal'], $site);
        $build = $site . '/build_portal';
        foreach (['robots.txt', 'sitemap.xml', 'CNAME', '.nojekyll', '404.html'] as $file) {
            self::assertFileDoesNotExist($build . '/' . $file);
        }
        $llms = (string) file_get_contents($build . '/llms.txt');
        self::assertStringContainsString('(' . self::SITE_URL . 'writing/)', $llms);
        self::assertStringNotContainsString('sitemap.xml', $llms);

        $home = (string) file_get_contents($build . '/index.html');
        self::assertStringContainsString('<html lang="ru"', $home);
        self::assertStringContainsString('<link rel="canonical" href="' . self::SITE_URL . '">', $home);
        self::assertMatchesRegularExpression('/<meta name="description" content="[^"]+">/', $home);
        self::assertStringContainsString('data-docara-region="header" data-portal-shell="header"', $home);
        self::assertStringContainsString('<a class="docara-portal-home" href="https://portal.example/ru/" data-docara-portal-home>Портал</a>', $home);
        self::assertStringContainsString('href="/ru/product/docs/writing/"', $home);
        self::assertStringContainsString('href="/ru/product/docs/components/"', $home);
        self::assertStringNotContainsString('/ru/product/docs/ru/', $home);
        self::assertStringContainsString('src="/ru/product/docs/assets/scheme.png"', $home);
        self::assertFileExists($build . '/assets/scheme.png');
        self::assertStringContainsString(
            '<link rel="canonical" href="' . self::SITE_URL . 'writing/">',
            (string) file_get_contents($build . '/writing/index.html'),
        );
        $search = $this->readJson($build . '/_docara/search-index.json');
        self::assertStringContainsString('"/ru/product/docs/writing/"', json_encode($search, JSON_UNESCAPED_SLASHES) ?: '');

        $verification = json_decode(
            $this->docara(['verify-static', 'build_portal', '--no-ansi'], $site, jsonOutput: true),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        self::assertSame([], $verification['broken']);
        self::assertSame('/ru/product/docs/', $verification['deployment_base']);

        $commit = str_repeat('ab', 20);
        $pack = fn (string $output): string => $this->docara([
            'portal-artifact', 'portal', '--release=v2.0.0', '--repository=example/product',
            '--commit=' . $commit, '--output=' . $output,
        ], $site);
        self::assertStringContainsString('product-docs-2.0.0-portal.tar.gz', $pack('first'));
        $pack('second');
        $archive = 'product-docs-2.0.0-portal.tar.gz';
        self::assertSame(
            hash_file('sha256', $site . '/first/' . $archive),
            hash_file('sha256', $site . '/second/' . $archive),
        );
        $receipt = $this->readJson($site . '/first/product-docs-2.0.0-portal-receipt.json');
        self::assertSame(
            ['schema', 'version', 'product', 'release', 'repository', 'commit', 'locale', 'base_path', 'archive', 'sha256', 'files', 'framework_pair', 'docara_version'],
            array_keys($receipt),
        );
        self::assertSame('v2.0.0', $receipt['release']);
        self::assertSame($commit, $receipt['commit']);
        self::assertSame('ru', $receipt['locale']);
        self::assertSame('/ru/product/docs/', $receipt['base_path']);
        self::assertSame(hash_file('sha256', $site . '/first/' . $archive), $receipt['sha256']);
        self::assertMatchesRegularExpression('/\Aui-[0-9a-f]{12}\z/', $receipt['framework_pair']);
        $files = 0;
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($build, \FilesystemIterator::SKIP_DOTS)) as $file) {
            $files++;
        }
        self::assertSame($files, $receipt['files']);

        $extracted = $this->tmpPath('extracted');
        mkdir($extracted);
        $tar = new Process(['tar', '-xzf', $site . '/first/' . $archive, '-C', $extracted]);
        $tar->mustRun();
        self::assertFileEquals($build . '/index.html', $extracted . '/index.html');
        self::assertFileEquals($build . '/_docara/search-index.json', $extracted . '/_docara/search-index.json');
    }

    #[Test]
    public function a_portal_build_refuses_more_than_one_locale(): void
    {
        $site = $this->tmpPath('site');
        $this->filesystem->ensureDirectoryExists($site);
        $this->docara(['init', '--template=minimal', '--lang=ru'], $site);
        $configuration = $this->readJson($site . '/docara.json');
        $configuration['base_url'] = '/ru/product/docs/';
        $configuration['site_url'] = self::SITE_URL;
        $configuration['portal'] = ['product' => 'product-docs', 'home_url' => 'https://portal.example/ru/'];
        $configuration['locales']['en'] = [
            'label' => 'English', 'direction' => 'ltr', 'content_root' => 'content/en', 'public_prefix' => 'en', 'fallbacks' => [],
        ];
        $this->writeJson($site . '/docara.json', $configuration);
        $this->filesystem->copyDirectory($site . '/content/ru', $site . '/content/en');

        $output = $this->docara(['build', 'portal'], $site, pass: false);
        self::assertStringContainsString('one locale', $output);
        self::assertDirectoryDoesNotExist($site . '/build_portal');
    }

    /** @return array<string, mixed> */
    private function readJson(string $path): array
    {
        return json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    }

    /** @param array<string, mixed> $value */
    private function writeJson(string $path, array $value): void
    {
        file_put_contents($path, json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n");
    }

    private function png(): string
    {
        $chunk = static fn (string $type, string $data): string => pack('N', strlen($data)) . $type . $data . pack('N', crc32($type . $data));
        $raw = str_repeat("\x00" . str_repeat("\x30\x60\xc0", 4), 2);

        return "\x89PNG\r\n\x1a\n" . $chunk('IHDR', pack('NNCCCCC', 4, 2, 8, 2, 0, 0, 0))
            . $chunk('IDAT', (string) gzcompress($raw)) . $chunk('IEND', '');
    }

    /** @param list<string> $arguments */
    private function docara(array $arguments, string $cwd, bool $jsonOutput = false, bool $pass = true): string
    {
        $node = getenv('DOCARA_NODE_BINARY') ?: (new ExecutableFinder)->find('node');
        self::assertIsString($node, 'Building a site requires Node.js.');
        $process = new Process(
            array_merge([PHP_BINARY, dirname(__DIR__) . '/docara'], $arguments, ['--no-interaction']),
            $cwd,
            [
                'TZ' => 'UTC',
                'PATH' => dirname(PHP_BINARY) . ':/usr/bin:/bin:/usr/sbin:/sbin',
                'SIMAI_UI_ROOT' => false,
                'DOCARA_SIMAI_UI_ROOT' => false,
                'SIMAI_UI_SMART_ROOT' => false,
                'DOCARA_NODE_BINARY' => $node,
            ],
            null,
            600,
        );
        $process->run();
        if ($pass) {
            self::assertSame(0, $process->getExitCode(), implode(' ', $arguments) . "\n" . $process->getErrorOutput() . $process->getOutput());
        }
        $output = $process->getOutput() . $process->getErrorOutput();
        if ($jsonOutput) {
            $start = strpos($output, '{');

            return $start === false ? $output : substr($output, $start);
        }

        return $output;
    }
}
