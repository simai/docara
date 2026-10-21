<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Simai\Docara\I18n\UiCopy;
use Simai\Docara\PortableSite\NotFoundPage;
use Tests\Support\LangReference;

final class LangReferenceTest extends TestCase
{
    private LangReference $reference;

    protected function setUp(): void
    {
        $this->reference = new LangReference(dirname(__DIR__, 2));
    }

    #[Test]
    public function the_language_packs_page_lists_every_string_of_the_current_code(): void
    {
        $page = $this->reference->page();

        self::assertSame(
            $this->reference->withTable($page),
            $page,
            'Run php scripts/capture-lang-reference.php after changing an interface string.',
        );
        foreach ($this->reference->ids() as $id) {
            self::assertStringContainsString('| `' . $id . '` |', $page, $id);
        }
    }

    #[Test]
    public function every_string_has_a_meaning_and_no_meaning_is_left_over(): void
    {
        $known = [...$this->reference->ids(), ...$this->reference->notRead()];

        self::assertSame([], array_values(array_diff($known, array_keys(LangReference::MEANINGS))));
        self::assertSame([], array_values(array_diff(array_keys(LangReference::MEANINGS), $known)));
    }

    #[Test]
    public function required_strings_come_with_english_and_russian_text_from_init(): void
    {
        $root = dirname(__DIR__, 2);
        foreach (['en' => 'stubs/minimal/en/content/en/lang.json', 'ru' => 'stubs/minimal/ru/content/ru/lang.json'] as $language => $path) {
            $lang = json_decode((string) file_get_contents($root . '/' . $path), true, 512, JSON_THROW_ON_ERROR);
            foreach (UiCopy::requiredIds() as $id) {
                [$group, $key] = explode('.', $id, 2);
                self::assertIsString($lang[$group][$key] ?? null, "$language $id");
            }
        }
    }

    #[Test]
    public function the_strings_the_table_calls_overridable_or_unused_are_so_in_the_code(): void
    {
        $build = UiCopy::buildDefaultsByLanguage()['en'];
        foreach (NotFoundPage::MESSAGE_IDS as $id) {
            self::assertArrayHasKey($id, $build);
        }
        self::assertStringContainsString(
            "'language.fallback_notice',",
            (string) file_get_contents(dirname(__DIR__, 2) . '/src/PortableSite/TranslationFallbackNotice.php'),
        );
        $shown = [...UiCopy::requiredIds(), ...array_keys(UiCopy::optionalDefaultsByLanguage()['en'])];
        foreach ($this->reference->notRead() as $id) {
            self::assertNotContains($id, $shown, "$id is shown now; move it out of the unused list.");
        }
    }
}
