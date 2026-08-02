<?php

declare(strict_types=1);

namespace Simai\Docara\Smart\Provider;

final class FrameworkLockSmartProvider extends FilesystemSmartProvider
{
    public function __construct(string $root, string $ownerPackage, string $revision)
    {
        if ($revision === '' || in_array(strtolower($revision), ['main', 'master', 'latest', 'head'], true)) {
            throw new SmartProviderException('SMART_FRAMEWORK_REVISION_IMMUTABLE_REQUIRED', $revision);
        }
        parent::__construct('framework.lock', 400, ['ui'], $root, $ownerPackage, $revision);
    }
}
