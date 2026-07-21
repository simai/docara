<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\Attributes\Test;
use Simai\Docara\Declarative\DeclarativePageResult;
use Simai\Docara\File\Filesystem;
use Simai\Docara\Framework\FrameworkAssetPlan;
use Simai\Docara\Framework\FrameworkComponentException;
use Simai\Docara\Framework\FrameworkComponentRuntime;
use Simai\Docara\Portable\CanonicalJson;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\PortableSite\LegacyPortablePagePublisher;
use Simai\Docara\PortableSite\PortableHtmlRenderer;
use Simai\Docara\PortableSite\PortableMarkdownRenderer;
use Simai\Docara\PortableSite\PortablePagePublisher;
use Simai\Docara\PortableSite\PortableSiteBuilder;
use Symfony\Component\Process\Process;

final class PortableSiteBuilderTest extends TestCase
{
    #[Test]
    public function it_builds_arbitrary_bcp47_locale_trees_with_rtl_switching_and_alternate_links(): void
    {
        $this->copyPortableFixture($this->tmp);
        rename($this->tmpPath('content'), $this->tmpPath('content-source'));
        foreach (['ru', 'en', 'ar', 'zh-Hans', 'fr-CA'] as $locale) {
            $this->filesystem->copyDirectory(
                $this->tmpPath('content-source'),
                $this->tmpPath('content/' . $locale),
            );
        }
        $this->filesystem->deleteDirectory($this->tmpPath('content-source'));

        $headings = [
            'ru' => '# Документация',
            'en' => '# Documentation',
            'ar' => '# التوثيق',
            'zh-Hans' => '# 文档',
            'fr-CA' => '# Documentation',
        ];
        foreach ($headings as $locale => $heading) {
            file_put_contents($this->tmpPath("content/$locale/index.md"), $heading . "\n\nLocale: $locale\n");
        }

        $site = $this->jsonFile($this->tmpPath('docara.json'));
        $site['content_root'] = 'content/ru';
        $site['locales'] = [
            'ru' => $this->localeDefinition('Русский', 'ltr', 'content/ru', '@docara/ru', '', []),
            'en' => $this->localeDefinition('English', 'ltr', 'content/en', '@docara/en', 'en', []),
            'ar' => $this->localeDefinition('العربية', 'rtl', 'content/ar', '@docara/ar', 'ar', ['en']),
            'zh-Hans' => $this->localeDefinition('简体中文', 'ltr', 'content/zh-Hans', '@docara/zh-Hans', 'zh-hans', ['en']),
            'fr-CA' => $this->localeDefinition('Français (Canada)', 'ltr', 'content/fr-CA', '@docara/fr-CA', 'fr-ca', ['en']),
        ];
        file_put_contents(
            $this->tmpPath('docara.json'),
            json_encode($site, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n",
        );

        $result = $this->builder()->build($this->tmp, $this->tmpPath('build_local'));

        self::assertGreaterThan(40, $result->count());
        foreach (['index.html', 'en/index.html', 'ar/index.html', 'zh-hans/index.html', 'fr-ca/index.html'] as $output) {
            self::assertFileExists($this->tmpPath('build_local/' . $output));
        }
        $russian = (string) file_get_contents($this->tmpPath('build_local/index.html'));
        $arabic = (string) file_get_contents($this->tmpPath('build_local/ar/index.html'));
        $arabicGuide = (string) file_get_contents($this->tmpPath('build_local/ar/guides/getting-started/index.html'));
        $chinese = (string) file_get_contents($this->tmpPath('build_local/zh-hans/index.html'));
        $chineseGuide = (string) file_get_contents($this->tmpPath('build_local/zh-hans/guides/getting-started/index.html'));
        self::assertStringContainsString('<html lang="ru" dir="ltr"', $russian);
        self::assertStringContainsString('<html lang="ar" dir="rtl"', $arabic);
        self::assertStringContainsString('aria-label="اللغة"', $arabic);
        self::assertStringContainsString('>إعدادات القراءة</h2>', $arabic);
        self::assertStringContainsString('data-docara-smart="docara.navigation"', $arabic);
        self::assertStringContainsString('aria-label="الأقسام"', $arabic);
        self::assertStringContainsString('data-docara-smart="docara.toc"', $arabicGuide);
        self::assertStringContainsString('aria-label="في هذه الصفحة"', $arabicGuide);
        self::assertStringContainsString('<html lang="zh-Hans" dir="ltr"', $chinese);
        self::assertStringContainsString('>阅读设置</h2>', $chinese);
        self::assertStringContainsString('aria-label="章节"', $chinese);
        self::assertStringContainsString('aria-label="本页内容"', $chineseGuide);
        foreach ([
            'hreflang="ru" href="/"',
            'hreflang="en" href="/en/"',
            'hreflang="ar" href="/ar/"',
            'hreflang="zh-Hans" href="/zh-hans/"',
            'hreflang="fr-CA" href="/fr-ca/"',
        ] as $alternate) {
            self::assertStringContainsString($alternate, $arabic);
        }
        self::assertStringContainsString('<option value="/ar/" lang="ar" selected>', $arabic);

        $search = $this->jsonFile($this->tmpPath('build_local/_docara/search-index.json'));
        self::assertSame(
            ['ar', 'en', 'fr-CA', 'ru', 'zh-Hans'],
            array_values(array_unique(array_column($search['documents'], 'locale'))),
        );

        $firstBuildHashes = $this->treeHashes($this->tmpPath('build_local'));
        $this->builder()->build($this->tmp, $this->tmpPath('build_local'));
        self::assertSame(
            $firstBuildHashes,
            $this->treeHashes($this->tmpPath('build_local')),
            'A repeated five-locale build must be byte-for-byte deterministic.',
        );

        $verification = new Process([
            PHP_BINARY,
            'scripts/verify-static-build.php',
            $this->tmpPath('build_local'),
        ], dirname(__DIR__));
        $verification->run();
        self::assertSame(
            0,
            $verification->getExitCode(),
            $verification->getErrorOutput() . $verification->getOutput(),
        );
    }

    #[Test]
    public function it_builds_docs_landing_inheritance_components_and_explainable_plans(): void
    {
        $this->copyPortableFixture($this->tmp);
        $section = $this->jsonFile($this->tmpPath('content/guides/section.json'));
        $section['navigation'] = ['$reset' => true];
        file_put_contents(
            $this->tmpPath('content/guides/section.json'),
            json_encode($section, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n",
        );
        $guidePage = $this->jsonFile($this->tmpPath('content/guides/getting-started.page.json'));
        $guidePage['navigation'] = ['order' => 10];
        file_put_contents(
            $this->tmpPath('content/guides/getting-started.page.json'),
            json_encode($guidePage, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n",
        );
        file_put_contents(
            $this->tmpPath('content/index.md'),
            file_get_contents($this->tmpPath('content/index.md')) . "\n<script id=\"unsafe\">alert(1)</script>\n",
        );
        file_put_contents(
            $this->tmpPath('content/guides/getting-started.md'),
            file_get_contents($this->tmpPath('content/guides/getting-started.md'))
            . "\n## Параметры\n\nОписание параметров.\n\n### Наследование\n\nОписание наследования.\n",
        );

        $result = $this->builder()->build($this->tmp, $this->tmpPath('build_local'));

        self::assertCount(20, $result);
        self::assertFileExists($this->tmpPath('build_local/index.html'));
        self::assertFileExists($this->tmpPath('build_local/guides/getting-started/index.html'));
        self::assertFileExists($this->tmpPath('build_local/guides/platform/configuration/layout/index.html'));
        self::assertFileExists($this->tmpPath('build_local/landing/index.html'));
        self::assertFileExists($this->tmpPath('build_local/_docara/component-catalog.json'));
        self::assertFileExists($this->tmpPath('build_local/.docara/component-catalog-pages.json'));
        self::assertFileExists($this->tmpPath('build_local/components/catalog/index.html'));
        self::assertFileExists($this->tmpPath('build_local/components/catalog/docara.columns/index.html'));
        self::assertFileExists($this->tmpPath('build_local/_docara/search-index.json'));
        self::assertFileExists($this->tmpPath('build_local/_docara/search.js'));
        self::assertFileExists($this->tmpPath('build_local/_docara/declarative-preview/index.html'));
        self::assertFileExists($this->tmpPath('build_local/_docara/declarative-preview/index.json'));
        self::assertFileExists($this->tmpPath('build_local/_docara/declarative-preview/pages/index.html'));
        self::assertFileExists(
            $this->tmpPath('build_local/_docara/declarative-preview/pages/guides/index.html'),
        );

        $index = (string) file_get_contents($this->tmpPath('build_local/index.html'));
        $guide = (string) file_get_contents($this->tmpPath('build_local/guides/getting-started/index.html'));
        $fourthLevel = (string) file_get_contents($this->tmpPath('build_local/guides/platform/configuration/layout/index.html'));
        $landing = (string) file_get_contents($this->tmpPath('build_local/landing/index.html'));
        $shellCss = (string) file_get_contents(
            $this->tmpPath('build_local/_docara/declarative-shell.css'),
        );
        $shellRuntime = (string) file_get_contents(
            $this->tmpPath('build_local/_docara/declarative-shell.js'),
        );
        $smartSurface = implode('', array_map(
            static fn (string $path): string => (string) file_get_contents($path),
            [
                $this->tmpPath('build_local/_docara/smart/brand.css'),
                $this->tmpPath('build_local/_docara/smart/navigation.css'),
                $this->tmpPath('build_local/_docara/smart/navigation.js'),
                $this->tmpPath('build_local/_docara/smart/toc.css'),
                $this->tmpPath('build_local/_docara/smart/toc.js'),
            ],
        ));

        self::assertStringContainsString('docara-docs-layout gap-0', $index);
        self::assertStringNotContainsString('docara-sidebar bg-surface-0 border radius-2', $index);
        self::assertStringNotContainsString('docara-content bg-surface-0 border radius-2', $index);
        self::assertStringNotContainsString('docara-outline-rail bg-surface-0 border radius-2', $guide);
        self::assertStringContainsString('class="docara-landing p-4"', $landing);
        self::assertStringContainsString(
            'class="docara-content docara-prose flex flex-col gap-2"',
            $landing,
        );
        self::assertStringNotContainsString(
            'docara-content docara-prose bg-surface-0 border radius-3',
            $landing,
        );
        self::assertStringContainsString(
            '<a data-docara-block="cta" class="docara-cta-link sf-button',
            $landing,
        );
        self::assertStringContainsString('href="/guides/getting-started/"', $landing);
        self::assertStringContainsString(
            'data-docara-block="features" class="docara-feature-grid grid grid-col-1 lg:grid-col-3',
            $landing,
        );
        self::assertStringContainsString(
            'bg-primary color-on-primary p-1/2 line-none',
            $landing,
        );
        self::assertStringContainsString(
            '.docara-feature-grid>li{min-width:0;max-width:none}',
            $shellCss,
        );
        self::assertStringContainsString('.docara-code-scroll{max-width:100%;background:transparent;', $shellCss);
        self::assertStringContainsString('.docara-code-scroll code{display:block;min-inline-size:max-content;white-space:pre}', $shellCss);
        self::assertStringContainsString(
            'href="/_docara/declarative-shell.css" data-docara-declarative-shell-style',
            $landing,
        );
        self::assertStringContainsString(
            'src="/_docara/declarative-shell.js" data-docara-shell-controller',
            $landing,
        );
        self::assertStringContainsString(
            '<code class="language-shell">php vendor/bin/docara init --portable' . "\n"
                . 'php vendor/bin/docara build local',
            $landing,
        );
        self::assertStringNotContainsString('composer require simai/docara', $landing);
        self::assertStringNotContainsString(
            "\n            php vendor/bin/docara",
            $landing,
        );
        self::assertStringContainsString('aria-current="page"', $index);
        self::assertStringContainsString('aria-current="page"', $guide);
        self::assertStringContainsString('data-docara-breadcrumbs', $guide);
        self::assertMatchesRegularExpression('~data-docara-breadcrumbs data-max-items="[3-9][0-9]*"~', $guide);
        self::assertStringContainsString('class="sf-breadcrumbs flex"', $guide);
        self::assertStringContainsString('sf-breadcrumbs-item--link', $guide);
        self::assertStringContainsString('sf-breadcrumbs-item--default', $guide);
        self::assertStringContainsString('aria-current="page"', $guide);
        self::assertStringContainsString('data-docara-outline', $guide);
        self::assertStringContainsString('data-docara-outline-mobile', $guide);
        self::assertStringContainsString('href="#параметры"', $guide);
        self::assertStringContainsString('<h2 id="параметры">Параметры</h2>', $guide);
        self::assertStringContainsString('<h3 id="наследование">Наследование</h3>', $guide);
        self::assertStringContainsString('data-docara-previous-next', $guide);
        self::assertStringContainsString('rel="prev" href="/guides/"', $guide);
        self::assertStringContainsString('rel="next" href="/guides/platform/"', $guide);
        self::assertStringNotContainsString('data-docara-breadcrumbs', $landing);
        self::assertStringNotContainsString('<nav data-docara-outline', $landing);
        self::assertStringNotContainsString('data-docara-outline-mobile', $landing);
        self::assertStringNotContainsString('<nav data-docara-previous-next', $landing);
        self::assertStringContainsString('<sf-alert', $index);
        self::assertStringContainsString('<sf-alert', $guide);
        self::assertStringContainsString('<sf-button', $guide);
        self::assertStringNotContainsString('<sf-button', $landing);
        self::assertStringNotContainsString('<script id="unsafe">', $index);
        self::assertStringNotContainsString('alert(1)', $index);
        foreach (['docara.smart.brand.css', 'docara.smart.navigation.css', 'docara.smart.navigation.js', 'docara.smart.toc.css', 'docara.smart.toc.js'] as $asset) {
            self::assertStringContainsString('data-docara-smart-asset="' . $asset . '"', $guide);
        }
        self::assertStringContainsString("new CustomEvent('docara-navigation-toggle'", $smartSurface);
        self::assertStringContainsString("new CustomEvent('docara-toc-navigate'", $smartSurface);

        foreach ([$index, $guide, $fourthLevel, $landing] as $html) {
            $surface = $html . $shellCss . $shellRuntime . $smartSurface;
            self::assertStringContainsString('theme-light', $html);
            self::assertStringContainsString('theme-dark', $html);
            self::assertStringContainsString('data-docara-documentation-version="current"', $html);
            self::assertStringContainsString('<meta name="docara:documentation-version" content="current">', $html);
            self::assertStringContainsString('href="#docara-main">К содержанию</a>', $html);
            self::assertStringContainsString('id="docara-main" tabindex="-1"', $html);
            self::assertStringContainsString('data-docara-reader-settings-trigger', $html);
            self::assertStringContainsString('aria-haspopup="dialog"', $html);
            self::assertStringContainsString('data-docara-reader-settings-dialog', $html);
            self::assertStringContainsString('data-docara-theme-option', $html);
            self::assertStringContainsString('value="system"', $html);
            self::assertStringContainsString('value="light"', $html);
            self::assertStringContainsString('value="dark"', $html);
            self::assertStringContainsString('data-docara-reader-settings-reset', $html);
            self::assertStringNotContainsString('class="sf-theme-button ', $html);
            self::assertStringContainsString('"theme":false', $html);
            self::assertStringContainsString('docara.framework.storage.compatibility', $html);
            self::assertStringContainsString('docaraFrameworkStorage', $html);
            self::assertTrue(
                strpos($html, 'docara.framework.storage.compatibility') < strpos($html, 'data-docara-theme-bootstrap'),
                'The volatile Framework storage guard must run before Docara reads the reader preference.',
            );
            self::assertStringContainsString('<sf-icon icon="tune" aria-hidden="true"></sf-icon>', $html);
            self::assertStringContainsString('data-docara-shell-controller', $html);
            self::assertStringContainsString('railRect.width<=0||railRect.height<=0', $surface);
            self::assertStringContainsString("addEventListener('resize',reveal", $surface);
            self::assertStringContainsString("customElements.whenDefined('sf-icon')", $surface);
            self::assertStringNotContainsString('data-docara-code-copy', $html);
            self::assertStringNotContainsString('navigator.clipboard.writeText(text)', $surface);
            self::assertStringNotContainsString("document.execCommand('copy')", $surface);
            self::assertStringContainsString('background:transparent;border:0;border-radius:0;box-shadow:none', $surface);
            self::assertStringContainsString('.docara-code-block>.sf--highlight-head button{min-inline-size:44px;min-block-size:44px}', $surface);
            self::assertStringContainsString('.docara-mobile-navigation-trigger{min-inline-size:44px;min-block-size:44px}', $surface);
            self::assertStringContainsString('.docara-outline-trigger{min-block-size:44px}', $surface);
            self::assertStringContainsString("dialog.addEventListener('cancel'", $surface);
            self::assertStringContainsString('if(event.target===dialog){closeSheet()}', $surface);
            self::assertStringContainsString('function trapDialogTab(dialog,event)', $surface);
            self::assertStringContainsString("settingsDialog.addEventListener('keydown',function(event){trapDialogTab(settingsDialog,event)})", $surface);
            self::assertStringContainsString('[data-docara-reader-settings-trigger]:focus-visible', $surface);
            self::assertStringContainsString('data-docara-reader-settings-close', $html);
            self::assertStringContainsString('[data-docara-reader-settings-close]:focus-visible', $surface);
            self::assertStringContainsString('[data-docara-component-details-summary]:focus-visible', $surface);
            self::assertStringContainsString('sf-button>button:focus-visible', $surface);
            self::assertStringContainsString('@7e836d8a9414d5da553fb1ab0404721e5b48769a/', $html);
            self::assertStringNotContainsString('simai/ui-smart@', $html);
            self::assertStringContainsString('window.sfSmartPath="/_docara/framework"', $html);
            self::assertStringContainsString('/distr/fonts/MaterialSymbols-Outlined.woff2', $html);
            self::assertDoesNotMatchRegularExpression('~@(?:main|master|latest)(?:/|$)~i', $html);
            self::assertStringContainsString('class="docara-brand-logo docara-brand-logo--light"', $html);
            self::assertStringContainsString('class="docara-brand-logo docara-brand-logo--light" src="', $html);
            self::assertStringContainsString('" alt="">', $html);
            self::assertStringNotContainsString('alt="Docara"', $html);
            self::assertStringContainsString('<link rel="icon" href="/_docara/brand/', $html);
        }
        foreach ([$index, $guide, $fourthLevel] as $html) {
            self::assertStringContainsString('data-docara-search-trigger', $html);
            self::assertStringContainsString('data-docara-search-dialog', $html);
            self::assertStringContainsString('data-docara-search-input', $html);
            self::assertStringContainsString('data-docara-search-status', $html);
            self::assertStringContainsString('data-docara-search-results', $html);
            self::assertStringContainsString('docara-search-trigger-label sf-button-text-container', $html);
            self::assertStringNotContainsString('class="sf-list docara-search-results', $html);
            self::assertStringContainsString('data-docara-search-runtime', $html);
            self::assertStringContainsString('/_docara/search-index.json?docara_v=', $html);
            self::assertStringContainsString('/_docara/search.js?docara_v=', $html);
            self::assertStringNotContainsString('algolia', strtolower($html));
            self::assertStringNotContainsString('typesense', strtolower($html));
        }
        self::assertStringNotContainsString('data-docara-search-trigger', $landing);
        self::assertStringNotContainsString('<dialog id="docara-search-dialog"', $landing);
        self::assertStringNotContainsString('data-docara-search-runtime', $landing);
        self::assertStringContainsString('id="docara-mobile-navigation"', $guide);
        self::assertStringContainsString('id="docara-mobile-navigation-title"', $guide);
        self::assertStringContainsString('id="docara-outline-dialog"', $guide);
        self::assertStringContainsString('data-docara-sheet-trigger', $guide);
        self::assertStringContainsString('data-docara-transient-dialog', $guide);
        self::assertStringContainsString("document.addEventListener('docara:open-transient'", $shellRuntime);
        self::assertStringContainsString('block-size:100dvh', $shellCss);
        self::assertStringNotContainsString('<details id="docara-mobile-navigation"', $guide);
        self::assertStringContainsString('data-docara-navigation-depth="4"', $fourthLevel);
        self::assertStringContainsString('<sf-icon icon="expand_less" aria-hidden="true"></sf-icon>', $fourthLevel);
        self::assertStringContainsString('href="/guides/platform/configuration/layout/" aria-current="page"', $fourthLevel);
        self::assertGreaterThanOrEqual(3, substr_count($fourthLevel, ' expanded aria-expanded="true"'));
        self::assertStringContainsString('data-docara-breadcrumbs data-max-items="5"', $fourthLevel);
        self::assertStringNotContainsString('data-sf-breadcrumbs-generated="ellipsis"', $fourthLevel);

        self::assertSame(3, substr_count($index, '<input data-docara-theme-option'));
        self::assertStringContainsString('docara.reader.theme.v1', $index);
        self::assertStringContainsString("matchMedia('(prefers-color-scheme: dark)')", $index);
        self::assertStringContainsString('localStorage.getItem', $index);
        self::assertStringContainsString('localStorage.setItem', $index);
        self::assertStringContainsString('localStorage.removeItem', $index);
        self::assertStringContainsString("frameworkMemory(){return document.documentElement.dataset.docaraFrameworkStorage==='memory'}", $index);
        self::assertStringContainsString('if(!frameworkMemory()){try{window.localStorage.setItem', $index);
        self::assertStringContainsString("volatile=''", $index);
        self::assertStringContainsString('persisted:persisted', $index);
        self::assertStringContainsString('syncExternal', $index);
        self::assertStringContainsString("message('reader.applied_not_saved')", $shellRuntime);
        self::assertStringNotContainsString('Браузер не разрешил сохранить выбор', $shellRuntime);
        self::assertStringContainsString('sf-theme=', $index);
        self::assertStringNotContainsString('<sf-modal', $index);
        self::assertStringNotContainsString('<sf-dropdown', $index);

        $guideDocument = new \DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $guideDocument->loadHTML($guide, LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $guideXpath = new \DOMXPath($guideDocument);
        self::assertSame(
            1,
            $guideXpath->query('//nav[@data-docara-breadcrumbs]//*[@aria-current="page"]')?->length,
        );
        $desktopOutline = [];
        foreach ($guideXpath->query('//aside[contains(@class, "docara-outline-rail")]//a[contains(@class, "docara-outline-link")]') ?: [] as $link) {
            $desktopOutline[] = $link->attributes?->getNamedItem('href')?->nodeValue;
        }
        $mobileOutline = [];
        foreach ($guideXpath->query('//dialog[@id="docara-outline-dialog"]//a[contains(@class, "docara-outline-link")]') ?: [] as $link) {
            $mobileOutline[] = $link->attributes?->getNamedItem('href')?->nodeValue;
        }
        self::assertNotSame([], $desktopOutline);
        self::assertSame($desktopOutline, $mobileOutline);

        $navigationDocument = new \DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $navigationDocument->loadHTML($fourthLevel, LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $navigationXpath = new \DOMXPath($navigationDocument);
        foreach (range(1, 4) as $depth) {
            self::assertGreaterThanOrEqual(
                1,
                $navigationXpath->query(
                    '//aside[contains(concat(" ", normalize-space(@class), " "), " docara-sidebar ")]'
                    . '//li[@data-docara-navigation-depth="' . $depth . '"]'
                    . '/div[contains(concat(" ", normalize-space(@class), " "), " sf-menu-element--level-'
                    . $depth . ' ")]',
                )?->length ?? 0,
                "Desktop navigation depth [$depth] must use the pinned Framework menu level class.",
            );
        }
        self::assertSame(
            1,
            $navigationXpath->query(
                '//aside[contains(concat(" ", normalize-space(@class), " "), " docara-sidebar ")]'
                . '//li[@data-docara-active-role="page"]//a[@aria-current="page"]',
            )?->length,
        );
        self::assertSame(
            1,
            $navigationXpath->query(
                '//aside[contains(concat(" ", normalize-space(@class), " "), " docara-sidebar ")]'
                . '//li[@data-docara-active-role="section"]',
            )?->length,
        );
        self::assertSame(
            2,
            $navigationXpath->query(
                '//aside[contains(concat(" ", normalize-space(@class), " "), " docara-sidebar ")]'
                . '//li[@data-docara-active-role="ancestor"]',
            )?->length,
        );
        self::assertSame(
            3,
            $navigationXpath->query(
                '//aside[contains(concat(" ", normalize-space(@class), " "), " docara-sidebar ")]'
                . '//*[@data-docara-disclosure][@data-docara-contains-current="true"]',
            )?->length,
        );

        $brandAssets = glob($this->tmpPath('build_local/_docara/brand/*')) ?: [];
        self::assertCount(1, $brandAssets, 'Identical logo, dark logo and favicon bytes must be deduplicated.');
        self::assertSame(
            hash_file('sha256', $this->tmpPath('assets/docara-mark.svg')),
            hash_file('sha256', $brandAssets[0]),
        );

        foreach ([
            'smart/alert/js/alert.js' => 'e994066dd2a7f9c4d15c573ea66bb47ccb0f12c24f4cf2e7dedee29eaddf9f1c',
            'smart/buttons/js/buttons.js' => 'fe977fc7c608b7bacb79b7641a302c30a6195659ac2351594ae5aef0656d0a27',
            'smart/icons/js/icons.js' => 'c810be681b51f98002e01fb8852e992e454fa607af005033f9cc10309016fa09',
        ] as $relativePath => $sha256) {
            $published = $this->tmpPath('build_local/_docara/framework/' . $relativePath);
            self::assertFileExists($published);
            self::assertSame($sha256, hash_file('sha256', $published));
        }

        $diagnosticPath = $this->tmpPath('build_local/.docara/resolved-page-plans.json');
        $diagnosticJson = (string) file_get_contents($diagnosticPath);
        self::assertMatchesRegularExpression('/"navigation":\s*\{\}/', $diagnosticJson);
        self::assertDoesNotMatchRegularExpression('/"navigation":\s*\[\]/', $diagnosticJson);
        $diagnostics = $this->jsonFile($diagnosticPath);
        self::assertSame('docara.resolved_page_plans.v1', $diagnostics['schema']);
        self::assertCount(20, $diagnostics['pages']);
        $indexPlan = collect($diagnostics['pages'])->firstWhere('output', 'index.html');
        self::assertIsArray($indexPlan);
        self::assertSame('rendered', $indexPlan['declarative_pipeline']['status']);
        self::assertSame('pass', $indexPlan['declarative_pipeline']['semantic_parity']['status']);
        self::assertSame('pass', $indexPlan['declarative_pipeline']['shell_structural_parity']['status']);
        self::assertSame(
            'larena.layout.resolved_render_plan.v1',
            $indexPlan['declarative_pipeline']['larena_contract']['schema'],
        );
        self::assertSame('pass', $indexPlan['declarative_pipeline']['larena_contract']['semantic_parity']);
        self::assertSame(
            ['footer', 'header', 'main', 'outline', 'sidebar'],
            array_keys($indexPlan['declarative_pipeline']['plan']['regions']),
        );
        self::assertCount(1, $indexPlan['declarative_pipeline']['plan']['regions']['header']);
        self::assertCount(1, $indexPlan['declarative_pipeline']['plan']['regions']['sidebar']);
        self::assertCount(1, $indexPlan['declarative_pipeline']['plan']['regions']['outline']);
        self::assertSame(
            'docara.navigation',
            $indexPlan['declarative_pipeline']['plan']['regions']['sidebar'][0]['blocks'][0]['smart']['smart'],
        );
        self::assertSame('rendered', $indexPlan['declarative_pipeline']['preview']['status']);
        self::assertSame(
            '/_docara/declarative-preview/pages/',
            $indexPlan['declarative_pipeline']['preview']['url'],
        );
        self::assertSame(
            'ui.alert',
            $indexPlan['declarative_pipeline']['semantic_parity']['declarative']['smart'][0]['smart'],
        );
        $guidePlan = collect($diagnostics['pages'])->firstWhere('output', 'guides/getting-started/index.html');
        self::assertIsArray($guidePlan);
        self::assertSame(1, $guidePlan['resolved_page_plan']['contract_version']);
        self::assertSame('docs', $guidePlan['resolved_page_plan']['configuration']['preset']);
        self::assertSame('wide', $guidePlan['resolved_page_plan']['configuration']['layout']['max_width']);
        self::assertSame(
            ['ui.alert', 'ui.button'],
            array_column($guidePlan['component_runtime']['normalized_calls'], 'id'),
        );
        self::assertSame(
            ['docara.component_call.v1', 'docara.component_call.v1'],
            array_column($guidePlan['component_runtime']['normalized_calls'], 'schema'),
        );
        self::assertSame('rendered', $guidePlan['declarative_pipeline']['status']);
        self::assertSame([], $guidePlan['declarative_unsupported_components'] ?? []);
        self::assertSame(
            ['ui.alert', 'ui.button'],
            array_column($guidePlan['declarative_pipeline']['semantic_parity']['declarative']['smart'], 'smart'),
        );
        $previewReceipt = $this->jsonFile(
            $this->tmpPath('build_local/_docara/declarative-preview/index.json'),
        );
        self::assertSame('docara.declarative_preview_receipt.v1', $previewReceipt['schema']);
        self::assertCount(7, $previewReceipt['pages']);
        self::assertCount(7, array_filter(
            $previewReceipt['pages'],
            static fn (array $page): bool => $page['status'] === 'rendered',
        ));
        self::assertSame(
            ['full_visual_parity' => false, 'primary_publisher_switched' => true, 'production_ready' => false],
            $previewReceipt['nonclaims'],
        );
        $previewHome = (string) file_get_contents(
            $this->tmpPath('build_local/_docara/declarative-preview/pages/index.html'),
        );
        self::assertStringContainsString('Декларативный предпросмотр', $previewHome);
        self::assertStringContainsString(
            'href="/_docara/declarative-preview/pages/guides/" data-docara-original-href="/guides/"',
            $previewHome,
        );
        self::assertTrue($guidePlan['resolved_page_plan']['configuration']['search']['enabled']);
        self::assertTrue($guidePlan['resolved_page_plan']['configuration']['search']['indexed']);
        self::assertSame(
            ['docara.json', 'simai-framework.lock.json', 'content/section.json', 'content/guides/section.json', 'content/guides/getting-started.page.json', 'content/guides/getting-started.md'],
            array_column($guidePlan['resolved_page_plan']['trace'], 'source'),
        );

        $searchIndex = $this->jsonFile($this->tmpPath('build_local/_docara/search-index.json'));
        self::assertSame('docara.search_index.v1', $searchIndex['schema']);
        self::assertSame('docara-prefix-v1', $searchIndex['algorithm']);
        self::assertCount(19, $searchIndex['documents']);
        self::assertNotContains('/landing/', array_column($searchIndex['documents'], 'url'));
        self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $searchIndex['content_sha256']);
        $searchText = implode(' ', array_column($searchIndex['documents'], 'text'));
        self::assertStringContainsString('Наследование работает', $searchText);
        self::assertStringContainsString('Параметры страницы дополняют настройки родительских разделов.', $searchText);
        self::assertStringNotContainsString('alert(1)', $searchText);
        self::assertSame(
            hash_file('sha256', dirname(__DIR__) . '/resources/portable/search.js'),
            hash_file('sha256', $this->tmpPath('build_local/_docara/search.js')),
        );
        $componentCatalog = $this->jsonFile($this->tmpPath('build_local/_docara/component-catalog.json'));
        self::assertSame('docara.effective_component_catalog.v1', $componentCatalog['schema']);
        self::assertSame('sf-v5.3.2-7e836d8a-dd786bba', $componentCatalog['framework_pair']);
        self::assertCount(17, $componentCatalog['entries']);
        self::assertEquals(
            [
                'catalog_is_canonical_framework_registry' => false,
                'all_framework_components_supported' => false,
                'production_ready' => false,
                'public_release_ready' => false,
            ],
            $componentCatalog['nonclaims'],
        );
        $searchRuntime = (string) file_get_contents($this->tmpPath('build_local/_docara/search.js'));
        self::assertStringContainsString(
            "document.dispatchEvent(new CustomEvent('docara:open-transient'",
            $searchRuntime,
        );
        self::assertStringContainsString('detail: { id: dialog.id }', $searchRuntime);
        self::assertTrue(
            strpos($searchRuntime, "new CustomEvent('docara:open-transient'") < strpos($searchRuntime, 'dialog.showModal()'),
            'Search must request shared transient-dialog exclusivity before it becomes modal.',
        );
        self::assertStringNotContainsString("var searchTrigger=document.querySelector('[data-docara-search-trigger]');", $index);
        self::assertStringNotContainsString(
            "searchTrigger.addEventListener('click',function(){if(settingsDialog.open){settingsDialog.close()}})",
            $index,
            'Search/settings mutual exclusion must have one shared owner in openSearch().',
        );
    }

    #[Test]
    public function two_builds_are_byte_for_byte_deterministic(): void
    {
        $this->copyPortableFixture($this->tmp);
        $builder = $this->builder();

        $builder->build($this->tmp, $this->tmpPath('build_local'));
        $first = $this->treeHashes($this->tmpPath('build_local'));
        $builder->build($this->tmp, $this->tmpPath('build_local'));

        self::assertSame($first, $this->treeHashes($this->tmpPath('build_local')));
    }

    #[Test]
    public function legacy_section_descriptor_name_fails_before_existing_output_is_cleaned(): void
    {
        $this->copyPortableFixture($this->tmp);
        rename(
            $this->tmpPath('content/guides/section.json'),
            $this->tmpPath('content/guides/_section.json'),
        );
        mkdir($this->tmpPath('build_local'), 0777, true);
        file_put_contents($this->tmpPath('build_local/keep.txt'), 'previous build');

        try {
            $this->builder()->build($this->tmp, $this->tmpPath('build_local'));
            self::fail('Legacy section descriptor name unexpectedly passed.');
        } catch (PortableConfigurationException $exception) {
            self::assertSame('SECTION_DESCRIPTOR_LEGACY_NAME', $exception->errorCode);
            self::assertStringContainsString(
                'Rename portable section descriptor [content/guides/_section.json] to [content/guides/section.json].',
                $exception->getMessage(),
            );
        }

        self::assertSame('previous build', file_get_contents($this->tmpPath('build_local/keep.txt')));
    }

    #[Test]
    public function reader_settings_keep_the_inherited_author_theme_as_the_reset_target(): void
    {
        $this->copyPortableFixture($this->tmp);
        $sectionPath = $this->tmpPath('content/guides/section.json');
        $section = $this->jsonFile($sectionPath);
        $section['settings'] = ['theme' => 'dark'];
        file_put_contents(
            $sectionPath,
            json_encode($section, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n",
        );

        $this->builder()->build($this->tmp, $this->tmpPath('build_local'));

        $html = (string) file_get_contents($this->tmpPath('build_local/guides/getting-started/index.html'));
        self::assertStringContainsString('data-configured-theme="dark"', $html);
        self::assertStringContainsString('var configured="dark"', $html);
        self::assertMatchesRegularExpression(
            '~data-docara-theme-option name="docara-reader-theme" type="radio" value="dark" checked~',
            $html,
        );
    }

    #[Test]
    public function canonical_starter_build_with_default_locale_passes_static_verification(): void
    {
        $this->copyPortableFixture($this->tmp, legacyCompatibility: false);
        file_put_contents(
            $this->tmpPath('content/ru/index.md'),
            file_get_contents($this->tmpPath('content/ru/index.md')) . "\n## Docara main\n",
        );
        $this->builder()->build($this->tmp, $this->tmpPath('build_production'));

        self::assertFileExists($this->tmpPath('build_production/index.html'));
        self::assertStringContainsString(
            '<link rel="canonical" href="/ru/">',
            (string) file_get_contents($this->tmpPath('build_production/index.html')),
        );
        self::assertStringContainsString(
            'id="docara-main-1"',
            (string) file_get_contents($this->tmpPath('build_production/ru/index.html')),
        );
        self::assertStringContainsString(
            '<link rel="alternate" hreflang="x-default" href="/">',
            (string) file_get_contents($this->tmpPath('build_production/ru/index.html')),
        );

        $process = new Process([
            PHP_BINARY,
            'scripts/verify-static-build.php',
            $this->tmpPath('build_production'),
        ], dirname(__DIR__));
        $process->run();

        self::assertSame(0, $process->getExitCode(), $process->getErrorOutput() . $process->getOutput());
        self::assertStringContainsString('"broken": []', $process->getOutput());
    }

    #[Test]
    public function static_verification_rejects_a_tampered_declarative_preview_receipt(): void
    {
        $this->copyPortableFixture($this->tmp);
        $this->builder()->build($this->tmp, $this->tmpPath('build_production'));
        $receiptPath = $this->tmpPath(
            'build_production/_docara/declarative-preview/index.json',
        );
        $receipt = $this->jsonFile($receiptPath);
        $receipt['index']['html_sha256'] = str_repeat('0', 64);
        file_put_contents(
            $receiptPath,
            CanonicalJson::encodePretty($receipt),
        );

        $process = new Process([
            PHP_BINARY,
            'scripts/verify-static-build.php',
            $this->tmpPath('build_production'),
        ], dirname(__DIR__));
        $process->run();

        self::assertSame(1, $process->getExitCode());
        self::assertStringContainsString(
            '"reference": "@declarative-preview"',
            $process->getOutput(),
        );
        self::assertStringContainsString(
            'is missing, unsafe or has a wrong hash',
            $process->getOutput(),
        );
    }

    #[Test]
    public function page_can_hide_all_reading_surfaces_without_losing_stable_heading_anchors(): void
    {
        $this->copyPortableFixture($this->tmp);
        $page = $this->jsonFile($this->tmpPath('content/guides/getting-started.page.json'));
        $page['reading'] = [
            'breadcrumbs' => false,
            'toc' => false,
            'previous_next' => false,
        ];
        file_put_contents(
            $this->tmpPath('content/guides/getting-started.page.json'),
            json_encode($page, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n",
        );
        file_put_contents(
            $this->tmpPath('content/guides/getting-started.md'),
            "# Быстрый старт\n\n## Параметры\n\nТекст.\n",
        );

        $this->builder()->build($this->tmp, $this->tmpPath('build_local'));
        $html = (string) file_get_contents($this->tmpPath('build_local/guides/getting-started/index.html'));

        self::assertStringNotContainsString('<nav data-docara-breadcrumbs', $html);
        self::assertStringNotContainsString('<nav data-docara-outline', $html);
        self::assertStringNotContainsString('data-docara-outline-mobile', $html);
        self::assertStringNotContainsString('<nav data-docara-previous-next', $html);
        self::assertStringContainsString('<h1 id="быстрый-старт">Быстрый старт</h1>', $html);
        self::assertStringContainsString('<h2 id="параметры">Параметры</h2>', $html);
    }

    #[Test]
    public function disabled_optional_regions_emit_no_empty_shell_or_mobile_navigation(): void
    {
        $this->copyPortableFixture($this->tmp);
        $pagePath = $this->tmpPath('content/guides/getting-started.page.json');
        $page = $this->jsonFile($pagePath);
        $page['layout'] = [
            'regions' => [
                'sidebar' => ['enabled' => false],
                'outline' => ['enabled' => false],
            ],
        ];
        file_put_contents(
            $pagePath,
            json_encode($page, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n",
        );

        $this->builder()->build($this->tmp, $this->tmpPath('build_local'));
        $html = (string) file_get_contents($this->tmpPath('build_local/guides/getting-started/index.html'));

        self::assertStringContainsString(
            '<div class="docara-docs-layout gap-0" data-sidebar="false" data-outline="false">',
            $html,
        );
        self::assertStringNotContainsString('data-docara-region="sidebar"', $html);
        self::assertStringNotContainsString('docara-mobile-navigation-trigger', $html);
        self::assertStringNotContainsString('id="docara-mobile-navigation"', $html);
        self::assertStringNotContainsString('data-docara-outline-mobile', $html);
        self::assertStringContainsString('data-docara-region="main"', $html);
    }

    #[Test]
    public function declarative_examples_publish_live_results_and_exact_sources_through_primary_pipeline(): void
    {
        $this->copyPortableFixture($this->tmp);
        $this->installDeclarativeExampleFixture($this->tmp);

        $this->builder()->build($this->tmp, $this->tmpPath('build_local'));

        self::assertFileExists($this->tmpPath('build_local/examples/index.html'));
        self::assertFileExists($this->tmpPath('build_local/examples/smart-button/index.html'));
        self::assertFileExists($this->tmpPath('build_local/example-results/button/index.html'));
        self::assertFileExists($this->tmpPath('build_local/_docara/declarative-examples.json'));
        self::assertFileExists($this->tmpPath('build_local/.docara/declarative-example-pages.json'));

        $index = (string) file_get_contents($this->tmpPath('build_local/examples/index.html'));
        $detail = (string) file_get_contents($this->tmpPath('build_local/examples/smart-button/index.html'));
        $result = (string) file_get_contents($this->tmpPath('build_local/example-results/button/index.html'));
        self::assertStringContainsString('href="/examples/" aria-current="page"', $index);
        self::assertStringContainsString('data-docara-demonstrator-detail', $detail);
        self::assertStringContainsString('src="/example-results/button/"', $detail);
        self::assertStringNotContainsString(' sandbox=', $detail);
        self::assertStringContainsString('# Кнопка', $detail);
        self::assertStringContainsString('{"text":"Продолжить","preset":"primary"}', $detail);
        self::assertStringContainsString('<sf-button', $result);

        $receipt = $this->jsonFile($this->tmpPath('build_local/_docara/declarative-examples.json'));
        self::assertSame('docara.declarative_examples.v1', $receipt['schema']);
        self::assertSame('smart-button', $receipt['pages'][0]['id']);
        self::assertCount(3, $receipt['pages'][0]['sources']);
        foreach ($receipt['pages'][0]['sources'] as $source) {
            self::assertSame(
                hash_file('sha256', $this->tmpPath($source['path'])),
                $source['sha256'],
            );
        }
    }

    #[Test]
    public function declarative_example_preflight_fails_closed_and_preserves_existing_output(): void
    {
        foreach ([
            'invalid-schema' => 'SCHEMA_VALIDATION_FAILED',
            'missing-source' => 'DECLARATIVE_EXAMPLE_SOURCE_MISSING',
            'visible-result' => 'DECLARATIVE_EXAMPLE_RESULT_MUST_BE_HIDDEN',
            'route-collision' => 'DECLARATIVE_EXAMPLE_ROUTE_COLLISION',
            'symlink-source' => 'DECLARATIVE_EXAMPLE_SOURCE_SYMLINK_FORBIDDEN',
        ] as $case => $expected) {
            $siteRoot = $this->tmpPath('declarative-example-' . $case);
            $this->copyPortableFixture($siteRoot);
            $this->installDeclarativeExampleFixture($siteRoot);
            $descriptorPath = $siteRoot . '/examples/smart-button.json';
            $descriptor = $this->jsonFile($descriptorPath);

            if ($case === 'invalid-schema') {
                $descriptor['preview'] = 'unsupported';
            } elseif ($case === 'missing-source') {
                $descriptor['sources'][] = [
                    'label' => 'Missing',
                    'path' => 'content/example-results/missing.json',
                    'language' => 'json',
                ];
            } elseif ($case === 'visible-result') {
                unlink($siteRoot . '/content/example-results/section.json');
            } elseif ($case === 'route-collision') {
                file_put_contents($siteRoot . '/content/examples.md', "# Collision\n");
            } else {
                $outside = $this->tmpPath('outside-example.json');
                file_put_contents($outside, "{}\n");
                $this->filesystem->ensureDirectoryExists($siteRoot . '/examples/sources');
                self::assertTrue(symlink($outside, $siteRoot . '/examples/sources/linked.json'));
                $descriptor['sources'][] = [
                    'label' => 'Linked',
                    'path' => 'examples/sources/linked.json',
                    'language' => 'json',
                ];
            }
            file_put_contents(
                $descriptorPath,
                json_encode($descriptor, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n",
            );
            $this->filesystem->ensureDirectoryExists($siteRoot . '/build_local');
            file_put_contents($siteRoot . '/build_local/sentinel.txt', 'keep-output');

            try {
                $this->builder()->build($siteRoot, $siteRoot . '/build_local');
                self::fail("Unsafe declarative example case [$case] unexpectedly passed.");
            } catch (PortableConfigurationException $exception) {
                self::assertSame($expected, $exception->errorCode);
            }
            self::assertSame('keep-output', file_get_contents($siteRoot . '/build_local/sentinel.txt'));
        }
    }

    #[Test]
    public function navigation_order_is_inherited_from_sections_overridden_by_pages_and_tied_by_url(): void
    {
        $this->copyPortableFixture($this->tmp);
        file_put_contents($this->tmpPath('content/guides/advanced.md'), "# Advanced\n");
        file_put_contents($this->tmpPath('content/guides/basics.md'), "# Basics\n");
        file_put_contents($this->tmpPath('content/reference.md'), "# Reference\n");
        file_put_contents(
            $this->tmpPath('content/reference.page.json'),
            json_encode([
                'schema' => 'docara.page.v1',
                'navigation' => ['order' => 15],
            ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n",
        );

        $guidePage = $this->jsonFile($this->tmpPath('content/guides/getting-started.page.json'));
        $guidePage['navigation'] = ['order' => 5];
        file_put_contents(
            $this->tmpPath('content/guides/getting-started.page.json'),
            json_encode($guidePage, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n",
        );

        $this->builder()->build($this->tmp, $this->tmpPath('build_local'));
        $html = (string) file_get_contents($this->tmpPath('build_local/index.html'));
        self::assertSame([
            '/',
            '/reference/',
            '/guides/',
            '/guides/getting-started/',
            '/guides/advanced/',
            '/guides/basics/',
            '/guides/platform/',
            '/guides/platform/configuration/',
            '/guides/platform/configuration/layout/',
            '/components/catalog/',
        ], $this->desktopNavigationLinks($html));

        $diagnostics = $this->jsonFile($this->tmpPath('build_local/.docara/resolved-page-plans.json'));
        $orders = [];
        foreach ($diagnostics['pages'] as $record) {
            $orders[$record['url']] = $record['resolved_page_plan']['configuration']['navigation']['order'] ?? null;
        }
        self::assertSame(5, $orders['/guides/getting-started/']);
        self::assertSame(20, $orders['/guides/advanced/']);
        self::assertSame(20, $orders['/guides/basics/']);
    }

    #[Test]
    public function pages_without_navigation_order_follow_every_explicit_order(): void
    {
        $this->copyPortableFixture($this->tmp);
        foreach ([
            'first' => 0,
            'middle' => 1000,
            'later' => 1500,
            'z-explicit-max' => 2147483647,
            'a-unspecified' => null,
        ] as $slug => $order) {
            file_put_contents($this->tmpPath("content/{$slug}.md"), "# {$slug}\n");
            if ($order !== null) {
                file_put_contents(
                    $this->tmpPath("content/{$slug}.page.json"),
                    json_encode([
                        'schema' => 'docara.page.v1',
                        'navigation' => ['order' => $order],
                    ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n",
                );
            }
        }

        $this->builder()->build($this->tmp, $this->tmpPath('build_local'));
        $html = (string) file_get_contents($this->tmpPath('build_local/index.html'));
        self::assertSame([
            '/first/',
            '/',
            '/guides/',
            '/middle/',
            '/later/',
            '/z-explicit-max/',
            '/a-unspecified/',
        ], $this->desktopNavigationLinks($html, topLevelOnly: true));

        $first = (string) file_get_contents($this->tmpPath('build_local/first/index.html'));
        self::assertStringContainsString('data-docara-breadcrumbs', $first);
        self::assertStringContainsString('href="/"><span class="sf-breadcrumbs-item-container', $first);
    }

    #[Test]
    public function clean_cli_install_and_build_work_without_legacy_scaffold(): void
    {
        $site = $this->tmpPath('empty-site');
        $this->filesystem->ensureDirectoryExists($site);
        $binary = dirname(__DIR__) . '/docara';
        $environment = ['TZ' => 'UTC', 'PATH' => dirname(PHP_BINARY) . ':/usr/bin:/bin:/usr/sbin:/sbin'];

        $init = new Process([PHP_BINARY, $binary, 'init', '--portable', '--no-interaction'], $site, $environment);
        $init->run();
        self::assertSame(0, $init->getExitCode(), $init->getErrorOutput() . $init->getOutput());
        self::assertFileExists($site . '/docara.json');
        self::assertFileDoesNotExist($site . '/config.php');
        self::assertDirectoryDoesNotExist($site . '/source');

        $build = new Process([PHP_BINARY, $binary, 'build', 'local', '--pretty=true', '--no-interaction'], $site, $environment);
        $build->run();
        self::assertSame(0, $build->getExitCode(), $build->getErrorOutput() . $build->getOutput());
        self::assertFileExists($site . '/build_local/index.html');
        self::assertFileExists($site . '/build_local/guides/getting-started/index.html');
        self::assertFileExists($site . '/build_local/guides/platform/configuration/layout/index.html');
        self::assertFileExists($site . '/build_local/landing/index.html');
        self::assertFileExists($site . '/build_local/_docara/component-catalog.json');
        self::assertFileExists($site . '/build_local/.docara/resolved-page-plans.json');
        self::assertFileExists($site . '/build_local/_docara/framework/smart/alert/js/alert.js');
        self::assertCount(1, glob($site . '/build_local/_docara/brand/*') ?: []);
    }

    #[Test]
    public function russian_json_component_payload_is_not_split_as_a_unicode_newline(): void
    {
        $lock = $this->jsonFile(dirname(__DIR__) . '/stubs/portable/simai-framework.lock.json');
        $runtime = FrameworkComponentRuntime::fromLock($lock);
        $markdown = <<<'MD'
# Проверка

Русский текст содержит байты, которые не являются переводом строки.

:::ui.alert
{"type":"info","title":"Наследование работает","supporting-text":"Параметры страницы сохраняются целиком."}
:::
MD;

        $document = $runtime->extract($markdown, 'content/unicode.md');

        self::assertCount(1, $document->normalizedCalls);
        self::assertSame('Наследование работает', $document->normalizedCalls[0]['props']['title']);
        self::assertSame('Параметры страницы сохраняются целиком.', $document->normalizedCalls[0]['props']['supporting-text']);
    }

    #[Test]
    public function base_url_scopes_routes_and_revisioned_local_framework_assets(): void
    {
        $this->copyPortableFixture($this->tmp);
        $site = $this->jsonFile($this->tmpPath('docara.json'));
        $site['base_url'] = '/project~/docs/';
        file_put_contents(
            $this->tmpPath('docara.json'),
            json_encode($site, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n",
        );

        $result = $this->builder()->build($this->tmp, $this->tmpPath('build_local'));
        self::assertTrue($result->has('/project~/docs/'));
        $html = (string) file_get_contents($this->tmpPath('build_local/index.html'));
        self::assertStringContainsString('href="/project~/docs/"', $html);
        self::assertStringContainsString(
            'window.sfSmartPath="/project~/docs/_docara/framework"',
            $html,
        );
        self::assertStringContainsString('href="/project~/docs/_docara/brand/', $html);
        self::assertMatchesRegularExpression(
            '#data-docara-search-index="/project~/docs/_docara/search-index\.json\?docara_v=[a-f0-9]{64}"#',
            $html,
        );
        self::assertMatchesRegularExpression(
            '#src="/project~/docs/_docara/search\.js\?docara_v=[a-f0-9]{64}"#',
            $html,
        );
        $searchIndex = $this->jsonFile($this->tmpPath('build_local/_docara/search-index.json'));
        foreach ($searchIndex['documents'] as $document) {
            self::assertStringStartsWith('/project~/docs/', $document['url']);
        }
        $diagnostics = (string) file_get_contents(
            $this->tmpPath('build_local/.docara/resolved-page-plans.json'),
        );
        self::assertMatchesRegularExpression(
            '#/project~/docs/_docara/framework/smart/alert/js/alert\.js\?sf_v=sf-v5\.3\.2-7e836d8a-dd786bba-[a-f0-9]{16}#',
            $diagnostics,
        );
        self::assertFileExists($this->tmpPath('build_local/_docara/framework/smart/alert/js/alert.js'));
    }

    #[Test]
    public function redirects_are_deterministic_scoped_to_the_build_and_bound_to_exact_generated_pages(): void
    {
        $this->copyPortableFixture($this->tmp);
        $site = $this->jsonFile($this->tmpPath('docara.json'));
        $site['base_url'] = '/project~/docs/';
        $site['documentation_version'] = '2.4.0';
        file_put_contents(
            $this->tmpPath('docara.json'),
            json_encode($site, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n",
        );
        file_put_contents(
            $this->tmpPath('content/landing.md'),
            str_replace(
                '/guides/getting-started/',
                '/project~/docs/guides/getting-started/',
                (string) file_get_contents($this->tmpPath('content/landing.md')),
            ),
        );
        $redirectSource = $this->jsonFile($this->tmpPath('redirects.json'));
        $redirectSource['redirects'][] = ['from' => 'home-old', 'to' => ''];
        file_put_contents(
            $this->tmpPath('redirects.json'),
            json_encode(
                $redirectSource,
                JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES,
            ) . "\n",
        );

        $this->builder()->build($this->tmp, $this->tmpPath('build_local'));

        $receiptPath = $this->tmpPath('build_local/.docara/redirects.json');
        self::assertFileExists($receiptPath);
        $receipt = $this->jsonFile($receiptPath);
        self::assertSame('docara.redirect_receipt.v1', $receipt['schema']);
        self::assertSame('/project~/docs/', $receipt['base_url']);
        self::assertSame('ru', $receipt['locale']);
        self::assertSame('2.4.0', $receipt['documentation_version']);
        $sourceDocument = $this->jsonFile($this->tmpPath('redirects.json'));
        usort(
            $sourceDocument['redirects'],
            static fn (array $left, array $right): int => [
                $left['from'],
                $left['to'],
            ] <=> [
                $right['from'],
                $right['to'],
            ],
        );
        self::assertSame(
            hash('sha256', CanonicalJson::encode($sourceDocument)),
            $receipt['source_sha256'],
        );
        self::assertSame(
            hash('sha256', CanonicalJson::encode($receipt['redirects'])),
            $receipt['content_sha256'],
        );
        self::assertSame(
            array_values(array_unique(array_column($receipt['redirects'], 'from'))),
            array_column($receipt['redirects'], 'from'),
        );
        $sortedSources = array_column($receipt['redirects'], 'from');
        sort($sortedSources, SORT_STRING);
        self::assertSame($sortedSources, array_column($receipt['redirects'], 'from'));

        $button = collect($receipt['redirects'])->firstWhere('from', 'components/button');
        self::assertIsArray($button);
        self::assertSame('components/button/index.html', $button['output']);
        self::assertSame('/project~/docs/components/button/', $button['url']);
        self::assertSame('/project~/docs/components/catalog/ui.button/', $button['target_url']);
        $html = (string) file_get_contents(
            $this->tmpPath('build_local/components/button/index.html'),
        );
        self::assertStringContainsString('<meta name="robots" content="noindex,follow">', $html);
        self::assertStringContainsString(
            '<link rel="canonical" href="/project~/docs/components/catalog/ui.button/">',
            $html,
        );
        self::assertStringContainsString(
            '<meta http-equiv="refresh" content="0; url=/project~/docs/components/catalog/ui.button/">',
            $html,
        );
        self::assertStringContainsString(
            '<a href="/project~/docs/components/catalog/ui.button/">',
            $html,
        );
        self::assertStringContainsString(
            'data-docara-documentation-version="2.4.0"',
            $html,
        );
        $home = collect($receipt['redirects'])->firstWhere('from', 'home-old');
        self::assertIsArray($home);
        self::assertSame('', $home['to']);
        self::assertSame('/project~/docs/', $home['target_url']);

        $manifest = $this->jsonFile(
            $this->tmpPath('build_local/.docara/resolved-page-plans.json'),
        );
        self::assertSame('ru', $manifest['build']['locale']);
        self::assertSame('2.4.0', $manifest['build']['documentation_version']);

        $firstTree = $this->treeHashes($this->tmpPath('build_local'));
        $sourceDocument['redirects'] = array_reverse($sourceDocument['redirects']);
        file_put_contents(
            $this->tmpPath('redirects.json'),
            json_encode($sourceDocument, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES),
        );
        $this->builder()->build($this->tmp, $this->tmpPath('build_local'));
        self::assertSame(
            $firstTree,
            $this->treeHashes($this->tmpPath('build_local')),
            'Redirect source formatting and record order must not change generated bytes.',
        );

        $process = new Process([
            PHP_BINARY,
            'scripts/verify-static-build.php',
            $this->tmpPath('build_local'),
        ], dirname(__DIR__));
        $process->run();
        self::assertSame(0, $process->getExitCode(), $process->getErrorOutput() . $process->getOutput());
    }

    #[Test]
    public function redirect_and_locale_preflight_failures_preserve_existing_output(): void
    {
        $cases = [
            'locale' => 'PORTABLE_BUILD_LOCALE_MISMATCH',
            'missing-target' => 'PORTABLE_REDIRECT_TARGET_NOT_FOUND',
            'chain' => 'PORTABLE_REDIRECT_CHAIN_FORBIDDEN',
            'page-collision' => 'PORTABLE_REDIRECT_PAGE_COLLISION',
        ];
        foreach ($cases as $case => $expectedCode) {
            $siteRoot = $this->tmpPath('redirect-preflight-' . $case);
            $this->copyPortableFixture($siteRoot);
            $this->filesystem->ensureDirectoryExists($siteRoot . '/build_local');
            file_put_contents($siteRoot . '/build_local/sentinel.txt', 'keep-output');

            if ($case === 'locale') {
                $pagePath = $siteRoot . '/content/guides/getting-started.page.json';
                $page = $this->jsonFile($pagePath);
                $page['locale'] = 'en';
                file_put_contents(
                    $pagePath,
                    json_encode($page, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n",
                );
            } else {
                $redirects = $this->jsonFile($siteRoot . '/redirects.json');
                if ($case === 'missing-target') {
                    $redirects['redirects'] = [['from' => 'old', 'to' => 'missing']];
                } elseif ($case === 'chain') {
                    $redirects['redirects'] = [
                        ['from' => 'old', 'to' => 'older'],
                        ['from' => 'older', 'to' => 'guides'],
                    ];
                } else {
                    $redirects['redirects'] = [['from' => 'guides', 'to' => 'components/catalog']];
                }
                file_put_contents(
                    $siteRoot . '/redirects.json',
                    json_encode($redirects, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n",
                );
            }

            try {
                $this->builder()->build($siteRoot, $siteRoot . '/build_local');
                self::fail("Unsafe redirect preflight case [$case] unexpectedly passed.");
            } catch (PortableConfigurationException $exception) {
                self::assertSame($expectedCode, $exception->errorCode);
            }
            self::assertSame(
                'keep-output',
                file_get_contents($siteRoot . '/build_local/sentinel.txt'),
            );
        }
    }

    #[Test]
    public function symlinked_and_hardlinked_redirect_sources_are_rejected_before_cleaning(): void
    {
        foreach (['symlink', 'hardlink'] as $kind) {
            $siteRoot = $this->tmpPath('redirect-source-' . $kind);
            $outside = $this->tmpPath('redirect-source-' . $kind . '-outside.json');
            $this->copyPortableFixture($siteRoot);
            file_put_contents($outside, (string) file_get_contents($siteRoot . '/redirects.json'));
            unlink($siteRoot . '/redirects.json');
            self::assertTrue(
                $kind === 'symlink'
                    ? symlink($outside, $siteRoot . '/redirects.json')
                    : link($outside, $siteRoot . '/redirects.json'),
            );
            $this->filesystem->ensureDirectoryExists($siteRoot . '/build_local');
            file_put_contents($siteRoot . '/build_local/sentinel.txt', 'keep-output');

            try {
                $this->builder()->build($siteRoot, $siteRoot . '/build_local');
                self::fail("Unsafe redirect source case [$kind] unexpectedly passed.");
            } catch (PortableConfigurationException $exception) {
                self::assertSame(
                    $kind === 'symlink'
                        ? 'PORTABLE_REDIRECT_SOURCE_SYMLINK_FORBIDDEN'
                        : 'PORTABLE_REDIRECT_SOURCE_UNSAFE',
                    $exception->errorCode,
                );
            }
            self::assertSame(
                'keep-output',
                file_get_contents($siteRoot . '/build_local/sentinel.txt'),
            );
            self::assertStringStartsWith('{', (string) file_get_contents($outside));
        }
    }

    #[Test]
    public function search_preflight_failures_do_not_clean_existing_output(): void
    {
        $this->copyPortableFixture($this->tmp);
        $site = $this->jsonFile($this->tmpPath('docara.json'));
        $site['search'] = ['enabled' => true, 'indexed' => false];
        file_put_contents(
            $this->tmpPath('docara.json'),
            json_encode($site, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n",
        );
        $this->filesystem->ensureDirectoryExists($this->tmpPath('build_local'));
        file_put_contents($this->tmpPath('build_local/sentinel.txt'), 'keep-output');

        try {
            $this->builder()->build($this->tmp, $this->tmpPath('build_local'));
            self::fail('An enabled locale without indexed pages unexpectedly passed.');
        } catch (PortableConfigurationException $exception) {
            self::assertSame('SEARCH_INDEX_LOCALE_EMPTY', $exception->errorCode);
        }
        self::assertSame('keep-output', file_get_contents($this->tmpPath('build_local/sentinel.txt')));
    }

    #[Test]
    public function a_fully_disabled_search_emits_no_ui_index_or_runtime(): void
    {
        $this->copyPortableFixture($this->tmp);
        $site = $this->jsonFile($this->tmpPath('docara.json'));
        $site['search'] = ['enabled' => false, 'indexed' => true];
        file_put_contents(
            $this->tmpPath('docara.json'),
            json_encode($site, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n",
        );

        $this->builder()->build($this->tmp, $this->tmpPath('build_local'));

        self::assertFileDoesNotExist($this->tmpPath('build_local/_docara/search-index.json'));
        self::assertFileDoesNotExist($this->tmpPath('build_local/_docara/search.js'));
        $html = (string) file_get_contents($this->tmpPath('build_local/index.html'));
        self::assertStringNotContainsString('<button type="button" data-docara-search-trigger', $html);
        self::assertStringNotContainsString('<dialog id="docara-search-dialog"', $html);
        self::assertStringNotContainsString('data-docara-search-runtime', $html);
    }

    #[Test]
    public function page_and_directory_nodes_merge_and_repeated_segments_keep_their_links(): void
    {
        $this->copyPortableFixture($this->tmp);
        $this->filesystem->ensureDirectoryExists($this->tmpPath('content/foo/foo'));
        file_put_contents($this->tmpPath('content/foo.md'), "# Первый Foo\n");
        file_put_contents($this->tmpPath('content/foo/foo/index.md'), "# Второй Foo\n");
        file_put_contents($this->tmpPath('content/foo/foo/deep.md'), "# Глубокая страница\n");

        $this->builder()->build($this->tmp, $this->tmpPath('build_local'));
        $html = (string) file_get_contents($this->tmpPath('build_local/foo/foo/deep/index.html'));

        $links = $this->desktopNavigationLinks($html);
        self::assertSame(1, count(array_keys($links, '/foo/', true)));
        self::assertSame(1, count(array_keys($links, '/foo/foo/', true)));
        self::assertContains('/foo/foo/deep/', $links);
        self::assertStringContainsString('data-docara-navigation-depth="3"', $html);
        self::assertStringContainsString('href="/foo/foo/deep/" aria-current="page"', $html);
    }

    #[Test]
    public function equivalent_overview_forms_prefer_matching_section_order_over_inherited_order(): void
    {
        $this->copyPortableFixture($this->tmp);
        $site = $this->jsonFile($this->tmpPath('docara.json'));
        $site['navigation'] = ['order' => 100];
        file_put_contents(
            $this->tmpPath('docara.json'),
            json_encode($site, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n",
        );

        foreach (['sibling' => false, 'indexed' => true] as $slug => $usesIndex) {
            $this->filesystem->ensureDirectoryExists($this->tmpPath("content/$slug"));
            $overview = $usesIndex ? "content/$slug/index.md" : "content/$slug.md";
            $sidecar = $usesIndex ? "content/$slug/index.page.json" : "content/$slug.page.json";
            file_put_contents($this->tmpPath($overview), "# Markdown $slug\n");
            file_put_contents($this->tmpPath("content/$slug/child.md"), "# Child $slug\n");
            file_put_contents(
                $this->tmpPath("content/$slug/section.json"),
                json_encode([
                    'schema' => 'docara.section.v1',
                    'title' => "Section $slug",
                    'navigation' => ['order' => $usesIndex ? 1 : 2],
                ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n",
            );
            file_put_contents(
                $this->tmpPath($sidecar),
                json_encode([
                    'schema' => 'docara.page.v1',
                    'title' => "Page $slug",
                    // A child override is not a reset of the navigation branch.
                    'navigation' => ['hidden' => false],
                ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n",
            );
        }

        $this->builder()->build($this->tmp, $this->tmpPath('build_local'));
        $html = (string) file_get_contents($this->tmpPath('build_local/index.html'));

        self::assertStringContainsString('<span class="sf-menu-element-text">Page indexed</span>', $html);
        self::assertStringContainsString('<span class="sf-menu-element-text">Page sibling</span>', $html);
        self::assertStringNotContainsString('Section indexed', $html);
        self::assertStringNotContainsString('Section sibling', $html);

        $links = $this->desktopNavigationLinks($html, topLevelOnly: true);
        self::assertLessThan(array_search('/sibling/', $links, true), array_search('/indexed/', $links, true));
        self::assertLessThan(array_search('/', $links, true), array_search('/sibling/', $links, true));
    }

    #[Test]
    public function equivalent_overview_forms_prefer_explicit_page_order_over_matching_section_order(): void
    {
        $this->copyPortableFixture($this->tmp);

        foreach (['sibling' => false, 'indexed' => true] as $slug => $usesIndex) {
            $this->filesystem->ensureDirectoryExists($this->tmpPath("content/$slug"));
            $overview = $usesIndex ? "content/$slug/index.md" : "content/$slug.md";
            $sidecar = $usesIndex ? "content/$slug/index.page.json" : "content/$slug.page.json";
            file_put_contents($this->tmpPath($overview), "# Markdown $slug\n");
            file_put_contents($this->tmpPath("content/$slug/child.md"), "# Child $slug\n");
            file_put_contents(
                $this->tmpPath("content/$slug/section.json"),
                json_encode([
                    'schema' => 'docara.section.v1',
                    'title' => "Section $slug",
                    'navigation' => ['order' => $usesIndex ? 1 : 2],
                ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n",
            );
            file_put_contents(
                $this->tmpPath($sidecar),
                json_encode([
                    'schema' => 'docara.page.v1',
                    'title' => "Page $slug",
                    // Reverse the metadata order and place both before home.
                    'navigation' => ['order' => $usesIndex ? 6 : 5],
                ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n",
            );
        }

        $this->builder()->build($this->tmp, $this->tmpPath('build_local'));
        $html = (string) file_get_contents($this->tmpPath('build_local/index.html'));

        $links = $this->desktopNavigationLinks($html, topLevelOnly: true);
        self::assertLessThan(array_search('/indexed/', $links, true), array_search('/sibling/', $links, true));
        self::assertLessThan(array_search('/', $links, true), array_search('/indexed/', $links, true));
    }

    #[Test]
    public function equivalent_overview_forms_honor_page_navigation_reset_with_sibling_values(): void
    {
        $this->copyPortableFixture($this->tmp);

        foreach (['sibling' => false, 'indexed' => true] as $slug => $usesIndex) {
            $this->filesystem->ensureDirectoryExists($this->tmpPath("content/$slug"));
            $overview = $usesIndex ? "content/$slug/index.md" : "content/$slug.md";
            $sidecar = $usesIndex ? "content/$slug/index.page.json" : "content/$slug.page.json";
            file_put_contents($this->tmpPath($overview), "# Markdown $slug\n");
            file_put_contents($this->tmpPath("content/$slug/child.md"), "# Child $slug\n");
            file_put_contents(
                $this->tmpPath("content/$slug/section.json"),
                json_encode([
                    'schema' => 'docara.section.v1',
                    'title' => "Section $slug",
                    'navigation' => ['order' => $usesIndex ? 1 : 2],
                ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n",
            );
            file_put_contents(
                $this->tmpPath($sidecar),
                json_encode([
                    'schema' => 'docara.page.v1',
                    'title' => "Page $slug",
                    'navigation' => ['$reset' => true, 'hidden' => false],
                ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n",
            );
        }

        $this->builder()->build($this->tmp, $this->tmpPath('build_local'));
        $html = (string) file_get_contents($this->tmpPath('build_local/index.html'));

        $links = $this->desktopNavigationLinks($html, topLevelOnly: true);
        self::assertLessThan(array_search('/indexed/', $links, true), array_search('/guides/', $links, true));
        self::assertLessThan(array_search('/sibling/', $links, true), array_search('/guides/', $links, true));
    }

    #[Test]
    public function equivalent_overview_forms_honor_matching_section_navigation_reset(): void
    {
        $this->copyPortableFixture($this->tmp);
        $site = $this->jsonFile($this->tmpPath('docara.json'));
        // A low inherited order makes this test fail if the sibling overview
        // accidentally keeps it instead of honoring the matching reset.
        $site['navigation'] = ['order' => 10];
        file_put_contents(
            $this->tmpPath('docara.json'),
            json_encode($site, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n",
        );

        foreach (['sibling' => false, 'indexed' => true] as $slug => $usesIndex) {
            $this->filesystem->ensureDirectoryExists($this->tmpPath("content/$slug"));
            $overview = $usesIndex ? "content/$slug/index.md" : "content/$slug.md";
            file_put_contents($this->tmpPath($overview), "# Markdown $slug\n");
            file_put_contents($this->tmpPath("content/$slug/child.md"), "# Child $slug\n");
            file_put_contents(
                $this->tmpPath("content/$slug/section.json"),
                json_encode([
                    'schema' => 'docara.section.v1',
                    'title' => "Section $slug",
                    'navigation' => ['$reset' => true, 'hidden' => false],
                ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n",
            );
        }

        $this->builder()->build($this->tmp, $this->tmpPath('build_local'));
        $html = (string) file_get_contents($this->tmpPath('build_local/index.html'));

        $links = $this->desktopNavigationLinks($html, topLevelOnly: true);
        self::assertLessThan(array_search('/indexed/', $links, true), array_search('/guides/', $links, true));
        self::assertLessThan(array_search('/sibling/', $links, true), array_search('/guides/', $links, true));
    }

    #[Test]
    public function brand_asset_failures_are_controlled_and_do_not_clean_existing_output(): void
    {
        foreach (['missing', 'unsupported', 'oversized', 'build-source', 'dark-without-default'] as $case) {
            $siteRoot = $this->tmpPath('brand-' . $case);
            $this->copyPortableFixture($siteRoot);
            $this->filesystem->ensureDirectoryExists($siteRoot . '/build_local');
            file_put_contents($siteRoot . '/build_local/sentinel.txt', 'keep-output');
            $site = $this->jsonFile($siteRoot . '/docara.json');

            if ($case === 'missing') {
                $site['branding']['logo'] = 'assets/missing.svg';
                $expected = 'BRAND_ASSET_NOT_FOUND';
            } elseif ($case === 'unsupported') {
                file_put_contents($siteRoot . '/assets/logo.txt', 'not-an-image');
                $site['branding']['logo'] = 'assets/logo.txt';
                $expected = 'BRAND_ASSET_TYPE_FORBIDDEN';
            } elseif ($case === 'oversized') {
                file_put_contents($siteRoot . '/assets/large.svg', str_repeat('x', 2097153));
                $site['branding']['logo'] = 'assets/large.svg';
                $expected = 'BRAND_ASSET_TOO_LARGE';
            } elseif ($case === 'build-source') {
                file_put_contents($siteRoot . '/build_local/logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"/>');
                $site['branding']['logo'] = 'build_local/logo.svg';
                $expected = 'BRAND_ASSET_PATH_INVALID';
            } else {
                unset($site['branding']['logo']);
                $expected = 'BRAND_DARK_LOGO_REQUIRES_LOGO';
            }
            file_put_contents(
                $siteRoot . '/docara.json',
                json_encode($site, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n",
            );

            try {
                $this->builder()->build($siteRoot, $siteRoot . '/build_local');
                self::fail("Unsafe brand asset case [$case] unexpectedly passed.");
            } catch (PortableConfigurationException $exception) {
                self::assertSame($expected, $exception->errorCode);
            }
            self::assertSame('keep-output', file_get_contents($siteRoot . '/build_local/sentinel.txt'));
        }
    }

    #[Test]
    public function symbolic_link_brand_assets_fail_before_existing_output_is_cleaned(): void
    {
        $this->copyPortableFixture($this->tmp);
        $outside = $this->tmpPath('outside-logo.svg');
        file_put_contents($outside, '<svg xmlns="http://www.w3.org/2000/svg"/>');
        if (! @symlink($outside, $this->tmpPath('assets/link.svg'))) {
            self::markTestSkipped('Symbolic links are not supported by this test environment.');
        }
        $site = $this->jsonFile($this->tmpPath('docara.json'));
        $site['branding']['logo'] = 'assets/link.svg';
        file_put_contents(
            $this->tmpPath('docara.json'),
            json_encode($site, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n",
        );
        $this->filesystem->ensureDirectoryExists($this->tmpPath('build_local'));
        file_put_contents($this->tmpPath('build_local/sentinel.txt'), 'keep-output');

        try {
            $this->builder()->build($this->tmp, $this->tmpPath('build_local'));
            self::fail('A symbolic-link brand asset unexpectedly passed.');
        } catch (PortableConfigurationException $exception) {
            self::assertSame('BRAND_ASSET_SYMLINK_FORBIDDEN', $exception->errorCode);
        }
        self::assertSame('keep-output', file_get_contents($this->tmpPath('build_local/sentinel.txt')));
    }

    #[Test]
    public function incomplete_or_extra_framework_projection_fails_before_existing_output_is_cleaned(): void
    {
        $this->copyPortableFixture($this->tmp);
        $lockPath = $this->tmpPath('simai-framework.lock.json');
        $lock = $this->jsonFile($lockPath);
        $lock['asset_projection']['files']['smart/unused/js/unused.js'] = [
            'sha256' => str_repeat('a', 64),
        ];
        file_put_contents(
            $lockPath,
            json_encode($lock, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n",
        );
        $this->filesystem->ensureDirectoryExists($this->tmpPath('build_local'));
        file_put_contents($this->tmpPath('build_local/sentinel.txt'), 'keep-output');

        try {
            $this->builder()->build($this->tmp, $this->tmpPath('build_local'));
            self::fail('An incomplete extra Framework projection unexpectedly passed.');
        } catch (FrameworkComponentException $exception) {
            self::assertSame('FRAMEWORK_BUNDLED_ASSET_MISSING', $exception->errorCode);
        }
        self::assertSame('keep-output', file_get_contents($this->tmpPath('build_local/sentinel.txt')));
    }

    #[Test]
    public function reserved_and_nonportable_derived_slugs_fail_before_existing_output_is_cleaned(): void
    {
        $this->copyPortableFixture($this->tmp);
        $this->filesystem->ensureDirectoryExists($this->tmpPath('build_local'));
        file_put_contents($this->tmpPath('build_local/sentinel.txt'), 'keep-output');
        file_put_contents($this->tmpPath('content/_docara.md'), "# Reserved\n");

        try {
            $this->builder()->build($this->tmp, $this->tmpPath('build_local'));
            self::fail('A reserved derived slug unexpectedly passed.');
        } catch (PortableConfigurationException $exception) {
            self::assertSame('PAGE_SLUG_RESERVED', $exception->errorCode);
        }
        unlink($this->tmpPath('content/_docara.md'));
        file_put_contents($this->tmpPath('content/Bad Name.md'), "# Unsafe\n");

        try {
            $this->builder()->build($this->tmp, $this->tmpPath('build_local'));
            self::fail('A nonportable derived slug unexpectedly passed.');
        } catch (PortableConfigurationException $exception) {
            self::assertSame('PAGE_SLUG_INVALID', $exception->errorCode);
        }
        self::assertSame('keep-output', file_get_contents($this->tmpPath('build_local/sentinel.txt')));
    }

    #[Test]
    public function destination_symlinks_and_input_overlap_fail_before_any_source_is_cleaned(): void
    {
        $this->copyPortableFixture($this->tmp);
        $outside = $this->tmpPath('outside');
        $this->filesystem->ensureDirectoryExists($outside);
        file_put_contents($outside . '/sentinel.txt', 'keep');
        if (! @symlink($outside, $this->tmpPath('build_local'))) {
            self::markTestSkipped('Symbolic links are not supported by this test environment.');
        }

        try {
            $this->builder()->build($this->tmp, $this->tmpPath('build_local'));
            self::fail('A symbolic-link destination unexpectedly passed.');
        } catch (PortableConfigurationException $exception) {
            self::assertSame('DESTINATION_SYMLINK_FORBIDDEN', $exception->errorCode);
        }
        self::assertSame('keep', file_get_contents($outside . '/sentinel.txt'));
        unlink($this->tmpPath('build_local'));

        rename($this->tmpPath('content'), $this->tmpPath('build_local'));
        $site = $this->jsonFile($this->tmpPath('docara.json'));
        $site['content_root'] = 'build_local';
        file_put_contents(
            $this->tmpPath('docara.json'),
            json_encode($site, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n",
        );
        file_put_contents($this->tmpPath('build_local/source-sentinel.txt'), 'keep-source');

        try {
            $this->builder()->build($this->tmp, $this->tmpPath('build_local'));
            self::fail('A destination overlapping portable input unexpectedly passed.');
        } catch (PortableConfigurationException $exception) {
            self::assertSame('DESTINATION_INPUT_OVERLAP_FORBIDDEN', $exception->errorCode);
        }
        self::assertSame('keep-source', file_get_contents($this->tmpPath('build_local/source-sentinel.txt')));
    }

    #[Test]
    public function symbolic_link_site_roots_are_rejected_before_the_resolved_destination_is_touched(): void
    {
        $site = $this->tmpPath('portable-site');
        $link = $this->tmpPath('portable-site-link');
        $this->copyPortableFixture($site);
        $this->filesystem->ensureDirectoryExists($site . '/build_local');
        file_put_contents($site . '/build_local/sentinel.txt', 'keep');
        if (! @symlink($site, $link)) {
            self::markTestSkipped('Symbolic links are not supported by this test environment.');
        }

        try {
            foreach ([$link, $link . '/', $link . '/.'] as $root) {
                try {
                    $this->builder()->build($root, $site . '/build_local');
                    self::fail("A symbolic-link site root [$root] unexpectedly passed.");
                } catch (PortableConfigurationException $exception) {
                    self::assertSame('ROOT_SYMLINK_FORBIDDEN', $exception->errorCode);
                }

                self::assertSame('keep', file_get_contents($site . '/build_local/sentinel.txt'));
            }
        } finally {
            @unlink($link);
        }
    }

    #[Test]
    public function generated_and_reserved_asset_collisions_fail_before_existing_output_is_cleaned(): void
    {
        $this->copyPortableFixture($this->tmp);
        $this->filesystem->ensureDirectoryExists($this->tmpPath('content/_docara/framework'));
        file_put_contents($this->tmpPath('content/_docara/framework/tamper.js'), 'tamper');
        $this->filesystem->ensureDirectoryExists($this->tmpPath('build_local'));
        file_put_contents($this->tmpPath('build_local/sentinel.txt'), 'keep-output');

        try {
            $this->builder()->build($this->tmp, $this->tmpPath('build_local'));
            self::fail('A reserved output collision unexpectedly passed.');
        } catch (PortableConfigurationException $exception) {
            self::assertSame('PORTABLE_ASSET_OUTPUT_COLLISION', $exception->errorCode);
        }
        self::assertSame('keep-output', file_get_contents($this->tmpPath('build_local/sentinel.txt')));
    }

    #[Test]
    public function declarative_publication_is_transactional_and_keeps_the_previous_build_on_late_failure(): void
    {
        $this->copyPortableFixture($this->tmp);
        $this->filesystem->ensureDirectoryExists($this->tmpPath('build_local'));
        file_put_contents($this->tmpPath('build_local/sentinel.txt'), 'accepted-build');
        $publisher = new class implements PortablePagePublisher
        {
            public function id(): string
            {
                return 'test.failing_publisher';
            }

            public function render(
                array $page,
                array $navigation,
                string $siteTitle,
                FrameworkAssetPlan $assets,
                ?DeclarativePageResult $declarative,
            ): string {
                throw new \RuntimeException('simulated late publisher failure');
            }
        };
        $builder = new PortableSiteBuilder(
            new Filesystem,
            new PortableMarkdownRenderer,
            new PortableHtmlRenderer,
            $publisher,
        );

        try {
            $builder->build($this->tmp, $this->tmpPath('build_local'));
            self::fail('A failing publisher unexpectedly replaced the accepted build.');
        } catch (\RuntimeException $exception) {
            self::assertSame('simulated late publisher failure', $exception->getMessage());
        }

        self::assertSame('accepted-build', file_get_contents($this->tmpPath('build_local/sentinel.txt')));
        self::assertDirectoryDoesNotExist($this->tmpPath('build_local.docara-candidate'));
    }

    #[Test]
    public function immutable_legacy_renderer_remains_an_explicit_url_compatible_rollback(): void
    {
        $this->copyPortableFixture($this->tmp);
        self::assertSame(
            'a28e914128a55143ce13e21c8bebc2216b5144919c6dbb2e5dfee366229125d0',
            hash_file('sha256', dirname(__DIR__) . '/src/PortableSite/PortableHtmlRenderer.php'),
        );

        $declarative = $this->builder()->build(
            $this->tmp,
            $this->tmpPath('build_declarative'),
        );
        $legacy = new PortableSiteBuilder(
            new Filesystem,
            new PortableMarkdownRenderer,
            new PortableHtmlRenderer,
            new LegacyPortablePagePublisher(new PortableHtmlRenderer),
        );
        $rollback = $legacy->build($this->tmp, $this->tmpPath('build_legacy'));

        self::assertSame($declarative->keys()->all(), $rollback->keys()->all());
        $declarativeHtml = array_values(array_filter(
            array_keys($this->treeHashes($this->tmpPath('build_declarative'))),
            static fn (string $path): bool => str_ends_with($path, '.html'),
        ));
        $legacyHtml = array_values(array_filter(
            array_keys($this->treeHashes($this->tmpPath('build_legacy'))),
            static fn (string $path): bool => str_ends_with($path, '.html'),
        ));
        self::assertSame($declarativeHtml, $legacyHtml);
        $declarativeDiagnostics = $this->jsonFile(
            $this->tmpPath('build_declarative/.docara/resolved-page-plans.json'),
        );
        $legacyDiagnostics = $this->jsonFile(
            $this->tmpPath('build_legacy/.docara/resolved-page-plans.json'),
        );
        self::assertSame(
            ['docara.declarative_page_publisher.v1'],
            array_values(array_unique(array_map(
                static fn (array $page): string => (string) $page['publisher']['id'],
                $declarativeDiagnostics['pages'],
            ))),
        );
        self::assertSame(
            ['docara.legacy_html_renderer.v1'],
            array_values(array_unique(array_map(
                static fn (array $page): string => (string) $page['publisher']['id'],
                $legacyDiagnostics['pages'],
            ))),
        );
        self::assertSame(
            [true],
            array_values(array_unique(array_map(
                static fn (array $page): bool => (bool) $page['publisher']['rollback'],
                $legacyDiagnostics['pages'],
            ))),
        );
    }

    private function builder(): PortableSiteBuilder
    {
        return new PortableSiteBuilder(
            new Filesystem,
            new PortableMarkdownRenderer,
            new PortableHtmlRenderer,
        );
    }

    /** @return array<string, mixed> */
    private function localeDefinition(
        string $label,
        string $direction,
        string $contentRoot,
        string $languagePack,
        string $publicPrefix,
        array $fallbacks,
    ): array {
        return [
            'label' => $label,
            'direction' => $direction,
            'content_root' => $contentRoot,
            'language_pack' => $languagePack,
            'public_prefix' => $publicPrefix,
            'fallbacks' => $fallbacks,
        ];
    }

    private function copyPortableFixture(string $target, bool $legacyCompatibility = true): void
    {
        $this->filesystem->copyDirectory(dirname(__DIR__) . '/stubs/portable', $target);
        if (! $legacyCompatibility) {
            return;
        }

        rename($target . '/content/ru', $target . '/content-legacy');
        rmdir($target . '/content');
        rename($target . '/content-legacy', $target . '/content');
        $site = $this->jsonFile($target . '/docara.json');
        $site['content_root'] = 'content';
        unset($site['locales']);
        $site['locale_routing'] = [
            'strategy' => 'default_unprefixed',
            'root' => 'default_locale',
            'detect_browser_language' => false,
            'legacy_unprefixed_redirects' => false,
        ];
        file_put_contents(
            $target . '/docara.json',
            json_encode($site, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n",
        );
        $redirects = $this->jsonFile($target . '/redirects.json');
        $redirects['redirects'] = array_values(array_map(
            static fn (array $redirect): array => [
                'from' => $redirect['from'],
                'to' => preg_replace('#^ru/#', '', (string) $redirect['to']),
            ],
            array_filter(
                $redirects['redirects'],
                static fn (array $redirect): bool => ! str_starts_with((string) $redirect['from'], 'ru/'),
            ),
        ));
        file_put_contents(
            $target . '/redirects.json',
            json_encode($redirects, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n",
        );
    }

    private function installDeclarativeExampleFixture(string $target): void
    {
        $this->filesystem->ensureDirectoryExists($target . '/examples');
        $this->filesystem->ensureDirectoryExists($target . '/content/example-results');
        file_put_contents(
            $target . '/content/example-results/section.json',
            json_encode([
                'schema' => 'docara.section.v1',
                'navigation' => ['hidden' => true],
                'search' => ['indexed' => false],
            ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n",
        );
        file_put_contents(
            $target . '/content/example-results/button.md',
            "# Кнопка\n\n:::ui.button\n{\"text\":\"Продолжить\",\"preset\":\"primary\"}\n:::\n",
        );
        file_put_contents(
            $target . '/content/example-results/button.page.json',
            json_encode([
                'schema' => 'docara.page.v1',
                'title' => 'Кнопка',
            ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n",
        );
        file_put_contents(
            $target . '/examples/smart-button.json',
            json_encode([
                'schema' => 'docara.declarative_example.v1',
                'id' => 'smart-button',
                'title' => 'Smart Button',
                'description' => 'A real Smart component example.',
                'category' => 'smart',
                'order' => 10,
                'result_page' => 'content/example-results/button.md',
                'preview' => 'compact',
                'sources' => [[
                    'label' => 'Markdown',
                    'path' => 'content/example-results/button.md',
                    'language' => 'markdown',
                ], [
                    'label' => 'Page settings',
                    'path' => 'content/example-results/button.page.json',
                    'language' => 'json',
                ]],
            ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n",
        );
    }

    /** @return array<string, mixed> */
    private function jsonFile(string $path): array
    {
        return json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
    }

    /** @return list<string> */
    private function desktopNavigationLinks(string $html, bool $topLevelOnly = false): array
    {
        $document = new \DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $xpath = new \DOMXPath($document);
        $query = $topLevelOnly
            ? '//aside[contains(concat(" ", normalize-space(@class), " "), " docara-sidebar ")]'
                . '//nav[contains(concat(" ", normalize-space(@class), " "), " docara-navigation ")]'
                . '/ul/li/div/*[@data-docara-menu-link]'
            : '//aside[contains(concat(" ", normalize-space(@class), " "), " docara-sidebar ")]//*[@data-docara-menu-link]';
        $links = [];
        foreach ($xpath->query($query) ?: [] as $node) {
            if ($node instanceof \DOMElement) {
                $links[] = $node->getAttribute('href');
            }
        }

        return $links;
    }

    /** @return array<string, string> */
    private function treeHashes(string $root): array
    {
        $hashes = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
        );
        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }
            $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
            $hashes[$relative] = hash_file('sha256', $file->getPathname());
        }
        ksort($hashes, SORT_STRING);

        return $hashes;
    }
}
