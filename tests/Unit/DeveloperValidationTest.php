<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Simai\Docara\Application\ArtifactTestService;
use Simai\Docara\Application\ScaffoldService;
use Simai\Docara\Application\ValidationService;
use Simai\Docara\File\Filesystem;
use Simai\Docara\PortableSite\PortableMarkdownRenderer;
use Simai\Docara\PortableSite\PortableSiteBuilder;
use Simai\Docara\Preview\PreviewKernel;
use Tests\TestCase;

final class DeveloperValidationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->filesystem->copyDirectory(dirname(__DIR__, 2) . '/stubs/portable', $this->tmp);
    }

    #[Test]
    public function scaffold_validate_preview_test_uses_the_accepted_production_kernel(): void
    {
        $scaffold = new ScaffoldService;
        $plan = $scaffold->plan($this->tmp, 'smart', 'project.card')->toArray();
        $scaffold->apply($this->tmp, $plan['data']['plan_id']);
        $this->filesystem->put($this->tmpPath('content/ru/sdk-card.md'), <<<'MD'
# SDK card

:::project.card
{"title":"Created by the SDK","text":"One portable runtime."}
:::
MD);

        $validated = (new ValidationService)->validate($this->tmp, 'smart', 'project.card')->toArray();
        self::assertSame('success', $validated['status']);
        self::assertSame(13, $validated['data']['passed']);
        self::assertSame(0, $validated['data']['not_declared']);

        $builder = new PortableSiteBuilder(new Filesystem, new PortableMarkdownRenderer);
        $tested = (new ArtifactTestService(new PreviewKernel($builder, new Filesystem)))->test(
            $this->tmp,
            'smart',
            'project.card',
            '/ru/sdk-card/',
        )->toArray();
        self::assertSame('success', $tested['status']);
        self::assertFalse($tested['data']['accepted_build_receipt']);
        self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $tested['data']['html_sha256']);
        self::assertSame('markdown>typed-ir>registry>gateway>layout-composer>page-builder', $tested['data']['provenance']['renderer_path']);
    }

    #[Test]
    public function validation_reports_optional_metadata_without_inventing_a_second_validator(): void
    {
        $result = (new ValidationService)->validate($this->tmp, 'smart', 'project.notice')->toArray();
        self::assertSame('success', $result['status']);
        self::assertContains('SMART_MANIFEST_VALID', array_column($result['data']['checks'], 'code'));
        self::assertGreaterThanOrEqual(1, $result['data']['not_declared']);
    }

    #[Test]
    public function unknown_test_kind_and_subject_fail_closed(): void
    {
        $builder = new PortableSiteBuilder(new Filesystem, new PortableMarkdownRenderer);
        $service = new ArtifactTestService(new PreviewKernel($builder, new Filesystem));
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('SDK_TEST_KIND_UNKNOWN');
        $service->test($this->tmp, 'section', 'project.section', '/ru/');
    }
}
