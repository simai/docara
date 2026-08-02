<?php

declare(strict_types=1);

namespace Simai\Docara\I18n;

use Simai\Docara\Portable\PortableConfigurationException;

final readonly class LocaleMissingPagePolicy
{
    public const SKIP = 'skip';

    public const ERROR = 'error';

    public function __construct(public string $value)
    {
        if (! in_array($value, [self::SKIP, self::ERROR], true)) {
            throw new PortableConfigurationException(
                'LOCALE_MISSING_PAGE_POLICY_INVALID',
                "locales.missing_page_policy must be skip or error; [$value] given.",
            );
        }
    }

    /** @param array<string, mixed> $site */
    public static function fromSite(array $site): self
    {
        $locales = $site['locales'] ?? [];
        $value = is_array($locales) ? ($locales['missing_page_policy'] ?? self::SKIP) : self::SKIP;

        return new self(is_string($value) ? $value : '');
    }
}
