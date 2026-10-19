<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use Simai\Docara\File\Filesystem;
use Simai\Docara\Portable\ResolvedPagePlan;
use Simai\Docara\PortableSite\PortableMarkdownRenderer;
use Simai\Docara\PortableSite\PortableSiteBuilder;

final class PageDescriptionTest extends TestCase
{
    /** @return array<string, array{string, string}> */
    public static function pages(): array
    {
        return [
            'first paragraph' => [
                "# Title\n\nThe page **explains** [one thing](x.md).\n\nMore.\n",
                'The page explains one thing.',
            ],
            'lead-in after a sentence' => [
                "# Title\n\nA component is a portable artifact. Every component takes one path:\n\n1. Step\n",
                'A component is a portable artifact.',
            ],
            'one-sentence lead-in' => [
                "# Title\n\nDocara reads three kinds of JSON:\n\n| A | B |\n| --- | --- |\n| x | y |\n\nEach file names its schema.\n\n## Next\n\nOther.\n",
                'Each file names its schema.',
            ],
            'lead-in before a section' => [
                "# Title\n\nThe layers apply in one order:\n\n- site\n- page\n\n## Next\n\nOther.\n",
                'The layers apply in one order:',
            ],
        ];
    }

    #[Test]
    #[DataProvider('pages')]
    public function a_derived_description_skips_a_lead_in_that_ends_with_a_colon(string $markdown, string $expected): void
    {
        $plan = new ResolvedPagePlan('content/en/page.md', $markdown, [], [], [], []);
        $method = new ReflectionMethod(PortableSiteBuilder::class, 'pageDescription');

        self::assertSame($expected, $method->invoke(new PortableSiteBuilder(new Filesystem, new PortableMarkdownRenderer), $plan));
    }
}
