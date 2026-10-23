<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Simai\Docara\Document\IconNameCatalog;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\PortableSite\PortableMarkdownRenderer;
use Tests\Support\IconNameReference;

final class IconNameCatalogTest extends TestCase
{
    #[Test]
    public function the_catalogue_names_every_icon_of_the_bundled_index(): void
    {
        $root = dirname(__DIR__, 2);
        $index = json_decode((string) file_get_contents($root . '/resources/portable/' . IconNameCatalog::INDEX), true, 512, JSON_THROW_ON_ERROR);
        $names = IconNameCatalog::bundled()->names();

        self::assertCount($index['icons']['count'], $names);
        self::assertSame($index['icons']['sha256'], hash('sha256', implode("\n", $names)));
        self::assertTrue(IconNameCatalog::bundled()->contains('settings'));
        self::assertFalse(IconNameCatalog::bundled()->contains('setings'));
        foreach ($index['instances'] as $instance) {
            $manifest = json_decode((string) file_get_contents(dirname($root . '/resources/portable/' . IconNameCatalog::INDEX) . '/' . $instance['manifest']['path']), true, 512, JSON_THROW_ON_ERROR);
            self::assertSame($names, $manifest['icons'], $instance['manifest']['path']);
        }
    }

    #[Test]
    public function an_index_whose_names_do_not_match_is_refused(): void
    {
        $directory = sys_get_temp_dir() . '/docara-icon-catalog-' . bin2hex(random_bytes(4));
        mkdir($directory);
        try {
            $manifest = json_encode(['icons' => ['add', 'home']], JSON_THROW_ON_ERROR);
            file_put_contents($directory . '/m.manifest.json', $manifest);
            file_put_contents($directory . '/icon-catalog.json', json_encode([
                'schema' => 'docara.icon_catalog.v1',
                'icons' => ['count' => 2, 'sha256' => hash('sha256', "add\nsearch")],
                'instances' => [['manifest' => ['path' => 'm.manifest.json', 'sha256' => hash('sha256', $manifest)]]],
            ], JSON_THROW_ON_ERROR));

            $this->expectException(PortableConfigurationException::class);
            $this->expectExceptionMessage('do not match');
            IconNameCatalog::fromIndex($directory . '/icon-catalog.json');
        } finally {
            array_map('unlink', glob($directory . '/*') ?: []);
            rmdir($directory);
        }
    }

    #[Test]
    public function an_icon_name_outside_the_catalogue_stops_the_build(): void
    {
        self::assertStringContainsString('>settings</i>', (new PortableMarkdownRenderer)->render(':icon[settings]'));

        try {
            (new PortableMarkdownRenderer)->render('Текст :icon[setings]{label="Настройки"}');
            self::fail('An unknown icon name rendered.');
        } catch (PortableConfigurationException $exception) {
            self::assertSame('CONTENT_COMPONENT_ICON_UNKNOWN', $exception->errorCode);
            self::assertStringContainsString('[setings]', $exception->getMessage());
        }
    }

    #[Test]
    public function the_icon_page_lists_every_catalogue_name(): void
    {
        $reference = new IconNameReference(dirname(__DIR__, 2));
        $page = $reference->page();

        self::assertSame(
            $reference->withList($page),
            $page,
            'Run php scripts/capture-icon-names.php after regenerating the icon catalogue.',
        );
        foreach (['settings', 'check_circle', 'zoom_out_map'] as $name) {
            self::assertStringContainsString('`' . $name . '`', $page);
        }
        $start = (int) strpos($page, IconNameReference::START);
        $list = substr($page, $start, (int) strpos($page, IconNameReference::END) - $start);
        self::assertSame(count($reference->names()), intdiv(substr_count($list, '`'), 2));
    }
}
