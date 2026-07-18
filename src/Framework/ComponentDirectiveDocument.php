<?php

declare(strict_types=1);

namespace Simai\Docara\Framework;

final readonly class ComponentDirectiveDocument
{
    /**
     * @param  array<string, string>  $renderedHtml
     * @param  list<array<string, mixed>>  $normalizedCalls
     * @param  array<string, mixed>  $diagnostics
     */
    public function __construct(
        public string $markdownWithPlaceholders,
        public array $renderedHtml,
        public array $normalizedCalls,
        public FrameworkAssetPlan $assetPlan,
        public array $diagnostics,
    ) {}

    public function hydrate(string $renderedMarkdownHtml): string
    {
        foreach ($this->renderedHtml as $placeholder => $html) {
            $renderedMarkdownHtml = str_replace(
                [
                    '<p>' . $placeholder . '</p>',
                    '<p>' . $placeholder . '</p>' . "\n",
                    $placeholder,
                ],
                $html,
                $renderedMarkdownHtml,
            );
        }

        return $renderedMarkdownHtml;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'markdown_with_placeholders' => $this->markdownWithPlaceholders,
            'rendered_html' => $this->renderedHtml,
            'normalized_calls' => $this->normalizedCalls,
            'asset_plan' => $this->assetPlan->toArray(),
            'diagnostics' => $this->diagnostics,
        ];
    }
}
