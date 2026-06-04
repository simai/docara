<?php

declare(strict_types=1);

namespace Larena\Docara\Contracts;

use Larena\Docara\Enums\PublicationStatus;

final readonly class PublicationState
{
    public function __construct(
        public PublicationStatus $status,
        public string $version,
        public bool $publiclyVisible,
        public ?string $publishedAt = null,
    ) {
    }
}
