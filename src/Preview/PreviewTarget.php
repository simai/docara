<?php

declare(strict_types=1);

namespace Simai\Docara\Preview;

enum PreviewTarget: string
{
    case Smart = 'smart';
    case Region = 'region';
    case Layout = 'layout';
    case Page = 'page';
}
