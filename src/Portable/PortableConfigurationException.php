<?php

namespace Simai\Docara\Portable;

use RuntimeException;
use Throwable;

final class PortableConfigurationException extends RuntimeException
{
    public function __construct(
        public readonly string $errorCode,
        string $message,
        ?Throwable $previous = null,
    ) {
        parent::__construct("[{$errorCode}] {$message}", 0, $previous);
    }
}
