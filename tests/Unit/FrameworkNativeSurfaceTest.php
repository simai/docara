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

    #[Test]
    public function search_trigger_uses_the_framework_smart_button_contract(): void
    {
        $root = dirname(__DIR__, 2);
        $template = file_get_contents(
            $root . '/resources/publisher/components/header-actions.php',
        );

        self::assertIsString($template);
        self::assertStringContainsString('<sf-button', $template);
        self::assertStringContainsString('size="1"', $template);
        self::assertStringContainsString('type="outline"', $template);
        self::assertStringContainsString('scheme="on-surface"', $template);
        self::assertStringContainsString('icon-left="search"', $template);
        self::assertStringContainsString('slot="icon-right"', $template);
        self::assertStringContainsString(
            'sf-kbd docara-search-shortcut hidden sm:inline-flex color-on-surface-variant m-inline-start-1/2',
            $template,
        );
        self::assertStringNotContainsString('docara-search-shortcut label-small', $template);
        self::assertStringNotContainsString('docara-search-shortcut text-1 color-on-surface-variant border', $template);
        self::assertStringNotContainsString('<button type="button" data-docara-search-trigger', $template);
        self::assertStringNotContainsString('items-center gap-1 radius-default', $template);
    }

    #[Test]
    public function framework_outline_buttons_need_no_docara_border_patch(): void
    {
        $root = dirname(__DIR__, 2);
        $css = file_get_contents($root . '/resources/portable/declarative-shell.css');

        self::assertIsString($css);
        self::assertStringNotContainsString(
            '.sf-button.sf-button--outline{border-inline-start-width:var(--sf-button--border-inline-start-width,1px);border-inline-end-width:var(--sf-button--border-inline-end-width,1px)}',
            $css,
        );
        self::assertStringNotContainsString('.docara-cta-link{border-', $css);
        self::assertStringNotContainsString('.docara-search-trigger{border-', $css);
        self::assertStringContainsString(
            '.docara-search-trigger{--sf-button--border-color:var(--sf-outline-variant)}',
            $css,
        );
    }

    #[Test]
    public function navigation_disclosure_uses_framework_icon_button_geometry(): void
    {
        $root = dirname(__DIR__, 2);
        $css = file_get_contents($root . '/resources/smart/assets/navigation.css');
        $template = file_get_contents(
            $root . '/resources/smart/docara.navigation/templates/item.php',
        );

        self::assertIsString($css);
        self::assertIsString($template);
        self::assertStringContainsString('sf-icon-button--size-1/3', $template);

        self::assertStringContainsString('order-first flex-none', $template);
        self::assertDoesNotMatchRegularExpression(
            '~\[data-docara-disclosure\]\{[^}]*(?:min-(?:inline|block)-size|margin-(?:inline|block)|flex:0 0 var\()~',
            $css,
        );
    }

    #[Test]
    public function navigation_projects_coherent_hover_and_input_specific_focus_states(): void
    {
        $root = dirname(__DIR__, 2);
        $css = file_get_contents($root . '/resources/smart/assets/navigation.css');

        self::assertIsString($css);
        self::assertStringNotContainsString('.sf-menu-element:not(.disabled):hover', $css);
        self::assertStringNotContainsString(':hover [data-docara-disclosure] .sf-icon', $css);
        self::assertStringContainsString(
            '[data-docara-disclosure]:focus{box-shadow:none}',
            $css,
        );
        self::assertStringContainsString(
            '[data-docara-disclosure]:focus-visible{outline:var(--sf-focus-outline-width,var(--sf-a4)) solid',
            $css,
        );
    }
}
