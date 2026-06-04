<?php

declare(strict_types=1);

namespace Larena\Docara\Enums;

enum DocumentationVisibility: string
{
    case Public = 'public';
    case Authenticated = 'authenticated';
    case Internal = 'internal';
    case Private = 'private';
}
