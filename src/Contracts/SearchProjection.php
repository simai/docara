<?php

declare(strict_types=1);

namespace Larena\Docara\Contracts;

final readonly class SearchProjection
{
    /**
     * @param list<string> $tokens
     */
    public function __construct(
        public string $pageRef,
        public string $locale,
        public string $title,
        public array $tokens,
        public bool $visibleToPublicSearch,
    ) {
    }
}
