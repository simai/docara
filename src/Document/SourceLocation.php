<?php

declare(strict_types=1);

namespace Simai\Docara\Document;

final readonly class SourceLocation
{
    public function __construct(
        public string $file,
        public int $line,
        public int $column,
        public int $endLine,
    ) {
        if ($file === '' || $line < 1 || $column < 1 || $endLine < $line) {
            throw new \InvalidArgumentException('DOCUMENT_IR_SOURCE_LOCATION_INVALID');
        }
    }

    /** @return array{file:string,line:int,column:int,end_line:int} */
    public function toArray(): array
    {
        return [
            'file' => $this->file,
            'line' => $this->line,
            'column' => $this->column,
            'end_line' => $this->endLine,
        ];
    }

    public function label(): string
    {
        return "{$this->file}:{$this->line}:{$this->column}";
    }
}
