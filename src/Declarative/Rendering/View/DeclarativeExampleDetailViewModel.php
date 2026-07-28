<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Rendering\View;

final readonly class DeclarativeExampleDetailViewModel
{
    /** @param list<DeclarativeExampleSourceViewModel> $sources */
    public function __construct(
        public string $title,
        public string $description,
        public string $category,
        public string $resultUrl,
        public string $previewSize,
        public array $sources,
        public string $resultLabel,
        public string $openSeparatelyLabel,
        public string $resultFrameLabel,
        public string $sourcesLabel,
        public string $sourcesDescription,
    ) {}
}
