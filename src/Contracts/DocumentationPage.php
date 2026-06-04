<?php

declare(strict_types=1);

namespace Larena\Docara\Contracts;

use Larena\Docara\Enums\DocumentationVisibility;

final readonly class DocumentationPage
{
    /**
     * @param list<string> $sectionRefs
     * @param list<DocumentationAssetRef> $assets
     */
    public function __construct(
        public string $pageRef,
        public string $slug,
        public string $locale,
        public DocumentationVisibility $visibility,
        public PublicationState $publication,
        public array $sectionRefs = [],
        public array $assets = [],
    ) {
    }
}
