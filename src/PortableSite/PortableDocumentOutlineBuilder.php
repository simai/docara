<?php

declare(strict_types=1);

namespace Simai\Docara\PortableSite;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use Normalizer;
use Simai\Docara\Portable\PortableConfigurationException;

final readonly class PortableDocumentOutlineBuilder
{
    /**
     * @return array{
     *     html: string,
     *     items: list<array{id: string, level: int, text: string}>
     * }
     */
    /** @param list<string> $reservedIds */
    public function build(string $html, int $maximumLevel, array $reservedIds = []): array
    {
        if ($maximumLevel < 2 || $maximumLevel > 6) {
            throw new PortableConfigurationException(
                'DOCUMENT_OUTLINE_DEPTH_INVALID',
                'Document outline depth must be between heading levels 2 and 6.',
            );
        }
        if (preg_match('//u', $html) !== 1) {
            throw new PortableConfigurationException(
                'DOCUMENT_OUTLINE_INVALID_UTF8',
                'Document HTML must be valid UTF-8 before outline generation.',
            );
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $loaded = $document->loadHTML(
            '<?xml encoding="UTF-8"><!doctype html><html><body><div id="docara-outline-root">'
            . $html . '</div></body></html>',
            LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_COMPACT,
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        if ($loaded !== true) {
            throw new PortableConfigurationException(
                'DOCUMENT_OUTLINE_PARSE_FAILED',
                'Document HTML could not be parsed for outline generation.',
            );
        }

        $root = $document->getElementById('docara-outline-root');
        if (! $root instanceof DOMElement) {
            throw new PortableConfigurationException(
                'DOCUMENT_OUTLINE_PARSE_FAILED',
                'Document HTML did not produce the expected outline root.',
            );
        }

        $xpath = new DOMXPath($document);
        $headings = [];
        foreach ($xpath->query(
            '//*[@id="docara-outline-root"]//*'
            . '[self::h1 or self::h2 or self::h3 or self::h4 or self::h5 or self::h6]'
            . '[not(ancestor::*[@data-docara-outline-exclude])]',
        ) ?: [] as $heading) {
            if ($heading instanceof DOMElement) {
                $headings[] = $heading;
            }
        }

        $used = [];
        foreach ($reservedIds as $reservedId) {
            if ($reservedId !== '') {
                $used[$reservedId] = true;
            }
        }
        foreach ($xpath->query('//*[@id="docara-outline-root"]//*[@id]') ?: [] as $node) {
            if (! $node instanceof DOMElement || in_array($node, $headings, true)) {
                continue;
            }
            $existing = $node->getAttribute('id');
            if ($existing !== '') {
                $used[$existing] = true;
            }
        }

        $items = [];
        foreach ($headings as $heading) {
            $text = $this->normalizeText($this->accessibleText($heading));
            if ($text === '') {
                throw new PortableConfigurationException(
                    'DOCUMENT_OUTLINE_HEADING_TEXT_REQUIRED',
                    'Document headings must have accessible text before outline generation.',
                );
            }
            $base = $this->slug($text);
            $id = $this->uniqueId($base, $used);
            $used[$id] = true;
            $heading->setAttribute('id', $id);

            $level = (int) substr(strtolower($heading->tagName), 1);
            if ($level >= 2 && $level <= $maximumLevel) {
                $items[] = [
                    'id' => $id,
                    'level' => $level,
                    'text' => $text,
                ];
            }
        }

        $decorated = '';
        foreach (iterator_to_array($root->childNodes) as $node) {
            if (! $node instanceof DOMNode) {
                continue;
            }
            $fragment = $document->saveHTML($node);
            if (! is_string($fragment)) {
                throw new PortableConfigurationException(
                    'DOCUMENT_OUTLINE_SERIALIZE_FAILED',
                    'Decorated document HTML could not be serialized.',
                );
            }
            $decorated .= $fragment;
        }

        return ['html' => $decorated, 'items' => $items];
    }

    private function accessibleText(DOMNode $node): string
    {
        if ($node->nodeType === XML_TEXT_NODE) {
            return $node->nodeValue ?? '';
        }
        if ($node instanceof DOMElement && strtolower($node->tagName) === 'img') {
            return ' ' . $node->getAttribute('alt') . ' ';
        }

        $text = '';
        foreach ($node->childNodes as $child) {
            $text .= $this->accessibleText($child);
        }

        return $text;
    }

    private function normalizeText(string $text): string
    {
        $normalized = Normalizer::normalize($text, Normalizer::FORM_C);
        $normalized = is_string($normalized) ? $normalized : $text;
        $normalized = preg_replace('/\s+/u', ' ', trim($normalized));
        if (! is_string($normalized)) {
            throw new PortableConfigurationException(
                'DOCUMENT_OUTLINE_INVALID_UTF8',
                'Heading text could not be normalized.',
            );
        }

        return $normalized;
    }

    private function slug(string $text): string
    {
        $slug = mb_strtolower($text, 'UTF-8');
        $slug = preg_replace('/[^\p{L}\p{N}\p{M}]+/u', '-', $slug);
        if (! is_string($slug)) {
            throw new PortableConfigurationException(
                'DOCUMENT_OUTLINE_INVALID_UTF8',
                'Heading slug could not be normalized.',
            );
        }
        $slug = trim($slug, '-');
        if ($slug === '') {
            $slug = 'section';
        }

        return rtrim(mb_substr($slug, 0, 120, 'UTF-8'), '-') ?: 'section';
    }

    /** @param array<string, true> $used */
    private function uniqueId(string $base, array $used): string
    {
        if (! isset($used[$base])) {
            return $base;
        }

        for ($counter = 1; ; $counter++) {
            $suffix = '-' . $counter;
            $prefixLength = max(1, 120 - mb_strlen($suffix, 'UTF-8'));
            $prefix = rtrim(mb_substr($base, 0, $prefixLength, 'UTF-8'), '-') ?: 'section';
            $candidate = $prefix . $suffix;
            if (! isset($used[$candidate])) {
                return $candidate;
            }
        }
    }
}
