<?php

declare(strict_types=1);

namespace Larena\Docara\Contracts;

interface DocumentationPageRepository
{
    public function save(DocumentationPage $page): DocumentationPage;

    public function findByLocaleAndSlug(string $locale, string $slug): ?DocumentationPage;

    public function findPublishedByLocaleAndSlug(string $locale, string $slug): ?DocumentationPage;

    public function findPublishedByPageRef(string $pageRef): ?DocumentationPage;
}
