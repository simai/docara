<?php

declare(strict_types=1);

namespace Simai\Docara\Document;

final readonly class DocumentRenderContext
{
    public function __construct(public ?string $sourceRoot, public ?string $sourceFile) {}
}
