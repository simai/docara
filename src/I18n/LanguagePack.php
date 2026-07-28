<?php

declare(strict_types=1);

namespace Simai\Docara\I18n;

final readonly class LanguagePack
{
    /**
     * @param  array<string, string>  $messages
     * @param  array<string, array<string, mixed>>  $components
     */
    public function __construct(
        public LocaleTag $locale,
        public array $messages,
        public array $components,
        public string $source,
    ) {}
}
