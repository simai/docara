<?php

declare(strict_types=1);

namespace Simai\Docara\Smart\Runtime\Strategy;

use Simai\Docara\Declarative\Rendering\TrustedTemplateRegistry;
use Simai\Docara\Smart\Runtime\SmartInvocation;
use Simai\Docara\Smart\Runtime\SmartTemplateContext;

interface SmartRendererStrategy
{
    public function id(): string;

    public function render(SmartInvocation $invocation, SmartTemplateContext $context, TrustedTemplateRegistry $templates): string;
}
