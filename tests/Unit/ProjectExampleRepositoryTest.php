<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\PortableSite\PortableMarkdownRenderer;
use Simai\Docara\PortableSite\ProjectExampleRepository;
use Tests\TestCase;

final class ProjectExampleRepositoryTest extends TestCase
{
    #[Test]
    public function it_renders_a_project_example_and_records_assets_and_consumers(): void
    {
        $this->createSource([
            'content/ru/page.md' => '# Page',
            'examples/utilities/card/index.html' => '<article><img src="assets/photo.png" alt=""></article>',
            'examples/utilities/card/index.css' => 'article { padding: 1rem; }',
            'examples/utilities/card/index.js' => 'document.body.dataset.ready = "true";',
            'examples/utilities/card/assets/photo.png' => "\x89PNG\r\n\x1a\nfixture",
        ]);
        $repository = new ProjectExampleRepository($this->tmp, '/docs/');
        $renderer = new PortableMarkdownRenderer(projectExamples: $repository);
        $html = $renderer->render(
            ":::example {id=\"utilities/card\" label=\"Result\"}\n:::\n",
            $this->tmp,
            $this->tmpPath('content/ru/page.md'),
        );

        self::assertStringContainsString('&lt;base href=&quot;/docs/_docara/examples/utilities/card/&quot;&gt;', $html);
        self::assertStringContainsString('data-docara-example-tab="html"', $html);
        self::assertStringContainsString('data-docara-example-tab="css"', $html);
        self::assertStringContainsString('data-docara-example-tab="javascript"', $html);
        self::assertSame([
            [
                'source' => $this->tmpPath('examples/utilities/card/assets/photo.png'),
                'relative' => '_docara/examples/utilities/card/assets/photo.png',
            ],
        ], $repository->publishedAssets());
        $receipt = $repository->receipt();
        self::assertSame('docara.example_receipt.v1', $receipt['schema']);
        self::assertSame(['content/ru/page.md'], $receipt['examples'][0]['consumers']);
        self::assertSame('auto', $receipt['previews'][0]['requested_preview']);
        self::assertSame('sandbox', $receipt['previews'][0]['resolved_preview']);
        self::assertSame('javascript', $receipt['previews'][0]['reason']);
        self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $receipt['previews'][0]['source_sha256']);
        self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $receipt['content_sha256']);
    }

    #[Test]
    public function an_html_only_project_example_renders_inline_inside_a_boundary_root(): void
    {
        $this->createSource([
            'content/ru/page.md' => '# Page',
            'examples/components/tooltip/index.html' => '<span id="save-tooltip" data-tooltip="save" class="sf-tooltip">Save</span>'
                . '<form class="grid"><input type="text" name="q"><button type="submit">Find</button></form>',
            'examples/components/script/index.html' => '<button type="button">Run</button>',
            'examples/components/script/index.js' => 'document.body.dataset.ready = "true";',
        ]);
        $repository = new ProjectExampleRepository($this->tmp, '/docs/');
        $renderer = new PortableMarkdownRenderer(projectExamples: $repository);
        $html = $renderer->render(
            ":::example {id=\"components/tooltip\" label=\"Result\"}\n:::\n\n:::example {id=\"components/script\" label=\"Result\"}\n:::\n",
            $this->tmp,
            $this->tmpPath('content/ru/page.md'),
        );

        self::assertStringContainsString(
            '<div data-docara-example-inline-preview="html" data-docara-example-source="components/tooltip" class="docara-example-inline">'
            . '<span id="save-tooltip" data-tooltip="save" class="sf-tooltip">Save</span><form class="grid">',
            $html,
        );
        self::assertSame(1, substr_count($html, '<iframe title="Result" data-docara-example-frame'));
        self::assertStringNotContainsString('data-docara-example-source="components/script"', $html);
        // The inline example also carries the hidden frame of the full-screen
        // viewer, which loads only when the reader opens it.
        self::assertSame(1, substr_count($html, '<iframe data-docara-example-responsive-frame '));
        $outsideFrames = (string) preg_replace('/\s(?:data-docara-)?srcdoc="[^"]*"/', '', $html);
        self::assertStringNotContainsString('<style', $outsideFrames);
        self::assertStringNotContainsString('<script', $outsideFrames);

        $reasons = [];
        foreach ($repository->receipt()['previews'] as $preview) {
            $reasons[$preview['id']] = [$preview['resolved_preview'], $preview['reason']];
        }
        ksort($reasons);
        self::assertSame([
            'markdown-example-' . substr(hash('sha256', 'components/script'), 0, 12) => ['sandbox', 'javascript'],
            'markdown-example-' . substr(hash('sha256', 'components/tooltip'), 0, 12) => ['inline', 'admitted_html'],
        ], $reasons);
    }

    #[Test]
    public function an_inline_project_example_points_its_own_assets_at_the_published_folder(): void
    {
        $this->createSource([
            'content/ru/page.md' => '# Page',
            'examples/components/card/index.html' => '<div data-picture="assets/place-01-4x3.svg" class="grid">'
                . '<span data-value=\'{"label":"cover","href":"assets/cover 02.svg"}\'>assets/place-01-4x3.svg</span>'
                . '<span data-other="assets/missing.svg" data-parent="../assets/place-01-4x3.svg" data-dot="./assets/place-01-4x3.svg"></span></div>',
            'examples/components/card/assets/place-01-4x3.svg' => '<svg xmlns="http://www.w3.org/2000/svg"/>',
            'examples/components/card/assets/cover 02.svg' => '<svg xmlns="http://www.w3.org/2000/svg"/>',
        ]);
        $repository = new ProjectExampleRepository($this->tmp, '/docs/');
        $renderer = new PortableMarkdownRenderer(projectExamples: $repository);
        $html = $renderer->render(
            ":::example {id=\"components/card\" label=\"Result\"}\n:::\n",
            $this->tmp,
            $this->tmpPath('content/ru/page.md'),
        );

        self::assertStringContainsString(
            '<div data-docara-example-inline-preview="html" data-docara-example-source="components/card" class="docara-example-inline">'
            . '<div data-picture="/docs/_docara/examples/components/card/assets/place-01-4x3.svg" class="grid">'
            // A path inside a JSON attribute moves too; text content stays.
            . '<span data-value=\'{"label":"cover","href":"/docs/_docara/examples/components/card/assets/cover 02.svg"}\'>assets/place-01-4x3.svg</span>'
            // An unknown file and a path outside the example stay as written;
            // "./assets/..." is the same file.
            . '<span data-other="assets/missing.svg" data-parent="../assets/place-01-4x3.svg" data-dot="/docs/_docara/examples/components/card/assets/place-01-4x3.svg"></span></div>',
            $html,
        );
        // The source tab shows what the author wrote, and the hidden
        // full-screen frame keeps its <base href> to the same folder.
        self::assertStringContainsString('<code class="language-html">&lt;div data-picture=&quot;assets/place-01-4x3.svg&quot;', $html);
        self::assertStringContainsString('&lt;base href=&quot;/docs/_docara/examples/components/card/&quot;&gt;', $html);
        self::assertSame(['inline', 'admitted_html'], [
            $repository->receipt()['previews'][0]['resolved_preview'],
            $repository->receipt()['previews'][0]['reason'],
        ]);
    }

    #[Test]
    public function it_rejects_inline_body_with_id_and_unsafe_project_files(): void
    {
        $this->createSource(['examples/demo/index.html' => '<p>Demo</p>']);
        $renderer = new PortableMarkdownRenderer(
            projectExamples: new ProjectExampleRepository($this->tmp),
        );
        try {
            $renderer->render(":::example {id=demo}\n```html\n<p>Inline</p>\n```\n:::\n", $this->tmp, $this->tmpPath('page.md'));
            self::fail('An id-based example unexpectedly accepted an inline body.');
        } catch (PortableConfigurationException $exception) {
            self::assertSame('MARKDOWN_EXAMPLE_ID_BODY_CONFLICT', $exception->errorCode);
        }

        $outside = $this->tmpPath('outside.html');
        file_put_contents($outside, '<p>Outside</p>');
        unlink($this->tmpPath('examples/demo/index.html'));
        symlink($outside, $this->tmpPath('examples/demo/index.html'));
        $this->expectException(PortableConfigurationException::class);
        (new ProjectExampleRepository($this->tmp))->load('demo');
    }

    #[Test]
    public function it_requires_a_safe_lowercase_id_and_index_html(): void
    {
        $repository = new ProjectExampleRepository($this->tmp);
        foreach (['../demo', '/demo', 'Demo', 'demo//card'] as $id) {
            try {
                $repository->load($id);
                self::fail("Unsafe example id [$id] was accepted.");
            } catch (PortableConfigurationException $exception) {
                self::assertSame('MARKDOWN_EXAMPLE_ID_INVALID', $exception->errorCode);
            }
        }
        $this->createSource(['examples/demo/index.css' => 'body{}']);
        $this->expectException(PortableConfigurationException::class);
        $repository->load('demo');
    }

    #[Test]
    public function it_rejects_hardlinks_unknown_entries_invalid_utf8_and_asset_case_collisions(): void
    {
        $this->createSource(['examples/demo/index.html' => '<p>Demo</p>']);
        file_put_contents($this->tmpPath('examples/demo/notes.md'), 'not allowed');
        try {
            (new ProjectExampleRepository($this->tmp))->load('demo');
            self::fail('An unknown root entry was accepted.');
        } catch (PortableConfigurationException $exception) {
            self::assertSame('PROJECT_EXAMPLE_FILE_FORBIDDEN', $exception->errorCode);
        }

        unlink($this->tmpPath('examples/demo/notes.md'));
        file_put_contents($this->tmpPath('examples/demo/index.html'), "\xFF");
        try {
            (new ProjectExampleRepository($this->tmp))->load('demo');
            self::fail('Invalid UTF-8 was accepted.');
        } catch (PortableConfigurationException $exception) {
            self::assertSame('PROJECT_EXAMPLE_SOURCE_INVALID', $exception->errorCode);
        }

        file_put_contents($this->tmpPath('examples/demo/index.html'), '<p>Demo</p>');
        link($this->tmpPath('examples/demo/index.html'), $this->tmpPath('examples/demo/index.css'));
        try {
            (new ProjectExampleRepository($this->tmp))->load('demo');
            self::fail('A hardlinked source was accepted.');
        } catch (PortableConfigurationException $exception) {
            self::assertSame('PROJECT_EXAMPLE_SOURCE_INVALID', $exception->errorCode);
        }

        unlink($this->tmpPath('examples/demo/index.css'));
        mkdir($this->tmpPath('examples/demo/Assets'));
        try {
            (new ProjectExampleRepository($this->tmp))->load('demo');
            self::fail('A case-conflicting contract entry was accepted.');
        } catch (PortableConfigurationException $exception) {
            self::assertContains($exception->errorCode, ['PROJECT_EXAMPLE_CASE_COLLISION', 'PROJECT_EXAMPLE_FILE_FORBIDDEN']);
        }
    }
}
