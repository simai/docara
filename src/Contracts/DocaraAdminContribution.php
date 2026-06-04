<?php

declare(strict_types=1);

namespace Larena\Docara\Contracts;

final readonly class DocaraAdminContribution
{
    /**
     * @param list<string> $requiredPermissions
     */
    public function __construct(
        public string $screenKey,
        public string $title,
        public array $requiredPermissions = [],
        public bool $diagnosticsOnly = false,
    ) {
    }
}
