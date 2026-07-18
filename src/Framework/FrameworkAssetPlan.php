<?php

declare(strict_types=1);

namespace Simai\Docara\Framework;

final readonly class FrameworkAssetPlan
{
    /** @param list<array<string, mixed>> $assets */
    public function __construct(
        public string $runtimePair,
        public array $assets,
    ) {}

    public function headHtml(): string
    {
        $html = [];
        foreach ($this->assets as $asset) {
            $kind = $asset['kind'] ?? null;
            if ($kind === 'boot') {
                $html[] = '<script data-docara-framework-boot="' . $this->escape($this->runtimePair) . '">'
                    . (string) $asset['content'] . '</script>';

                continue;
            }
            if ($kind === 'css') {
                $html[] = '<link rel="stylesheet" href="' . $this->escape((string) $asset['url'])
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
            }
        }

        return implode("\n", $html);
    }

    /** @return array{runtime_pair:string,assets:list<array<string,mixed>>,head_html:string} */
    public function toArray(): array
    {
        return [
            'runtime_pair' => $this->runtimePair,
            'assets' => $this->assets,
            'head_html' => $this->headHtml(),
        ];
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
