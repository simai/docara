<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Simai\Docara\Content\FrontMatterParser;
use Simai\Docara\Portable\PortableConfigurationException;

final class FrontMatterParserTest extends TestCase
{
    #[Test]
    public function it_parses_the_bounded_contract_and_preserves_source_line_numbers(): void
    {
        $document = (new FrontMatterParser)->parse(<<<'MD'
---
title: Бейдж
description: Короткая метка для статуса.
tags: [ui, status]
draft: false
translation_key: components.badge
---
# Бейдж

Текст.
MD, 'content/ru/components/badge.md');

        self::assertSame([
            'title' => 'Бейдж',
            'description' => 'Короткая метка для статуса.',
            'tags' => ['ui', 'status'],
            'draft' => false,
            'translation_key' => 'components.badge',
        ], $document->metadata);
        self::assertSame(8, strpos($document->markdown, '# Бейдж') === false
            ? 0
            : substr_count(substr($document->markdown, 0, strpos($document->markdown, '# Бейдж')), "\n") + 1);
        self::assertStringNotContainsString('title: Бейдж', $document->markdown);
    }

    #[Test]
    public function it_fails_closed_with_source_line_column_and_actionable_error(): void
    {
        try {
            (new FrontMatterParser)->parse("---\ntitle: Page\nlayout: wide\n---\n# Page\n", 'content/en/page.md');
            self::fail('Unknown front matter unexpectedly passed.');
        } catch (PortableConfigurationException $exception) {
            self::assertSame('FRONT_MATTER_KEY_UNKNOWN', $exception->errorCode);
            self::assertStringContainsString('content/en/page.md', $exception->getMessage());
            self::assertStringContainsString('line [3]', $exception->getMessage());
            self::assertStringContainsString('column [1]', $exception->getMessage());
            self::assertStringContainsString('Supported keys', $exception->getMessage());
        }
    }

    #[Test]
    public function it_rejects_invalid_draft_tags_translation_keys_and_unterminated_blocks(): void
    {
        foreach ([
            ["---\ndraft: yes\n---\n", 'FRONT_MATTER_DRAFT_INVALID'],
            ["---\ntags: [UI]\n---\n", 'FRONT_MATTER_TAG_INVALID'],
            ["---\ntranslation_key: Components Badge\n---\n", 'FRONT_MATTER_TRANSLATION_KEY_INVALID'],
            ["---\ntitle: Missing close\n", 'FRONT_MATTER_UNTERMINATED'],
        ] as [$markdown, $code]) {
            try {
                (new FrontMatterParser)->parse($markdown, 'content/ru/page.md');
                self::fail("Invalid front matter unexpectedly passed [$code].");
            } catch (PortableConfigurationException $exception) {
                self::assertSame($code, $exception->errorCode);
            }
        }
    }
}
