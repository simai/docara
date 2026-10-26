<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Simai\Docara\Application\CapabilitiesService;
use Simai\Docara\Application\DiscoveryService;
use Simai\Docara\Application\McpAdapter;
use Simai\Docara\Application\SdkServiceFactory;
use Simai\Docara\Application\ValidationService;
use Simai\Docara\Authoring\DocumentationStandard;
use Simai\Docara\Authoring\ProfileInference;
use Simai\Docara\Console\ApplicationFactory;
use Simai\Docara\File\Filesystem;
use Simai\Docara\PortableSite\PortableProjectInitializer;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\TestCase;

final class DocumentationStandardTest extends TestCase
{
    /** @return array<string, array{string, string, string, string}> */
    public static function inferenceCases(): array
    {
        $steps = "# Task\n\nIntro.\n\n1. One.\n2. Two.\n3. Three.\n";
        $table = "# Options\n\n| Name | Value |\n| --- | --- |\n| a | 1 |\n| b | 2 |\n| c | 3 |\n";

        return [
            'home page' => ['index.md', $steps, 'landing', 'index_page'],
            'section index' => ['guide/index.md', $table, 'landing', 'index_page'],
            'tutorial directory' => ['getting-started/install.md', "# Install\n\nText.\n", 'tutorial', 'path:getting-started'],
            'how-to directory' => ['how-to/deploy.md', "# Deploy\n\nText.\n", 'how_to', 'path:how-to'],
            'reference file name' => ['docs/api.md', "# API\n\nText.\n", 'reference', 'path:api'],
            'concepts directory' => ['concepts/cache.md', $steps, 'explanation', 'path:concepts'],
            'three numbered steps' => ['deploy.md', $steps, 'how_to', 'ordered_steps'],
            'two numbered items are not steps' => ['notes.md', "# Notes\n\nSome prose here.\n\nMore prose.\n\n1. One.\n2. Two.\n", 'article', 'fallback'],
            'mostly a table' => ['options.md', $table, 'reference', 'structured_content'],
            'mostly a list' => ['terms.md', "# Terms\n\nShort intro.\n\n- a: one\n- b: two\n- c: three\n", 'reference', 'structured_content'],
            'definitions' => ['glossary-terms.md', "# Terms\n\nBuild\n: Makes HTML.\n\nServe\n: Opens it.\n", 'reference', 'structured_content'],
            'steps inside code do not count' => ['code.md', "# Code\n\nProse line.\n\n```text\n1. a\n2. b\n3. c\n```\n", 'article', 'fallback'],
            'prose' => ['story.md', "# Story\n\nOne paragraph.\n\n## Part\n\nAnother paragraph.\n", 'article', 'fallback'],
        ];
    }

    #[Test]
    #[DataProvider('inferenceCases')]
    public function the_profile_is_inferred_deterministically(string $path, string $markdown, string $profile, string $reason): void
    {
        $inference = new ProfileInference;

        self::assertSame(['profile' => $profile, 'reason' => $reason], $inference->infer($path, $markdown));
        self::assertSame($inference->infer($path, $markdown), $inference->infer($path, $markdown));
    }

    #[Test]
    public function validate_page_marks_an_inferred_profile_as_advice_and_explicit_profiles_win(): void
    {
        $this->starter('en');
        $this->filesystem->put($this->tmpPath('content/en/deploy.md'), "# Deploy\n\n1. Build.\n2. Check.\n3. Copy.\n");

        $checks = (new ValidationService)->validate($this->tmp, 'page', '/deploy/')->toArray();
        self::assertSame(0, $checks['exit_code']);
        $codes = array_column($checks['data']['checks'], null, 'code');
        self::assertSame('how_to', $codes['PAGE_PROFILE_INFERRED']['profile']);
        self::assertSame('ordered_steps', $codes['PAGE_PROFILE_INFERRED']['reason']);
        self::assertSame('advisory', $codes['PAGE_PROFILE_INFERRED']['severity']);
        $inspection = (new DiscoveryService)->inspect($this->tmp, 'page', '/deploy/')->toArray()['data'];
        self::assertSame('inferred', $inspection['profile']['source']);
        self::assertSame('ordered_steps', $inspection['profile']['inferred_from']);

        // A structural miss of an inferred profile is advice too.
        $this->filesystem->put($this->tmpPath('content/en/index.md'), "# Home\n\nWelcome.\n");
        foreach ((new ValidationService)->validate($this->tmp, 'page', '/')->toArray()['data']['checks'] as $check) {
            self::assertNotSame('warning', $check['severity'] ?? null, $check['code']);
        }

        // Front matter wins over the guess.
        $this->filesystem->put($this->tmpPath('content/en/deploy.md'), "---\nprofile: tutorial\n---\n\n# Deploy\n\n1. Build.\n2. Check.\n3. Copy.\n");
        $inspection = (new DiscoveryService)->inspect($this->tmp, 'page', '/deploy/')->toArray()['data'];
        self::assertSame(['tutorial', 'front_matter'], [$inspection['profile']['id'], $inspection['profile']['source']]);
        self::assertNotContains('PAGE_PROFILE_INFERRED', array_column($inspection['diagnostics'], 'code'));

        // Inference can be switched off in docara.authoring.json.
        $authoring = json_decode((string) file_get_contents($this->tmpPath('docara.authoring.json')), true, 512, JSON_THROW_ON_ERROR);
        $authoring['infer_profiles'] = false;
        $this->filesystem->put($this->tmpPath('docara.authoring.json'), json_encode($authoring, JSON_THROW_ON_ERROR));
        $item = array_values(array_filter((new DiscoveryService)->list($this->tmp, 'page')->toArray()['data']['items'], static fn (array $page): bool => $page['route'] === '/'))[0];
        self::assertSame([null, 'none'], [$item['profile'], $item['profile_source']]);

        // Without docara.authoring.json nothing is inferred.
        $this->filesystem->delete($this->tmpPath('docara.authoring.json'));
        $this->filesystem->put($this->tmpPath('content/en/deploy.md'), "# Deploy\n\n1. Build.\n2. Check.\n3. Copy.\n");
        $checks = (new ValidationService)->validate($this->tmp, 'page', '/deploy/')->toArray()['data']['checks'];
        self::assertSame(['PAGE_MARKDOWN_VALID'], array_column($checks, 'code'));
    }

    #[Test]
    public function scaffold_page_creates_a_typed_page_in_one_step(): void
    {
        $this->starter('en');
        $application = ApplicationFactory::create($this->tmp);

        $created = $this->execute($application, ['kind' => 'page', '--type' => 'how_to', '--title' => 'Rotate the API keys']);
        self::assertSame('scaffold.apply', $created['operation']);
        self::assertSame(['content/en/how-to/rotate-the-api-keys.md'], $created['data']['created']);
        $page = (string) file_get_contents($this->tmpPath('content/en/how-to/rotate-the-api-keys.md'));
        self::assertSame((new DocumentationStandard)->page('how_to', 'en', 'Rotate the API keys'), $page);
        self::assertStringStartsWith("---\ntitle: \"Rotate the API keys\"\ndescription: \"", $page);
        self::assertStringContainsString("draft: true\nprofile: how_to\n---\n\n# Rotate the API keys\n", $page);
        foreach (['## Before you start', '## Steps', '1. ', '## Result', '## See also'] as $part) {
            self::assertStringContainsString($part, $page);
        }

        // The page passes the structural checks of its type.
        $codes = array_column((new ValidationService)->validate($this->tmp, 'page', '/how-to/rotate-the-api-keys/')->toArray()['data']['checks'], 'code');
        self::assertSame(['PAGE_MARKDOWN_VALID', 'PAGE_EDITORIAL_REVIEW_REQUIRED'], $codes);

        // An explicit route; a type without a skeleton directory goes to the root.
        $this->execute($application, ['kind' => 'page', 'id' => 'guides/install', '--profile' => 'tutorial', '--title' => 'Install']);
        self::assertFileExists($this->tmpPath('content/en/guides/install.md'));
        $this->execute($application, ['kind' => 'page', '--type' => 'article', '--title' => 'Release notes']);
        self::assertFileExists($this->tmpPath('content/en/release-notes.md'));

        // --dry-run keeps the reviewed two-step flow.
        $plan = $this->execute($application, ['kind' => 'page', '--type' => 'reference', '--title' => 'Options', '--dry-run' => true]);
        self::assertSame('scaffold.plan', $plan['operation']);
        self::assertFileDoesNotExist($this->tmpPath('content/en/reference/options.md'));

        // The same page twice, or two different types, is refused.
        $tester = new CommandTester($application->find('scaffold'));
        self::assertNotSame(0, $tester->execute(['kind' => 'page', '--type' => 'how_to', '--title' => 'Rotate the API keys', '--json' => true]));
        self::assertNotSame(0, $tester->execute(['kind' => 'page', '--type' => 'how_to', '--profile' => 'reference', '--title' => 'X', '--json' => true]));
        // Smart and design scaffolds still require a reviewed dry-run.
        self::assertNotSame(0, $tester->execute(['kind' => 'smart', 'id' => 'project.card', '--json' => true]));
    }

    #[Test]
    public function scaffold_page_writes_the_template_in_the_locale_language(): void
    {
        $this->starter('ru');
        $application = ApplicationFactory::create($this->tmp);

        $this->execute($application, ['kind' => 'page', '--type' => 'how_to', '--title' => 'Опубликовать сайт']);

        $page = (string) file_get_contents($this->tmpPath('content/ru/how-to/opublikovat-sajt.md'));
        foreach (['title: "Опубликовать сайт"', 'profile: how_to', '## Перед началом', '## Шаги', '## Результат', '## См. также'] as $part) {
            self::assertStringContainsString($part, $page);
        }
        self::assertSame('en', DocumentationStandard::language('de-DE'));
        self::assertSame('ru', DocumentationStandard::language('ru-RU'));
    }

    #[Test]
    public function every_template_meets_the_structure_of_its_type(): void
    {
        $this->starter('en');
        $this->filesystem->ensureDirectoryExists($this->tmpPath('content/en/templates'));
        $pages = [];
        foreach (['en', 'ru'] as $language) {
            foreach (['landing', 'article', 'tutorial', 'how_to', 'reference', 'explanation'] as $profile) {
                $route = 'templates/' . $language . '-' . str_replace('_', '-', $profile);
                $this->filesystem->put(
                    $this->tmpPath('content/en/' . $route . '.md'),
                    (new DocumentationStandard)->page($profile, $language, 'Title', draft: false),
                );
                $pages[] = [$language, $profile, $route];
            }
        }
        foreach ($pages as [$language, $profile, $route]) {
            $codes = array_column((new ValidationService)->validate($this->tmp, 'page', '/' . $route . '/')->toArray()['data']['checks'], 'code');
            self::assertSame(['PAGE_MARKDOWN_VALID', 'PAGE_EDITORIAL_REVIEW_REQUIRED'], $codes, "$language $profile");
        }
    }

    #[Test]
    public function capabilities_and_mcp_expose_the_same_standard(): void
    {
        $this->starter('en');
        $standard = (new DocumentationStandard)->toArray();

        $capabilities = (new CapabilitiesService(dirname(__DIR__, 2), '3.8.0', 'fixture'))->capabilities($this->tmp, ApplicationFactory::create($this->tmp))->data;
        self::assertSame($standard, $capabilities['documentation_standard']);
        self::assertSame(DocumentationStandard::SCHEMA, $standard['schema']);
        self::assertSame(['landing', 'article', 'tutorial', 'how_to', 'reference', 'explanation'], array_keys($standard['page_types']));
        self::assertSame(['Before you start', 'Steps', 'Result', 'See also'], $standard['page_types']['how_to']['sections']);
        self::assertContains('a numbered list of at least two steps', $standard['page_types']['how_to']['required_parts']);

        $adapter = new McpAdapter(SdkServiceFactory::create(), $this->tmp);
        $initialize = $adapter->handle(['jsonrpc' => '2.0', 'id' => 1, 'method' => 'initialize']);
        self::assertStringContainsString('docara_authoring_standard', $initialize['result']['instructions']);
        $tool = array_values(array_filter(
            $adapter->handle(['jsonrpc' => '2.0', 'id' => 2, 'method' => 'tools/list'])['result']['tools'],
            static fn (array $tool): bool => $tool['name'] === 'docara_authoring_standard',
        ))[0];
        self::assertTrue($tool['annotations']['readOnlyHint']);
        $call = $adapter->handle(['jsonrpc' => '2.0', 'id' => 3, 'method' => 'tools/call', 'params' => ['name' => 'docara_authoring_standard', 'arguments' => []]]);
        self::assertFalse($call['result']['isError']);
        $data = $call['result']['structuredContent']['data'];
        self::assertSame($standard['page_types'], $data['page_types']);
        self::assertSame(file_get_contents($this->tmpPath('AGENTS.md')), $data['agents_markdown']);
    }

    #[Test]
    public function agents_guide_says_which_diagram_type_to_pick_and_the_label_gotchas(): void
    {
        $agents = (new DocumentationStandard)->agentsMarkdown('docara', 'content/en', 'en');

        self::assertStringContainsString("## Diagrams\n", $agents);
        self::assertStringContainsString(':::diagram {engine=mermaid title="..."}', $agents);
        foreach (['flowchart', 'sequenceDiagram', 'stateDiagram-v2', 'erDiagram', 'C4Context', 'architecture-beta', 'gantt', 'pie', 'xychart-beta'] as $type) {
            self::assertStringContainsString('`' . $type . '`', $agents);
        }
        self::assertStringContainsString('Write `sankey-beta`' . "\n" . 'labels in Latin letters only', $agents);
        self::assertStringContainsString('non-Latin labels of `architecture-beta` and' . "\n" . '`xychart-beta` in double quotes', $agents);
        self::assertLessThan(
            strpos($agents, '## Create a page'),
            strpos($agents, '## Diagrams'),
        );
        self::assertStringContainsString("## Component parameters\n", $agents);
        self::assertStringContainsString('`MARKDOWN_PARAMETER_DEPRECATED`', $agents);
        self::assertStringContainsString('{title="app.php" highlight="2-3" numbers}', $agents);
        self::assertStringContainsString('`[!code --]`', $agents);
        self::assertLessThanOrEqual(100, substr_count($agents, "\n"));
    }

    private function starter(string $language): void
    {
        $files = new Filesystem;
        (new PortableProjectInitializer($files))->initialize($this->tmp, 'minimal', $language);
    }

    /** @param array<string, mixed> $arguments @return array<string, mixed> */
    private function execute(object $application, array $arguments): array
    {
        $tester = new CommandTester($application->find('scaffold'));
        $exit = $tester->execute($arguments + ['--json' => true]);
        self::assertSame(0, $exit, $tester->getDisplay());

        return json_decode($tester->getDisplay(), true, 512, JSON_THROW_ON_ERROR);
    }
}
