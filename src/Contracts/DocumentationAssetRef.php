<?php

declare(strict_types=1);

namespace Larena\Docara\Contracts;

final readonly class DocumentationAssetRef
{
    public function __construct(
        public string $logicalFileRef,
        public string $purpose,
        public ?string $altText = null,
    ) {
    }
}
