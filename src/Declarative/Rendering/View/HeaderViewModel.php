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
    ) {}

    public function markSizeClasses(): string
    {
        return match ($this->size) {
            'small' => 'w-c2 h-c2',
            'large' => 'w-d0 h-d0',
            default => 'w-c6 h-c6',
        };
    }

    public function titleSizeClass(): string
    {
        return match ($this->size) {
            'small' => 'text-1/2',
            'large' => 'text-2',
            default => '',
        };
    }
}
