<?php

declare(strict_types=1);

namespace Simai\Docara\PortableSite;

enum BuildPurpose: string
{
    case Production = 'production';
    case Preview = 'preview';
}
