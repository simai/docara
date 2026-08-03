<?php

declare(strict_types=1);

namespace Simai\Docara\Smart\Provider;

use Simai\Docara\Portable\SourceLocatedException;

final class SmartProviderException extends \RuntimeException implements SourceLocatedException
{
    public function __construct(
        string $code,
        string $detail,
        private readonly ?string $diagnosticPath = null,
        private readonly string $diagnosticPointer = '/',
        private readonly ?int $diagnosticLine = null,
        private readonly ?int $diagnosticColumn = null,
    ) {
        parent::__construct($code . ':' . $detail);
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
