<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Simai\Docara\Documentation\MarkdownLocalLinkVerifier;
use Simai\Docara\File\Filesystem;
use Simai\Docara\I18n\ContentLanguageRepository;
use Simai\Docara\I18n\LocaleRegistry;
use Simai\Docara\I18n\Translator;
use Simai\Docara\Portable\SchemaRepository;
use Simai\Docara\PortableSite\PortableMarkdownRenderer;
use Simai\Docara\PortableSite\PortableSiteBuilder;
use SplFileInfo;

final class DocumentationContractTest extends TestCase
{
    private const RETIRED_COMPONENT_SLUGS = [
        'cta',
        'features',
    ];

    #[Test]
    public function product_surfaces_use_the_canonical_simai_brand_spelling(): void
    {
        $files = [
            $this->repositoryRoot() . '/README.md',
            $this->repositoryRoot() . '/CONTRIBUTING.md',
        ];
        foreach (['docs', 'resources', 'stubs', 'src', 'scripts'] as $directory) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(
                    $this->repositoryRoot() . '/' . $directory,
                    RecursiveDirectoryIterator::SKIP_DOTS,
                ),
            );
            foreach ($iterator as $file) {
                if (! $file instanceof SplFileInfo || ! $file->isFile()) {
                    continue;
                }
                $relative = $this->relativeToRepository($file->getPathname());
                if (
                    str_starts_with($relative, 'docs/site/build_')
                    || str_contains($relative, '/.docara/')
                    || str_starts_with($relative, 'resources/framework/manifests/')
                ) {
                    continue;
                }
                if (! in_array(strtolower($file->getExtension()), ['json', 'md', 'php', 'yaml', 'yml'], true)) {
                    continue;
                }
                $files[] = $file->getPathname();
            }
        }

        $nonCanonical = 'Simai' . ' Framework';
        foreach ($files as $path) {
            self::assertStringNotContainsString(
                $nonCanonical,
                (string) file_get_contents($path),
                $this->relativeToRepository($path) . ' must spell the brand as SIMAI Framework.',
            );
        }
    }

    #[Test]
    public function source_documentation_has_no_broken_local_links(): void
    {
        (new MarkdownLocalLinkVerifier)->verify($this->documentationInventory());

        self::addToAssertionCount(1);
    }

    #[Test]
    public function public_localization_surfaces_expose_only_content_locale_lang_files(): void
    {
        self::assertFileDoesNotExist($this->repositoryRoot() . '/resources/schemas/language-pack.schema.json');
        self::assertDirectoryDoesNotExist($this->repositoryRoot() . '/resources/language-packs');
        self::assertFileDoesNotExist($this->repositoryRoot() . '/src/I18n/LanguagePack.php');
        self::assertFileDoesNotExist($this->repositoryRoot() . '/src/I18n/LanguagePackRepository.php');

        $siteSchema = (string) file_get_contents($this->repositoryRoot() . '/resources/schemas/site.schema.json');
        $starter = (string) file_get_contents($this->repositoryRoot() . '/stubs/portable/docara.json');
        self::assertStringNotContainsString('language_pack', $siteSchema);
        self::assertStringNotContainsString('language_pack', $starter);

        $langSchema = json_decode(
            (string) file_get_contents($this->repositoryRoot() . '/resources/schemas/lang.schema.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        self::assertSame([
            'accessibility', 'code', 'common', 'copy', 'language', 'navigation',
            'reader', 'redirect', 'search', 'shell', 'toc', 'transitions',
        ], $this->langNamespaces($langSchema));

        foreach ([$this->repositoryRoot() . '/README.md', ...$this->markdownDocuments()] as $path) {
            $contents = (string) file_get_contents($path);
            self::assertDoesNotMatchRegularExpression(
                '~(?:`|["\'])languages/[a-z<{]|["\']language_pack["\']\s*:|docara\.language_pack\.v1|resources/schemas/language-pack\.schema\.json~ui',
                $contents,
                $this->relativeToRepository($path) . ' teaches the retired public language-pack contract.',
            );
        }

        $guide = (string) file_get_contents($this->contentRoot() . '/authoring/language-packs.md');
        self::assertStringContainsString('content/<locale>/lang.json', $guide);
        self::assertStringContainsString('повторяющиеся подписи интерфейса', $guide);
        self::assertStringContainsString('Текст страниц и описания', $guide);
        self::assertStringContainsString('остаются в Markdown', $guide);
    }

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
                'authoring/localization.md',
                'authoring/branding.md',
                'authoring/multilingual-site.md',
                'authoring/language-packs.md',
                'build/update.md',
                'build/publish.md',
            ],
            'migrating_owner' => [
                'migration.md',
                'troubleshooting.md',
            ],
            'maintainer' => [
                'development/getting-started.md',
                'development/architecture.md',
                'development/composition-extensions.md',
                'development/smart-components.md',
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
        $requiredAudienceDocuments = array_unique(array_merge(...array_values($audiencePaths)));
        self::assertGreaterThanOrEqual(
            count($requiredAudienceDocuments),
            count($documents),
            'The authored documentation inventory must cover every declared audience path.',
        );

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
    public function portable_installation_surfaces_use_the_same_local_source_candidate_until_release(): void
    {
        $surfaces = [
            $this->repositoryRoot() . '/README.md',
            $this->contentRoot() . '/start.md',
            $this->contentRoot() . '/reference/cli.md',
        ];

        foreach ($surfaces as $path) {
            $contents = (string) file_get_contents($path);

            self::assertStringContainsString('git rev-parse HEAD', $contents);
            self::assertStringContainsString('composer install', $contents);
            self::assertDoesNotMatchRegularExpression(
                '/dev-codex\/docara-consolidation#[0-9a-f]{40}/',
                $contents,
                $this->relativeToRepository($path) . ' must not embed an obsolete candidate SHA.',
            );
            self::assertDoesNotMatchRegularExpression(
                '/composer require simai\/docara\h*\Rphp vendor\/bin\/docara init --portable/u',
                $contents,
                $this->relativeToRepository($path) . ' pairs the legacy stable package with portable init.',
            );
        }

        foreach ([$this->repositoryRoot() . '/README.md', ...$this->markdownDocuments()] as $path) {
            self::assertStringNotContainsString(
                '2640503ba14913aa83bc3b4343c86966a807e29f',
                (string) file_get_contents($path),
                $this->relativeToRepository($path) . ' contains the retired portable candidate.',
            );
        }
    }

    #[Test]
    public function user_ready_examples_are_schema_and_runtime_valid(): void
    {
        $branding = $this->firstJsonExample('authoring/branding.md');
        (new SchemaRepository)->assertValid($branding, 'site.schema.json');

        $localization = $this->firstJsonExample('authoring/localization.md');
        $localizationRegistry = LocaleRegistry::fromSite($localization);
        self::assertSame(['ar', 'en'], array_map(
            static fn ($locale): string => $locale->tag->value(),
            $localizationRegistry->fallbackChain('ar'),
        ));
        self::assertSame(['fr-CA', 'en'], array_map(
            static fn ($locale): string => $locale->tag->value(),
            $localizationRegistry->fallbackChain('fr-CA'),
        ));

        $multilingual = $this->firstJsonExample('authoring/multilingual-site.md');
        (new SchemaRepository)->assertValid($multilingual, 'site.schema.json');
        $multilingualRegistry = LocaleRegistry::fromSite($multilingual);
        self::assertCount(3, $multilingualRegistry->all());
        self::assertSame('rtl', $multilingualRegistry->get('ar')->direction);

        $language = $this->firstJsonExample('authoring/language-packs.md');
        (new SchemaRepository)->assertValid($language, 'lang.schema.json');
        self::assertSame('docara.lang.v1', $language['schema']);

        $temporary = sys_get_temp_dir() . '/docara-documented-content-lang-' . bin2hex(random_bytes(8));
        self::assertTrue(mkdir($temporary . '/content/fr-CA', 0700, true));
        self::assertTrue(mkdir($temporary . '/content/en', 0700, true));
        try {
            file_put_contents(
                $temporary . '/content/fr-CA/lang.json',
                json_encode($language, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            );
            file_put_contents($temporary . '/content/en/lang.json', json_encode([
                'schema' => 'docara.lang.v1',
                'version' => 1,
                'navigation' => ['open' => 'Open navigation'],
            ], JSON_THROW_ON_ERROR));
            $registry = LocaleRegistry::fromSite([
                'default_locale' => 'en',
                'locales' => [
                    'en' => [
                        'label' => 'English',
                        'direction' => 'ltr',
                        'content_root' => 'content/en',
                        'public_prefix' => 'en',
                        'fallbacks' => [],
                    ],
                    'fr-CA' => [
                        'label' => 'Français (Canada)',
                        'direction' => 'ltr',
                        'content_root' => 'content/fr-CA',
                        'public_prefix' => 'fr-ca',
                        'fallbacks' => ['en'],
                    ],
                ],
            ]);
            $translator = new Translator($registry, new ContentLanguageRepository($temporary));
            self::assertSame('Continuer', $translator->message('fr-CA', 'common.continue'));
            self::assertSame(
                'Open navigation',
                $translator->message('fr-CA', 'navigation.open'),
                'The documented partial content lang file must resolve missing UI messages through explicit fallback.',
            );
        } finally {
            (new Filesystem)->deleteDirectory($temporary);
        }
    }

    #[Test]
    public function documented_multilingual_site_builds_all_locale_roots(): void
    {
        $temporary = sys_get_temp_dir() . '/docara-documented-multilingual-' . bin2hex(random_bytes(8));
        $filesystem = new Filesystem;
        try {
            $filesystem->copyDirectory($this->repositoryRoot() . '/stubs/portable', $temporary);
            $resolvedTemporary = realpath($temporary);
            self::assertIsString($resolvedTemporary);
            $temporary = $resolvedTemporary;
            $filesystem->deleteDirectory($temporary . '/content');
            file_put_contents(
                $temporary . '/docara.json',
                json_encode(
                    $this->firstJsonExample('authoring/multilingual-site.md'),
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
                ) . "\n",
            );
            foreach (['ru' => 'Документация', 'en' => 'Documentation', 'ar' => 'التوثيق'] as $locale => $title) {
                self::assertTrue(mkdir($temporary . '/content/' . $locale . '/guide', 0700, true));
                file_put_contents($temporary . '/content/' . $locale . '/index.md', "# $title\n");
                file_put_contents($temporary . '/content/' . $locale . '/guide/install.md', "# Install $locale\n");
                if ($locale === 'ru') {
                    self::assertTrue(mkdir($temporary . '/content/ru/components', 0700, true));
                    copy(
                        $this->repositoryRoot() . '/stubs/portable/content/ru/components.md',
                        $temporary . '/content/ru/components.md',
                    );
                    copy(
                        $this->repositoryRoot() . '/stubs/portable/content/ru/lang.json',
                        $temporary . '/content/ru/lang.json',
                    );
                    foreach (['alert', 'backlinks', 'badge', 'banner', 'button', 'card', 'code', 'code-from-file', 'details', 'diagram', 'download', 'embed', 'example', 'figure', 'footnotes-and-sources', 'grid', 'headings-and-text', 'hero', 'html', 'icon', 'kbd', 'links-and-images', 'lists-and-quotes', 'logos', 'math', 'media', 'steps', 'table', 'tabs', 'tree'] as $slug) {
                        copy(
                            $this->repositoryRoot() . "/stubs/portable/content/ru/components/$slug.md",
                            $temporary . "/content/ru/components/$slug.md",
                        );
                    }
                } else {
                    copy(
                        $this->repositoryRoot() . "/tests/fixtures/m5-multilingual-site/content/$locale/lang.json",
                        $temporary . "/content/$locale/lang.json",
                    );
                }
            }
            foreach ([
                'ru' => [
                    ['id' => 'home', 'label' => 'Главная', 'href' => '/ru/'],
                    ['id' => 'guide', 'label' => 'Руководство', 'href' => '/ru/guide/install/'],
                ],
                'en' => [
                    ['id' => 'home', 'label' => 'Home', 'href' => '/en/'],
                    ['id' => 'guide', 'label' => 'Guide', 'href' => '/en/guide/install/'],
                    ['id' => 'github', 'label' => 'GitHub', 'href' => 'https://github.com/simai/docara'],
                ],
                'ar' => [
                    ['id' => 'home', 'label' => 'الرئيسية', 'href' => '/ar/'],
                ],
            ] as $locale => $items) {
                file_put_contents(
                    $temporary . '/content/' . $locale . '/section.json',
                    json_encode([
                        'schema' => 'docara.section.v1',
                        'header_navigation' => ['enabled' => true, 'items' => $items],
                    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . "\n",
                );
            }

            (new PortableSiteBuilder(
                $filesystem,
                new PortableMarkdownRenderer,
            ))->build($temporary, $temporary . '/build_test');

            self::assertFileExists($temporary . '/build_test/index.html');
            self::assertFileExists($temporary . '/build_test/en/index.html');
            self::assertFileExists($temporary . '/build_test/ar/index.html');
            $arabic = (string) file_get_contents($temporary . '/build_test/ar/index.html');
            self::assertStringContainsString('lang="ar"', $arabic);
            self::assertStringContainsString('dir="rtl"', $arabic);
            foreach (['ru' => 2, 'en' => 3, 'ar' => 1] as $locale => $expectedItems) {
                $html = (string) file_get_contents($temporary . '/build_test/' . $locale . '/index.html');
                self::assertSame(
                    1,
                    preg_match('/<nav class="docara-navigation docara-header-navigation".*?<\/nav>/s', $html, $match),
                );
                self::assertSame($expectedItems, substr_count($match[0], '<a '));
            }
        } finally {
            $filesystem->deleteDirectory($temporary);
        }
    }

    #[Test]
    public function optional_sidecars_update_path_and_composition_registration_are_explicit(): void
    {
        $projectFiles = (string) file_get_contents($this->contentRoot() . '/authoring/project-files.md');
        self::assertMatchesRegularExpression('/page\.json.{0,120}необязател/uis', $projectFiles);
        self::assertStringContainsString('соберётся без `install.page.json`', $projectFiles);

        $update = (string) file_get_contents($this->contentRoot() . '/build/update.md');
        self::assertStringContainsString('init --update', $update);
        self::assertStringContainsString('update --verify', $update);
        self::assertStringContainsString('update --dry-run', $update);
        self::assertStringContainsString('update --apply', $update);
        self::assertStringContainsString('update --rollback=latest', $update);
        self::assertStringContainsString('ownership manifest', $update);
        self::assertStringContainsString('composer.lock', $update);
        self::assertStringContainsString('Rollback', $update);
        self::assertStringContainsString('hash-bound plan', $update);

        $composition = (string) file_get_contents(
            $this->contentRoot() . '/development/composition-extensions.md',
        );
        foreach (['Layout', 'Section', 'Block', 'View Tree', 'Smart', 'DesignRegistry', 'design/', 'docara preview'] as $term) {
            self::assertStringContainsString($term, $composition);
        }
        self::assertStringContainsString('build.purpose=preview', $composition);
        self::assertStringContainsString('PREVIEW_BUILD_PURPOSE_FORBIDDEN', $composition);
        self::assertStringNotContainsString('accepted_build_receipt=false', $composition);
        self::assertStringNotContainsString('DefinitionRepository::DEFINITIONS', $composition);
        self::assertStringNotContainsString('Расширьте `RegionCompositionResolver`', $composition);

        $smart = (string) file_get_contents($this->contentRoot() . '/development/smart-components.md');
        foreach (['docara.brand', 'docara.navigation', 'docara.toc', 'SmartComponentGateway', 'project.notice'] as $term) {
            self::assertStringContainsString($term, $smart);
        }
        foreach (['DocaraSmartContribution', 'FrameworkSmartContribution', 'ViewModelFactory'] as $obsolete) {
            self::assertStringNotContainsString($obsolete, $smart . $composition);
        }
    }

    #[Test]
    public function every_authored_json_fence_is_valid_json(): void
    {
        foreach ($this->markdownDocuments() as $path) {
            $contents = (string) file_get_contents($path);
            preg_match_all('/```json\h*\R(.*?)\R```/su', $contents, $matches);
            foreach ($matches[1] as $index => $example) {
                try {
                    $decoded = json_decode($example, true, 512, JSON_THROW_ON_ERROR);
                    self::assertIsArray($decoded);
                } catch (\JsonException $exception) {
                    self::fail(sprintf(
                        '%s JSON example %d is invalid: %s',
                        $this->relativeToRepository($path),
                        $index + 1,
                        $exception->getMessage(),
                    ));
                }
            }
        }
    }

    #[Test]
    public function executable_shell_fences_do_not_contain_reference_placeholders(): void
    {
        foreach ($this->markdownDocuments() as $path) {
            $contents = (string) file_get_contents($path);
            preg_match_all('/```(?:bash|shell|sh|zsh|console)\h*\R(.*?)\R```/su', $contents, $matches);
            foreach ($matches[1] as $index => $example) {
                self::assertDoesNotMatchRegularExpression(
                    '/\[(?:environment|--no-build)\]|<exact-[^>]+>|\/absolute\/path\//u',
                    $example,
                    sprintf(
                        '%s shell example %d contains a non-executable placeholder; use a text fence for syntax notation.',
                        $this->relativeToRepository($path),
                        $index + 1,
                    ),
                );
            }
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
                '/\bSF5\b(?!\.smart\.artifact\.v1)/ui',
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

    /** @return array<string, string> */
    private function documentationInventory(): array
    {
        $inventory = [];
        $process = proc_open(
            ['git', '-C', $this->repositoryRoot(), 'ls-files', '-z'],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
        );
        self::assertIsResource($process);
        $tracked = stream_get_contents($pipes[1]);
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        self::assertSame(0, proc_close($process), (string) $error);

        foreach (array_filter(explode("\0", (string) $tracked)) as $relative) {
            if (str_starts_with($relative, 'docs/site/build_')) {
                continue;
            }
            $path = $this->repositoryRoot() . '/' . $relative;
            $inventory[$relative] = str_ends_with(strtolower($relative), '.md')
                && (str_starts_with($relative, 'docs/') || in_array($relative, ['README.md', 'CONTRIBUTING.md'], true))
                ? (string) file_get_contents($path)
                : '';
        }

        return $inventory;
    }

    /** @param array<string, mixed> $schema @return list<string> */
    private function langNamespaces(array $schema): array
    {
        $namespaces = array_keys(array_filter(
            is_array($schema['properties'] ?? null) ? $schema['properties'] : [],
            static fn (mixed $_value, string $key): bool => ! in_array($key, ['schema', 'version'], true),
            ARRAY_FILTER_USE_BOTH,
        ));
        sort($namespaces, SORT_STRING);

        return $namespaces;
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
        if (str_contains($target, 'components/')) {
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

    /** @return array<string, mixed> */
    private function firstJsonExample(string $relative): array
    {
        $contents = (string) file_get_contents($this->contentRoot() . '/' . $relative);
        self::assertMatchesRegularExpression('/```json\h*\R.*?\R```/su', $contents, $relative);
        preg_match('/```json\h*\R(.*?)\R```/su', $contents, $match);
        $decoded = json_decode($match[1], true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($decoded, $relative);

        return $decoded;
    }

    private function relativeToRepository(string $path): string
    {
        return ltrim(str_replace($this->repositoryRoot(), '', $path), '/');
    }

    private function contentRoot(): string
    {
        return $this->repositoryRoot() . '/docs/site/content/ru';
    }

    private function repositoryRoot(): string
    {
        return dirname(__DIR__, 2);
    }
}
