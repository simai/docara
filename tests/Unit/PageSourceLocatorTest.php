<?php

declare(strict_types=1);

namespace Tests\Unit;

use FilesystemIterator;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Simai\Docara\Content\PageSourceLocator;
use Simai\Docara\Content\RouteMapper;
use Simai\Docara\I18n\LocaleRegistry;
use Simai\Docara\Portable\PortableConfigurationException;

final class PageSourceLocatorTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = sys_get_temp_dir() . '/docara-page-source-' . bin2hex(random_bytes(8));
        mkdir($this->root . '/content/ru/guide', 0777, true);
    }

    protected function tearDown(): void
    {
        if (! is_dir($this->root)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->root, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($iterator as $item) {
            $item->isLink() || $item->isFile() ? unlink($item->getPathname()) : rmdir($item->getPathname());
        }
        rmdir($this->root);
    }

    public function test_it_returns_one_typed_source_per_route_in_stable_path_order(): void
    {
        file_put_contents($this->root . '/content/ru/guide/start.md', '# Start');
        file_put_contents($this->root . '/content/ru/index.md', '# Home');
        file_put_contents($this->root . '/content/ru/navigation.json', '{}');

        $sources = (new PageSourceLocator($this->root, $this->registry()))->forLocale('ru');

        self::assertSame(['content/ru/guide/start.md', 'content/ru/index.md'], array_column($sources, 'path'));
        self::assertSame(['guide/start', ''], array_column($sources, 'route'));
        self::assertSame(['ru', 'ru'], array_column($sources, 'locale'));
    }

    public function test_it_rejects_two_physical_sources_for_one_route(): void
    {
        file_put_contents($this->root . '/content/ru/guide.md', '# Guide');
        file_put_contents($this->root . '/content/ru/guide/index.md', '# Duplicate');

        $this->assertError(
            'PAGE_SOURCE_ROUTE_AMBIGUOUS',
            fn () => (new PageSourceLocator($this->root, $this->registry()))->forLocale('ru'),
        );
    }

    public function test_mapper_rejects_unknown_locale_outside_paths_parent_segments_and_old_extensions(): void
    {
        $mapper = new RouteMapper($this->registry());

        $this->assertError('LOCALE_NOT_CONFIGURED', fn () => $mapper->map('en', 'content/en/index.md'));
        $this->assertError('PAGE_SOURCE_OUTSIDE_LOCALE_ROOT', fn () => $mapper->map('ru', 'content/en/index.md'));
        $this->assertError('PAGE_SOURCE_PATH_INVALID', fn () => $mapper->map('ru', 'content/ru/../en/index.md'));
        $this->assertError('PAGE_SOURCE_EXTENSION_INVALID', fn () => $mapper->map('ru', 'content/ru/index.markdown'));
    }

    public function test_locator_rejects_a_legacy_markdown_extension_instead_of_silently_omitting_it(): void
    {
        file_put_contents($this->root . '/content/ru/legacy.markdown', '# Legacy');

        $this->assertError(
            'PAGE_SOURCE_EXTENSION_INVALID',
            fn () => (new PageSourceLocator($this->root, $this->registry()))->forLocale('ru'),
        );
    }

    private function registry(): LocaleRegistry
    {
        return LocaleRegistry::fromSite([
            'default_locale' => 'ru',
            'locales' => [
                'ru' => [
                    'label' => 'Русский',
                    'direction' => 'ltr',
                    'content_root' => 'content/ru',
                    'public_prefix' => 'ru',
                    'fallbacks' => [],
                ],
            ],
        ]);
    }

    private function assertError(string $code, callable $callback): void
    {
        try {
            $callback();
            self::fail("Expected [$code].");
        } catch (PortableConfigurationException $exception) {
            self::assertSame($code, $exception->errorCode);
        }
    }
}
