<?php

declare(strict_types=1);

namespace Simai\Docara\Framework;

use RuntimeException;

final class FrameworkComponentException extends RuntimeException
{
    public function __construct(public readonly string $errorCode, string $detail = '')
    {
        parent::__construct('[' . $errorCode . ']' . ($detail === '' ? '' : ' ' . $detail));
    }
}
