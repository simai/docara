<?php

declare(strict_types=1);

namespace Simai\Docara\PortableSite;

use DOMDocument;
use DOMElement;
use DOMXPath;
use Simai\Docara\Portable\PortableConfigurationException;

/**
 * An inline example shares the page's id space with the shell, the heading
 * anchors and every other inline example. The policy admits an author id per
 * example; only the final page can show whether it is unique, so the site
 * builder runs this guard on each page before writing it.
 */
final class InlineExampleIdGuard
{
    public function assertUnique(string $html, string $source): void
    {
        if (! str_contains($html, 'data-docara-example-inline-preview="html"')) {
            return;
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $loaded = $document->loadHTML(
            str_starts_with(ltrim($html), '<?xml') ? $html : '<?xml encoding="UTF-8">' . $html,
            LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_COMPACT | LIBXML_PARSEHUGE,
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        if ($loaded !== true) {
            throw new PortableConfigurationException(
                'MARKDOWN_EXAMPLE_INLINE_ID_CHECK_FAILED',
                "Page [$source] could not be parsed to check inline example ids.",
            );
        }

        $xpath = new DOMXPath($document);
        $counts = [];
        foreach ($xpath->query('//*[@id]') ?: [] as $node) {
            if ($node instanceof DOMElement) {
                $id = $node->getAttribute('id');
                $counts[$id] = ($counts[$id] ?? 0) + 1;
            }
        }
        foreach ($xpath->query('//*[@data-docara-example-inline-preview="html"]') ?: [] as $root) {
            if (! $root instanceof DOMElement) {
                continue;
            }
            foreach ($xpath->query('.//*[@id]', $root) ?: [] as $node) {
                if (! $node instanceof DOMElement) {
                    continue;
                }
                $id = $node->getAttribute('id');
                if (($counts[$id] ?? 0) > 1) {
                    $example = $root->getAttribute('data-docara-example-source');
                    throw new PortableConfigurationException(
                        'MARKDOWN_EXAMPLE_INLINE_ID_DUPLICATE',
                        "Inline example id [$id]"
                        . ($example === '' ? '' : " from example [$example]")
                        . " appears more than once on page [$source]. Ids in inline examples must be unique on the page;"
                        . ' rename the id or render the example with preview=sandbox.',
                    );
                }
            }
        }
    }
}
