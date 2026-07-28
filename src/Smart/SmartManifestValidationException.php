<?php

declare(strict_types=1);

namespace Simai\Docara\Smart;

final class SmartManifestValidationException extends \RuntimeException
{
    public function __construct(
        public readonly string $reason,
        public readonly string $component,
        public readonly string $path = '',
    ) {
        parent::__construct($reason . ':' . $component . ($path === '' ? '' : ':' . $path));
    }
}
