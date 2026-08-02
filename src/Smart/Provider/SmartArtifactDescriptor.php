<?php

declare(strict_types=1);

namespace Simai\Docara\Smart\Provider;

use Simai\Docara\Smart\SmartComponentDefinition;

final readonly class SmartArtifactDescriptor
{
    /**
     * @param  array{path:string,schema:?string}  $manifest
     * @param  array<string, array{path:string,schema:string,template:string}>  $views
     * @param  array<string, array{path:string,schema:string,template:string}>  $presets
     * @param  array<string, array{path:string,renderer:string}>  $templates
     * @param  array<string, string>  $aliases
     * @param  array<string, array{path:string,kind:string,public:string,version:string}>  $assets
     * @param  array<string, mixed>  $portableManifest
     * @param  array<string, mixed>  $provenance
     */
    public function __construct(
        public string $id,
        public string $providerId,
        public int $priority,
        public string $ownerPackage,
        public string $root,
        public array $manifest,
        public array $views,
        public array $presets,
        public array $templates,
        public array $aliases,
        public array $assets,
        public array $portableManifest,
        public string $strategy,
        public ?string $adapterId,
        public array $provenance,
    ) {}

    public function definition(): SmartComponentDefinition
    {
        return new SmartComponentDefinition(
            $this->id,
            $this->ownerPackage,
            $this->manifest,
            $this->views,
            $this->templates,
            $this->aliases,
            $this->assets,
            $this->root,
            $this->providerId,
            $this->priority,
            $this->presets,
            $this->portableManifest,
            $this->strategy,
            $this->adapterId,
            $this->provenance,
        );
    }
}
