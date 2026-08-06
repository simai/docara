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
    public function render(array $props, string $content, string $backgroundUrl = ''): string
    {
        $toneClass = match ($props['tone']) {
            'muted' => 'bg-surface-container-low color-on-surface',
            'accent' => 'bg-primary-container color-on-primary-container',
            'contrast' => 'bg-inverse-surface color-inverse-on-surface',
            default => 'bg-surface-0 color-on-surface',
        };
        $paddingClass = match ($props['padding']) {
            'none' => 'p-0',
            'sm' => 'p-1',
            'lg' => 'p-3',
            'xl' => 'p-4',
            default => 'p-2',
        };
        $innerClass = $props['content_width'] === 'container'
            ? 'container m-inline-auto'
            : 'w-full';

        $media = $backgroundUrl === '' ? ''
            : '<img data-docara-surface-background alt="" aria-hidden="true" src="' . $backgroundUrl
                . '" data-fit="' . $props['background_fit']
                . '" data-x="' . $props['background_x']
                . '" data-y="' . $props['background_y']
                . '" loading="lazy" decoding="async">';
        $overlay = $props['overlay'] === 'none' ? ''
            : '<span data-docara-surface-overlay data-overlay="' . $props['overlay']
                . '" data-strength="' . $props['overlay_strength'] . '" aria-hidden="true"></span>';

        return '<section data-docara-block="surface" data-docara-surface data-docara-width="'
            . $props['width'] . '" data-docara-tone="' . $props['tone']
            . '" class="docara-surface relative isolate overflow-hidden m-bottom-1 ' . $toneClass . '">'
            . $media . $overlay
            . '<div data-docara-container data-docara-content-width="' . $props['content_width']
            . '" class="docara-surface__content relative ' . $innerClass . ' ' . $paddingClass . '">'
            . $content . '</div></section>';
    }
}
