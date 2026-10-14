<?php

declare(strict_types=1);

namespace Simai\Docara\Design\Provider;

use Simai\Docara\Design\Artifact\DesignArtifactDescriptor;

interface DesignArtifactProvider
{
    public function id(): string;

    public function revision(): string;

    public function priority(): int;

    /** @return list<string> */
    public function namespaces(): array;

    /** @return list<DesignArtifactDescriptor> */
    public function descriptors(): array;

    public function fingerprint(): string;
}
