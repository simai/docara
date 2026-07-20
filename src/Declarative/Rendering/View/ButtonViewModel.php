<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Rendering\View;

final readonly class ButtonViewModel
{
    public function __construct(
        public string $runtimePair,
        public string $text,
        public string $size,
        public string $type,
        public string $scheme,
        public string $nativeType,
        public string $ariaLabel,
        public ?string $radius,
        public bool $loading,
        public bool $disabled,
    ) {}
}
