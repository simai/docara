<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Preview;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use Simai\Docara\Portable\PortableConfigurationException;

final class DeclarativePreviewLinkProjector
{
    public function project(string $html, DeclarativePreviewRouteMap $routes): string
    {
        if (preg_match('//u', $html) !== 1) {
            throw new PortableConfigurationException(
                'DECLARATIVE_PREVIEW_HTML_INVALID_UTF8',
                'Declarative preview HTML must be valid UTF-8.',
            );
        }
        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $loaded = $document->loadHTML(
            '<?xml encoding="UTF-8"><!doctype html><html><body>'
            . '<div id="docara-declarative-preview-root">' . $html . '</div>'
            . '</body></html>',
            LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_COMPACT,
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        if ($loaded !== true) {
            throw new PortableConfigurationException(
                'DECLARATIVE_PREVIEW_HTML_PARSE_FAILED',
                'Declarative preview HTML could not be parsed.',
            );
        }
        $root = $document->getElementById('docara-declarative-preview-root');
        if (! $root instanceof DOMElement) {
            throw new PortableConfigurationException(
                'DECLARATIVE_PREVIEW_HTML_PARSE_FAILED',
                'Declarative preview root is missing.',
            );
        }
        $xpath = new DOMXPath($document);
        foreach ($xpath->query('//*[@id="docara-declarative-preview-root"]//a[@href]') ?: [] as $link) {
            if (! $link instanceof DOMElement) {
                continue;
            }
            $original = $link->getAttribute('href');
            $preview = $routes->previewUrl($original);
            if ($preview === null) {
                continue;
            }
            $link->setAttribute('data-docara-original-href', $original);
            $link->setAttribute('href', $preview);
        }

        $projected = '';
        foreach (iterator_to_array($root->childNodes) as $node) {
            if (! $node instanceof DOMNode) {
                continue;
            }
            $fragment = $document->saveHTML($node);
            if (! is_string($fragment)) {
                throw new PortableConfigurationException(
                    'DECLARATIVE_PREVIEW_HTML_SERIALIZE_FAILED',
                    'Declarative preview HTML could not be serialized.',
                );
            }
            $projected .= $fragment;
        }

        return $projected;
    }
}
