<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class FrameworkNativeSurfaceTest extends TestCase
{
    #[Test]
    public function locale_navigation_uses_an_icon_disclosure_instead_of_a_form_select(): void
    {
        $root = dirname(__DIR__, 2);
        $template = file_get_contents($root . '/resources/publisher/components/header-actions.php');
        $runtime = file_get_contents($root . '/resources/portable/declarative-shell.js');
        $css = file_get_contents($root . '/resources/portable/declarative-shell.css');

        self::assertIsString($template);
        self::assertIsString($runtime);
        self::assertIsString($css);
        self::assertStringContainsString('data-docara-language-menu', $template);
        self::assertStringContainsString('data-docara-language-trigger', $template);
        self::assertStringContainsString('<sf-icon icon="language"', $template);
        self::assertStringContainsString('data-docara-language-option', $template);
        self::assertStringContainsString('aria-current="page"', $template);
        self::assertStringNotContainsString('<select data-docara-language-switcher', $template);
        self::assertStringContainsString("event.key==='Escape'", $runtime);
        self::assertStringContainsString('.docara-language-menu__popup{position:absolute', $css);
        self::assertStringContainsString('[data-docara-block="features"]>li>.docara-icon{align-self:flex-start}', $css);
        self::assertStringContainsString("head.classList.add('docara-code-header')", $runtime);
        self::assertStringContainsString('title&&title.textContent!==languageLabel', $runtime);
        self::assertStringContainsString("button.classList.add('docara-code-copy','sf-icon-button'", $runtime);
        self::assertStringContainsString("codeIcon.classList.add('sf-icon-regular')", $runtime);
        self::assertStringContainsString("language||'code'", $runtime);
        self::assertStringContainsString(
            '.docara-code-header>span,.docara-code-block>.sf--highlight-head>span{display:inline-flex',
            $css,
        );
        self::assertStringNotContainsString('line-height:var(--sf-text-height-1);box-shadow:inset 0 -2px var(--sf-outline)', $css);
        self::assertStringContainsString('.docara-code-block.bg-surface-container{background:var(--sf-surface-0)}', $css);
        self::assertStringContainsString('.docara-code-scroll code{display:block;min-inline-size:max-content;white-space:pre;background:transparent}', $css);
        self::assertStringContainsString('.docara-step:not(:last-child)::after{content:""', $css);
    }

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
            '~\.(?:sf-button|sf-icon-button|sf-input|sf-radio-button|sf-breadcrumbs)(?=[\s.#:{>,])[^{}]*\{[^}]*(?:min-(?:inline|block)-size|(?:inline|block)-size|font-size|line-height|font-weight|padding)~',
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
    public function search_trigger_uses_static_framework_button_geometry(): void
    {
        $root = dirname(__DIR__, 2);
        $template = file_get_contents(
            $root . '/resources/publisher/components/header-actions.php',
        );

        self::assertIsString($template);
        self::assertStringContainsString('<button', $template);
        self::assertStringContainsString('type="button"', $template);
        self::assertStringContainsString('data-docara-search-trigger', $template);
        self::assertStringContainsString(
            'class="sf-button sf-button--size-1 sf-button--outline sf-button--on-surface flex items-cross-center docara-search-trigger h-d0"',
            $template,
        );
        self::assertStringContainsString(
            '<span class="sf-button-text-container text-center pointer-event-none"><?= $view->copy[\'search.label\'] ?></span>',
            $template,
        );
        self::assertStringContainsString(
            '<span class="sf-icon sf-icon-regular pointer-event-none" aria-hidden="true">search</span>',
            $template,
        );
        self::assertStringContainsString(
            'docara-search-shortcut text-1 color-on-surface-variant m-inline-start-1/2',
            $template,
        );
        self::assertStringNotContainsString('docara-search-shortcut label-small', $template);
        self::assertStringNotContainsString('docara-search-shortcut text-1 color-on-surface-variant border', $template);
        self::assertStringNotContainsString('<sf-button', $template);
        self::assertStringNotContainsString('items-center gap-1 radius-default', $template);
    }

    #[Test]
    public function modal_hosts_delegate_the_document_id_to_the_framework_generated_dialog(): void
    {
        $root = dirname(__DIR__, 2);
        foreach ([
            $root . '/resources/smart/docara.search/templates/default.php' => 'docara-search-dialog',
            $root . '/resources/smart/docara.preferences/templates/side-panel.php' => 'docara-reader-settings-dialog',
        ] as $path => $id) {
            $template = file_get_contents($path);
            self::assertIsString($template);
            self::assertStringContainsString('modal-id="' . $id . '"', $template);
            self::assertDoesNotMatchRegularExpression('/(?:^|\\s)id="' . preg_quote($id, '/') . '"/', $template);
        }
    }

    #[Test]
    public function framework_outline_buttons_keep_their_logical_side_borders(): void
    {
        $root = dirname(__DIR__, 2);
        $css = file_get_contents($root . '/resources/portable/declarative-shell.css');

        self::assertIsString($css);
        self::assertStringContainsString(
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

        self::assertMatchesRegularExpression(
            '~\[data-docara-disclosure\]\{[^}]*flex:0 0 auto;[^}]*\}~',
            $css,
        );
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
        self::assertStringContainsString(
            '.sf-menu-element:not(.disabled):hover{--sf-menu-element--background-color:var(--sf-surface-container-hover)',
            $css,
        );
        self::assertStringContainsString(
            ':hover [data-docara-disclosure] .sf-icon{--sf-icon--color:var(--sf-on-surface)}',
            $css,
        );
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
