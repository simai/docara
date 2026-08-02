<?php

declare(strict_types=1);

namespace Simai\Docara\Design\Provider;

final class BuiltinDesignProvider extends FilesystemDesignProvider
{
    public function __construct(string $root = __DIR__ . '/../../../resources')
    {
        parent::__construct(
            'docara.builtin',
            'package-resources-v1',
            $root,
            ['docara', 'content', 'shell'],
            100,
        );
    }
}
