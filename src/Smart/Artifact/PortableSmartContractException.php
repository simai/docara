<?php

declare(strict_types=1);

namespace Simai\Docara\Smart\Artifact;

final class PortableSmartContractException extends \RuntimeException
{
    public function __construct(string $code, string $artifact, string $path)
    {
        parent::__construct($code . ':' . $artifact . ':' . $path);
    }
}
