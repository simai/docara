<?php

declare(strict_types=1);

namespace Simai\Docara\Content;

final readonly class FrontMatterDocument
{
    /** @param array{title?:string,description?:string,tags?:list<string>,draft?:bool,translation_key?:string,profile?:string} $metadata */
    public function __construct(
        public string $markdown,
        public array $metadata,
    ) {}
}
