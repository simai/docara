<?php

declare(strict_types=1);

namespace Simai\Docara\Framework;

final readonly class ComponentDirective
{
    /** @param array<string, mixed> $props */
    public function __construct(
        public string $component,
        public array $props,
        public int $ordinal,
        public int $line,
        public string $placeholder,
    ) {}
}
