<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Simai\Docara\Framework\FrameworkAssetPlanner;
use Simai\Docara\Framework\FrameworkLock;
use Simai\Docara\Framework\FrameworkManifestRepository;

final class FrameworkFoundationFontPreloadTest extends TestCase
{
    #[Test]
    public function a_russian_page_preloads_the_latin_and_cyrillic_faces_core_css_declares(): void
    {
        [$repository, $uiCommit] = $this->bundled();
        $faces = $this->facesFromStylesheet($repository->bundledRuntimeAsset('core/css/core.css'));

        $plan = (new FrameworkAssetPlanner($repository, '/_docara/framework-runtime'))->planForHtml(
            '<!doctype html><html lang="ru"><head></head><body><main><p>Текст</p></main></body></html>',
            [],
        );
        $preloads = $this->foundationPreloads($plan->assets);
        $base = '/_docara/vendor/simai-framework/runtime/' . $uiCommit . '/distr/';
        self::assertSame(['latin', 'cyrillic'], array_keys($preloads));
        self::assertSame($base . $faces['latin'], $preloads['latin']['url']);
        self::assertSame($base . $faces['cyrillic'], $preloads['cyrillic']['url']);
        foreach ($preloads as $script => $asset) {
            self::assertSame($repository->runtimeAssetRecord($faces[$script])['sha256'], $asset['sha256']);
            self::assertSame($uiCommit, $asset['source_revision']);
            // The href is the URL the stylesheet resolves url() to: no query,
            // so the browser reuses the preload for the face.
            self::assertStringNotContainsString('?', $asset['url']);
            self::assertStringContainsString(
                '<link rel="preload" as="font" type="font/woff2" crossorigin href="' . $asset['url'] . '"',
                $plan->headHtml(),
            );
        }
    }

    #[Test]
    public function an_english_page_preloads_only_the_latin_face(): void
    {
        [$repository] = $this->bundled();
        $plan = (new FrameworkAssetPlanner($repository, '/_docara/framework-runtime'))->planForHtml(
            '<!doctype html><html lang="en-GB"><head></head><body><main><p>Text</p></main></body></html>',
            [],
        );
        self::assertSame(['latin'], array_keys($this->foundationPreloads($plan->assets)));
    }

    #[Test]
    public function the_faces_are_read_from_the_stylesheet_and_a_stylesheet_without_faces_preloads_nothing(): void
    {
        $css = ':root{--sf-text--family: "Inter Variable", "Inter Fallback", system-ui}'
            . '@font-face{font-family:"Inter Variable";src:url(../aaaaaaaaaaaaaaaaaaaa.woff2) format("woff2");unicode-range:U+0100-02BA}'
            . '@font-face{font-family:"Inter Variable";src:url(../bbbbbbbbbbbbbbbbbbbb.woff2) format("woff2");unicode-range:U+0000-00FF, U+0131}'
            . '@font-face{font-family:"Other";src:url(../cccccccccccccccccccc.woff2) format("woff2");unicode-range:U+0400-045F}'
            . '@font-face{font-family:"Inter Variable";src:url(../dddddddddddddddddddd.woff2) format("woff2");unicode-range:U+0460-052F}'
            . '@font-face{font-family:"Inter Variable";src:url(../eeeeeeeeeeeeeeeeeeee.woff2) format("woff2");unicode-range:U+0301, U+0400-045F}'
            . '@font-face{font-family:"Inter Fallback";src:local("Arial")}';

        self::assertSame(
            ['latin' => 'core/bbbbbbbbbbbbbbbbbbbb.woff2', 'cyrillic' => 'core/eeeeeeeeeeeeeeeeeeee.woff2'],
            FrameworkAssetPlanner::foundationFontPreloadPaths($css, 'core/css/core.css', true),
        );
        self::assertSame(
            ['latin' => 'core/bbbbbbbbbbbbbbbbbbbb.woff2'],
            FrameworkAssetPlanner::foundationFontPreloadPaths($css, 'core/css/core.css', false),
        );
        // An older pair's core.css names system families and declares no faces.
        self::assertSame([], FrameworkAssetPlanner::foundationFontPreloadPaths(
            ':root{--sf-text--family: system-ui, -apple-system, sans-serif}',
            'core/css/core.css',
            true,
        ));
        self::assertTrue(FrameworkAssetPlanner::pageUsesCyrillic('<html lang="ru">'));
        self::assertTrue(FrameworkAssetPlanner::pageUsesCyrillic("<html dir='ltr' lang='uk-UA'>"));
        self::assertFalse(FrameworkAssetPlanner::pageUsesCyrillic('<html lang="en">'));
        self::assertFalse(FrameworkAssetPlanner::pageUsesCyrillic('<html>'));
    }

    /** @return array{FrameworkManifestRepository, string} */
    private function bundled(): array
    {
        $lock = json_decode(
            (string) file_get_contents(dirname(__DIR__, 2) . '/docs/site/simai-framework.lock.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        return [
            FrameworkManifestRepository::bundled(FrameworkLock::fromArray($lock)),
            (string) $lock['runtime']['ui']['commit'],
        ];
    }

    /** @return array{latin: string, cyrillic: string} */
    private function facesFromStylesheet(string $css): array
    {
        // Independent reading of the stylesheet: the face whose range starts
        // with Basic Latin and the face that carries U+0400-045F.
        $faces = [];
        preg_match_all('/@font-face\s*\{([^}]*)\}/', $css, $blocks);
        foreach ($blocks[1] as $block) {
            if (preg_match('/url\(\.\.\/([a-f0-9]{20}\.woff2)\)/', $block, $url) !== 1) {
                continue;
            }
            if (preg_match('/unicode-range:\s*U\+0000-00FF/', $block) === 1) {
                $faces['latin'] = 'core/' . $url[1];
            }
            if (preg_match('/unicode-range:[^;]*U\+0400-045F/', $block) === 1) {
                $faces['cyrillic'] = 'core/' . $url[1];
            }
        }
        self::assertArrayHasKey('latin', $faces);
        self::assertArrayHasKey('cyrillic', $faces);

        return $faces;
    }

    /**
     * @param  list<array<string, mixed>>  $assets
     * @return array<string, array<string, mixed>>
     */
    private function foundationPreloads(array $assets): array
    {
        $preloads = [];
        foreach ($assets as $asset) {
            $key = (string) ($asset['key'] ?? '');
            if (str_starts_with($key, 'simai.framework.foundation.preload.')) {
                self::assertSame('font_preload', $asset['kind']);
                $preloads[substr($key, strlen('simai.framework.foundation.preload.'))] = $asset;
            }
        }

        return $preloads;
    }
}
