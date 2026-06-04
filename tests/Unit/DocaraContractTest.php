<?php

declare(strict_types=1);

use Larena\Docara\Contracts\DocumentationAssetRef;
use Larena\Docara\Contracts\DocumentationPage;
use Larena\Docara\Contracts\PublicationState;
use Larena\Docara\Enums\DocumentationVisibility;
use Larena\Docara\Enums\PublicationStatus;

$publication = new PublicationState(
    status: PublicationStatus::Published,
    version: '1.0.0',
    publiclyVisible: true,
);

$page = new DocumentationPage(
    pageRef: 'docara:page:intro',
    slug: 'intro',
    locale: 'en',
    visibility: DocumentationVisibility::Public,
    publication: $publication,
    sectionRefs: ['docara:section:intro'],
    assets: [new DocumentationAssetRef(logicalFileRef: 'file:docara:intro-image', purpose: 'hero')],
);

assert($page->publication->publiclyVisible === true);
assert($page->assets[0]->logicalFileRef === 'file:docara:intro-image');
