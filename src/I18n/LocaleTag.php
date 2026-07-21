<?php

declare(strict_types=1);

namespace Simai\Docara\I18n;

use Simai\Docara\Portable\PortableConfigurationException;

final readonly class LocaleTag implements \Stringable
{
    private const GRANDFATHERED = [
        'art-lojban', 'cel-gaulish', 'en-gb-oed', 'i-ami', 'i-bnn',
        'i-default', 'i-enochian', 'i-hak', 'i-klingon', 'i-lux', 'i-mingo',
        'i-navajo', 'i-pwn', 'i-tao', 'i-tay', 'i-tsu', 'no-bok', 'no-nyn',
        'sgn-be-fr', 'sgn-be-nl', 'sgn-ch-de', 'zh-guoyu', 'zh-hakka',
        'zh-min', 'zh-min-nan', 'zh-xiang',
    ];

    private const WELL_FORMED = '/^(?:'
        . '(?:[A-Za-z]{2,3}(?:-[A-Za-z]{3}){0,3}|[A-Za-z]{4}|[A-Za-z]{5,8})'
        . '(?:-[A-Za-z]{4})?'
        . '(?:-(?:[A-Za-z]{2}|[0-9]{3}))?'
        . '(?:-(?:[A-Za-z0-9]{5,8}|[0-9][A-Za-z0-9]{3}))*'
        . '(?:-[0-9A-WY-Za-wy-z](?:-[A-Za-z0-9]{2,8})+)*'
        . '(?:-x(?:-[A-Za-z0-9]{1,8})+)?'
        . '|x(?:-[A-Za-z0-9]{1,8})+'
        . ')$/Di';

    private function __construct(private string $value) {}

    public static function from(string $value): self
    {
        $value = trim($value);
        if (! self::isWellFormed($value)) {
            throw new PortableConfigurationException(
                'LOCALE_TAG_INVALID',
                "Locale tag [$value] is not a well-formed BCP 47 language tag.",
            );
        }

        return new self(self::canonicalize($value));
    }

    public static function isWellFormed(string $value): bool
    {
        $value = trim($value);
        if ($value === '' || str_contains($value, '_') || str_contains($value, "\0")) {
            return false;
        }

        return in_array(strtolower($value), self::GRANDFATHERED, true)
            || preg_match(self::WELL_FORMED, $value) === 1;
    }

    public function language(): string
    {
        return strtolower(strtok($this->value, '-') ?: $this->value);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    private static function canonicalize(string $value): string
    {
        $lower = strtolower($value);
        if (in_array($lower, self::GRANDFATHERED, true)) {
            return $lower;
        }

        $parts = explode('-', $value);
        $parts[0] = strtolower($parts[0]);
        $private = $parts[0] === 'x';
        $extension = false;

        for ($index = 1, $count = count($parts); $index < $count; $index++) {
            $part = $parts[$index];
            if ($private) {
                $parts[$index] = strtolower($part);

                continue;
            }
            if (strtolower($part) === 'x') {
                $parts[$index] = 'x';
                $private = true;

                continue;
            }
            if (strlen($part) === 1) {
                $parts[$index] = strtolower($part);
                $extension = true;

                continue;
            }
            if ($extension) {
                $parts[$index] = strtolower($part);

                continue;
            }
            if (strlen($part) === 4 && ctype_alpha($part)) {
                $parts[$index] = ucfirst(strtolower($part));

                continue;
            }
            if ((strlen($part) === 2 && ctype_alpha($part))
                || (strlen($part) === 3 && ctype_digit($part))
            ) {
                $parts[$index] = strtoupper($part);

                continue;
            }
            $parts[$index] = strtolower($part);
        }

        return implode('-', $parts);
    }
}
