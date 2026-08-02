<?php

declare(strict_types=1);

namespace Simai\Docara\Smart\Provider;

final class SmartProviderException extends \RuntimeException
{
    public function __construct(string $code, string $detail)
    {
        parent::__construct($code . ':' . $detail);
    }
}
