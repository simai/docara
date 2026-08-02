<?php

declare(strict_types=1);

namespace Simai\Docara\Smart\Provider;

final class DocaraSmartProvider extends FilesystemSmartProvider
{
    public function __construct(string $root, string $revision)
    {
        parent::__construct('docara.package', 300, ['docara'], $root, 'simai/docara', $revision);
    }
}
