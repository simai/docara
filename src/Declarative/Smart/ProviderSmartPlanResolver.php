<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Smart;

use Simai\Docara\Declarative\Document\SmartCallNode;
use Simai\Docara\Declarative\Plan\ResolvedSmartPlan;

interface ProviderSmartPlanResolver
{
    public function providerId(): string;

    public function resolve(SmartCallNode $call): ResolvedSmartPlan;
}
