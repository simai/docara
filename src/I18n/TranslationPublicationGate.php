<?php

declare(strict_types=1);

namespace Simai\Docara\I18n;

use Simai\Docara\Portable\PortableConfigurationException;

/**
 * What a build may publish under `translation_tracking.mode: "publish-current"`.
 *
 * A target-locale page is published as written only while its translation is
 * `current` (or `excluded`, an accepted decision). A target page whose
 * translation is `stale`, `unverified` or `structure_mismatch` is held back:
 * the build publishes the source-locale page at the target URL with a notice
 * in the target language. A target page without a source (`orphan`) is not
 * published. A `duplicate_key` stops the build, because the gate cannot tell
 * which page the key means. Missing pages keep following
 * `locales.missing_page_policy`, exactly as in report mode.
 *
 * Interface strings follow the same rule: a `stale`, `unverified` or
 * `structure_mismatch` key in a target `lang.json` is replaced by the source
 * locale's string.
 */
final readonly class TranslationPublicationGate
{
    public const MODE = 'publish-current';

    public const SOURCE_FALLBACK = 'source_fallback';

    public const WITHHELD = 'withheld';

    public const SOURCE_STRING = 'source_string';

    private const HELD_BACK = ['stale', 'unverified', 'structure_mismatch'];

    /**
     * @param  array<string, array{action:string,status:string,key:string,locale:string,source_path:?string,target_path:string}>  $pages  by target page path
     * @param  array<string, list<string>>  $languageKeys  locale => held-back lang.json message ids
     */
    private function __construct(
        public string $sourceLocale,
        private array $pages,
        private array $languageKeys,
    ) {}

    /**
     * The gate for a site, or null when the site does not ask for it. Report
     * mode and disabled tracking build exactly as before.
     *
     * @param  array<string, mixed>  $site
     */
    public static function fromSite(string $root, array $site): ?self
    {
        $tracking = $site['translation_tracking'] ?? null;
        if (! is_array($tracking)
            || ($tracking['enabled'] ?? false) !== true
            || ($tracking['mode'] ?? 'report') !== self::MODE
        ) {
            return null;
        }

        return self::fromReport((new TranslationStatusService)->report($root));
    }

    /** @param array<string, mixed> $report a docara.translation_status.v1 report */
    public static function fromReport(array $report): self
    {
        $sourceLocale = $report['source_locale'] ?? null;
        if (($report['enabled'] ?? false) !== true || ! is_string($sourceLocale)) {
            throw new PortableConfigurationException(
                'TRANSLATION_GATE_REPORT_INVALID',
                'The publication gate needs an enabled translation status report.',
            );
        }
        $pages = [];
        $languageKeys = [];
        foreach ($report['items'] ?? [] as $item) {
            $status = (string) $item['status'];
            $locale = (string) $item['locale'];
            $key = (string) $item['key'];
            if ($item['kind'] === 'lang') {
                if (in_array($status, self::HELD_BACK, true)) {
                    $languageKeys[$locale][] = $key;
                }

                continue;
            }
            if ($status === 'duplicate_key') {
                throw new PortableConfigurationException(
                    'TRANSLATION_DUPLICATE_KEY_BLOCKS_PUBLICATION',
                    "Translation key [$key] for locale [$locale] belongs to more than one page; publish-current mode cannot decide which page to publish. Give each page its own translation_key.",
                );
            }
            $target = $item['target_path'] ?? null;
            if (! is_string($target)) {
                // Missing pages follow locales.missing_page_policy as before.
                continue;
            }
            if ($status === 'orphan') {
                $pages[$target] = self::record(self::WITHHELD, $status, $key, $locale, null, $target);

                continue;
            }
            if (in_array($status, self::HELD_BACK, true) && is_string($item['source_path'] ?? null)) {
                $pages[$target] = self::record(self::SOURCE_FALLBACK, $status, $key, $locale, $item['source_path'], $target);
            }
        }
        ksort($pages, SORT_STRING);
        foreach ($languageKeys as &$keys) {
            sort($keys, SORT_STRING);
        }
        unset($keys);
        ksort($languageKeys, SORT_STRING);

        return new self($sourceLocale, $pages, $languageKeys);
    }

    /**
     * How the build treats one page source: null publishes it as written.
     *
     * @return array{action:string,status:string,key:string,locale:string,source_path:?string,target_path:string}|null
     */
    public function decision(string $pagePath): ?array
    {
        return $this->pages[$pagePath] ?? null;
    }

    /** @return array<string, list<string>> locale => lang.json message ids that show the source string */
    public function languageKeys(): array
    {
        return $this->languageKeys;
    }

    /**
     * Everything the gate held back, for the build receipt and summary.
     *
     * @return array{schema:string,mode:string,source_locale:string,pages:list<array<string,mixed>>,lang:list<array<string,string>>,summary:array<string,int>}
     */
    public function receipt(): array
    {
        $pages = [];
        foreach ($this->pages as $record) {
            $pages[] = [
                'locale' => $record['locale'],
                'key' => $record['key'],
                'status' => $record['status'],
                'action' => $record['action'],
                'target_path' => $record['target_path'],
                'source_path' => $record['source_path'],
            ];
        }
        usort($pages, static fn (array $left, array $right): int => [$left['locale'], $left['key']] <=> [$right['locale'], $right['key']]);
        $lang = [];
        foreach ($this->languageKeys as $locale => $keys) {
            foreach ($keys as $key) {
                $lang[] = ['locale' => $locale, 'key' => $key, 'action' => self::SOURCE_STRING];
            }
        }

        return [
            'schema' => 'docara.translation_publication.v1',
            'mode' => self::MODE,
            'source_locale' => $this->sourceLocale,
            'pages' => $pages,
            'lang' => $lang,
            'summary' => [
                'source_fallback' => count(array_filter($pages, static fn (array $page): bool => $page['action'] === self::SOURCE_FALLBACK)),
                'withheld' => count(array_filter($pages, static fn (array $page): bool => $page['action'] === self::WITHHELD)),
                'source_strings' => count($lang),
            ],
        ];
    }

    /** @return array{action:string,status:string,key:string,locale:string,source_path:?string,target_path:string} */
    private static function record(string $action, string $status, string $key, string $locale, ?string $source, string $target): array
    {
        return [
            'action' => $action,
            'status' => $status,
            'key' => $key,
            'locale' => $locale,
            'source_path' => $source,
            'target_path' => $target,
        ];
    }
}
