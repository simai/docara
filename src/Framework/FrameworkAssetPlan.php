<?php

declare(strict_types=1);

namespace Simai\Docara\Framework;

final readonly class FrameworkAssetPlan
{
    /**
     * @param  list<array<string, mixed>>  $assets
     * @param  list<array<string, mixed>>  $generatedAssets
     * @param  array<string, mixed>  $preload
     * @param  list<array<string, string>>  $diagnostics
     */
    public function __construct(
        public string $runtimePair,
        public array $assets,
        public array $generatedAssets = [],
        public array $preload = [],
        public array $diagnostics = [],
    ) {}

    public function headHtml(): string
    {
        $html = [];
        foreach ($this->assets as $asset) {
            $kind = $asset['kind'] ?? null;
            if ($kind === 'boot') {
                $html[] = '<script data-docara-framework-boot="' . $this->escape($this->runtimePair)
                    . '" data-docara-framework-asset="' . $this->escape((string) $asset['key']) . '">'
                    . (string) $asset['content'] . '</script>';

                continue;
            }
            if ($kind === 'css') {
                $html[] = '<link rel="stylesheet" href="' . $this->escape((string) $asset['url'])
                    . '" data-docara-framework-asset="' . $this->escape((string) $asset['key']) . '">';

                continue;
            }
            if ($kind === 'font_preload') {
                $html[] = '<link rel="preload" as="font" type="font/woff2" crossorigin href="'
                    . $this->escape((string) $asset['url'])
                    . '" data-docara-framework-asset="' . $this->escape((string) $asset['key']) . '">';

                continue;
            }
            if ($kind === 'inline_css') {
                $html[] = '<style data-docara-framework-asset="' . $this->escape((string) $asset['key']) . '">'
                    . (string) $asset['content'] . '</style>';

                continue;
            }
            if ($kind === 'javascript') {
                $html[] = '<script defer src="' . $this->escape((string) $asset['url'])
                    . '" data-docara-framework-asset="' . $this->escape((string) $asset['key']) . '"></script>';

                continue;
            }
            if ($kind === 'preloaded_smart_javascript') {
                $html[] = '<script defer src="' . $this->escape((string) $asset['url'])
                    . '" data-docara-framework-preloaded-smart="' . $this->escape((string) $asset['tag'])
                    . '" data-docara-framework-asset="' . $this->escape((string) $asset['key']) . '"></script>';

                continue;
            }
            if ($kind === 'smart_javascript') {
                // The canonical Framework loader discovers sf-* elements and
                // resolves their pinned local assets through sfSmartPath.
                // Emitting these scripts here as well races that loader and
                // attempts to define the same custom element twice.
                continue;
            }
        }

        return implode("\n", $html);
    }

    public function shellCssUrl(): ?string
    {
        foreach ($this->generatedAssets as $asset) {
            if (($asset['kind'] ?? null) === 'shell_css' && is_string($asset['url'] ?? null)) {
                return $asset['url'];
            }
        }

        return null;
    }

    /** @return array<string, mixed> */
    public function receipt(): array
    {
        return [
            'schema' => 'docara.framework_shell_assets.v1',
            'runtime_pair' => $this->runtimePair,
            'generated_assets' => array_map($this->generatedAssetMetadata(...), $this->generatedAssets),
            'preload' => $this->preload,
            'diagnostics' => $this->diagnostics,
        ];
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'runtime_pair' => $this->runtimePair,
            'assets' => $this->assets,
            'generated_assets' => array_map($this->generatedAssetMetadata(...), $this->generatedAssets),
            'preload' => $this->preload,
            'diagnostics' => $this->diagnostics,
            'head_html' => $this->headHtml(),
        ];
    }

    /** @param array<string, mixed> $asset @return array<string, mixed> */
    private function generatedAssetMetadata(array $asset): array
    {
        unset($asset['content']);

        return $asset;
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
