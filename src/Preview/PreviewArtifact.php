<?php

declare(strict_types=1);

namespace Simai\Docara\Preview;

use Simai\Docara\Portable\CanonicalJson;

final readonly class PreviewArtifact
{
    /** @param list<string> $assets @param list<string> $dependencies @param array<string, mixed> $provenance */
    public function __construct(
        public PreviewTarget $target,
        public string $page,
        public ?string $selector,
        public string $html,
        public string $pageHtml,
        public array $assets,
        public array $dependencies,
        public array $provenance,
        public string $publicRoot,
    ) {}

    public function sha256(): string
    {
        return hash('sha256', $this->html);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'schema' => 'docara.preview_artifact.v1',
            'status' => 'ok',
            'target' => $this->target->value,
            'page' => $this->page,
            'selector' => $this->selector,
            'html_sha256' => $this->sha256(),
            'assets' => $this->assets,
            'dependencies' => $this->dependencies,
            'provenance' => $this->provenance,
            'accepted_build_receipt' => false,
            'canonical_sha256' => hash('sha256', CanonicalJson::encode([
                $this->target->value,
                $this->page,
                $this->selector,
                $this->sha256(),
                hash('sha256', $this->pageHtml),
                $this->assets,
                $this->dependencies,
                $this->provenance,
            ])),
        ];
    }
}
