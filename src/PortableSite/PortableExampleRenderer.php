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
    ): string {
        $safeId = preg_replace('/[^a-z0-9_-]+/i', '-', $id) ?: 'example';
        $previewTabId = $safeId . '-tab-example';
        $previewPanelId = $safeId . '-panel-example';
        $tabs = [
            '<button type="button" role="tab" id="' . $previewTabId
                . '" aria-controls="' . $previewPanelId
                . '" aria-selected="true" tabindex="0" data-docara-example-tab="example"'
                . ' class="docara-example-preview__tab">'
                . $this->escape($exampleLabel) . '</button>',
        ];
        $panels = [
            '<div role="tabpanel" id="' . $previewPanelId . '" aria-labelledby="'
                . $previewTabId . '" data-docara-example-panel="example"'
                . ($legacyComponentId === '' ? '' : ' data-docara-component-demo="' . $this->escape($legacyComponentId) . '"')
                . ' aria-hidden="false" class="docara-example-preview__panel is-active">' . $preview . '</div>',
        ];

        foreach ($sources as $language => $source) {
            $key = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $language) ?: 'source');
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
            . '" data-docara-outline-exclude data-source-active="false" class="docara-example-preview"'
            . ($legacyComponentId === '' ? '' : ' data-docara-component-example="' . $this->escape($legacyComponentId) . '"')
            . '><div class="docara-example-preview__header">'
            . '<div role="tablist" aria-label="' . $this->escape($exampleLabel)
            . '" class="docara-example-preview__tabs">' . implode('', $tabs) . '</div>'
            . '<div class="docara-example-preview__actions">'
            . ($fullscreen && $resolvedPreview === 'sandbox' ? '<div data-docara-example-viewports hidden role="group" aria-label="Preview width" class="docara-example-preview__viewports">'
                . '<button type="button" data-docara-example-viewport="desktop" aria-pressed="true" class="docara-example-preview__viewport">Desktop</button>'
                . '<button type="button" data-docara-example-viewport="tablet" aria-pressed="false" class="docara-example-preview__viewport">Tablet</button>'
                . '<button type="button" data-docara-example-viewport="mobile" aria-pressed="false" class="docara-example-preview__viewport">Mobile</button>'
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
}
