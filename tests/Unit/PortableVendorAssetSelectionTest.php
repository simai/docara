<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Simai\Docara\File\Filesystem;
use Simai\Docara\PortableSite\PortablePublisherAssetPublisher;
use Simai\Docara\PortableSite\PortableVendorAssetSelection;
use Simai\Docara\Smart\SmartRegistry;
use Tests\TestCase;

final class PortableVendorAssetSelectionTest extends TestCase
{
    private const PAGE = '<!doctype html><html><head>'
        // Docara's own inline icon assets name every family on every page.
        . '<style data-docara-framework-asset="simai.framework.icon_variant_fonts.css">'
        . '@font-face{font-family:"Material Symbols Rounded"}@font-face{font-family:"Material Symbols Sharp"}'
        . 'html body .sf-icon.sf-icon-rounded{}html body .sf-icon.sf-icon-shape{}</style>'
        . '<script data-docara-framework-asset="simai.framework.icon_font.ready">'
        . 'if(icon.classList.contains("sf-icon-rounded")||icon.classList.contains("sf-icon-shape")){}</script>'
        . '</head><body><main>%s</main></body></html>';

    #[Test]
    public function a_site_without_diagrams_math_or_icon_families_gets_none_of_their_files(): void
    {
        $selection = PortableVendorAssetSelection::fromDocuments([
            sprintf(self::PAGE, '<h1>Hello</h1><i class="sf-icon">home</i>'),
            sprintf(self::PAGE, '<iframe data-docara-example-frame srcdoc="'
                . htmlspecialchars('<i class="sf-icon">info</i><script data-docara-example-resize>'
                    . 'root.querySelector(".sf-icon-rounded");root.querySelector(".sf-icon-shape")</script>', ENT_QUOTES)
                . '"></iframe>'),
        ]);
        foreach (PortableVendorAssetSelection::FEATURES as $feature) {
            self::assertFalse($selection->has($feature), $feature);
        }

        $build = $this->tmpPath('plain');
        $this->publish($build, $selection);
        $vendor = $build . '/_docara/vendor';
        self::assertDirectoryDoesNotExist($vendor . '/mermaid');
        self::assertDirectoryDoesNotExist($vendor . '/katex');
        self::assertDirectoryDoesNotExist($vendor . '/licenses', 'a licence travels with its library');
        $icons = $this->iconDirectory($vendor);
        self::assertFileDoesNotExist($icons . '/MaterialSymbolsRounded.woff2');
        self::assertFileDoesNotExist($icons . '/MaterialSymbolsSharp.woff2');
        // The full Outlined face is the fallback for any icon outside the
        // subset, which Smart components may choose at run time: always shipped.
        self::assertFileExists($icons . '/MaterialSymbolsOutlined.woff2');
        self::assertFileExists($icons . '/LICENSE');
        self::assertNotSame([], glob($vendor . '/docara/icon-subset/*/*.woff2') ?: []);
        // The outlined catalogue serves any outlined name at any authored
        // weight and fill; the variant catalogues follow their families.
        self::assertCount(6, glob($vendor . '/docara/icon-catalog/*/material-symbols-outlined.*.woff2') ?: []);
        self::assertSame([], glob($vendor . '/docara/icon-catalog/*/material-symbols-rounded.*') ?: []);
        self::assertSame([], glob($vendor . '/docara/icon-catalog/*/material-symbols-sharp.*') ?: []);
        self::assertNotSame([], glob($vendor . '/docara/icon-catalog/*/icon-catalog.json') ?: []);
        self::assertSame([], glob($vendor . '/simai-framework/runtime/*/distr/core/css/utility.full.css') ?: []);
        self::assertNotSame([], glob($vendor . '/simai-framework/runtime/*/distr/core/css/core.css') ?: []);
    }

    #[Test]
    public function a_site_with_a_diagram_and_math_gets_mermaid_and_katex_with_their_licences(): void
    {
        $selection = PortableVendorAssetSelection::fromDocuments([
            sprintf(self::PAGE, '<p>plain</p>'),
            str_replace(
                '</head>',
                '<script src="/docs/_docara/vendor/mermaid/mermaid.min.js?docara_v=1"></script>'
                    . '<link rel="stylesheet" href="/docs/_docara/vendor/katex/katex.min.css?docara_v=2">'
                    . '<script src="/docs/_docara/vendor/katex/katex.min.js?docara_v=3"></script></head>',
                sprintf(self::PAGE, '<div data-docara-block="diagram"></div><div data-docara-block="math"></div>'),
            ),
        ]);
        self::assertTrue($selection->has('diagram'));
        self::assertTrue($selection->has('math'));
        self::assertFalse($selection->has('icons_rounded'));

        $build = $this->tmpPath('features');
        $this->publish($build, $selection);
        $vendor = $build . '/_docara/vendor';
        $portable = dirname(__DIR__, 2) . '/resources/portable/vendor';
        foreach (['mermaid/mermaid.min.js', 'katex/katex.min.js', 'katex/katex.min.css', 'katex/fonts/KaTeX_Main-Regular.woff2', 'licenses/mermaid-LICENSE', 'licenses/katex-LICENSE'] as $file) {
            self::assertFileEquals($portable . '/' . $file, $vendor . '/' . $file);
        }

        // Only diagrams: KaTeX and its licence stay out.
        $diagramOnly = PortableVendorAssetSelection::fromDocuments([
            '<script src="/_docara/vendor/mermaid/mermaid.min.js?docara_v=1"></script>',
        ]);
        $build = $this->tmpPath('diagram-only');
        $this->publish($build, $diagramOnly);
        self::assertFileExists($build . '/_docara/vendor/mermaid/mermaid.min.js');
        self::assertFileExists($build . '/_docara/vendor/licenses/mermaid-LICENSE');
        self::assertDirectoryDoesNotExist($build . '/_docara/vendor/katex');
        self::assertFileDoesNotExist($build . '/_docara/vendor/licenses/katex-LICENSE');
    }

    #[Test]
    public function an_icon_family_face_ships_only_when_an_author_asks_for_that_family(): void
    {
        $rounded = PortableVendorAssetSelection::fromDocuments([
            sprintf(self::PAGE, '<i class="sf-icon sf-icon-loaded sf-icon-rounded">settings</i>'),
        ]);
        self::assertTrue($rounded->has('icons_rounded'));
        self::assertFalse($rounded->has('icons_sharp'));

        // A Smart icon inside an example frame picks its family by attribute.
        $sharp = PortableVendorAssetSelection::fromDocuments([
            sprintf(self::PAGE, '<iframe data-docara-example-frame srcdoc="'
                . htmlspecialchars('<sf-icon icon="home" type="sharp"></sf-icon>', ENT_QUOTES) . '"></iframe>'),
        ]);
        self::assertTrue($sharp->has('icons_sharp'));
        self::assertFalse($sharp->has('icons_rounded'));

        // An example's own script, and the site's own stylesheet, count.
        self::assertTrue(PortableVendorAssetSelection::fromDocuments([
            sprintf(self::PAGE, '<iframe data-docara-example-frame srcdoc="'
                . htmlspecialchars('<script>icon.classList.add("sf-icon--rounded")</script>', ENT_QUOTES) . '"></iframe>'),
        ])->has('icons_rounded'));
        self::assertTrue(PortableVendorAssetSelection::fromDocuments(
            [sprintf(self::PAGE, '')],
            ['.brand-mark{font-family:"Material Symbols Sharp"}'],
        )->has('icons_sharp'));

        $build = $this->tmpPath('rounded');
        $this->publish($build, $rounded);
        $icons = $this->iconDirectory($build . '/_docara/vendor');
        self::assertFileEquals(
            $this->iconDirectory(dirname(__DIR__, 2) . '/resources/portable/vendor') . '/MaterialSymbolsRounded.woff2',
            $icons . '/MaterialSymbolsRounded.woff2',
        );
        self::assertFileDoesNotExist($icons . '/MaterialSymbolsSharp.woff2');
        self::assertCount(6, glob($build . '/_docara/vendor/docara/icon-catalog/*/material-symbols-rounded.*.woff2') ?: []);
        self::assertCount(6, glob($build . '/_docara/vendor/docara/icon-catalog/*/material-symbols-rounded.*.manifest.json') ?: []);
        self::assertSame([], glob($build . '/_docara/vendor/docara/icon-catalog/*/material-symbols-sharp.*') ?: []);
    }

    #[Test]
    public function framework_components_choose_an_icon_family_only_through_markup(): void
    {
        // The selection reads families from markup. That holds while no
        // bundled Framework script adds a family class on its own: the Loader
        // only maps the classes, and the icon component turns its `type`
        // attribute into one. A repin that changes this must change the
        // selection too.
        $resources = dirname(__DIR__, 2) . '/resources';
        $offenders = [];
        foreach ([$resources . '/framework', $resources . '/portable/vendor/simai-framework'] as $root) {
            $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));
            foreach ($files as $file) {
                if (! $file->isFile() || $file->getExtension() !== 'js') {
                    continue;
                }
                $source = (string) file_get_contents($file->getPathname());
                if (preg_match('/sf-icon-rounded|sf-icon--rounded|sf-icon-shape|sf-icon--sharp/', $source) !== 1) {
                    continue;
                }
                $name = basename($file->getPathname());
                if ($name === 'core-loader.js'
                    || ($name === 'icons.js' && str_contains($source, 'this.getProp("type")'))
                ) {
                    continue;
                }
                $offenders[] = substr($file->getPathname(), strlen($resources) + 1);
            }
        }
        self::assertSame([], $offenders);
    }

    #[Test]
    public function the_full_utility_sheet_ships_only_for_a_page_that_links_it(): void
    {
        $runtime = glob(dirname(__DIR__, 2) . '/resources/portable/vendor/simai-framework/runtime/*', GLOB_ONLYDIR) ?: [];
        self::assertCount(1, $runtime);
        $revision = basename($runtime[0]);
        $relative = 'vendor/simai-framework/runtime/' . $revision . '/distr/core/css/utility.full.css';

        self::assertFalse(PortableVendorAssetSelection::fromDocuments([
            // A page may name the file in prose.
            sprintf(self::PAGE, '<p><code>utility.full.css</code> is not published.</p>'),
        ])->publishes($relative));

        $linked = PortableVendorAssetSelection::fromDocuments([
            '<link rel="stylesheet" href="/_docara/' . $relative . '?sf_v=x">',
        ]);
        self::assertTrue($linked->publishes($relative));
        self::assertFalse($linked->publishes(str_replace($revision, str_repeat('0', 40), $relative)));
    }

    #[Test]
    public function a_rebuild_removes_a_group_the_site_no_longer_uses(): void
    {
        $build = $this->tmpPath('incremental');
        $this->publish($build, PortableVendorAssetSelection::everything());
        self::assertFileExists($build . '/_docara/vendor/mermaid/mermaid.min.js');
        self::assertNotSame([], glob($build . '/_docara/vendor/simai-framework/runtime/*/distr/core/css/utility.full.css') ?: []);

        $this->publish($build, PortableVendorAssetSelection::fromDocuments([sprintf(self::PAGE, '')]));
        self::assertDirectoryDoesNotExist($build . '/_docara/vendor/mermaid');
        self::assertDirectoryDoesNotExist($build . '/_docara/vendor/katex');
        self::assertDirectoryDoesNotExist($build . '/_docara/vendor/licenses');
        self::assertFileDoesNotExist($this->iconDirectory($build . '/_docara/vendor') . '/MaterialSymbolsRounded.woff2');
        self::assertSame([], glob($build . '/_docara/vendor/simai-framework/runtime/*/distr/core/css/utility.full.css') ?: []);
        self::assertNotSame([], glob($build . '/_docara/vendor/simai-framework/runtime/*/distr/core/css/core.css') ?: []);
    }

    #[Test]
    public function the_selection_of_a_build_directory_reads_its_pages_and_its_own_assets_only(): void
    {
        $build = $this->tmpPath('read');
        $this->filesystem->ensureDirectoryExists($build . '/guide/_docara');
        $this->filesystem->ensureDirectoryExists($build . '/_docara/vendor/mermaid');
        $this->filesystem->ensureDirectoryExists($build . '/.docara');
        file_put_contents($build . '/index.html', sprintf(self::PAGE, '<p>home</p>'));
        file_put_contents($build . '/guide/index.html', sprintf(self::PAGE, '<i class="sf-icon sf-icon-shape">x</i>'));
        // Docara's own published files and dot directories are not the site's.
        file_put_contents($build . '/_docara/declarative-shell.css', '.sf-icon-rounded{}');
        file_put_contents($build . '/_docara/vendor/mermaid/mermaid.min.js', 'vendor/katex/katex.min.css');
        file_put_contents($build . '/.docara/notes.html', '<script src="/_docara/vendor/mermaid/mermaid.min.js"></script>');
        // A nested folder that happens to be called _docara is the site's own.
        file_put_contents($build . '/guide/_docara/site.js', 'el.className="sf-icon sf-icon--rounded"');

        $selection = PortableVendorAssetSelection::fromBuild($build)->toArray();
        self::assertSame([
            'diagram' => false,
            'math' => false,
            'icons_rounded' => true,
            'icons_sharp' => true,
            'utility_full' => false,
        ], $selection['features']);
    }

    private function publish(string $build, PortableVendorAssetSelection $selection): void
    {
        (new PortablePublisherAssetPublisher(new Filesystem, SmartRegistry::bundled()))
            ->publish($build, [], null, $selection);
    }

    private function iconDirectory(string $vendor): string
    {
        $directories = glob($vendor . '/google/material-symbols/*', GLOB_ONLYDIR) ?: [];
        self::assertCount(1, $directories);

        return $directories[0];
    }
}
