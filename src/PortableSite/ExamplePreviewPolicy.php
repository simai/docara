<?php

declare(strict_types=1);

namespace Simai\Docara\PortableSite;

use DOMDocument;
use DOMElement;
use Simai\Docara\Portable\PortableConfigurationException;

final class ExamplePreviewPolicy
{
    /** @var list<string> */
    private const ELEMENTS = [
        'article', 'b', 'blockquote', 'br', 'button', 'caption', 'code', 'col',
        'colgroup', 'dd', 'details', 'div', 'dl', 'dt', 'em', 'fieldset',
        'footer', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'header', 'hr', 'i',
        'input', 'label', 'legend', 'li', 'mark', 'meter', 'ol', 'optgroup',
        'option', 'output', 'p', 'pre', 'progress', 's', 'section', 'select',
        'small', 'span', 'strong', 'sub', 'summary', 'sup', 'table', 'tbody',
        'td', 'textarea', 'tfoot', 'th', 'thead', 'time', 'tr', 'u', 'ul',
    ];

    /** @var list<string> */
    private const ATTRIBUTES = [
        'checked', 'class', 'cols', 'colspan', 'datetime', 'dir', 'disabled',
        'for', 'hidden', 'lang', 'max', 'min', 'multiple', 'name', 'open',
        'placeholder', 'readonly', 'required', 'role', 'rows', 'rowspan',
        'scope', 'selected', 'size', 'step', 'tabindex', 'title', 'type', 'value',
    ];

    /**
     * @param  array<string,string>  $sources
     * @return array{requested:string,resolved:string,reason:string}
     */
    public function resolve(array $sources, bool $reusable, string $requested): array
    {
        if (! in_array($requested, ['auto', 'inline', 'sandbox'], true)) {
            throw new PortableConfigurationException(
                'MARKDOWN_EXAMPLE_PREVIEW_INVALID',
                'Example preview must be one of [auto, inline, sandbox].',
            );
        }

        if ($requested === 'sandbox') {
            return ['requested' => $requested, 'resolved' => 'sandbox', 'reason' => 'forced_sandbox'];
        }

        if (isset($sources['Markdown'])) {
            return ['requested' => $requested, 'resolved' => 'inline', 'reason' => 'typed_markdown'];
        }

        $reason = $reusable ? 'reusable_example'
            : (isset($sources['JavaScript']) ? 'javascript'
                : (isset($sources['CSS']) ? 'custom_css' : $this->htmlReason($sources['HTML'] ?? '')));

        if ($reason === 'admitted_html') {
            return ['requested' => $requested, 'resolved' => 'inline', 'reason' => $reason];
        }

        if ($requested === 'inline') {
            throw new PortableConfigurationException(
                'MARKDOWN_EXAMPLE_INLINE_NOT_ADMITTED',
                "Example preview cannot be inline because [$reason] requires sandbox isolation.",
            );
        }

        return ['requested' => $requested, 'resolved' => 'sandbox', 'reason' => $reason];
    }

    private function htmlReason(string $html): string
    {
        if ($html === '') {
            return 'empty_html';
        }

        $previous = libxml_use_internal_errors(true);
        $document = new DOMDocument('1.0', 'UTF-8');
        $loaded = $document->loadHTML(
            '<?xml encoding="utf-8" ?><div data-docara-inline-policy-root>' . $html . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET,
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        if ($loaded !== true) {
            return 'html_parse_failed';
        }

        foreach ($document->getElementsByTagName('*') as $element) {
            if (! $element instanceof DOMElement) {
                continue;
            }
            $name = strtolower($element->tagName);
            $policyRoot = $element->hasAttribute('data-docara-inline-policy-root');
            if (! $policyRoot
                && ! in_array($name, self::ELEMENTS, true)
                && preg_match('/\Asf-[a-z][a-z0-9-]*\z/D', $name) !== 1
            ) {
                return 'html_element_not_admitted';
            }
            foreach (iterator_to_array($element->attributes) as $attribute) {
                $attributeName = strtolower($attribute->nodeName);
                if ($policyRoot && $attributeName === 'data-docara-inline-policy-root') {
                    continue;
                }
                if (str_starts_with($attributeName, 'on')
                    || in_array($attributeName, ['action', 'autofocus', 'contenteditable', 'form', 'formaction', 'href', 'id', 'src', 'srcdoc', 'style'], true)
                ) {
                    return 'html_attribute_not_admitted';
                }
                if (! in_array($attributeName, self::ATTRIBUTES, true)
                    && preg_match('/\Aaria-[a-z0-9-]+\z/D', $attributeName) !== 1
                    && preg_match('/\Adata-sf-[a-z0-9-]+\z/D', $attributeName) !== 1
                ) {
                    return 'html_attribute_not_admitted';
                }
                if ($attributeName === 'class') {
                    foreach (preg_split('/\s+/u', trim($attribute->nodeValue)) ?: [] as $class) {
                        if ($class !== '' && str_starts_with($class, 'docara-')) {
                            return 'html_shell_class_not_admitted';
                        }
                    }
                }
            }
        }

        return 'admitted_html';
    }
}
