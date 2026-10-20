<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Simai\Docara\PortableSite\PortableAtlasIndexHydrator;
use Simai\Docara\PortableSite\PortableComponentIndexHydrator;
use Simai\Docara\PortableSite\PortableMarkdownRenderer;
use Tests\TestCase;

/**
 * Docara's renderers style their markup with SIMAI Framework utility classes.
 * A class the bundled Framework does not define is silently ignored by the
 * browser, so a misspelt utility (p-inline-1, a width-less border-bottom)
 * leaves a component unstyled without any build error. Every class a
 * renderer emits must therefore be defined by the bundled Framework CSS,
 * be in Docara's own docara-* namespace, or be a named hook.
 */
final class RendererFrameworkUtilityTest extends TestCase
{
    /**
     * Classes that carry state, select a client engine or come from a
     * CommonMark extension (footnotes, fenced-code languages), not a style.
     */
    private const HOOKS = ['is-active', 'mermaid', 'footnote', 'footnote-ref', 'footnote-backref', 'footnotes'];

    private const HOOK_PREFIXES = ['docara-', 'language-'];

    /** @var array<string,true>|null */
    private static ?array $frameworkClasses = null;

    #[Test]
    public function rendered_documentation_pages_use_only_classes_the_bundled_framework_defines(): void
    {
        $root = dirname(__DIR__, 2);
        $renderer = new PortableMarkdownRenderer;
        $html = '';
        $files = [];
        $pages = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root . '/docs/site/content/ru', \FilesystemIterator::SKIP_DOTS));
        foreach ($pages as $page) {
            if ($page instanceof \SplFileInfo && $page->isFile() && $page->getExtension() === 'md') {
                $files[] = $page->getPathname();
            }
        }
        sort($files, SORT_STRING);
        self::assertGreaterThan(100, count($files));
        foreach ($files as $file) {
            $markdown = preg_replace('/\A---\n.*?\n---\n/s', '', (string) file_get_contents($file)) ?? '';
            $html .= $renderer->render($markdown, $root . '/docs/site', $file);
        }
        $html .= $this->componentIndexHtml() . $this->atlasIndexHtml();

        // The pages exercise the blocks whose utilities once did not exist.
        foreach (['data-docara-tabs', 'data-docara-block="features"', 'data-docara-block="showcase"', 'data-docara-block="columns"', 'data-step-state=', 'data-view="lines"', '<figcaption', 'data-docara-native-quote', 'data-docara-component-index-view', 'data-atlas-fingerprint'] as $marker) {
            self::assertStringContainsString($marker, $html, "The rendered pages no longer contain [$marker]; widen the fixture.");
        }
        $classes = $this->classesIn($html);
        self::assertSame([], $this->unknown($classes), 'Rendered markup uses classes the bundled SIMAI Framework does not define.');
    }

    #[Test]
    public function literal_class_attributes_in_renderer_sources_name_only_framework_classes(): void
    {
        $root = dirname(__DIR__, 2);
        $sources = [
            ...(glob($root . '/src/PortableSite/*.php') ?: []),
            ...(glob($root . '/src/Declarative/Rendering/*.php') ?: []),
            ...(glob($root . '/resources/components/templates/*.php') ?: []),
            $root . '/resources/portable/declarative-shell.js',
        ];
        $found = [];
        foreach ($sources as $source) {
            $code = (string) file_get_contents($source);
            // class="…" up to the attribute end, or up to a PHP string end
            // where the value continues through concatenation.
            preg_match_all('/class\s*=\s*\\\\?"([^"\'\\\\]*)(["\'\\\\])/', $code, $matches, PREG_SET_ORDER);
            preg_match_all('/className\s*=\s*\'([^\']*)\'/', $code, $jsMatches, PREG_SET_ORDER);
            foreach ([...$matches, ...$jsMatches] as $match) {
                $tokens = preg_split('/\s+/', (string) $match[1], -1, PREG_SPLIT_NO_EMPTY) ?: [];
                if (($match[2] ?? '"') === "'" && $tokens !== [] && ! preg_match('/\s$/', (string) $match[1])) {
                    array_pop($tokens); // completed by concatenation, e.g. sf-alert--' . $type
                }
                foreach ($tokens as $token) {
                    if (preg_match('#^[A-Za-z][A-Za-z0-9:/_-]*$#D', $token) === 1) {
                        $found[$token][basename($source)] = true;
                    }
                }
            }
        }
        self::assertGreaterThan(100, count($found));

        $unknown = [];
        foreach ($this->unknown($found) as $token) {
            $unknown[$token] = array_keys($found[$token]);
        }
        self::assertSame([], $unknown, 'Renderer sources use classes the bundled SIMAI Framework does not define.');
    }

    #[Test]
    public function the_check_rejects_the_utilities_that_left_tabs_unstyled(): void
    {
        $unknown = $this->unknown(array_fill_keys(['p-inline-1', 'p-block-1/3', 'border-bottom', 'p-x-1', 'border-bottom-1', 'docara-tab'], true));

        self::assertSame(['border-bottom', 'p-block-1/3', 'p-inline-1'], $unknown);
    }

    /** @param array<string,mixed> $classes @return list<string> */
    private function unknown(array $classes): array
    {
        $framework = self::frameworkClasses();
        $unknown = [];
        foreach (array_keys($classes) as $class) {
            $class = (string) $class;
            if (isset($framework[$class]) || in_array($class, self::HOOKS, true)) {
                continue;
            }
            foreach (self::HOOK_PREFIXES as $prefix) {
                if (str_starts_with($class, $prefix)) {
                    continue 2;
                }
            }
            $unknown[] = $class;
        }
        sort($unknown, SORT_STRING);

        return $unknown;
    }

    /** @return array<string,true> */
    private function classesIn(string $html): array
    {
        preg_match_all('/\sclass="([^"]*)"/', $html, $matches);
        $classes = [];
        foreach ($matches[1] as $value) {
            foreach (preg_split('/\s+/', html_entity_decode($value), -1, PREG_SPLIT_NO_EMPTY) ?: [] as $class) {
                $classes[$class] = true;
            }
        }

        return $classes;
    }

    /** @return array<string,true> */
    private static function frameworkClasses(): array
    {
        if (self::$frameworkClasses !== null) {
            return self::$frameworkClasses;
        }
        $root = dirname(__DIR__, 2);
        $lock = json_decode((string) file_get_contents($root . '/resources/framework/runtime-lock.json'), true, 512, JSON_THROW_ON_ERROR);
        $revision = (string) ($lock['ui']['commit'] ?? '');
        $smartRevision = (string) ($lock['ui_smart']['commit'] ?? $revision);
        self::assertMatchesRegularExpression('/^[a-f0-9]{40}$/D', $revision);

        $stylesheets = [];
        foreach ([
            $root . '/resources/portable/vendor/simai-framework/runtime/' . $revision . '/distr',
            $root . '/resources/framework/runtime-smart/' . $smartRevision . '/smart',
        ] as $directory) {
            self::assertDirectoryExists($directory);
            $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS));
            foreach ($files as $file) {
                if ($file instanceof \SplFileInfo && $file->isFile() && $file->getExtension() === 'css') {
                    $stylesheets[] = $file->getPathname();
                }
            }
        }
        self::assertContains(
            $root . '/resources/portable/vendor/simai-framework/runtime/' . $revision . '/distr/core/css/utility.full.css',
            $stylesheets,
        );

        $classes = [];
        foreach ($stylesheets as $stylesheet) {
            $css = preg_replace('#/\*.*?\*/#s', '', (string) file_get_contents($stylesheet)) ?? '';
            preg_match_all('/\.((?:\\\\.|[A-Za-z0-9_-])+)/', $css, $matches);
            foreach ($matches[1] as $selector) {
                $classes[(string) preg_replace('/\\\\(.)/', '$1', $selector)] = true;
            }
        }

        return self::$frameworkClasses = $classes;
    }

    private function componentIndexHtml(): string
    {
        $pages = [
            ['url' => '/ru/components/', 'page_source_kind' => 'authored_markdown', 'title' => 'Компоненты', 'description' => '', 'content_html' => '<nav data-docara-component-index></nav>'],
            ['url' => '/ru/components/alpha/', 'page_source_kind' => 'authored_markdown', 'title' => 'Альфа', 'description' => 'Первая', 'content_html' => ''],
        ];
        $hydrator = new PortableComponentIndexHydrator;

        return $hydrator->hydrate($pages, ['/ru/components/' => $hydrator->index($pages, '/ru/components/')])[0]['content_html'];
    }

    private function atlasIndexHtml(): string
    {
        $placeholder = (new PortableMarkdownRenderer)->render(":::atlas_index {kind=smart}\n:::\n");
        $entry = [
            'id' => 'ui.input', 'kind' => 'smart', 'namespace' => 'ui', 'owner' => 'simai/ui', 'owner_package' => 'simai/ui',
            'origin' => 'framework', 'authoring_kind' => 'block', 'support' => 'supported', 'status' => 'supported',
            'provider' => 'simai/ui.provider', 'capabilities' => ['content'],
        ];

        return (new PortableAtlasIndexHydrator)->hydrate(
            [['content_html' => $placeholder]],
            ['fingerprint' => str_repeat('a', 64), 'entries' => [$entry]],
        )[0]['content_html'];
    }
}
