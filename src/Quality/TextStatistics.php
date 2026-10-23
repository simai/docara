<?php

declare(strict_types=1);

namespace Simai\Docara\Quality;

/**
 * Word, sentence and script counts for the quality report. Everything is a
 * plain regular expression over Unicode text, so the same text always gives
 * the same numbers on any machine.
 */
final class TextStatistics
{
    /** Primary language subtags whose usual script is not Latin. */
    public const NON_LATIN_LANGUAGES = [
        'am', 'ar', 'be', 'bg', 'bn', 'el', 'fa', 'gu', 'he', 'hi', 'hy', 'ja', 'ka', 'kk', 'km', 'kn',
        'ko', 'ky', 'lo', 'mk', 'ml', 'mn', 'mr', 'my', 'ne', 'pa', 'ps', 'ru', 'si', 'ta', 'te', 'tg',
        'th', 'uk', 'ur', 'zh',
    ];

    /** Stand-in for inline code inside a sentence: it counts as one word. */
    public const CODE_TOKEN = "\u{2063}code\u{2063}";

    /** @return list<string> */
    public function words(string $text): array
    {
        $text = str_replace(self::CODE_TOKEN, ' ', $text);
        preg_match_all('/[\p{L}\p{N}]+(?:[\'’-][\p{L}\p{N}]+)*/u', $text, $matches);

        return array_values(array_filter($matches[0], static fn (string $word): bool => preg_match('/\p{L}/u', $word) === 1));
    }

    public function wordCount(string $text): int
    {
        return count($this->words($text));
    }

    /**
     * Sentences of one block of prose. A sentence ends with . ! ? or … that
     * is followed by a space and a capital letter, a digit or an opening
     * quote, or by the end of the block. Inline code counts as one word.
     *
     * @return list<int> word count of each sentence
     */
    public function sentenceLengths(string $block): array
    {
        $block = trim((string) preg_replace('/\s+/u', ' ', $block));
        if ($block === '') {
            return [];
        }
        $parts = preg_split('/(?<=[.!?…])\s+(?=[\p{Lu}\p{N}«"„(\x{2063}])/u', $block) ?: [$block];
        $lengths = [];
        foreach ($parts as $part) {
            $count = $this->wordCount($part) + substr_count($part, self::CODE_TOKEN);
            if ($count > 0) {
                $lengths[] = $count;
            }
        }

        return $lengths;
    }

    public static function isLatinWord(string $word): bool
    {
        return preg_match('/\p{Latin}/u', $word) === 1 && preg_match('/^[\p{Latin}\p{N}\'’-]+$/u', $word) === 1;
    }

    /** Share of Latin words among the words of the text, 0..1. */
    public function latinShare(string $text): float
    {
        $words = $this->words($text);
        if ($words === []) {
            return 0.0;
        }

        return count(array_filter($words, self::isLatinWord(...))) / count($words);
    }

    public static function usesNonLatinScript(string $locale): bool
    {
        $primary = strtolower(explode('-', str_replace('_', '-', $locale), 2)[0]);

        return in_array($primary, self::NON_LATIN_LANGUAGES, true);
    }

    /**
     * Lowercase stems of the words of a title or description: the first five
     * letters of every word of four letters or more. Five letters keep a
     * Russian word recognisable across its endings ("публикация",
     * "публикуйте").
     *
     * @return list<string>
     */
    public function stems(string $text): array
    {
        $stems = [];
        foreach ($this->words(mb_strtolower($text)) as $word) {
            if (mb_strlen($word) >= 4) {
                $stems[] = mb_substr($word, 0, 5);
            }
        }

        return array_values(array_unique($stems));
    }
}
