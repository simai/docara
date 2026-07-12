<?php

declare(strict_types=1);

namespace Larena\Docara\Ui;

use Larena\Ui\Contracts\SmartComponentManifest;
use Larena\Ui\Contracts\SmartContributionProvider;
use Larena\Ui\Registry\SmartRegistry;

final class DocaraSmartContribution implements SmartContributionProvider
{
    public function contributionId(): string
    {
        return 'docara.smart_components';
    }

    public function contribute(SmartRegistry $registry): void
    {
        $registry->registerManifest(SmartComponentManifest::fromJsonFile(
            __DIR__ . '/../../resources/smart/page-title-field/manifest.json',
        ));
    }
}
