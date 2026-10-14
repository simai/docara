<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\PortableSite\InlineExampleIdGuard;

final class InlineExampleIdGuardTest extends TestCase
{
    #[Test]
    public function unique_inline_example_ids_pass(): void
    {
        (new InlineExampleIdGuard)->assertUnique(
            '<!doctype html><html><body><main id="docara-main"><h2 id="usage">Usage</h2>'
            . '<div data-docara-example-inline-preview="html" class="docara-example-inline"><span id="save-tooltip">Save</span></div>'
            . '<div data-docara-example-inline-preview="html" class="docara-example-inline"><span id="close-tooltip">Close</span></div>'
            . '</main></body></html>',
            'content/ru/page.md',
        );
        $this->addToAssertionCount(1);
    }

    #[Test]
    public function an_inline_example_id_used_twice_on_a_page_fails_with_the_id_and_source(): void
    {
        foreach ([
            'another inline example' => '<div data-docara-example-inline-preview="html" data-docara-example-source="components/tooltip/overview"><span id="save-tooltip">Save</span></div>'
                . '<div data-docara-example-inline-preview="html"><span id="save-tooltip">Again</span></div>',
            'the page outside the example' => '<p id="save-tooltip">Prose</p>'
                . '<div data-docara-example-inline-preview="html" data-docara-example-source="components/tooltip/overview"><span id="save-tooltip">Save</span></div>',
        ] as $case => $body) {
            try {
                (new InlineExampleIdGuard)->assertUnique(
                    '<!doctype html><html><body><main id="docara-main">' . $body . '</main></body></html>',
                    'content/ru/page.md',
                );
                self::fail("A duplicate id with $case was accepted.");
            } catch (PortableConfigurationException $exception) {
                self::assertSame('MARKDOWN_EXAMPLE_INLINE_ID_DUPLICATE', $exception->errorCode);
                self::assertStringContainsString('[save-tooltip]', $exception->getMessage());
                self::assertStringContainsString('[content/ru/page.md]', $exception->getMessage());
            }
        }
    }

    #[Test]
    public function ids_inside_sandboxed_frames_and_typed_markdown_examples_are_not_checked(): void
    {
        (new InlineExampleIdGuard)->assertUnique(
            '<!doctype html><html><body><span id="save-tooltip"></span>'
            . '<iframe data-docara-example-frame srcdoc="&lt;span id=&quot;save-tooltip&quot;&gt;&lt;/span&gt;"></iframe>'
            . '<div data-docara-example-inline-preview><h3 id="x-y">A</h3></div><h3 id="x-y">B</h3>'
            . '</body></html>',
            'content/ru/page.md',
        );
        $this->addToAssertionCount(1);
    }
}
