<?php

declare(strict_types=1);

namespace Simai\Docara\Markdown;

use RuntimeException;

final class DirectiveLimitExceeded extends RuntimeException
{
    public function __construct(
        public readonly string $family,
        string $message,
    ) {
        parent::__construct($message);
    }
}
