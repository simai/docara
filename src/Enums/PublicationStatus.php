<?php

declare(strict_types=1);

namespace Larena\Docara\Enums;

enum PublicationStatus: string
{
    case Draft = 'draft';
    case Review = 'review';
    case Published = 'published';
    case Archived = 'archived';
}
