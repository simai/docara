<?php

namespace Simai\Docara\Portable;

final readonly class MergeResult
{
    /**
     * @param  array<string, mixed>  $configuration
     * @param  array<string, string>  $provenance
     */
    public function __construct(
        public array $configuration,
        public array $provenance,
    ) {}
}
