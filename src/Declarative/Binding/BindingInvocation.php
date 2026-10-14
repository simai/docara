<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Binding;

final readonly class BindingInvocation
{
    /** @param array<string, mixed> $staticProps */
    public function __construct(
        public string $smart,
        public string $view,
        public array $staticProps,
        public string $source,
    ) {}
}
