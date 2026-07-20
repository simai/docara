<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Simai\Docara\Declarative\Preview\DeclarativePreviewLinkProjector;
use Simai\Docara\Declarative\Preview\DeclarativePreviewRenderer;
use Simai\Docara\Declarative\Preview\DeclarativePreviewRouteMap;
use Simai\Docara\Framework\FrameworkAssetPlan;
use Simai\Docara\Portable\PortableConfigurationException;

final class DeclarativePreviewTest extends TestCase
{
    public function test_it_projects_known_internal_links_and_renders_browsable_preview_documents(): void
    {
        $routes = DeclarativePreviewRouteMap::fromPages($this->pages());
        self::assertSame('/_docara/declarative-preview/', $routes->indexUrl);
        self::assertSame(
            '/_docara/declarative-preview/pages/guide/',
            $routes->previewUrl('/guide/'),
        );
        self::assertNull($routes->previewUrl('/unsupported/'));

        $projected = (new DeclarativePreviewLinkProjector)->project(
            '<article><a href="/">Home</a><a href="/guide/">Guide</a>'
            . '<a href="/unsupported/">Unsupported</a><a href="#section">Section</a></article>',
            $routes,
        );
        self::assertStringContainsString(
            'href="/_docara/declarative-preview/pages/" data-docara-original-href="/"',
            $projected,
        );
        self::assertStringContainsString(
            'href="/_docara/declarative-preview/pages/guide/" data-docara-original-href="/guide/"',
            $projected,
        );
        self::assertStringContainsString('href="/unsupported/"', $projected);
        self::assertStringContainsString('href="#section"', $projected);

        $renderer = new DeclarativePreviewRenderer;
        $assets = new FrameworkAssetPlan('pair', [[
            'key' => 'framework.css',
            'kind' => 'css',
            'url' => 'https://example.test/framework.css',
        ]]);
        $page = $renderer->page(
            'ru',
            'current',
            'Guide',
            '/guide/',
            $routes->indexUrl,
            $projected,
            $assets,
        );
        self::assertStringContainsString('<!doctype html>', $page);
        self::assertStringContainsString('data-docara-documentation-version="current"', $page);
        self::assertStringContainsString('Декларативный preview', $page);
        self::assertStringContainsString('href="/guide/">Открыть legacy</a>', $page);
        self::assertStringContainsString('href="https://example.test/framework.css"', $page);

        $index = $renderer->index(
            'ru',
            'current',
            'Docara',
            $routes->receiptUrl,
            [
                [
                    'title' => 'Home',
                    'legacy_url' => '/',
                    'preview_url' => $routes->previewUrl('/'),
                    'unsupported_components' => [],
                ],
                [
                    'title' => 'Unsupported',
                    'legacy_url' => '/unsupported/',
                    'preview_url' => null,
                    'unsupported_components' => ['ui.button'],
                ],
            ],
            $assets,
        );
        self::assertStringContainsString('Собрано: 1', $index);
        self::assertStringContainsString('Пропущено: 1', $index);
        self::assertStringContainsString('Не поддержано: ui.button', $index);
        self::assertStringContainsString($routes->receiptUrl, $index);
    }

    public function test_preview_route_map_rejects_unsafe_source_outputs(): void
    {
        $pages = $this->pages();
        $pages[0]['output'] = '../index.html';

        $this->expectException(PortableConfigurationException::class);
        $this->expectExceptionMessage('DECLARATIVE_PREVIEW_SOURCE_ROUTE_INVALID');

        DeclarativePreviewRouteMap::fromPages($pages);
    }

    /** @return list<array<string, mixed>> */
    private function pages(): array
    {
        return [
            [
                'home_url' => '/',
                'url' => '/',
                'output' => 'index.html',
                'declarative_supported' => true,
            ],
            [
                'home_url' => '/',
                'url' => '/guide/',
                'output' => 'guide/index.html',
                'declarative_supported' => true,
            ],
            [
                'home_url' => '/',
                'url' => '/unsupported/',
                'output' => 'unsupported/index.html',
                'declarative_supported' => false,
            ],
        ];
    }
}
