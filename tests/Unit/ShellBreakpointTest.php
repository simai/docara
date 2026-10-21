<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Docara's own stylesheets and scripts state their width breakpoints in rem,
 * like the SIMAI Framework, so the layout follows the reader's browser text
 * size. The vendored Framework runtime is not Docara's and is not checked.
 */
final class ShellBreakpointTest extends TestCase
{
    #[Test]
    public function docaras_own_stylesheets_use_no_pixel_media_queries(): void
    {
        $files = $this->ownFiles('css');
        self::assertContains('resources/portable/declarative-shell.css', array_keys($files));
        foreach ($files as $path => $css) {
            preg_match_all('/@media[^{]*/', $css, $queries);
            foreach ($queries[0] as $query) {
                self::assertDoesNotMatchRegularExpression('/\d\s*px/', $query, $path . ': ' . $query);
            }
        }
    }

    #[Test]
    public function docaras_own_scripts_use_no_pixel_media_queries(): void
    {
        foreach ($this->ownFiles('js') as $path => $script) {
            preg_match_all('/matchMedia\(\s*([\'"])(.*?)\1/', $script, $queries);
            foreach ($queries[2] as $query) {
                self::assertDoesNotMatchRegularExpression('/\d\s*px/', $query, $path . ': ' . $query);
            }
        }
    }

    #[Test]
    public function the_shell_script_closes_sheets_at_the_same_widths_the_stylesheet_hides_them(): void
    {
        $root = dirname(__DIR__, 2);
        $css = (string) file_get_contents($root . '/resources/portable/declarative-shell.css');
        $js = (string) file_get_contents($root . '/resources/portable/declarative-shell.js');

        self::assertStringContainsString("@media(max-width:50rem){\n  .docara-mobile-navigation-trigger{display:inline-flex}", $css);
        self::assertStringContainsString("window.matchMedia('not all and (max-width: 50rem)')", $js);
        self::assertStringContainsString("@media(max-width:72rem){\n  .docara-header-navigation{display:none}", $css);
        self::assertStringContainsString("window.matchMedia('not all and (max-width: 72rem)')", $js);
    }

    /** @return array<string, string> */
    private function ownFiles(string $extension): array
    {
        $root = dirname(__DIR__, 2);
        $files = [];
        foreach (['resources', 'stubs'] as $directory) {
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root . '/' . $directory, \FilesystemIterator::SKIP_DOTS));
            foreach ($iterator as $file) {
                $relative = substr($file->getPathname(), strlen($root) + 1);
                if ($file->getExtension() !== $extension
                    || str_starts_with($relative, 'resources/framework/')
                    || str_contains($relative, '/vendor/')
                ) {
                    continue;
                }
                $files[$relative] = (string) file_get_contents($file->getPathname());
            }
        }
        ksort($files);

        return $files;
    }
}
