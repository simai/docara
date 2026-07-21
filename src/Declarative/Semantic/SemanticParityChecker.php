<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Semantic;

use DOMDocument;
use DOMElement;
use DOMXPath;
use Simai\Docara\Declarative\DeclarativePageResult;
use Simai\Docara\Portable\PortableConfigurationException;

final class SemanticParityChecker
{
    /**
     * @param  list<array<string, mixed>>  $legacySmartCalls
     */
    public function assertEquivalent(
        string $title,
        string $legacyContentHtml,
        array $legacySmartCalls,
        DeclarativePageResult $declarative,
    ): SemanticParityResult {
        $regions = ['header', 'sidebar', 'main', 'outline', 'footer'];
        $legacy = SemanticPageProjection::fromHtml(
            $title,
            $legacyContentHtml,
            $regions,
            $legacySmartCalls,
        );
        $smart = [];
        foreach ($declarative->plan->regions['main'] as $section) {
            foreach ($section->blocks as $block) {
                $call = $block->smart;
                if ($call === null || ! str_starts_with($call->smart, 'ui.')) {
                    continue;
                }
                $smart[] = [
                    'id' => $call->smart,
                    'view' => $call->view,
                    'props' => $call->props,
                ];
            }
        }
        $current = SemanticPageProjection::fromHtml(
            $declarative->plan->title,
            $this->mainRegion($declarative->artifact->html),
            array_keys($declarative->plan->regions),
            $smart,
        );
        $result = new SemanticParityResult(
            $legacy->toArray() === $current->toArray(),
            $legacy->toArray(),
            $current->toArray(),
        );
        if (! $result->passed) {
            throw new PortableConfigurationException(
                'DECLARATIVE_SEMANTIC_PARITY_FAILED',
                'Legacy and declarative page semantics differ.',
            );
        }

        return $result;
    }

    private function mainRegion(string $html): string
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $loaded = $document->loadHTML(
            '<?xml encoding="UTF-8"><!doctype html><html><body>' . $html . '</body></html>',
            LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_COMPACT,
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        if ($loaded !== true) {
            throw new PortableConfigurationException(
                'DECLARATIVE_SEMANTIC_HTML_INVALID',
                'Declarative page could not be parsed for main-region comparison.',
            );
        }
        $xpath = new DOMXPath($document);
        $nodes = $xpath->query('//*[@data-docara-region="main"]');
        $main = $nodes?->item(0);
        if (! $main instanceof DOMElement || $nodes?->length !== 1) {
            throw new PortableConfigurationException(
                'DECLARATIVE_MAIN_REGION_REQUIRED',
                'Declarative page must contain exactly one main region.',
            );
        }
        $content = '';
        foreach ($main->childNodes as $child) {
            $fragment = $document->saveHTML($child);
            if (is_string($fragment)) {
                $content .= $fragment;
            }
        }

        return $content;
    }
}
