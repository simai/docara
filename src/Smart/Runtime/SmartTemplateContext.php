<?php

declare(strict_types=1);

namespace Simai\Docara\Smart\Runtime;

final readonly class SmartTemplateContext
{
    /**
     * @param  array<string, mixed>  $manifest
     * @param  array<string, mixed>  $view
     * @param  array<string, mixed>  $preset
     * @param  array<string, mixed>  $props
     * @param  array<string, string>  $slotsHtml
     */
    public function __construct(
        public string $id,
        public string $smart,
        public array $manifest,
        public array $view,
        public array $preset,
        public array $props,
        public array $slotsHtml,
        public string $childrenHtml,
        public string $locale,
        public string $direction,
        public string $route,
        public \Closure $assetUrl,
        public \Closure $escape,
        public \Closure $renderChild,
        public object $viewModel,
    ) {}

    public static function forInvocation(SmartInvocation $invocation, object $viewModel): self
    {
        return new self(
            $invocation->id,
            $invocation->smart,
            is_array($invocation->provenance['portable_manifest'] ?? null)
                ? $invocation->provenance['portable_manifest']
                : [],
            [],
            [],
            $invocation->props,
            [],
            '',
            (string) ($invocation->provenance['locale'] ?? ''),
            (string) ($invocation->provenance['direction'] ?? 'ltr'),
            (string) ($invocation->provenance['route'] ?? ''),
            static fn (string $asset): string => $asset,
            static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            static fn (): string => '',
            $viewModel,
        );
    }
}
