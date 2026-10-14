<?php

declare(strict_types=1);

namespace Simai\Docara\PortableSite;

/**
 * One immutable presentation primitive for bounded full-bleed surfaces.
 *
 * It receives only already-admitted enum values, an escaped local asset URL
 * and already-rendered content. It deliberately has no parser, filesystem or
 * component identity knowledge.
 */
final readonly class SurfacePresentation
{
    /** @param array<string, string> $props */
    public function render(
        array $props,
        string $content,
        string $backgroundUrl = '',
        bool $publishBackgroundAsset = false,
    ): string {
        return $this->compose(
            '<section data-docara-block="surface" data-docara-surface data-docara-width="'
                . $props['width'] . '" data-docara-tone="' . $props['tone'] . '"',
            'docara-surface relative isolate overflow-hidden m-bottom-1 ' . $this->toneClass($props['tone']),
            $props,
            $content,
            $backgroundUrl,
            $publishBackgroundAsset,
            '<div data-docara-container data-docara-content-width="' . $props['content_width'] . '"',
            'docara-surface__content relative ' . $this->innerClass($props['content_width']) . ' '
                . $this->paddingClass($props['padding']),
        );
    }

    /**
     * Semantic blocks may reuse the same admitted background frame without
     * exposing presentation paths or creating a second background renderer.
     *
     * @param  array<string, string>  $props
     */
    public function renderSemanticBackground(
        string $semanticBlock,
        string $variant,
        array $props,
        string $content,
        string $backgroundUrl,
        bool $publishBackgroundAsset,
    ): string {
        if (preg_match('/^[a-z][a-z0-9-]*$/D', $semanticBlock) !== 1
            || preg_match('/^[a-z][a-z0-9-]*$/D', $variant) !== 1
            || $backgroundUrl === ''
        ) {
            throw new \LogicException('SURFACE_SEMANTIC_FRAME_INVALID');
        }

        return $this->compose(
            '<section data-docara-block="' . $semanticBlock . '" data-variant="' . $variant
                . '" data-docara-surface data-docara-width="full" data-docara-tone="' . $props['tone'] . '"',
            'docara-surface relative isolate overflow-hidden m-bottom-1 ' . $this->toneClass($props['tone']),
            $props,
            $content,
            $backgroundUrl,
            $publishBackgroundAsset,
            '<div data-docara-container data-docara-content-width="container"',
            'docara-surface__content relative container m-inline-auto grid grid-col-1 gap-4 items-cross-center '
                . $this->paddingClass($props['padding']),
        );
    }

    /**
     * Render the common outer frame for semantic content blocks whose media
     * remains ordinary meaningful content. Component renderers own their
     * content semantics; this presentation owns only the admitted surface
     * geometry and token classes.
     */
    public function renderSemanticFrame(
        string $semanticBlock,
        string $variant,
        string $tone,
        string $padding,
        bool $twoColumns,
        string $content,
        string $media = '',
    ): string {
        if (preg_match('/^[a-z][a-z0-9-]*$/D', $semanticBlock) !== 1
            || ($variant !== '' && preg_match('/^[a-z][a-z0-9-]*$/D', $variant) !== 1)
        ) {
            throw new \LogicException('SURFACE_SEMANTIC_FRAME_INVALID');
        }

        $surfaceClass = match ($tone) {
            'default' => 'bg-surface-0',
            'muted' => 'bg-surface-container',
            default => throw new \LogicException('SURFACE_SEMANTIC_TONE_INVALID'),
        };
        $paddingClass = match ($padding) {
            'lg' => 'p-3',
            'xl' => 'p-4',
            default => throw new \LogicException('SURFACE_SEMANTIC_PADDING_INVALID'),
        };
        $columns = $twoColumns ? 'grid-col-1 lg:grid-col-2' : 'grid-col-1';
        $variantAttribute = $variant === '' ? '' : ' data-variant="' . $variant . '"';

        return '<section data-docara-block="' . $semanticBlock . '"' . $variantAttribute
            . ' data-docara-width="full" class="' . $surfaceClass . ' overflow-hidden m-bottom-1">'
            . '<div data-docara-container class="container m-inline-auto grid ' . $columns
            . ' gap-4 items-cross-center ' . $paddingClass . '">' . $content . $media . '</div></section>';
    }

    private function toneClass(string $tone): string
    {
        return match ($tone) {
            'muted' => 'bg-surface-container-low color-on-surface',
            'accent' => 'bg-primary-container color-on-primary-container',
            'contrast' => 'bg-inverse-surface color-inverse-on-surface',
            default => 'bg-surface-0 color-on-surface',
        };
    }

    private function paddingClass(string $padding): string
    {
        return match ($padding) {
            'none' => 'p-0',
            'sm' => 'p-1',
            'lg' => 'p-3',
            'xl' => 'p-4',
            default => 'p-2',
        };
    }

    private function innerClass(string $contentWidth): string
    {
        return $contentWidth === 'container'
            ? 'container m-inline-auto'
            : 'w-full';
    }

    /** @param array<string, string> $props */
    private function compose(
        string $opening,
        string $outerClass,
        array $props,
        string $content,
        string $backgroundUrl,
        bool $publishBackgroundAsset,
        string $contentOpening,
        string $contentClass,
    ): string {
        $assetReceipt = $publishBackgroundAsset ? ' data-docara-publish-local-asset' : '';
        $media = $backgroundUrl === '' ? ''
            : '<img data-docara-surface-background alt="" aria-hidden="true" src="' . $backgroundUrl
                . '" data-fit="' . $props['background_fit']
                . '" data-x="' . $props['background_x']
                . '" data-y="' . $props['background_y']
                . '" loading="lazy" decoding="async"' . $assetReceipt . '>';
        $overlay = $props['overlay'] === 'none' ? ''
            : '<span data-docara-surface-overlay data-overlay="' . $props['overlay']
                . '" data-strength="' . $props['overlay_strength'] . '" aria-hidden="true"></span>';

        return $opening . ' class="' . $outerClass . '">'
            . $media . $overlay
            . $contentOpening . ' class="' . $contentClass . '">'
            . $content . '</div></section>';
    }
}
