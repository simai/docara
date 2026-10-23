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

    /** Common words of four letters or more that never name the topic of a page. */
    public const TOPIC_STOP_WORDS = [
        'этот', 'этой', 'этом', 'этого', 'эти', 'этих', 'если', 'когда', 'чтобы', 'только', 'также', 'можно', 'нужно',
        'который', 'которая', 'которое', 'которые', 'через', 'после', 'перед', 'между', 'может', 'здесь', 'всех', 'весь',
        'with', 'from', 'that', 'this', 'what', 'when', 'your', 'into', 'about', 'page',
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
     * Words of four letters or more that can name a topic: the lowercase
     * words of a title, description or paragraph, with compound words split
     * at the hyphen ("Smart-компоненты" gives "smart" and "компоненты") and
     * common short words such as "этот" or "with" left out.
     *
     * @return list<string>
     */
    public function topicWords(string $text): array
    {
        $words = [];
        foreach ($this->words(mb_strtolower($text)) as $word) {
            foreach (preg_split('/[\'’-]/u', $word) ?: [] as $part) {
                if (mb_strlen($part) >= 4 && preg_match('/\p{L}/u', $part) === 1 && ! in_array($part, self::TOPIC_STOP_WORDS, true)) {
                    $words[$part] = true;
                }
            }
        }

        return array_keys($words);
    }

    /**
     * Whether two lowercase words are forms of one word, by a light stemming
     * that needs no dictionary: they share their first five letters
     * ("публикация", "публикуйте"), or their first four letters when neither
     * has more than three letters after them ("ошибки", "ошибок";
     * "начало", "начать"). Words are compared whole when one of them is
     * shorter than four letters.
     */
    public static function sameWordForm(string $a, string $b): bool
    {
        if ($a === $b) {
            return true;
        }
        $shared = 0;
        $limit = min(mb_strlen($a), mb_strlen($b));
        while ($shared < $limit && mb_substr($a, $shared, 1) === mb_substr($b, $shared, 1)) {
            $shared++;
        }

        return $shared >= 5 || ($shared === 4 && mb_strlen($a) - 4 <= 3 && mb_strlen($b) - 4 <= 3);
    }

    /**
     * Whether a word of the title appears, in some form, in the text.
     *
     * @param  list<string>  $titleWords  from topicWords()
     * @param  list<string>  $textWords  from topicWords()
     */
    public static function sharesTopicWord(array $titleWords, array $textWords): bool
    {
        foreach ($titleWords as $title) {
            foreach ($textWords as $word) {
                if (self::sameWordForm($title, $word)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * The singular of a lowercase English word by the regular plural rules:
     * "registries" → "registry", "hashes" → "hash", "assets" → "asset".
     * Words that only look plural ("class", "status", "canvas") keep their
     * form. Used to treat both forms of a term as one.
     */
    public static function englishSingular(string $word): string
    {
        if (preg_match('/[^aeiou]ies$/', $word) === 1 && strlen($word) > 4) {
            return substr($word, 0, -3) . 'y';
        }
        if (preg_match('/(?:ss|sh|ch|x|z)es$/', $word) === 1) {
            return substr($word, 0, -2);
        }
        if (preg_match('/[^asu]s$/', $word) === 1 && strlen($word) > 3) {
            return substr($word, 0, -1);
        }

        return $word;
    }
}
