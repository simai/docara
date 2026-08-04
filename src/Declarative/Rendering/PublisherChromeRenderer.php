<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Rendering;

use Simai\Docara\Declarative\Document\SmartCallNode;
use Simai\Docara\Declarative\Document\SourceSpan;
use Simai\Docara\Declarative\Rendering\View\PublisherChromeViewModel;
use Simai\Docara\Declarative\Smart\SmartComponentGateway;

final class PublisherChromeRenderer
{
    private readonly SmartComponentGateway $gateway;

    private readonly SmartRenderer $smartRenderer;

    public function __construct(
        private readonly TrustedTemplateRegistry $templates = new TrustedTemplateRegistry,
        ?SmartComponentGateway $gateway = null,
        ?SmartRenderer $smartRenderer = null,
    ) {
        $this->gateway = $gateway ?? SmartComponentGateway::content();
        $this->smartRenderer = $smartRenderer ?? new SmartRenderer;
    }

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
            if ($part === 'reader-settings' && ! $view->readerPreferencesEnabled) {
                $result['reader_settings'] = '';

                continue;
            }
            $result[str_replace('-', '_', $part)] = match ($part) {
                'breadcrumbs' => $this->smart('docara.breadcrumbs', 'publisher-breadcrumbs', [
                    'items' => $view->breadcrumbs,
                    'label' => $view->copy['navigation.breadcrumbs'],
                    'expand_label' => $view->copy['navigation.breadcrumbs_expand'],
                    'max_items' => 3,
                ]),
                'pager' => $this->smart('docara.pager', 'publisher-pager', [
                    'previous' => $view->previous,
                    'next' => $view->next,
                    'label' => $view->copy['navigation.previous_next'],
                    'previous_label' => $view->copy['navigation.previous'],
                    'next_label' => $view->copy['navigation.next'],
                ]),
                'search-dialog' => $this->smart('docara.search', 'publisher-search', [
                    'enabled' => $view->searchEnabled,
                    'index_url' => $view->searchIndexUrl,
                    'placeholder' => $view->copy['search.placeholder'],
                    'query_label' => $view->copy['search.query'],
                    'close_label' => $view->copy['search.close'],
                    'title' => $view->copy['search.title'],
                    'idle_label' => $view->copy['search.idle'],
                    'navigate_label' => $view->copy['search.navigate'],
                    'open_label' => $view->copy['search.open_result'],
                    'dismiss_label' => $view->copy['search.dismiss'],
                ]),
                default => $this->templates->render(
                    'publisher.docara.' . $part,
                    ['view' => $view],
                ),
            };
        }

        return $result;
    }

    /** @param array<string, mixed> $props */
    private function smart(string $id, string $nodeId, array $props): string
    {
        $call = new SmartCallNode($nodeId, $id, 'default', $props, 1, new SourceSpan('@publisher', 1, 1));

        return $this->smartRenderer->render($this->gateway->resolve($call))->html;
    }
}
