<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Rendering;

final readonly class RenderArtifact
{
    /**
     * @param  list<string>  $assets
     * @param  array<string, mixed>  $hydration
     * @param  array<string, mixed>  $provenance
     */
    public function __construct(
        public string $html,
        public array $assets,
        public array $hydration,
        public array $provenance,
    ) {
        if ($html === '') {
            throw new \InvalidArgumentException('RENDER_ARTIFACT_HTML_REQUIRED');
        }
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'html_sha256' => hash('sha256', $this->html),
            'assets' => $this->assets,
            'hydration' => $this->hydration,
            'provenance' => $this->provenance,
        ];
    }
}
