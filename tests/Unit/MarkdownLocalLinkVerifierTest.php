<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Simai\Docara\Documentation\MarkdownLocalLinkVerifier;

final class MarkdownLocalLinkVerifierTest extends TestCase
{
    #[Test]
    public function it_accepts_existing_files_directories_anchors_and_external_links(): void
    {
        (new MarkdownLocalLinkVerifier)->verify([
            'README.md' => '[Guide](docs/guide.md) [Docs](docs/) [Route](/ru/) [Web](https://example.com)',
            'docs/guide.md' => '[Back](../README.md#start) ![Image](assets/example.png)',
            'docs/assets/example.png' => 'png',
            'resources/example.md' => '[Runtime-owned projection](../generated/asset.svg)',
        ]);

        self::addToAssertionCount(1);
    }

    #[Test]
    public function it_rejects_a_missing_local_target(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Broken local documentation link [README.md -> docs/missing.md]');

        (new MarkdownLocalLinkVerifier)->verify(['README.md' => '[Missing](docs/missing.md)']);
    }

    #[Test]
    public function it_rejects_a_link_that_escapes_the_documentation_inventory(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Documentation link escapes its root');

        (new MarkdownLocalLinkVerifier)->verify(['docs/README.md' => '[Secret](../../secret.txt)']);
    }
}
