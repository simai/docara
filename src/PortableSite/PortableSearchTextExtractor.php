<?php

declare(strict_types=1);

namespace Simai\Docara\PortableSite;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use Simai\Docara\Portable\PortableConfigurationException;

final readonly class PortableSearchTextExtractor
{
    /**
     * @param  list<array<string, mixed>>  $componentCalls
     * @return array{headings:list<array{level:int,text:string}>,text:string}
     */
    public function extract(string $html, array $componentCalls): array
    {
        if (preg_match('//u', $html) !== 1) {
            throw new PortableConfigurationException(
                'SEARCH_TEXT_INVALID_UTF8',
                'Searchable page HTML must be valid UTF-8.',
            );
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $loaded = $document->loadHTML(
            '<?xml encoding="UTF-8"><!doctype html><html><body><div id="docara-search-root">'
            . $html . '</div></body></html>',
            LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_COMPACT,
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        if ($loaded !== true) {
            throw new PortableConfigurationException(
                'SEARCH_TEXT_PARSE_FAILED',
                'Searchable page HTML could not be parsed.',
            );
        }

        $xpath = new DOMXPath($document);
        $root = $document->getElementById('docara-search-root');
        if (! $root instanceof DOMElement) {
            throw new PortableConfigurationException(
                'SEARCH_TEXT_PARSE_FAILED',
                'Searchable page HTML did not produce the expected document root.',
            );
        }

        $excluded = [];
        foreach ($xpath->query(
            '//*[@id="docara-search-root"]//*[self::script or self::style or self::template or @hidden or @aria-hidden="true"]',
        ) ?: [] as $node) {
            if ($node instanceof DOMNode) {
                $excluded[] = $node;
            }
        }
        foreach ($excluded as $node) {
            $node->parentNode?->removeChild($node);
        }

        $headings = [];
        foreach ($xpath->query(
            '//*[@id="docara-search-root"]//*[self::h1 or self::h2 or self::h3 or self::h4 or self::h5 or self::h6]',
        ) ?: [] as $heading) {
            if (! $heading instanceof DOMElement) {
                continue;
            }
            $text = $this->normalizeText($heading->textContent);
            if ($text !== '') {
                $headings[] = [
                    'level' => (int) substr(strtolower($heading->tagName), 1),
                    'text' => $text,
                ];
            }
        }

        $parts = [$this->normalizeText($root->textContent)];
        foreach ($componentCalls as $call) {
            $id = $call['id'] ?? null;
            $props = $call['props'] ?? null;
            if (! is_string($id) || ! is_array($props)) {
                throw new PortableConfigurationException(
                    'SEARCH_COMPONENT_TEXT_PROJECTION_MISSING',
                    'Search component call does not expose a supported text projection.',
                );
            }
            $keys = match ($id) {
                'ui.alert' => ['title', 'supporting-text'],
                'ui.button' => ['text'],
                default => throw new PortableConfigurationException(
                    'SEARCH_COMPONENT_TEXT_PROJECTION_MISSING',
                    "Search text projection is missing for component [$id].",
                ),
            };
            foreach ($keys as $key) {
                if (is_string($props[$key] ?? null)) {
                    $parts[] = $this->normalizeText($props[$key]);
                }
            }
        }

        return [
            'headings' => $headings,
            'text' => $this->normalizeText(implode(' ', array_filter($parts))),
        ];
    }

    private function normalizeText(string $text): string
    {
        if (preg_match('//u', $text) !== 1) {
            throw new PortableConfigurationException(
                'SEARCH_TEXT_INVALID_UTF8',
                'Extracted search text must be valid UTF-8.',
            );
        }
        $normalized = preg_replace('/\s+/u', ' ', trim($text));
        if (! is_string($normalized)) {
            throw new PortableConfigurationException(
                'SEARCH_TEXT_INVALID_UTF8',
                'Extracted search text could not be normalized.',
            );
        }

        return $normalized;
    }
}
