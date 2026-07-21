<?php

declare(strict_types=1);

namespace Simai\Docara\I18n;

final readonly class LocaleDefinition
{
    /** @param list<string> $fallbacks */
    public function __construct(
        public LocaleTag $tag,
        public string $label,
        public string $direction,
        public string $contentRoot,
        public string $languagePack,
        public string $publicPrefix,
        public array $fallbacks,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'tag' => $this->tag->value(),
            'label' => $this->label,
            'direction' => $this->direction,
            'content_root' => $this->contentRoot,
            'language_pack' => $this->languagePack,
            'public_prefix' => $this->publicPrefix,
            'fallbacks' => $this->fallbacks,
        ];
    }
}
