<?php

declare(strict_types=1);

namespace Simai\Docara\Smart\Runtime\Context;

use Simai\Docara\Smart\Runtime\SmartInvocation;

interface SmartContextAdapter
{
    public function id(): string;

    public function prepare(SmartInvocation $invocation): object;
}
