<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class DocumentationContractTest extends TestCase
{
    private const RETIRED_COMPONENT_SLUGS = [
        'alert',
        'button',
        'card',
        'code',
        'cta',
        'features',
        'steps',
        'table',
        'tabs',
    ];

    #[Test]
    public function authored_documentation_covers_five_audience_paths_and_every_page_has_one_h1(): void
    {
        $audiencePaths = [
            'beginner' => [
                'start.md',
                'build/verify.md',
                'build/local-preview.md',
            ],
            'author' => [
                'authoring/configuration.md',
                'authoring/inheritance.md',
                'authoring/layout-and-navigation.md',
                'build/publish.md',
            ],
            'migrating_owner' => [
                'migration/legacy.md',
                'migration/template.md',
                'migration/mix-to-vite.md',
                'troubleshooting.md',
            ],
            'maintainer' => [
                'development/getting-started.md',
                'development/architecture.md',
                'development/testing.md',
            ],
            'extension_developer_or_ai' => [
                'components/syntax.md',
                'development/framework-components.md',
                'development/extensions.md',
            ],
        ];

        foreach ($audiencePaths as $audience => $pages) {
            self::assertNotEmpty($pages, $audience);
            foreach ($pages as $page) {
                self::assertFileExists($this->contentRoot() . '/' . $page, "$audience: $page");
            }
        }

        $documents = $this->markdownDocuments();
        self::assertCount(43, $documents, 'The authored documentation inventory must stay exact.');

        foreach ($documents as $path) {
            $markdown = (string) file_get_contents($path);
            preg_match_all('/^#(?!#)\h+\S.*$/mu', $this->withoutFencedCode($markdown), $matches);

            self::assertCount(
                1,
                $matches[0],
                $this->relativeToRepository($path) . ' must contain exactly one authored H1.',
            );
        }
    }

    #[Test]
    public function beginner_path_ends_with_a_verified_http_preview_contract(): void
    {
        $path = implode("\n", array_map(
            fn (string $relative): string => (string) file_get_contents($this->contentRoot() . '/' . $relative),
            ['start.md', 'build/verify.md', 'build/local-preview.md'],
        ));

        self::assertStringContainsString('docara build production', $path);
        self::assertStringContainsString('docara verify-static', $path);
        self::assertMatchesRegularExpression(
            '/docara serve production[^\r\n]*--no-build/u',
            $path,
        );
        self::assertMatchesRegularExpression('/Ctrl\s*\+\s*C/ui', $path);
        self::assertMatchesRegularExpression(
            '/не открывайте.{0,200}file:\/\//uis',
            preg_replace('/\s+/u', ' ', $path) ?? $path,
        );
        self::assertDoesNotMatchRegularExpression(
            '/\]\(\s*file:\/\/|href=["\']file:\/\/|'
            . '(?:^|\R)\h*(?:open|xdg-open|start)\h+["\']?file:\/\//umi',
            $path,
            'file:// may be mentioned in a warning but cannot be a link or preview command.',
        );
        preg_match_all(
            '/^```(?:bash|sh|shell|zsh|console)\h*\R(.*?)\R```[ \t]*$/msi',
            $path,
            $shellExamples,
        );
        foreach ($shellExamples[1] as $shellExample) {
            self::assertStringNotContainsString(
                'file://',
                $shellExample,
                'Shell examples must preview the site through HTTP.',
            );
        }
    }

    #[Test]
    public function retired_manual_component_pages_and_links_do_not_return(): void
    {
        foreach (self::RETIRED_COMPONENT_SLUGS as $slug) {
            self::assertFileDoesNotExist($this->contentRoot() . "/components/$slug.md");
        }

        $surfaces = [
            $this->repositoryRoot() . '/README.md',
            ...$this->markdownDocuments(),
        ];
        foreach ($surfaces as $path) {
            $contents = (string) file_get_contents($path);
            preg_match_all('/\]\(([^)\s]+)(?:\s+["\'][^"\']*["\'])?\)/u', $contents, $matches);

            foreach ($matches[1] as $target) {
                self::assertFalse(
                    $this->isRetiredManualTarget($path, html_entity_decode($target)),
                    $this->relativeToRepository($path) . " links to retired manual component target [$target].",
                );
            }
        }
    }

    #[Test]
    public function product_documentation_uses_the_stable_framework_name_without_readiness_overclaim(): void
    {
        $surfaces = [
            $this->repositoryRoot() . '/README.md',
            ...$this->markdownDocuments(),
        ];

        foreach ($surfaces as $path) {
            $contents = (string) file_get_contents($path);
            self::assertDoesNotMatchRegularExpression(
                '/\bSF5\b/ui',
                $contents,
                $this->relativeToRepository($path) . ' uses the retired technical shorthand.',
            );

            foreach (preg_split('/\R\h*\R/u', $this->withoutFencedCode($contents)) ?: [] as $paragraphNumber => $paragraph) {
                if (! preg_match(
                    '/production[- ]ready|public[- ]release[- ]ready|'
                    . 'all (?:simai framework|framework) components (?:are )?supported|'
                    . 'готов\p{L}*(?:\h+\p{L}+){0,4}\h+(?:production|продакшен\p{L}*|'
                    . 'публичн\p{L}*\h+релиз\p{L}*)|поддержива\p{L}*\h+все\h+компонент\p{L}*/ui',
                    $paragraph,
                )) {
                    continue;
                }

                self::assertMatchesRegularExpression(
                    '/\b(?:not|false|cannot)\b|does not|is not|\bне\b|не подтвержда\p{L}*|'
                    . 'не означа\p{L}*|не явля\p{L}*|запреща\p{L}*/ui',
                    $paragraph,
                    sprintf(
                        '%s paragraph %d contains an unbounded readiness claim: %s',
                        $this->relativeToRepository($path),
                        $paragraphNumber + 1,
                        trim(preg_replace('/\s+/u', ' ', $paragraph) ?? $paragraph),
                    ),
                );
            }
        }
    }

    /** @return list<string> */
    private function markdownDocuments(): array
    {
        $paths = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->contentRoot(), RecursiveDirectoryIterator::SKIP_DOTS),
        );
        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->isFile() && strtolower($file->getExtension()) === 'md') {
                $paths[] = $file->getPathname();
            }
        }
        sort($paths, SORT_STRING);

        return $paths;
    }

    private function withoutFencedCode(string $markdown): string
    {
        $insideFence = false;
        $fence = null;
        $kept = [];

        foreach (preg_split('/\R/u', $markdown) ?: [] as $line) {
            if (! $insideFence && preg_match('/^\h*(`{3,}|~{3,})/', $line, $match)) {
                $insideFence = true;
                $fence = $match[1][0];

                continue;
            }
            if ($insideFence && $fence !== null && preg_match('/^\h*' . preg_quote($fence, '/') . '{3,}\h*$/', $line)) {
                $insideFence = false;
                $fence = null;

                continue;
            }
            if (! $insideFence) {
                $kept[] = $line;
            }
        }

        return implode("\n", $kept);
    }

    private function isRetiredManualTarget(string $source, string $target): bool
    {
        $target = preg_replace('/[?#].*$/', '', str_replace('\\', '/', $target)) ?? $target;
        if ($target === '' || preg_match('~^(?:[a-z]+:|#)~i', $target)) {
            return false;
        }
        if (str_contains($target, 'components/catalog/')) {
            return false;
        }

        $slugs = implode('|', array_map(
            static fn (string $slug): string => preg_quote($slug, '~'),
            self::RETIRED_COMPONENT_SLUGS,
        ));
        if (preg_match("~(?:^|/)components/(?:$slugs)(?:/index)?(?:\\.md)?/?$~", $target)) {
            return true;
        }

        $relativeSource = str_replace($this->contentRoot() . '/', '', $source);

        return str_starts_with($relativeSource, 'components/')
            && preg_match("~^(?:\\./|\\.\\./)*(?:$slugs)(?:\\.md|/)?$~", $target) === 1;
    }

    private function relativeToRepository(string $path): string
    {
        return ltrim(str_replace($this->repositoryRoot(), '', $path), '/');
    }

    private function contentRoot(): string
    {
        return $this->repositoryRoot() . '/docs/site/content';
    }

    private function repositoryRoot(): string
    {
        return dirname(__DIR__, 2);
    }
}
