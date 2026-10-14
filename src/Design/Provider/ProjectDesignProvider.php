<?php

declare(strict_types=1);

namespace Simai\Docara\Design\Provider;

use Simai\Docara\Portable\PortableConfigurationException;

final class ProjectDesignProvider extends FilesystemDesignProvider
{
    public function __construct(string $projectRoot, string $namespace, string $revision)
    {
        if (in_array($namespace, ['docara', 'content', 'shell', 'ui'], true)) {
            throw new PortableConfigurationException(
                'DESIGN_PROJECT_NAMESPACE_RESERVED',
                "Project design namespace [$namespace] is reserved.",
            );
        }
        parent::__construct(
            'project.' . $namespace,
            $revision,
            rtrim($projectRoot, '/\\') . '/design',
            [$namespace],
            300,
            true,
        );
    }
}
