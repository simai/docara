<?php

declare(strict_types=1);

namespace Simai\Docara\PortableSite;

use DOMDocument;
use DOMElement;
use Simai\Docara\Framework\FrameworkLock;
use Simai\Docara\Framework\FrameworkManifestRepository;
use Simai\Docara\Portable\PortableConfigurationException;

/**
 * Decides whether an example renders inline in the documentation page or in a
 * sandboxed frame. Inline is admitted only for HTML that cannot run code in
 * the page: no CSS or JavaScript source, and markup limited to an element and
 * attribute allowlist. Reusable project examples and examples written inside
 * the page follow the same rule.
 */
final class ExamplePreviewPolicy
{
    /** @var list<string> */
    private const ELEMENTS = [
        'article', 'b', 'blockquote', 'br', 'button', 'caption', 'code', 'col',
        'colgroup', 'dd', 'details', 'div', 'dl', 'dt', 'em', 'fieldset',
        'footer', 'form', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'header', 'hr', 'i',
        'input', 'label', 'legend', 'li', 'mark', 'meter', 'ol', 'optgroup',
        'option', 'output', 'p', 'pre', 'progress', 's', 'section', 'select',
        'small', 'span', 'strong', 'sub', 'summary', 'sup', 'table', 'tbody',
        'td', 'textarea', 'tfoot', 'th', 'thead', 'time', 'tr', 'u', 'ul',
    ];

    /** @var list<string> */
    private const ATTRIBUTES = [
        'checked', 'class', 'cols', 'colspan', 'datetime', 'dir', 'disabled',
        'for', 'hidden', 'id', 'lang', 'max', 'min', 'multiple', 'name', 'open',
        'placeholder', 'readonly', 'required', 'role', 'rows', 'rowspan',
        'scope', 'selected', 'size', 'step', 'tabindex', 'title', 'type', 'value',
    ];

    /**
     * Never admitted inline, on any element, even when a Framework lock
     * declares the attribute for a Smart tag: each one can load or submit to
     * a URL, run style or script, or move focus out of the reader's control.
     *
     * @var list<string>
     */
    private const FORBIDDEN_ATTRIBUTES = [
        'action', 'autofocus', 'background', 'contenteditable', 'enctype',
        'form', 'formaction', 'formenctype', 'formmethod', 'formnovalidate',
        'formtarget', 'href', 'method', 'ping', 'poster', 'src', 'srcdoc',
        'srcset', 'style', 'target', 'xlink:href',
    ];

    /**
     * $smartAttributes maps each Smart tag to the attributes the Framework
     * lock declares for it. Without a lock the map is empty and Smart tags
     * admit only the generic attributes.
     *
     * @param  array<string,list<string>>  $smartAttributes
     */
    public function __construct(private readonly array $smartAttributes = []) {}

    /**
     * Builds the policy from a project Framework lock through the bundled
     * manifest repository, so the declared lists are the effective runtime's.
     *
     * @param  array<string,mixed>  $frameworkLock
     */
    public static function fromFrameworkLock(array $frameworkLock): self
    {
        return self::fromFrameworkRuntime(
            FrameworkManifestRepository::bundled(FrameworkLock::fromArray($frameworkLock))->runtime(),
        );
    }

    /**
     * Reads the declared attributes of every Smart tag from an effective
     * Framework runtime (the `components` map of the runtime lock). A tag with
     * a missing or empty list declares nothing.
     *
     * @param  array<string,mixed>  $runtime
     */
    public static function fromFrameworkRuntime(array $runtime): self
    {
        $declared = [];
        foreach ((is_array($runtime['components'] ?? null) ? $runtime['components'] : []) as $tag => $component) {
            if (! is_string($tag)
                || preg_match('/\Asf-[a-z][a-z0-9-]*\z/D', $tag) !== 1
                || ! is_array($component)
                || ! is_array($component['attributes'] ?? null)
            ) {
                continue;
            }
            $attributes = [];
            foreach ($component['attributes'] as $attribute) {
                if (is_string($attribute) && preg_match('/\A[a-z][a-z0-9-]*\z/D', $attribute) === 1) {
                    $attributes[] = $attribute;
                }
            }
            if ($attributes !== []) {
                $declared[$tag] = $attributes;
            }
        }

        return new self($declared);
    }

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

        // Reusable project examples follow the same rule as examples written
        // in the page; $reusable no longer forces isolation on its own.
        $reason = isset($sources['JavaScript']) ? 'javascript'
            : (isset($sources['CSS']) ? 'custom_css' : $this->htmlReason($sources['HTML'] ?? ''));

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
            $smart = preg_match('/\Asf-[a-z][a-z0-9-]*\z/D', $name) === 1;
            if (! $policyRoot && ! $smart && ! in_array($name, self::ELEMENTS, true)) {
                return 'html_element_not_admitted';
            }
            foreach (iterator_to_array($element->attributes) as $attribute) {
                $attributeName = strtolower($attribute->nodeName);
                if ($policyRoot && $attributeName === 'data-docara-inline-policy-root') {
                    continue;
                }
                $reason = $this->attributeReason($name, $smart, $attributeName, (string) $attribute->nodeValue);
                if ($reason !== null) {
                    return $reason;
                }
            }
        }

        return 'admitted_html';
    }

    private function attributeReason(string $element, bool $smart, string $name, string $value): ?string
    {
        if (str_starts_with($name, 'on') || in_array($name, self::FORBIDDEN_ATTRIBUTES, true)) {
            return 'html_attribute_not_admitted';
        }
        // A named form becomes a property of document and could shadow the
        // DOM API the shell and the Framework call (`document.forms`, ...).
        if ($element === 'form' && $name === 'name') {
            return 'html_attribute_not_admitted';
        }
        if (str_starts_with($name, 'data-docara')) {
            return 'html_shell_attribute_not_admitted';
        }
        $generic = in_array($name, self::ATTRIBUTES, true)
            || $name === 'slot'
            || preg_match('/\Aaria-[a-z0-9-]+\z/D', $name) === 1
            || preg_match('/\Adata-[a-z0-9][a-z0-9._-]*\z/D', $name) === 1;
        $declared = $smart && in_array($name, $this->smartAttributes[$element] ?? [], true);
        if (! $generic && ! $declared) {
            return 'html_attribute_not_admitted';
        }
        // Framework components read data-* and their declared attributes; a
        // script-bearing URL scheme must not reach them from inline markup.
        $scheme = preg_replace('/[\x00-\x20]+/', '', $value) ?? $value;
        if (preg_match('/\A(?:javascript|vbscript|data):/i', $scheme) === 1) {
            return 'html_attribute_value_not_admitted';
        }
        if ($name === 'id') {
            // Inline ids share the page's id space. They must be kebab-case
            // with at least one hyphen, so they can never shadow a global
            // JavaScript name through window named access, and must not use
            // the shell's own prefix. Uniqueness is checked per built page.
            if (preg_match('/\A[A-Za-z][A-Za-z0-9_]*(?:-[A-Za-z0-9_]+)+\z/D', $value) !== 1) {
                return 'html_id_not_admitted';
            }
            if (str_starts_with(strtolower($value), 'docara-')) {
                return 'html_shell_id_not_admitted';
            }
        }
        if ($name === 'slot' && preg_match('/\A[a-z][a-z0-9]*(?:-[a-z0-9]+)*\z/D', $value) !== 1) {
            // A slot only names the part of a Smart component a child fills
            // (content, footer, title); anything but a plain token is refused.
            return 'html_attribute_value_not_admitted';
        }
        if ($name === 'class') {
            foreach (preg_split('/\s+/u', trim($value)) ?: [] as $class) {
                if ($class !== '' && str_starts_with($class, 'docara-')) {
                    return 'html_shell_class_not_admitted';
                }
            }
        }

        return null;
    }
}
