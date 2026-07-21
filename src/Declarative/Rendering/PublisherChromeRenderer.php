<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Rendering;

use Simai\Docara\Declarative\Rendering\View\PublisherChromeViewModel;

final readonly class PublisherChromeRenderer
{
    public function __construct(
        private TrustedTemplateRegistry $templates = new TrustedTemplateRegistry,
    ) {}

    /** @return array<string, string> */
    public function render(PublisherChromeViewModel $view): array
    {
        $result = [];
        foreach ([
            'head',
            'header-actions',
            'mobile-navigation',
            'breadcrumbs',
            'mobile-toc',
            'pager',
            'search-dialog',
            'reader-settings',
        ] as $part) {
            if ($part === 'mobile-toc' && ! $view->mobileTocEnabled) {
                $result['mobile_toc'] = '';

                continue;
            }
            $result[str_replace('-', '_', $part)] = $this->templates->render(
                'publisher.docara.' . $part,
                ['view' => $view],
            );
        }

        return $result;
    }
}
