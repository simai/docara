<?php

namespace Simai\Docara\Portable;

use RuntimeException;
use Throwable;

final class PortableConfigurationException extends RuntimeException implements SourceLocatedException
{
    public function __construct(
        public readonly string $errorCode,
        string $message,
        ?Throwable $previous = null,
        private readonly ?string $diagnosticPath = null,
        private readonly string $diagnosticPointer = '/',
        private readonly ?int $diagnosticLine = null,
        private readonly ?int $diagnosticColumn = null,
    ) {
        parent::__construct("[{$errorCode}] {$message}", 0, $previous);
    }

    public function sourcePath(): string
    {
        return $this->diagnosticPath ?? 'command';
    }

    public function sourcePointer(): string
    {
        return $this->diagnosticPointer;
    }

    public function sourceLine(): int
    {
        return $this->diagnosticLine ?? 1;
    }

    public function sourceColumn(): int
    {
        return $this->diagnosticColumn ?? 1;
    }

    public function hasFileLocation(): bool
    {
        return $this->diagnosticPath !== null && $this->diagnosticLine !== null && $this->diagnosticColumn !== null;
    }
}
