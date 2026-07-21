<?php

declare(strict_types=1);

namespace Simai\Docara\Smart;

final class DocaraSmartContribution implements SmartContribution
{
    public function contribute(SmartRegistryBuilder $registry): void
    {
        $this->add(
            $registry,
            'docara.brand',
            ['default' => ['file' => 'default.php', 'renderer' => 'php'], 'compact' => ['file' => 'compact.php', 'renderer' => 'php']],
            [],
            ['docara.header' => 'Renamed because header is a layout region; use docara.brand.'],
            ['docara.smart.brand.css' => $this->asset('brand.css', 'css')],
        );
        $this->add(
            $registry,
            'docara.navigation',
            [
                'default' => ['file' => 'default.blade.php', 'renderer' => 'blade'],
                'compact' => ['file' => 'compact.blade.php', 'renderer' => 'blade'],
                'tree' => ['file' => 'tree.blade.php', 'renderer' => 'blade'],
            ],
            ['smart.docara.navigation.item' => [
                'path' => 'smart/docara.navigation/templates/item.php',
                'renderer' => 'php',
            ]],
            [],
            [
                'docara.smart.navigation.css' => $this->asset('navigation.css', 'css'),
                'docara.smart.navigation.js' => $this->asset('navigation.js', 'javascript'),
            ],
        );
        $this->add(
            $registry,
            'docara.toc',
            ['default' => ['file' => 'default.php', 'renderer' => 'php'], 'compact' => ['file' => 'compact.php', 'renderer' => 'php']],
            [],
            ['docara.outline' => 'Renamed because outline is a layout region; use docara.toc.'],
            [
                'docara.smart.toc.css' => $this->asset('toc.css', 'css'),
                'docara.smart.toc.js' => $this->asset('toc.js', 'javascript'),
            ],
        );
    }

    /**
     * @param array<string, array{file:string,renderer:string}> $views
     * @param array<string, array{path:string,renderer:string}> $extraTemplates
     * @param array<string, string> $aliases
     * @param array<string, array{path:string,kind:string,public:string}> $assets
     */
    private function add(
        SmartRegistryBuilder $registry,
        string $key,
        array $views,
        array $extraTemplates = [],
        array $aliases = [],
        array $assets = [],
    ): void {
        $viewRecords = [];
        $templates = $extraTemplates;
        foreach ($views as $view => $render) {
            $template = 'smart.' . $key . '.' . $view;
            $viewRecords[$view] = [
                'path' => 'smart/' . $key . '/views/' . $view . '.json',
                'schema' => 'declarative-smart-view.schema.json',
                'template' => $template,
            ];
            $templates[$template] = [
                'path' => 'smart/' . $key . '/templates/' . $render['file'],
                'renderer' => $render['renderer'],
            ];
        }
        $registry->add(new SmartComponentDefinition(
            $key,
            'simai/docara',
            [
                'path' => 'smart/' . $key . '/manifest.json',
                'schema' => 'declarative-smart-manifest.schema.json',
            ],
            $viewRecords,
            $templates,
            $aliases,
            $assets,
        ));
    }

    /** @return array{path:string,kind:string,public:string} */
    private function asset(string $file, string $kind): array
    {
        return [
            'path' => 'smart/assets/' . $file,
            'kind' => $kind,
            'public' => 'smart/' . $file,
        ];
    }
}
