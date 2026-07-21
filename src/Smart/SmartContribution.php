<?php

declare(strict_types=1);

namespace Simai\Docara\Smart;

interface SmartContribution
{
    public function contribute(SmartRegistryBuilder $registry): void;
}
