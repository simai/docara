<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class FrameworkNativeSurfaceTest extends TestCase
{
    #[Test]
    public function declarative_shell_does_not_override_framework_component_geometry_or_prose_typography(): void
    {
        $root = dirname(__DIR__, 2);
        $css = file_get_contents($root . '/resources/portable/declarative-shell.css');

        self::assertIsString($css);
        foreach ([
            'min-block-size:44px',
            'min-inline-size:44px',
            'block-size:44px',
            'inline-size:44px',
            'min-block-size:36px',
            '.docara-prose{line-height:',
            '.docara-prose h1{font-size:',
            '.docara-prose h2{font-size:',
            '.docara-prose h3{font-size:',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $css);
        }

        self::assertDoesNotMatchRegularExpression(
            '~\.(?:sf-button|sf-icon-button|sf-input|sf-radio-button|sf-breadcrumbs|sf--highlight-head)[^{]*\{[^}]*(?:min-(?:inline|block)-size|(?:inline|block)-size|font-size|line-height|font-weight|padding)~',
            $css,
        );
    }

    #[Test]
    public function canonical_component_markup_does_not_duplicate_framework_button_presentation(): void
    {
        $root = dirname(__DIR__, 2);
        $sources = [
            $root . '/src/PortableSite/PortableMarkdownRenderer.php',
            $root . '/resources/previews/templates/index.php',
            $root . '/resources/previews/templates/page.php',
            $root . '/resources/demonstrator/templates/index.php',
            $root . '/resources/demonstrator/templates/detail.php',
        ];

        foreach ($sources as $source) {
            $markup = file_get_contents($source);
            self::assertIsString($markup);
            self::assertStringNotContainsString(
                'bg-primary color-on-primary p-1/2 line-none',
                $markup,
                $source,
            );
        }
    }
}
