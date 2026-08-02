<?php

declare(strict_types=1);

namespace Simai\Docara\Smart\Provider;

final class ProjectSmartProvider extends FilesystemSmartProvider
{
    public function __construct(string $namespace, string $root, string $ownerPackage, string $revision)
    {
        if (in_array($namespace, ['ui', 'docara'], true)) {
            throw new SmartProviderException('SMART_PROJECT_NAMESPACE_RESERVED', $namespace);
        }
        parent::__construct('project.' . $namespace, 100, [$namespace], $root, $ownerPackage, $revision);
    }
}
