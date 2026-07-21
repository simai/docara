<?php

declare(strict_types=1);

namespace Simai\Docara\I18n;

use Simai\Docara\Portable\PortableConfigurationException;

final readonly class LocaleRoutingPolicy
{
    public const STRATEGY_DEFAULT_UNPREFIXED = 'default_unprefixed';

    public const STRATEGY_PREFIXED = 'prefixed';

    public const ROOT_DEFAULT_LOCALE = 'default_locale';

    public const ROOT_REDIRECT = 'redirect';

    public function __construct(
        public string $strategy,
        public string $root,
        public bool $detectBrowserLanguage,
        public bool $legacyUnprefixedRedirects,
    ) {}

    /** @param array<string, mixed> $site */
    public static function fromSite(array $site, LocaleRegistry $registry): self
    {
        $configured = $site['locale_routing'] ?? null;
        if ($configured === null) {
            $policy = new self(
                self::STRATEGY_DEFAULT_UNPREFIXED,
                self::ROOT_DEFAULT_LOCALE,
                false,
                false,
            );
        } elseif (! is_array($configured)) {
            throw new PortableConfigurationException(
                'LOCALE_ROUTING_INVALID',
                'locale_routing must be an object.',
            );
        } else {
            $policy = new self(
                (string) ($configured['strategy'] ?? ''),
                (string) ($configured['root'] ?? ''),
                (bool) ($configured['detect_browser_language'] ?? false),
                (bool) ($configured['legacy_unprefixed_redirects'] ?? false),
            );
        }

        $policy->assertValid($registry);

        return $policy;
    }

    public function isSymmetric(): bool
    {
        return $this->strategy === self::STRATEGY_PREFIXED;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'strategy' => $this->strategy,
            'root' => $this->root,
            'detect_browser_language' => $this->detectBrowserLanguage,
            'legacy_unprefixed_redirects' => $this->legacyUnprefixedRedirects,
        ];
    }

    private function assertValid(LocaleRegistry $registry): void
    {
        if (! in_array($this->strategy, [self::STRATEGY_DEFAULT_UNPREFIXED, self::STRATEGY_PREFIXED], true)) {
            throw new PortableConfigurationException(
                'LOCALE_ROUTING_STRATEGY_INVALID',
                "Unsupported locale routing strategy [{$this->strategy}].",
            );
        }
        if (! in_array($this->root, [self::ROOT_DEFAULT_LOCALE, self::ROOT_REDIRECT], true)) {
            throw new PortableConfigurationException(
                'LOCALE_ROOT_ROUTE_INVALID',
                "Unsupported locale root policy [{$this->root}].",
            );
        }

        $default = $registry->default();
        if ($this->strategy === self::STRATEGY_PREFIXED) {
            foreach ($registry->all() as $locale => $definition) {
                if ($definition->publicPrefix === '') {
                    throw new PortableConfigurationException(
                        'LOCALE_PREFIX_REQUIRED',
                        "Symmetric locale routing requires a public prefix for locale [$locale].",
                    );
                }
            }
            if ($this->root !== self::ROOT_REDIRECT) {
                throw new PortableConfigurationException(
                    'LOCALE_ROOT_REDIRECT_REQUIRED',
                    'Symmetric locale routing requires root=redirect.',
                );
            }
        } elseif ($default->publicPrefix !== '') {
            throw new PortableConfigurationException(
                'DEFAULT_LOCALE_MUST_BE_UNPREFIXED',
                'default_unprefixed routing requires an empty public prefix for the default locale.',
            );
        }

        if ($this->root === self::ROOT_DEFAULT_LOCALE && $default->publicPrefix !== '') {
            throw new PortableConfigurationException(
                'DEFAULT_LOCALE_ROOT_COLLISION',
                'root=default_locale requires the default locale to use the root URL.',
            );
        }
        if ($this->root === self::ROOT_REDIRECT && $default->publicPrefix === '') {
            throw new PortableConfigurationException(
                'LOCALE_ROOT_REDIRECT_TARGET_INVALID',
                'root=redirect requires the default locale to have a non-empty public prefix.',
            );
        }
        if ($this->legacyUnprefixedRedirects && ! $this->isSymmetric()) {
            throw new PortableConfigurationException(
                'LEGACY_UNPREFIXED_REDIRECTS_REQUIRE_PREFIXED_ROUTING',
                'Legacy unprefixed redirects are available only for symmetric prefixed routing.',
            );
        }
    }
}
