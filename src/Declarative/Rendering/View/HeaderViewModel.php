<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Rendering\View;

final readonly class HeaderViewModel
{
    public function __construct(
        public string $title,
        public ?string $label,
        public string $size,
        public string $homeUrl,
        public ?string $logo,
        public ?string $logoDark,
        /** The icon-only mark a narrow header shows in place of the logo. */
        public ?string $logoIcon = null,
    ) {}
}
