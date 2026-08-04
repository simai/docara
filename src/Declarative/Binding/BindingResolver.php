<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Binding;

use Simai\Docara\Declarative\Composition\PageCompositionContext;

interface BindingResolver
{
    /** @return array<string, mixed> */
    public function resolve(BindingInvocation $invocation, PageCompositionContext $context): array;
}
