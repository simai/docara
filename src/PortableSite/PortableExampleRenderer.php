<?php

declare(strict_types=1);

namespace Simai\Docara\PortableSite;

final class PortableExampleRenderer
{
    /**
     * @param  array<string, string>  $sources  Rendered code blocks keyed by their
     *                                          human-readable language label.
     */
    public function render(
        string $id,
        string $preview,
        array $sources,
        string $exampleLabel,
        string $copyLabel,
        string $copiedLabel,
        string $legacyComponentId = '',
        string $requestedPreview = 'auto',
        string $resolvedPreview = 'inline',
        string $previewReason = 'typed_markdown',
        bool $fullscreen = true,
        bool $wrap = true,
        bool $defaultLabel = false,
        bool $responsive = false,
        bool $wide = false,
    ): string {
        $safeId = preg_replace('/[^a-z0-9_-]+/i', '-', $id) ?: 'example';
        $previewTabId = $safeId . '-tab-example';
        $previewPanelId = $safeId . '-panel-example';
        $tabs = [
            '<button type="button" role="tab" id="' . $previewTabId
                . '" aria-controls="' . $previewPanelId
                . '" aria-selected="true" tabindex="0" data-docara-example-tab="example"'
                . ($defaultLabel ? ' data-docara-example-default-label' : '')
                . ' class="docara-example-preview__tab">'
                . $this->escape($exampleLabel) . '</button>',
        ];
        $panels = [
            '<div role="tabpanel" id="' . $previewPanelId . '" aria-labelledby="'
                . $previewTabId . '" data-docara-example-panel="example"'
                . ($legacyComponentId === '' ? '' : ' data-docara-component-demo="' . $this->escape($legacyComponentId) . '"')
                . ' aria-hidden="false" class="docara-example-preview__panel is-active">' . $preview . '</div>',
        ];

        $keys = [];
        foreach ($sources as $language => $source) {
            // A tab named in another script, such as a Russian file label,
            // keeps no Latin letters; a counter keeps every tab's id unique.
            $key = trim(strtolower(preg_replace('/[^a-z0-9]+/i', '-', (string) $language) ?? ''), '-');
            $key = $key === '' ? 'source' : $key;
            $base = $key;
            for ($suffix = 2; isset($keys[$key]); $suffix++) {
                $key = $base . '-' . $suffix;
            }
            $keys[$key] = true;
            $tabId = $safeId . '-tab-' . $key;
            $panelId = $safeId . '-panel-' . $key;
            $tabs[] = '<button type="button" role="tab" id="' . $tabId
                . '" aria-controls="' . $panelId
                . '" aria-selected="false" tabindex="-1" data-docara-example-tab="' . $this->escape($key) . '"'
                . ' class="docara-example-preview__tab">'
                . $this->escape($language) . '</button>';
            $panels[] = '<div role="tabpanel" id="' . $panelId . '" aria-labelledby="'
                . $tabId . '" data-docara-example-panel="' . $this->escape($key)
                . '" aria-hidden="true" class="docara-example-preview__panel docara-example-preview__panel--source">'
                . $source . '</div>';
        }

        return '<section data-docara-example="' . $this->escape($safeId)
            . '" data-docara-example-preview-requested="' . $this->escape($requestedPreview)
            . '" data-docara-example-preview-resolved="' . $this->escape($resolvedPreview)
            . '" data-docara-example-preview-reason="' . $this->escape($previewReason)
            . '" data-docara-example-fullscreen="' . ($fullscreen ? 'true' : 'false')
            . '" data-docara-example-wrap="' . ($wrap ? 'true' : 'false')
            . '" data-docara-outline-exclude data-source-active="false"'
            // A wide example takes the page's full width like a full-width
            // surface; the shell's data-docara-width rule does the breakout.
            . ($wide ? ' data-docara-width="full" class="docara-example-preview docara-example-preview--wide"' : ' class="docara-example-preview"')
            . ($legacyComponentId === '' ? '' : ' data-docara-component-example="' . $this->escape($legacyComponentId) . '"')
            . '><div class="docara-example-preview__header">'
            . '<div role="tablist" aria-label="' . $this->escape($exampleLabel)
            . '" class="docara-example-preview__tabs">' . implode('', $tabs) . '</div>'
            . '<div class="docara-example-preview__actions">'
            // The width switcher is three Framework icon buttons like the
            // close button beside it; the chosen width is pressed.
            // An inline Markdown example gets the switcher when its preview
            // carries a hidden responsive frame.
            . ($fullscreen && ($resolvedPreview === 'sandbox' || $responsive) ? '<div data-docara-example-viewports hidden role="group" aria-label="Preview width" class="docara-example-preview__viewports">'
                . $this->viewportButton('desktop', 'desktop_windows', 'Desktop', true)
                . $this->viewportButton('tablet', 'tablet', 'Tablet', false)
                . $this->viewportButton('mobile', 'smartphone', 'Mobile', false)
                . '</div><button type="button" data-docara-example-viewer hidden aria-pressed="false" aria-label="Check responsive preview"'
                . ' data-viewer-icon="devices" data-viewer-exit-icon="close"'
                . ' class="docara-example-preview__action sf-icon-button sf-icon-button--icon sf-icon-button--on-surface sf-icon-button--link sf-icon-button--size-1 inline-grid items-cross-center content-main-center m-0">'
                . '<sf-icon icon="devices" aria-hidden="true"></sf-icon></button>' : '')
            . ($wrap ? '<button type="button" data-docara-example-wrap hidden aria-pressed="false" aria-label="Wrap source lines"'
                . ' data-wrap-icon="wrap_text" data-unwrap-icon="format_text_overflow"'
                . ' class="docara-example-preview__action sf-icon-button sf-icon-button--icon sf-icon-button--on-surface sf-icon-button--link sf-icon-button--size-1 inline-grid items-cross-center content-main-center m-0">'
                . '<sf-icon icon="wrap_text" aria-hidden="true"></sf-icon></button>' : '')
            . '<button type="button" data-docara-example-copy hidden aria-label="' . $this->escape($copyLabel)
            . '" data-copy-label="' . $this->escape($copyLabel) . '" data-copied-label="'
            . $this->escape($copiedLabel)
            . '" data-copy-icon="content_copy" data-copied-icon="check"'
            . ' class="docara-example-preview__copy sf-icon-button sf-icon-button--icon sf-icon-button--on-surface sf-icon-button--link sf-icon-button--size-1 inline-grid items-cross-center content-main-center m-0">'
            . '<sf-icon icon="content_copy" aria-hidden="true"></sf-icon>'
            . '</button></div><span class="docara-example-preview__indicator" aria-hidden="true"></span></div>'
            . '<div class="docara-example-preview__panels">' . implode('', $panels) . '</div></section>';
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
    }

    private function viewportButton(string $viewport, string $icon, string $label, bool $selected): string
    {
        return '<button type="button" data-docara-example-viewport="' . $viewport . '" aria-pressed="'
            . ($selected ? 'true' : 'false') . '" aria-label="' . $label . '" title="' . $label
            . '" class="docara-example-preview__viewport sf-icon-button sf-icon-button--icon sf-icon-button--on-surface'
            . ' sf-icon-button--link sf-icon-button--size-1 inline-grid items-cross-center content-main-center m-0'
            . ($selected ? ' active' : '') . '"><sf-icon icon="' . $icon . '" aria-hidden="true"></sf-icon></button>';
    }
}
