<?php

declare(strict_types=1);

namespace Simai\Docara\Smart;

final readonly class SmartComponentDefinition
{
    /**
     * @param array{path:string,schema:?string} $manifest
     * @param array<string, array{path:string,schema:string,template:string}> $views
     * @param array<string, array{path:string,renderer:string}> $templates
     * @param array<string, string> $aliases
     * @param array<string, array{path:string,kind:string,public:string,version:string}> $assets
     */
    public function __construct(
        public string $key,
        public string $ownerPackage,
        public array $manifest,
        public array $views,
        public array $templates,
        public array $aliases = [],
        public array $assets = [],
    ) {}
}
