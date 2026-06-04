<?php

declare(strict_types=1);

namespace Larena\Docara\Contracts;

final readonly class DocumentationSection
{
    /**
     * @param list<string> $childSectionRefs
     */
    public function __construct(
        public string $sectionRef,
        public string $slug,
        public string $title,
        public array $childSectionRefs = [],
    ) {
    }
}
