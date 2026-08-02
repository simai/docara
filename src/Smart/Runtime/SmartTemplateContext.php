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
     */
    public function __construct(
        public string $id,
        public string $smart,
        public array $manifest,
        public array $view,
        public array $preset,
        public array $props,
        public string $childrenHtml,
        public string $slot,
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
        $view = $invocation->provenance['portable_view'] ?? [];
        $preset = $invocation->provenance['portable_preset'] ?? [];
        $childrenHtml = $invocation->provenance['children_html'] ?? '';
        $slot = $invocation->provenance['slot'] ?? '';
        if (! is_array($view) || ! is_array($preset)
            || ! is_string($childrenHtml) || ! is_string($slot)
        ) {
            throw new \InvalidArgumentException('SMART_TEMPLATE_CONTEXT_INVALID');
        }

        return new self(
            $invocation->id,
            $invocation->smart,
            is_array($invocation->provenance['portable_manifest'] ?? null)
                ? $invocation->provenance['portable_manifest']
                : [],
            $view,
            $preset,
            $invocation->props,
            $childrenHtml,
            $slot,
            (string) ($invocation->provenance['locale'] ?? ''),
            (string) ($invocation->provenance['direction'] ?? 'ltr'),
            (string) ($invocation->provenance['route'] ?? ''),
            static fn (string $asset): string => $asset,
            static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            static fn (): string => '',
            $viewModel,
        );
    }

    /** @return array{id:string,smart:string,manifest:array<string,mixed>,view:array<string,mixed>,preset:array<string,mixed>,props:array<string,mixed>,childrenHtml:string,slot:string} */
    public function portableVariables(): array
    {
        return [
            'id' => $this->id,
            'smart' => $this->smart,
            'manifest' => $this->manifest,
            'view' => $this->view,
            'preset' => $this->preset,
            'props' => $this->props,
            'childrenHtml' => $this->childrenHtml,
            'slot' => $this->slot,
        ];
    }

    /** @return array{view:object,smartContext:self} */
    public function legacyVariables(): array
    {
        return [
            'view' => $this->viewModel,
            'smartContext' => $this,
        ];
    }
}
