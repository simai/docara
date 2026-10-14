<?php

declare(strict_types=1);

namespace Simai\Docara\Smart\Provider;

final class PackageSmartProvider extends FilesystemSmartProvider
{
    /** @param list<string> $namespaces */
    public function __construct(string $id, array $namespaces, string $root, string $ownerPackage, string $revision)
    {
        parent::__construct('package.' . $id, 200, $namespaces, $root, $ownerPackage, $revision);
    }
}
