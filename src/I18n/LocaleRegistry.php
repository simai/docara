<?php

declare(strict_types=1);

namespace Simai\Docara\I18n;

use Simai\Docara\Portable\PortableConfigurationException;

final readonly class LocaleRegistry
{
    /** @param array<string, LocaleDefinition> $locales */
    private function __construct(
        private array $locales,
        private string $defaultLocale,
    ) {}

    /** @param array<string, mixed> $site */
    public static function fromSite(array $site): self
    {
        $configured = $site['locales'] ?? null;
        if (! is_array($configured) || $configured === []) {
            $legacyTag = LocaleTag::from((string) ($site['default_locale'] ?? $site['locale'] ?? 'und'));
            $tag = $legacyTag->value();
            $configured = [
                $tag => [
                    'label' => $tag,
                    'direction' => 'ltr',
                    'content_root' => (string) ($site['content_root'] ?? 'content'),
                    'language_pack' => '@docara/' . $tag,
                    'public_prefix' => '',
                    'fallbacks' => [],
                ],
            ];
        }

        $locales = [];
        $prefixes = [];
        $contentRoots = [];
        foreach ($configured as $rawTag => $record) {
            if (! is_string($rawTag) || ! is_array($record)) {
                throw new PortableConfigurationException(
                    'LOCALE_DEFINITION_INVALID',
                    'Every locale definition must be an object keyed by a BCP 47 tag.',
                );
            }
            $tag = LocaleTag::from($rawTag)->value();
            if (isset($locales[$tag])) {
                throw new PortableConfigurationException(
                    'LOCALE_DUPLICATE',
                    "Locale [$rawTag] duplicates canonical locale [$tag].",
                );
            }
            $direction = (string) ($record['direction'] ?? '');
            if (! in_array($direction, ['ltr', 'rtl'], true)) {
                throw new PortableConfigurationException(
                    'LOCALE_DIRECTION_INVALID',
                    "Locale [$tag] must declare direction ltr or rtl.",
                );
            }
            $prefix = trim((string) ($record['public_prefix'] ?? $tag), '/');
            if ($prefix !== '' && preg_match('/^[A-Za-z0-9](?:[A-Za-z0-9._~-]*[A-Za-z0-9])?$/D', $prefix) !== 1) {
                throw new PortableConfigurationException(
                    'LOCALE_PUBLIC_PREFIX_INVALID',
                    "Locale [$tag] has an invalid public prefix [$prefix].",
                );
            }
            if (isset($prefixes[$prefix])) {
                throw new PortableConfigurationException(
                    'LOCALE_PUBLIC_PREFIX_DUPLICATE',
                    "Locales [$tag] and [{$prefixes[$prefix]}] use the same public prefix [$prefix].",
                );
            }
            $prefixes[$prefix] = $tag;

            $contentRoot = trim((string) ($record['content_root'] ?? ''), '/');
            if ($contentRoot === '') {
                throw new PortableConfigurationException(
                    'LOCALE_CONTENT_ROOT_INVALID',
                    "Locale [$tag] must declare a non-empty content root.",
                );
            }
            foreach ($contentRoots as $otherRoot => $otherTag) {
                if ($contentRoot === $otherRoot
                    || str_starts_with($contentRoot . '/', $otherRoot . '/')
                    || str_starts_with($otherRoot . '/', $contentRoot . '/')
                ) {
                    throw new PortableConfigurationException(
                        'LOCALE_CONTENT_ROOT_OVERLAP',
                        "Locale content roots [$contentRoot] and [$otherRoot] overlap for [$tag] and [$otherTag].",
                    );
                }
            }
            $contentRoots[$contentRoot] = $tag;

            $languagePack = trim((string) ($record['language_pack'] ?? ''));
            if ($languagePack === '') {
                throw new PortableConfigurationException(
                    'LOCALE_LANGUAGE_PACK_REQUIRED',
                    "Locale [$tag] must declare a language pack.",
                );
            }

            $fallbacks = [];
            foreach (($record['fallbacks'] ?? []) as $fallback) {
                if (! is_string($fallback)) {
                    throw new PortableConfigurationException(
                        'LOCALE_FALLBACK_INVALID',
                        "Locale [$tag] has a non-string fallback.",
                    );
                }
                $fallbacks[] = LocaleTag::from($fallback)->value();
            }
            if (count($fallbacks) !== count(array_unique($fallbacks))) {
                throw new PortableConfigurationException(
                    'LOCALE_FALLBACK_DUPLICATE',
                    "Locale [$tag] contains duplicate fallbacks.",
                );
            }

            $locales[$tag] = new LocaleDefinition(
                LocaleTag::from($tag),
                (string) ($record['label'] ?? $tag),
                $direction,
                $contentRoot,
                $languagePack,
                $prefix,
                $fallbacks,
            );
        }

        $default = LocaleTag::from((string) ($site['default_locale'] ?? array_key_first($locales)))->value();
        if (! isset($locales[$default])) {
            throw new PortableConfigurationException(
                'DEFAULT_LOCALE_NOT_CONFIGURED',
                "Default locale [$default] is not present in the locale registry.",
            );
        }

        $registry = new self($locales, $default);
        $registry->assertFallbackGraph();

        return $registry;
    }

    public function default(): LocaleDefinition
    {
        return $this->locales[$this->defaultLocale];
    }

    public function get(string $locale): LocaleDefinition
    {
        $canonical = LocaleTag::from($locale)->value();
        if (! isset($this->locales[$canonical])) {
            throw new PortableConfigurationException(
                'LOCALE_NOT_CONFIGURED',
                "Locale [$canonical] is not configured for this site.",
            );
        }

        return $this->locales[$canonical];
    }

    /** @return array<string, LocaleDefinition> */
    public function all(): array
    {
        return $this->locales;
    }

    public function forPage(string $page): LocaleDefinition
    {
        $page = trim(str_replace('\\', '/', $page), '/');
        foreach ($this->locales as $definition) {
            if (str_starts_with($page . '/', rtrim($definition->contentRoot, '/') . '/')) {
                return $definition;
            }
        }

        throw new PortableConfigurationException(
            'PAGE_OUTSIDE_LOCALE_CONTENT_ROOTS',
            "Portable page [$page] is outside every configured locale content root.",
        );
    }

    /** @return list<LocaleDefinition> */
    public function fallbackChain(string $locale): array
    {
        $chain = [];
        $seen = [];
        $visit = function (string $tag) use (&$visit, &$chain, &$seen): void {
            if (isset($seen[$tag])) {
                return;
            }
            $seen[$tag] = true;
            $definition = $this->get($tag);
            $chain[] = $definition;
            foreach ($definition->fallbacks as $fallback) {
                $visit($fallback);
            }
        };
        $visit(LocaleTag::from($locale)->value());

        return $chain;
    }

    private function assertFallbackGraph(): void
    {
        foreach ($this->locales as $tag => $definition) {
            foreach ($definition->fallbacks as $fallback) {
                if (! isset($this->locales[$fallback])) {
                    throw new PortableConfigurationException(
                        'LOCALE_FALLBACK_NOT_CONFIGURED',
                        "Locale [$tag] references unconfigured fallback [$fallback].",
                    );
                }
            }
        }

        $state = [];
        $visit = function (string $tag) use (&$visit, &$state): void {
            if (($state[$tag] ?? null) === 'visiting') {
                throw new PortableConfigurationException(
                    'LOCALE_FALLBACK_CYCLE',
                    "Locale fallback graph contains a cycle at [$tag].",
                );
            }
            if (($state[$tag] ?? null) === 'done') {
                return;
            }
            $state[$tag] = 'visiting';
            foreach ($this->locales[$tag]->fallbacks as $fallback) {
                $visit($fallback);
            }
            $state[$tag] = 'done';
        };

        foreach (array_keys($this->locales) as $tag) {
            $visit($tag);
        }
    }
}
