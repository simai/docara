<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Rendering\View;

final readonly class AlertViewModel
{
    public function __construct(
        public string $id,
        public string $runtimePair,
        public string $type,
        public string $variant,
        public string $title,
        public string $supportingText,
        public string $ariaLabel,
        public bool $closable,
        public ?string $icon,
    ) {}
}
