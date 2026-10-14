<?php

declare(strict_types=1);

namespace Simai\Docara\Document;

final readonly class DocumentRenderContext
{
    /**
     * @param  array<string, mixed>  $examples
     * @param  array<string, mixed>  $code
     */
    public function __construct(
        public ?string $sourceRoot,
        public ?string $sourceFile,
        public array $examples = [],
        public array $code = [],
    ) {}
}
