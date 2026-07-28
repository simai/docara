<?php

declare(strict_types=1);

namespace Simai\Docara\Framework;

final readonly class ParsedComponentDirectives
{
    /** @param list<ComponentDirective> $directives */
    public function __construct(
        public string $markdownWithPlaceholders,
        public array $directives,
    ) {}
}
