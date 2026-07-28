<?php

declare(strict_types=1);

namespace Simai\Docara\Smart;

final class FrameworkSmartContribution implements SmartContribution
{
    public function contribute(SmartRegistryBuilder $registry): void
    {
        foreach (['alert', 'button'] as $name) {
            $key = 'ui.' . $name;
            $template = 'smart.ui.' . $name . '.default';
            $registry->add(new SmartComponentDefinition(
                $key,
                'larena/ui',
                ['path' => 'framework/manifests/ui-' . $name . '.json', 'schema' => null],
                ['default' => [
                    'path' => 'smart/' . $key . '/views/default.json',
                    'schema' => 'declarative-smart-view.schema.json',
                    'template' => $template,
                ]],
                [$template => [
                    'path' => 'smart/' . $key . '/templates/default.php',
                    'renderer' => 'php',
                ]],
            ));
        }
    }
}
