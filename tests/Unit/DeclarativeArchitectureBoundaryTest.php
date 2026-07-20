<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class DeclarativeArchitectureBoundaryTest extends TestCase
{
    public function test_parser_compiler_and_build_orchestrators_contain_no_markup_styles_or_client_code(): void
    {
        $root = dirname(__DIR__, 2);
        $files = [
            $root . '/src/Declarative/Document/DocumentParser.php',
            $root . '/src/Declarative/DeclarativePipeline.php',
            $root . '/src/Declarative/DeclarativePageCompiler.php',
            $root . '/src/Declarative/Adapter/LarenaContractAdapter.php',
            $root . '/src/PortableSite/PortableSiteBuilder.php',
        ];

        foreach ($files as $file) {
            $source = (string) file_get_contents($file);
            self::assertDoesNotMatchRegularExpression(
                '/<\\/?(?:html|head|body|header|aside|main|section|article|footer|div|p|script|style|sf-)/i',
                $source,
                $file,
            );
            self::assertDoesNotMatchRegularExpression(
                '/\\b(?:document|window|customElements|localStorage)\\s*\\./',
                $source,
                $file,
            );
            self::assertDoesNotMatchRegularExpression(
                '/(?:display|position|margin|padding|background|color|inline-size|block-size)\\s*:/i',
                $source,
                $file,
            );
        }
    }

    public function test_accepted_legacy_renderer_is_preserved_byte_for_byte(): void
    {
        self::assertSame(
            'a28e914128a55143ce13e21c8bebc2216b5144919c6dbb2e5dfee366229125d0',
            hash_file(
                'sha256',
                dirname(__DIR__, 2) . '/src/PortableSite/PortableHtmlRenderer.php',
            ),
        );
    }
}
