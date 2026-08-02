<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use Simai\Docara\Declarative\Document\SmartCallNode;
use Simai\Docara\Declarative\Document\SourceSpan;
use Simai\Docara\Declarative\Rendering\SmartRenderer;
use Simai\Docara\Declarative\Rendering\TrustedTemplateRegistry;
use Simai\Docara\Declarative\Smart\SmartComponentGateway;
use Simai\Docara\Smart\Artifact\Sf5SmartArtifactV1Contract;
use Simai\Docara\Smart\SmartRegistry;
use Symfony\Component\Process\Process;

final class Sf5CrossHostSmartCompatibilityTest extends TestCase
{
    private string $temporaryRoot;

    protected function setUp(): void
    {
        $this->temporaryRoot = sys_get_temp_dir() . '/docara-sf5-cross-host-' . bin2hex(random_bytes(8));
        self::assertTrue(mkdir($this->temporaryRoot, 0700, true));
    }

    protected function tearDown(): void
    {
        if (! isset($this->temporaryRoot) || ! is_dir($this->temporaryRoot)) {
            return;
        }
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->temporaryRoot, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($iterator as $item) {
            if ($item->isLink() || $item->isFile()) {
                unlink($item->getPathname());
            } else {
                rmdir($item->getPathname());
            }
        }
        rmdir($this->temporaryRoot);
    }

    public function test_one_unchanged_artifact_exposes_the_exact_sf5_view_context_blocker(): void
    {
        $projectRoot = dirname(__DIR__, 2);
        $artifactRoot = realpath(dirname(__DIR__) . '/fixtures/smart/portable');
        self::assertIsString($artifactRoot);
        $source = $this->sourceContract($projectRoot);
        $sf5Repository = getenv('DOCARA_SF5_SOURCE_REPO');
        if (! is_string($sf5Repository) || $sf5Repository === '') {
            $sf5Repository = dirname($projectRoot) . '/bx-simai.main';
        }
        if (! is_dir($sf5Repository . '/.git')) {
            self::markTestSkipped('Set DOCARA_SF5_SOURCE_REPO to an exact bx-simai.main Git checkout.');
        }

        $this->assertPinnedBlobs($sf5Repository, $source);
        $sf5Root = $this->exportExactRevision($sf5Repository, (string) $source['source_revision']);
        $docara = $this->renderWithDocara($artifactRoot);
        $sf5 = $this->renderWithSf5($sf5Root, $artifactRoot);

        self::assertSame('<aside data-fixture-notice data-view="default" data-preset="compact" data-slot="content"><strong>Portable title</strong><p>Portable text</p></aside>', $docara['html']);
        self::assertSame('<aside data-fixture-notice data-view="" data-preset="compact" data-slot=""><strong>Portable title</strong><p>Portable text</p></aside>', $sf5['html']);
        self::assertNotSame($docara['html'], $sf5['html']);
        self::assertStringContainsString('Portable title', $docara['html']);
        self::assertStringContainsString('Portable title', $sf5['html']);
        self::assertStringContainsString('Portable text', $docara['html']);
        self::assertStringContainsString('Portable text', $sf5['html']);
        self::assertSame('sf5.smart.template.v1', $docara['hydration']['template_abi']);
        self::assertSame('server-static', $docara['hydration']['render']['strategy']);
        self::assertSame('server-static', $sf5['hydration']['nodes'][0]['render']['strategy']);
        self::assertContains('assets/notice.css', $sf5['assets']['css']);
        self::assertContains('simai.ui', $sf5['assets']['depends']);
        self::assertNotEmpty($sf5['resolvedArtifacts']);

        $reportPath = getenv('DOCARA_SF5_CROSS_HOST_REPORT');
        if (is_string($reportPath) && $reportPath !== '') {
            $report = [
                'schema' => 'docara.sf5_cross_host_report.v2',
                'source_revision' => $source['source_revision'],
                'artifact' => [
                    'path' => 'tests/fixtures/smart/portable/fixture.notice',
                    'tree_sha256' => $this->treeHash($artifactRoot . '/fixture.notice'),
                ],
                'docara' => $docara,
                'sf5' => $sf5,
                'comparison' => [
                    'html_byte_equal' => $docara['html'] === $sf5['html'],
                    'full_context_compatible' => false,
                    'docara_html_sha256' => hash('sha256', $docara['html']),
                    'sf5_html_sha256' => hash('sha256', $sf5['html']),
                    'docara_normalized_html_sha256' => hash('sha256', $this->normalizeHtml($docara['html'])),
                    'sf5_normalized_html_sha256' => hash('sha256', $this->normalizeHtml($sf5['html'])),
                    'title_text_present_both' => str_contains($docara['html'], 'Portable title')
                        && str_contains($docara['html'], 'Portable text')
                        && str_contains($sf5['html'], 'Portable title')
                        && str_contains($sf5['html'], 'Portable text'),
                    'selected_view' => [
                        'docara' => 'default',
                        'sf5' => null,
                    ],
                    'selected_preset' => [
                        'docara' => 'compact',
                        'sf5' => 'compact',
                    ],
                    'slot' => [
                        'docara' => 'content',
                        'sf5' => null,
                    ],
                    'render_strategy_equal' => $docara['hydration']['render']['strategy']
                        === $sf5['hydration']['nodes'][0]['render']['strategy'],
                    'blockers' => [
                        'exact_sf5_resolved_view_record_overwritten_before_template',
                        'exact_sf5_render_shortcut_does_not_forward_slot_as_node_field',
                    ],
                ],
            ];
            self::assertNotFalse(file_put_contents(
                $reportPath,
                json_encode($report, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n",
            ));
        }
    }

    public function test_minimal_host_patch_restores_the_full_fixture_context_in_a_disposable_export(): void
    {
        $projectRoot = dirname(__DIR__, 2);
        $artifactRoot = realpath(dirname(__DIR__) . '/fixtures/smart/portable');
        self::assertIsString($artifactRoot);
        $source = $this->sourceContract($projectRoot);
        $sf5Repository = getenv('DOCARA_SF5_SOURCE_REPO');
        if (! is_string($sf5Repository) || $sf5Repository === '') {
            $sf5Repository = dirname($projectRoot) . '/bx-simai.main';
        }
        if (! is_dir($sf5Repository . '/.git')) {
            self::markTestSkipped('Set DOCARA_SF5_SOURCE_REPO to an exact bx-simai.main Git checkout.');
        }

        $this->assertPinnedBlobs($sf5Repository, $source);
        $sf5Root = $this->exportExactRevision($sf5Repository, (string) $source['source_revision']);
        $this->applyProposedHostContextPatch($sf5Root);
        $docara = $this->renderWithDocara($artifactRoot);
        $patchedSf5 = $this->renderWithSf5($sf5Root, $artifactRoot);

        self::assertSame($docara['html'], $patchedSf5['html']);
        self::assertSame('content', $patchedSf5['hydration']['nodes'][0]['slot']);
        self::assertSame('', $patchedSf5['stderr']);
        self::assertSame([], $patchedSf5['warnings']);
    }

    /** @return array<string, mixed> */
    private function renderWithDocara(string $artifactRoot): array
    {
        $registry = SmartRegistry::withProject('fixture', $artifactRoot, 'fixture-revision-v1');
        $gateway = SmartComponentGateway::withProject(
            $registry,
            'project.fixture',
            $this->json(dirname(__DIR__, 2) . '/stubs/portable/simai-framework.lock.json'),
        );
        $call = new SmartCallNode(
            'fixture-notice',
            'fixture.notice',
            'default',
            [
                'preset' => 'compact',
                'title' => 'Portable title',
                'text' => 'Portable text',
            ],
            1,
            new SourceSpan('content/fixture.md', 1, 4),
            'content',
        );
        $renderer = new SmartRenderer(new TrustedTemplateRegistry(smarts: $registry));
        $warnings = [];
        set_error_handler(static function (int $severity, string $message) use (&$warnings): never {
            $warnings[] = [$severity, $message];
            throw new \ErrorException($message, 0, $severity);
        });
        try {
            $artifact = $renderer->render($gateway->resolve($call));
        } finally {
            restore_error_handler();
        }
        self::assertSame([], $warnings);

        return [
            'html' => $artifact->html,
            'assets' => $artifact->assets,
            'hydration' => $artifact->hydration,
            'provenance' => [
                'provider' => $artifact->provenance['provider'] ?? null,
                'provider_revision' => $artifact->provenance['provider_revision'] ?? null,
                'contract' => $artifact->provenance['contract'] ?? null,
                'template_abi' => $artifact->provenance['template_abi'] ?? null,
            ],
            'exit_code' => 0,
            'stderr' => '',
            'warnings' => $warnings,
        ];
    }

    /** @return array<string, mixed> */
    private function renderWithSf5(string $sf5Root, string $artifactRoot): array
    {
        $runner = $this->temporaryRoot . '/render-sf5.php';
        $source = <<<'PHP'
<?php
declare(strict_types=1);
set_error_handler(static function (int $severity, string $message, string $file, int $line): never {
    throw new ErrorException($message, 0, $severity, $file, $line);
});
$sf5Root = $argv[1];
$artifactRoot = $argv[2];
spl_autoload_register(static function (string $class) use ($sf5Root): void {
    $prefix = 'Simai\\Main\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $file = $sf5Root . '/local/modules/simai.main/lib/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});
require_once $sf5Root . '/local/modules/simai.main/lib/UI/Smart.php';
$options = [
    'artifactRoots' => [$artifactRoot],
    'id' => 'fixture-notice',
    'view' => 'default',
    'preset' => 'compact',
    'slot' => 'content',
    'props' => [
        'title' => 'Portable title',
        'text' => 'Portable text',
    ],
];
$shortcutHtml = Simai\Main\UI\Smart::render('fixture.notice', $options);
$result = Simai\Main\UI\Smart::tree([
    'schemaVersion' => '1.0',
    'kind' => 'smart.tree',
    'root' => [
        'id' => 'fixture-notice',
        'smart' => 'fixture.notice',
        'view' => 'default',
        'preset' => 'compact',
        'slot' => 'content',
        'props' => [
            'title' => 'Portable title',
            'text' => 'Portable text',
        ],
    ],
], ['artifactRoots' => [$artifactRoot]]);
$result['treeHtml'] = $result['html'];
$result['html'] = $shortcutHtml;
echo json_encode($result, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
PHP;
        self::assertNotFalse(file_put_contents($runner, $source));
        $process = new Process([PHP_BINARY, $runner, $sf5Root, $artifactRoot]);
        $process->setTimeout(30);
        $process->run();
        self::assertSame(0, $process->getExitCode(), $process->getErrorOutput());
        self::assertSame('', $process->getErrorOutput());

        $result = json_decode($process->getOutput(), true, 512, JSON_THROW_ON_ERROR);
        foreach (array_keys($result['resolvedArtifacts'] ?? []) as $index) {
            $artifact = &$result['resolvedArtifacts'][$index];
            if (is_array($artifact) && is_string($artifact['path'] ?? null)) {
                $artifact['path'] = ltrim(substr($artifact['path'], strlen($artifactRoot)), '/');
                self::assertFalse(str_starts_with($artifact['path'], '/'));
            }
            unset($artifact);
        }
        $result['exit_code'] = $process->getExitCode();
        $result['stderr'] = $process->getErrorOutput();
        $result['warnings'] = [];

        return $result;
    }

    /** @param array<string, mixed> $source */
    private function assertPinnedBlobs(string $repository, array $source): void
    {
        $revision = (string) $source['source_revision'];
        foreach ($source['tracked_files'] as $record) {
            self::assertIsArray($record);
            $process = new Process(['git', '-C', $repository, 'show', $revision . ':' . $record['path']]);
            $process->setTimeout(30);
            $process->run();
            self::assertSame(0, $process->getExitCode(), $process->getErrorOutput());
            self::assertSame($record['sha256'], hash('sha256', $process->getOutput()), (string) $record['path']);
        }
    }

    private function exportExactRevision(string $repository, string $revision): string
    {
        $archive = $this->temporaryRoot . '/sf5.tar';
        $root = $this->temporaryRoot . '/sf5';
        self::assertTrue(mkdir($root, 0700));
        $archiveProcess = new Process(['git', '-C', $repository, 'archive', '--format=tar', '--output=' . $archive, $revision]);
        $archiveProcess->setTimeout(60);
        $archiveProcess->run();
        self::assertSame(0, $archiveProcess->getExitCode(), $archiveProcess->getErrorOutput());
        $extractProcess = new Process(['/usr/bin/tar', '-xf', $archive, '-C', $root]);
        $extractProcess->setTimeout(60);
        $extractProcess->run();
        self::assertSame(0, $extractProcess->getExitCode(), $extractProcess->getErrorOutput());

        return $root;
    }

    private function applyProposedHostContextPatch(string $sf5Root): void
    {
        $smartPath = $sf5Root . '/local/modules/simai.main/lib/UI/Smart.php';
        $source = (string) file_get_contents($smartPath);
        $replacements = [
            "            'children' => true,\n" => "            'children' => true,\n            'slot' => true,\n",
            "        foreach (['view', 'preset', 'children'] as \$key) {" => "        foreach (['view', 'preset', 'children', 'slot'] as \$key) {",
            "        \$view = (string)(\$node['view'] ?? '');\n        \$slot = (string)(\$node['slot'] ?? '');" => "        \$slot = (string)(\$node['slot'] ?? '');",
        ];
        foreach ($replacements as $before => $after) {
            self::assertSame(1, substr_count($source, $before));
            $source = str_replace($before, $after, $source);
        }
        self::assertNotFalse(file_put_contents($smartPath, $source));
    }

    /** @return array<string,mixed> */
    private function sourceContract(string $projectRoot): array
    {
        $source = $this->json($projectRoot . '/resources/contracts/sf5/smart/v1/source.json');
        self::assertSame(Sf5SmartArtifactV1Contract::SOURCE_REVISION, $source['source_revision']);

        return $source;
    }

    /** @return array<string,mixed> */
    private function json(string $path): array
    {
        $value = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($value);

        return $value;
    }

    private function normalizeHtml(string $html): string
    {
        return preg_replace('/>\s+</', '><', trim($html)) ?? trim($html);
    }

    private function treeHash(string $root): string
    {
        $records = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
        );
        foreach ($iterator as $file) {
            if ($file->isFile() && ! $file->isLink()) {
                $relative = ltrim(substr($file->getPathname(), strlen($root)), '/');
                $records[] = $relative . "\0" . hash_file('sha256', $file->getPathname());
            }
        }
        sort($records, SORT_STRING);

        return hash('sha256', implode("\n", $records));
    }
}
