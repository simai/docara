<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Rendering\View;

final readonly class HeaderViewModel
{
    public function __construct(
        public string $title,
        public ?string $label,
        public string $homeUrl,
        public ?string $logo,
        public ?string $logoDark,
    ) {}
}
