<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Simai\Docara\I18n\LocaleRegistry;
use Simai\Docara\I18n\LocaleRoutingPolicy;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\PortableSite\PortalArtifactWriter;
use Simai\Docara\PortableSite\PortalProfile;
use Simai\Docara\PortableSite\SiteDiscovery;
use Tests\TestCase;

final class PortalArtifactTest extends TestCase
{
    private const SITE_URL = 'https://portal.example/ru/product/docs/';

    private const BASE = '/ru/product/docs/';

    private const COMMIT = '0123456789abcdef0123456789abcdef01234567';

    #[Test]
    public function a_site_without_a_portal_section_has_no_profile(): void
    {
        self::assertNull($this->profile($this->site(portal: null)));
        $discovery = SiteDiscovery::fromSite($this->site(portal: null));
        self::assertFalse($discovery?->portal);
        self::assertSame(SiteDiscovery::FILES, $discovery?->fileNames());
    }

    #[Test]
    public function the_profile_reads_the_product_home_and_portal_path(): void
    {
        $profile = $this->profile($this->site());
        self::assertInstanceOf(PortalProfile::class, $profile);
        self::assertSame('product-docs', $profile->product);
        self::assertSame(['url' => 'https://portal.example/ru/', 'label' => 'portal.example'], $profile->home());
        self::assertSame(self::SITE_URL, $profile->siteUrl);
        self::assertSame(self::BASE, $profile->basePath);
        self::assertSame('ru', $profile->locale);

        $labelled = $this->site();
        $labelled['portal']['home_label'] = '  Портал   SIMAI ';
        self::assertSame('Портал SIMAI', $this->profile($labelled)?->homeLabel);

        $discovery = SiteDiscovery::fromSite($this->site());
        self::assertTrue($discovery?->portal);
        self::assertSame([SiteDiscovery::LLMS], $discovery?->fileNames());
        $files = $discovery?->files([['url' => self::BASE, 'locale' => 'ru', 'title' => 'Home', 'description' => 'Start here.']], [], 'Docs', 'Start here.', []);
        self::assertSame([SiteDiscovery::LLMS], array_keys($files ?? []));
        self::assertStringNotContainsString('sitemap.xml', $files[SiteDiscovery::LLMS]);
        self::assertStringContainsString('(' . self::SITE_URL . '_docara/page-metadata.json)', $files[SiteDiscovery::LLMS]);
    }

    #[Test]
    public function the_profile_refuses_what_a_portal_cannot_host(): void
    {
        $cases = [];
        $cases['PORTAL_PRODUCT_INVALID'] = $this->site(portal: ['product' => 'Product', 'home_url' => 'https://portal.example/ru/']);
        $cases['PORTAL_HOME_URL_INVALID'] = $this->site(portal: ['product' => 'product-docs', 'home_url' => 'http://portal.example/ru/']);
        $cases['PORTAL_HOME_LABEL_INVALID'] = $this->site(portal: ['product' => 'product-docs', 'home_url' => 'https://portal.example/', 'home_label' => ' ']);
        $noSiteUrl = $this->site();
        unset($noSiteUrl['site_url']);
        $cases['PORTAL_SITE_URL_REQUIRED'] = $noSiteUrl;
        $twoLocales = $this->site();
        $twoLocales['locales']['en'] = ['label' => 'English', 'direction' => 'ltr', 'content_root' => 'content/en', 'public_prefix' => 'en', 'fallbacks' => []];
        $cases['PORTAL_SINGLE_LOCALE_REQUIRED'] = $twoLocales;
        $prefixed = $this->site();
        $prefixed['locales']['ru']['public_prefix'] = 'ru';
        $prefixed['locale_routing'] = ['strategy' => 'prefixed', 'root' => 'redirect', 'detect_browser_language' => false, 'legacy_unprefixed_redirects' => false];
        $cases['PORTAL_UNPREFIXED_ROUTING_REQUIRED'] = $prefixed;
        foreach ($cases as $code => $site) {
            try {
                $this->profile($site);
                self::fail("[$code] was accepted.");
            } catch (PortableConfigurationException $exception) {
                self::assertSame($code, $exception->errorCode);
            }
        }
    }

    #[Test]
    public function the_writer_packs_a_reproducible_archive_with_a_checksum_and_receipt(): void
    {
        [$root, $build] = $this->portalBuild();
        $writer = new PortalArtifactWriter;
        $receipt = $writer->write($root, $build, $this->release(), $this->tmpPath('out-1'));
        $again = $writer->write($root, $build, $this->release(), $this->tmpPath('out-2'));

        $archive = 'product-docs-1.2.0-portal.tar.gz';
        $bytes = (string) file_get_contents($this->tmpPath('out-1/' . $archive));
        self::assertSame($bytes, file_get_contents($this->tmpPath('out-2/' . $archive)));
        self::assertSame($receipt, $again);
        self::assertSame([
            'schema' => 'simai.io.portal-artifact',
            'version' => 1,
            'product' => 'product-docs',
            'release' => 'v1.2.0',
            'repository' => 'example/product',
            'commit' => self::COMMIT,
            'locale' => 'ru',
            'base_path' => self::BASE,
            'archive' => $archive,
            'sha256' => hash('sha256', $bytes),
            'files' => 4,
            'framework_pair' => 'ui-0123456789ab',
            'docara_version' => trim((string) file_get_contents(dirname(__DIR__, 2) . '/VERSION')),
        ], $receipt);
        self::assertSame(
            json_encode($receipt, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n",
            file_get_contents($this->tmpPath('out-1/product-docs-1.2.0-portal-receipt.json')),
        );
        self::assertSame(
            hash('sha256', $bytes) . '  ' . $archive . "\n",
            file_get_contents($this->tmpPath('out-1/' . $archive . '.sha256')),
        );

        // Fixed gzip header: no name, mtime 0, unknown operating system.
        self::assertSame("\x1f\x8b\x08\x00\x00\x00\x00\x00\x02\xff", substr($bytes, 0, 10));
        $tar = (string) gzdecode($bytes);
        self::assertSame(0, strlen($tar) % 10240);
        $entries = $this->tarEntries($tar);
        $long = 'guide/' . str_repeat('a', 60) . '/' . str_repeat('b', 60) . '/index.html';
        self::assertSame([
            '_docara/', '_docara/app.js',
            'guide/', 'guide/' . str_repeat('a', 60) . '/', 'guide/' . str_repeat('a', 60) . '/' . str_repeat('b', 60) . '/', $long,
            'index.html', 'llms.txt',
        ], array_keys($entries));
        foreach ($entries as $name => $entry) {
            self::assertSame(str_ends_with($name, '/') ? '5' : '0', $entry['type'], $name);
            self::assertSame(str_ends_with($name, '/') ? 0755 : 0644, $entry['mode'], $name);
            self::assertSame([0, 0, 0, 'root', 'root'], [$entry['mtime'], $entry['uid'], $entry['gid'], $entry['uname'], $entry['gname']], $name);
        }
        self::assertSame(file_get_contents($build . '/' . $long), $entries[$long]['body']);
        self::assertSame(file_get_contents($build . '/index.html'), $entries['index.html']['body']);

        $extracted = $this->tmpPath('extracted');
        mkdir($extracted);
        exec('tar -xzf ' . escapeshellarg($this->tmpPath('out-1/' . $archive)) . ' -C ' . escapeshellarg($extracted) . ' 2>&1', $output, $status);
        self::assertSame(0, $status, implode("\n", $output));
        self::assertSame(file_get_contents($build . '/' . $long), file_get_contents($extracted . '/' . $long));
    }

    #[Test]
    public function the_writer_refuses_a_build_the_portal_cannot_take(): void
    {
        $cases = [
            'PORTAL_FILE_COLLISION' => static fn (string $build) => file_put_contents($build . '/404.html', 'x'),
            'PORTAL_INDEX_MISSING' => static fn (string $build) => unlink($build . '/index.html'),
            'PORTAL_BUILD_LINK' => static fn (string $build) => symlink($build . '/llms.txt', $build . '/alias.txt'),
            'PORTAL_PAGE_INVALID' => static fn (string $build) => file_put_contents(
                $build . '/index.html',
                str_replace('<meta name="description" content="Start here.">', '', (string) file_get_contents($build . '/index.html')),
            ),
        ];
        foreach ($cases as $code => $break) {
            [$root, $build] = $this->portalBuild($code);
            $break($build);
            try {
                (new PortalArtifactWriter)->write($root, $build, $this->release(), $this->tmpPath('out-' . $code));
                self::fail("[$code] was accepted.");
            } catch (PortableConfigurationException $exception) {
                self::assertSame($code, $exception->errorCode);
            }
        }

        foreach ([
            ['canonical', '<link rel="canonical" href="https://elsewhere.example/">', 'not under'],
            ['lang', '<html lang="en">', 'lang'],
            ['back link', '<a href="https://elsewhere.example/" data-docara-portal-home>x</a>', 'link back'],
            ['pair', 'data-docara-framework-boot="ui-ffffffffffff"', 'pair'],
        ] as [$label, $replacement, $message]) {
            [$root, $build] = $this->portalBuild('page-' . str_replace(' ', '-', $label));
            $page = $build . '/guide/' . str_repeat('a', 60) . '/' . str_repeat('b', 60) . '/index.html';
            $html = (string) file_get_contents($page);
            $html = match ($label) {
                'canonical' => preg_replace('/<link rel="canonical"[^>]*>/', $replacement, $html),
                'lang' => str_replace('<html lang="ru">', $replacement, $html),
                'back link' => preg_replace('/<a class="docara-portal-home" href="[^"]*" data-docara-portal-home>[^<]*<\/a>/', $replacement, $html),
                default => str_replace('data-docara-framework-boot="ui-0123456789ab"', $replacement, $html),
            };
            file_put_contents($page, $html);
            try {
                (new PortalArtifactWriter)->write($root, $build, $this->release(), $this->tmpPath('out-' . $label));
                self::fail("A page with a wrong $label was accepted.");
            } catch (PortableConfigurationException $exception) {
                self::assertSame('PORTAL_PAGE_INVALID', $exception->errorCode, $label);
                self::assertStringContainsString($message, $exception->getMessage(), $label);
            }
        }

        [$root, $build] = $this->portalBuild('release');
        $release = $this->release();
        $release['commit'] = 'abc';
        try {
            (new PortalArtifactWriter)->write($root, $build, $release, $this->tmpPath('out-release'));
            self::fail('A short commit was accepted.');
        } catch (PortableConfigurationException $exception) {
            self::assertSame('PORTAL_RELEASE_INVALID', $exception->errorCode);
        }
        $plain = $this->site(portal: null);
        file_put_contents($root . '/docara.json', json_encode($plain));
        try {
            (new PortalArtifactWriter)->write($root, $build, $this->release(), $this->tmpPath('out-plain'));
            self::fail('A build without a portal section was packed.');
        } catch (PortableConfigurationException $exception) {
            self::assertSame('PORTAL_PROFILE_MISSING', $exception->errorCode);
        }
    }

    /**
     * @param  array<string, mixed>|null  $portal
     * @return array<string, mixed>
     */
    private function site(?array $portal = ['product' => 'product-docs', 'home_url' => 'https://portal.example/ru/']): array
    {
        return [
            'schema' => 'docara.site.v1',
            'preset' => 'docs',
            'framework_lock' => 'simai-framework.lock.json',
            'base_url' => self::BASE,
            'site_url' => self::SITE_URL,
            'default_locale' => 'ru',
            'locales' => [
                'ru' => ['label' => 'Русский', 'direction' => 'ltr', 'content_root' => 'content/ru', 'public_prefix' => '', 'fallbacks' => []],
            ],
            'locale_routing' => ['strategy' => 'default_unprefixed', 'root' => 'default_locale', 'detect_browser_language' => false, 'legacy_unprefixed_redirects' => false],
            ...($portal === null ? [] : ['portal' => $portal]),
        ];
    }

    /** @param array<string, mixed> $site */
    private function profile(array $site): ?PortalProfile
    {
        $locales = LocaleRegistry::fromSite($site);

        return PortalProfile::fromSite($site, $locales, LocaleRoutingPolicy::fromSite($site, $locales), SiteDiscovery::fromSite($site));
    }

    /** @return array{version:string,release:string,repository:string,commit:string} */
    private function release(): array
    {
        return ['version' => '1.2.0', 'release' => 'v1.2.0', 'repository' => 'example/product', 'commit' => self::COMMIT];
    }

    /** @return array{0:string,1:string} the site root and its build folder */
    private function portalBuild(string $name = 'site'): array
    {
        $root = $this->tmpPath($name);
        $build = $root . '/build_portal';
        $long = 'guide/' . str_repeat('a', 60) . '/' . str_repeat('b', 60);
        mkdir($build . '/' . $long, 0777, true);
        mkdir($build . '/_docara');
        file_put_contents($root . '/docara.json', json_encode($this->site()));
        $page = static fn (string $path): string => '<!doctype html><html lang="ru"><head>'
            . '<meta name="description" content="Start here.">'
            . '<link rel="canonical" href="' . self::SITE_URL . $path . '">'
            . '<title>Docs</title><script data-docara-framework-boot="ui-0123456789ab"></script></head><body>'
            . '<header data-portal-shell="header"><a class="docara-portal-home" href="https://portal.example/ru/" data-docara-portal-home>portal.example</a></header>'
            . '</body></html>';
        file_put_contents($build . '/index.html', $page(''));
        file_put_contents($build . '/' . $long . '/index.html', $page($long . '/'));
        file_put_contents($build . '/llms.txt', "# Docs\n");
        file_put_contents($build . '/_docara/app.js', str_repeat('x', 700));

        return [$root, $build];
    }

    /** @return array<string, array<string, mixed>> */
    private function tarEntries(string $tar): array
    {
        $entries = [];
        $offset = 0;
        while ($offset + 512 <= strlen($tar)) {
            $header = substr($tar, $offset, 512);
            if (trim($header, "\0") === '') {
                break;
            }
            $field = static fn (int $start, int $length): string => rtrim(substr($header, $start, $length), "\0 ");
            $checksum = array_sum(array_map('ord', str_split(substr_replace($header, str_repeat(' ', 8), 148, 8))));
            self::assertSame($checksum, octdec($field(148, 8)));
            self::assertSame("ustar\x0000", substr($header, 257, 8));
            $prefix = $field(345, 155);
            $name = ($prefix === '' ? '' : $prefix . '/') . $field(0, 100);
            $size = (int) octdec($field(124, 12));
            $entries[$name] = [
                'type' => $header[156],
                'mode' => (int) octdec($field(100, 8)),
                'uid' => (int) octdec($field(108, 8)),
                'gid' => (int) octdec($field(116, 8)),
                'mtime' => (int) octdec($field(136, 12)),
                'uname' => $field(265, 32),
                'gname' => $field(297, 32),
                'body' => substr($tar, $offset + 512, $size),
            ];
            $offset += 512 + (int) ceil($size / 512) * 512;
        }

        return $entries;
    }
}
