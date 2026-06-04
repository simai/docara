<?php

declare(strict_types=1);

use Larena\Docara\Contracts\PublicationState;
use Larena\Docara\Contracts\SearchProjection;
use Larena\Docara\Enums\PublicationStatus;

$draft = new PublicationState(
    status: PublicationStatus::Draft,
    version: '1.0.0-draft',
    publiclyVisible: false,
);

assert($draft->publiclyVisible === false);

$projection = new SearchProjection(
    pageRef: 'docara:page:hidden',
    locale: 'en',
    title: 'Hidden draft',
    tokens: [],
    visibleToPublicSearch: false,
);

assert($projection->visibleToPublicSearch === false);
