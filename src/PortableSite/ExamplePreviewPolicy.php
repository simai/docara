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
     * $breakpoints lists the Framework breakpoint names (sm, md, ...) whose
     * prefixed utilities respond to the viewport or, with a cq- prefix, to
     * their query container. Without a lock it is empty and no example is
     * treated as responsive.
     *
     * @param  array<string,list<string>>  $smartAttributes
     * @param  list<string>  $breakpoints
     */
    public function __construct(
        private readonly array $smartAttributes = [],
        private readonly array $breakpoints = [],
    ) {}

    /**
     * Builds the policy from a project Framework lock through the bundled
     * manifest repository, so the declared lists are the effective runtime's.
     *
     * @param  array<string,mixed>  $frameworkLock
     */
    public static function fromFrameworkLock(array $frameworkLock): self
    {
        $repository = FrameworkManifestRepository::bundled(FrameworkLock::fromArray($frameworkLock));

        return self::fromFrameworkRuntime($repository->runtime(), self::frameworkBreakpoints($repository));
    }

    /**
     * Reads the breakpoint names from the bundled utility registry: a variant
     * that prefixes its classes (sm:aspect-16x9) and whose stylesheet is a
     * min-width media query. State variants such as hover: carry a prefix too,
     * but no media query, so they are not breakpoints.
     *
     * @return list<string>
     */
    public static function frameworkBreakpoints(FrameworkManifestRepository $repository): array
    {
        try {
            $rules = json_decode($repository->bundledRuntimeAsset('rule/rule.json'), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return [];
        }
        $candidates = [];
        foreach (is_array($rules) ? $rules : [] as $rule) {
            $name = is_array($rule) ? ($rule['name'] ?? null) : null;
            $regex = is_array($rule) ? ($rule['regex'] ?? null) : null;
            if (! is_string($name) || ! is_string($regex)
                || preg_match('#\A(?<family>[a-z0-9-]+)/(?<variant>[a-z][a-z0-9-]*)\z#D', $name, $match) !== 1
                || $match['variant'] === 'default'
                || ! str_starts_with($regex, '/^(?:' . $match['variant'] . ':')
            ) {
                continue;
            }
            $candidates[$match['variant']] ??= 'utility/' . $match['family'] . '/' . $match['variant'] . '/css/' . $match['variant'] . '.css';
        }
        $breakpoints = [];
        foreach ($candidates as $variant => $path) {
            try {
                $css = $repository->bundledRuntimeAsset($path);
            } catch (\Throwable) {
                continue;
            }
            if (preg_match('/@media\s*\(\s*min-width\s*:/i', $css) === 1) {
                $breakpoints[] = (string) $variant;
            }
        }
        sort($breakpoints, SORT_STRING);

        return $breakpoints;
    }

    /**
     * Reads the declared attributes of every Smart tag from an effective
     * Framework runtime (the `components` map of the runtime lock). A tag with
     * a missing or empty list declares nothing.
     *
     * @param  array<string,mixed>  $runtime
     * @param  list<string>  $breakpoints
     */
    public static function fromFrameworkRuntime(array $runtime, array $breakpoints = []): self
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

        return new self($declared, $breakpoints);
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
            // Breakpoint utilities respond to the viewport (or, cq-, to their
            // query container); inline they follow the documentation page, so
            // auto gives them a frame and its responsive viewer. The markup is
            // safe inline, so an explicit preview=inline stays the author's
            // choice: only isolation that safety needs fails closed.
            if ($requested === 'auto' && $this->usesResponsiveUtilities($sources['HTML'] ?? '')) {
                return ['requested' => $requested, 'resolved' => 'sandbox', 'reason' => 'responsive_utilities'];
            }

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

    private function usesResponsiveUtilities(string $html): bool
    {
        if ($this->breakpoints === [] || preg_match_all('/\bclass\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s>]+))/i', $html, $matches, PREG_SET_ORDER) < 1) {
            return false;
        }
        $names = implode('|', array_map(static fn (string $name): string => preg_quote($name, '/'), $this->breakpoints));
        foreach ($matches as $match) {
            $classes = ($match[1] ?? '') . ($match[2] ?? '') . ($match[3] ?? '');
            foreach (preg_split('/\s+/', trim(html_entity_decode($classes, ENT_QUOTES | ENT_HTML5, 'UTF-8'))) ?: [] as $class) {
                if (preg_match('/\A(?:cq-)?(?:' . $names . ')(?:\/[a-z0-9-]+)?:./D', $class) === 1) {
                    return true;
                }
            }
        }

        return false;
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
