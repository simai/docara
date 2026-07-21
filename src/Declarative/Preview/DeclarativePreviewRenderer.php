<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Preview;

use Simai\Docara\Declarative\Preview\View\PreviewIndexItemViewModel;
use Simai\Docara\Declarative\Preview\View\PreviewIndexViewModel;
use Simai\Docara\Declarative\Preview\View\PreviewPageViewModel;
use Simai\Docara\Declarative\Rendering\TrustedTemplateRegistry;
use Simai\Docara\Framework\FrameworkAssetPlan;
use Simai\Docara\I18n\Translator;

final readonly class DeclarativePreviewRenderer
{
    public function __construct(
        private TrustedTemplateRegistry $templates = new TrustedTemplateRegistry,
        private ?Translator $translator = null,
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
            $this->copy($locale),
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
            $this->copy($locale),
        );

        return $this->templates->render('preview.docara.index', ['view' => $view]);
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /** @return array<string, string> */
    private function copy(string $locale): array
    {
        if (! $this->translator instanceof Translator) {
            throw new \LogicException('Declarative preview requires a resolved language-pack translator.');
        }
        $copy = [];
        foreach ([
            'eyebrow', 'title', 'description', 'rendered', 'skipped', 'receipt',
            'pages', 'unsupported', 'open_preview', 'legacy_only', 'all_pages',
            'open_legacy',
        ] as $key) {
            $copy[$key] = $this->escape($this->translator->message($locale, 'preview.' . $key));
        }

        return $copy;
    }
}
