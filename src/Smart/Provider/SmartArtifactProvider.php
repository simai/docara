<?php

declare(strict_types=1);

namespace Simai\Docara\Smart\Provider;

interface SmartArtifactProvider
{
    public function id(): string;

    public function priority(): int;

    /** @return list<string> */
    public function namespaces(): array;

    /** @return iterable<SmartArtifactDescriptor> */
    public function descriptors(): iterable;

    public function fingerprint(): string;
}
