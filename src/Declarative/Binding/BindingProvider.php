<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Binding;

interface BindingProvider
{
    public function id(): string;

    public function revision(): string;

    public function priority(): int;

    /** @return list<string> */
    public function namespaces(): array;

    /** @return list<BindingDescriptor> */
    public function descriptors(): array;
}
