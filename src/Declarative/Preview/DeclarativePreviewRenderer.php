<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Preview;

use Simai\Docara\Declarative\Preview\View\PreviewIndexItemViewModel;
use Simai\Docara\Declarative\Preview\View\PreviewIndexViewModel;
use Simai\Docara\Declarative\Preview\View\PreviewPageViewModel;
use Simai\Docara\Declarative\Rendering\TrustedTemplateRegistry;
use Simai\Docara\Framework\FrameworkAssetPlan;

final readonly class DeclarativePreviewRenderer
{
    public function __construct(
        private TrustedTemplateRegistry $templates = new TrustedTemplateRegistry,
    ) {}

    public function page(
        string $locale,
        string $documentationVersion,
        string $title,
        string $legacyUrl,
        string $catalogUrl,
        string $contentHtml,
        FrameworkAssetPlan $assets,
    ): string {
        $view = new PreviewPageViewModel(
            $this->escape($locale),
            $this->escape($documentationVersion),
            $this->escape($title . ' — Declarative preview'),
            $this->escape($title),
            $this->escape($legacyUrl),
            $this->escape($catalogUrl),
            $assets->headHtml(),
            $contentHtml,
        );

        return $this->templates->render('preview.docara.page', ['view' => $view]);
    }

    /**
     * @param  list<array<string, mixed>>  $records
     */
    public function index(
        string $locale,
        string $documentationVersion,
        string $siteTitle,
        string $receiptUrl,
        array $records,
        FrameworkAssetPlan $assets,
    ): string {
        $items = [];
        $rendered = 0;
        foreach ($records as $record) {
            $previewUrl = is_string($record['preview_url'] ?? null)
                ? $this->escape($record['preview_url'])
                : null;
            if ($previewUrl === null) {
                $status = 'skipped';
            } else {
                $status = 'rendered';
                $rendered++;
            }
            $unsupported = [];
            foreach (is_array($record['unsupported_components'] ?? null)
                ? $record['unsupported_components']
                : [] as $component
            ) {
                if (is_string($component)) {
                    $unsupported[] = $this->escape($component);
                }
            }
            $items[] = new PreviewIndexItemViewModel(
                $this->escape((string) $record['title']),
                $this->escape((string) $record['legacy_url']),
                $previewUrl,
                $status,
                implode(', ', $unsupported),
            );
        }

        $view = new PreviewIndexViewModel(
            $this->escape($locale),
            $this->escape($documentationVersion),
            $this->escape($siteTitle . ' — Declarative preview'),
            $assets->headHtml(),
            $this->escape($receiptUrl),
            $rendered,
            count($items) - $rendered,
            $items,
        );

        return $this->templates->render('preview.docara.index', ['view' => $view]);
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
