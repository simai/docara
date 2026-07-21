<?php

declare(strict_types=1);

namespace Simai\Docara\Smart;

final class SmartPropsValidationException extends \RuntimeException
{
    public function __construct(string $component, string $path)
    {
        parent::__construct('SMART_PROPS_INVALID:' . $component . ':' . $path);
    }
}
