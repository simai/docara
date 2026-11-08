<?php

declare(strict_types=1);

namespace Simai\Docara\PortableSite;

use Simai\Docara\I18n\UiCopy;
use Simai\Docara\Portable\PortableConfigurationException;

/**
 * Writes the :::component_reference blocks of the built pages: a component's tag,
 * attributes, events, methods, CSS variables and accessibility, read from
 * its manifests (ComponentReferenceRepository). The tables are Markdown run
 * through the page renderer, so they are the site's ordinary tables.
 *
 * Manifest text is English. A locale that keeps component-reference.json
 * shows each manifest string through it: a string the dictionary lacks stops
 * the build, and so does, on a full build, a dictionary string no block on
 * the site shows any more. Labels come from lang.json (reference.*).
 */
final class PortableComponentReferenceHydrator
{
    /** @var list<string> */
    public const SECTIONS = ['overview', 'attributes', 'events', 'methods', 'css', 'accessibility'];

    /** Manifest text that says no more than the name next to it. */
    private const GENERIC = [
        '/^Public runtime method [\w$]+\.$/u',
        '/^Runtime event [\w:-]+\.$/u',
        '/^The action [\w-]+\.$/u',
    ];

    private const UNKNOWN_RETURN = ['object', 'boolean', 'null'];

    /** @var array<string, array<string, true>> locale => dictionary key => used */
    private array $used = [];

    /** @var array<string, array<string, true>> locale => manifest string without a translation */
    private array $missing = [];

    public function __construct(
        private readonly ComponentReferenceRepository $repository,
        private readonly PortableMarkdownRenderer $markdown,
        private readonly ?UiCopy $uiCopy = null,
    ) {}

    /**
     * @param  list<array<string,mixed>>  $pages
     * @param  bool  $complete  every page of the site is in $pages, so an unused dictionary string is stale
     * @return list<array<string,mixed>>
     */
    public function hydrate(array $pages, bool $complete = true): array
    {
        $this->used = [];
        $this->missing = [];
        foreach ($pages as &$page) {
            $html = (string) ($page['content_html'] ?? '');
            if (! str_contains($html, 'data-docara-component-reference')) {
                continue;
            }
            $locale = (string) ($page['locale'] ?? 'en');
            $copy = $this->uiCopy?->buildCopy($locale) ?? UiCopy::buildDefaults($locale);
            $html = preg_replace_callback(
                '/<div\b(?<attributes>[^>]*)\bdata-docara-component-reference\b(?<tail>[^>]*)><\/div>/iu',
                fn (array $match): string => $this->block($match['attributes'] . $match['tail'], $copy, $locale),
                $html,
            ) ?? $html;
            // An example's responsive viewer keeps the same markup escaped
            // in its frame document (data-docara-srcdoc).
            $page['content_html'] = preg_replace_callback(
                '/&lt;div\b(?<attributes>(?:(?!&gt;).)*?)\bdata-docara-component-reference\b(?<tail>(?:(?!&gt;).)*?)&gt;&lt;\/div&gt;/u',
                fn (array $match): string => $this->escape($this->block(
                    html_entity_decode($match['attributes'] . $match['tail'], ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                    $copy,
                    $locale,
                )),
                $html,
            ) ?? $html;
        }
        unset($page);

        foreach ($this->missing as $locale => $strings) {
            $list = array_keys($strings);
            throw new PortableConfigurationException(
                'COMPONENT_REFERENCE_TRANSLATION_MISSING',
                sprintf(
                    'Dictionary [%s] has no translation for %d manifest string(s), first: %s',
                    $this->repository->dictionaryPath($locale),
                    count($list),
                    implode(' | ', array_map(fn (string $text): string => '"' . $this->clip($text) . '"', array_slice($list, 0, 3))),
                ),
            );
        }
        if ($complete) {
            foreach ($this->repository->locales() as $locale) {
                $dictionary = $this->repository->dictionary($locale);
                if ($dictionary === null) {
                    continue;
                }
                $stale = array_values(array_diff(array_map('strval', array_keys($dictionary)), array_keys($this->used[$locale] ?? [])));
                if ($stale !== []) {
                    throw new PortableConfigurationException(
                        'COMPONENT_REFERENCE_TRANSLATION_STALE',
                        sprintf(
                            'Dictionary [%s] holds %d string(s) no reference block shows, first: %s',
                            $this->repository->dictionaryPath($locale),
                            count($stale),
                            implode(' | ', array_map(fn (string $text): string => '"' . $this->clip($text) . '"', array_slice($stale, 0, 3))),
                        ),
                    );
                }
            }
        }

        return $pages;
    }

    /** @param array<string,string> $copy */
    private function block(string $attributes, array $copy, string $locale): string
    {
        preg_match('/\bdata-component="(?<value>[^"]+)"/u', $attributes, $component);
        preg_match('/\bdata-sections="(?<value>[^"]+)"/u', $attributes, $sections);
        $tag = html_entity_decode((string) ($component['value'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $names = ($sections['value'] ?? '') === '' ? self::SECTIONS : explode(',', (string) $sections['value']);

        return '<div data-docara-block="component-reference" data-component="' . $this->escape($tag) . '">'
            . $this->markdown->render($this->markdownFor($tag, $names, $copy, $locale))
            . '</div>';
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
    }

    /**
     * @param  list<string>  $sections
     * @param  array<string,string>  $copy
     */
    public function markdownFor(string $tag, array $sections, array $copy, string $locale): string
    {
        $source = $this->repository->component($tag);
        $manifest = $source['manifest'];
        $headings = count($sections) > 1;
        $out = [];
        foreach ($sections as $section) {
            $body = match ($section) {
                'overview' => $this->overview($manifest, $copy, $locale),
                'attributes' => $this->attributes($manifest, $copy, $locale),
                'events' => $this->events($manifest, $copy, $locale),
                'methods' => $this->methods($manifest, $copy, $locale),
                'css' => $this->variables($source['component'], $copy, $locale),
                'accessibility' => $this->accessibility($manifest, $copy, $locale),
                default => throw new PortableConfigurationException(
                    'MARKDOWN_COMPONENT_REFERENCE_SECTIONS_INVALID',
                    "component_reference section [$section] is unknown.",
                ),
            };
            if ($headings && $section !== 'overview') {
                $out[] = '### ' . $copy['reference.section_' . $section];
            }
            $out[] = $body;
        }

        return implode("\n\n", $out) . "\n";
    }

    /** @param array<string,mixed> $manifest @param array<string,string> $copy */
    private function overview(array $manifest, array $copy, string $locale): string
    {
        $terms = [[$copy['reference.tag'], $this->code('<' . (string) $manifest['custom_element'] . '>')]];
        if (is_string($manifest['summary'] ?? null) && trim($manifest['summary']) !== '') {
            $terms[] = [$copy['reference.summary'], $this->translate($manifest['summary'], $locale)];
        }
        if (is_string($manifest['id'] ?? null)) {
            $terms[] = [$copy['reference.contract'], strtr($copy['reference.contract_version'], [
                '{id}' => $this->code($manifest['id']),
                '{version}' => $this->code((string) ($manifest['version'] ?? '?')),
            ])];
        }
        if (is_string($manifest['lifecycle'] ?? null)) {
            $terms[] = [$copy['reference.lifecycle'], $copy['reference.lifecycle_' . $manifest['lifecycle']] ?? $this->code($manifest['lifecycle'])];
        }

        return $this->definitions($terms);
    }

    /** @param array<string,mixed> $manifest @param array<string,string> $copy */
    private function attributes(array $manifest, array $copy, string $locale): string
    {
        $properties = $manifest['inputs']['properties'] ?? [];
        if (! is_array($properties) || $properties === []) {
            return $copy['reference.none'];
        }
        $required = is_array($manifest['inputs']['required'] ?? null) ? $manifest['inputs']['required'] : [];
        $rows = [];
        foreach ($properties as $name => $schema) {
            $schema = is_array($schema) ? $schema : [];
            $values = isset($schema['enum']) && is_array($schema['enum'])
                ? implode(', ', array_map(fn (mixed $value): string => $this->literal($value, $copy), $schema['enum']))
                : (array_key_exists('const', $schema) ? $this->literal($schema['const'], $copy) : $this->typeName($schema));
            $default = array_key_exists('default', $schema) && $schema['default'] !== null
                ? $this->literal($schema['default'], $copy)
                : '—';
            $rows[] = [
                $this->code((string) $name) . (in_array($name, $required, true) ? ' (' . $copy['reference.required'] . ')' : ''),
                $values,
                $default,
                $this->describe($schema['description'] ?? null, $locale),
            ];
        }

        return $this->table([$copy['reference.attribute'], $copy['reference.values'], $copy['reference.default'], $copy['reference.description']], $rows);
    }

    /** @param array<string,mixed> $manifest @param array<string,string> $copy */
    private function events(array $manifest, array $copy, string $locale): string
    {
        $events = is_array($manifest['events'] ?? null) ? $manifest['events'] : [];
        $current = array_filter($events, static fn (mixed $event): bool => is_array($event) && ! isset($event['alias_of']));
        $former = array_filter($events, static fn (mixed $event): bool => is_array($event) && isset($event['alias_of']));
        if ($current === []) {
            return $copy['reference.none'];
        }
        $rows = [];
        foreach ($current as $name => $event) {
            $detail = $this->shape($manifest, $event['payload'] ?? null);
            if ($detail === null) {
                foreach ($former as $alias) {
                    if (($alias['alias_of'] ?? null) === $name) {
                        $detail = $this->shape($manifest, $alias['payload'] ?? null);
                        if ($detail !== null) {
                            break;
                        }
                    }
                }
            }
            $rows[] = [$this->code((string) $name), $detail ?? '—', $this->describe($event['summary'] ?? null, $locale)];
        }
        $out = $this->table([$copy['reference.event'], $copy['reference.detail'], $copy['reference.description']], $rows);
        if ($former !== []) {
            $pairs = [];
            foreach ($former as $name => $event) {
                $pairs[] = $this->code((string) $name) . ' → ' . $this->code((string) $event['alias_of']);
            }
            $out .= "\n\n" . $copy['reference.former_events'] . ' ' . implode(', ', $pairs) . '.';
        }

        return $out;
    }

    /** @param array<string,mixed> $manifest @param array<string,string> $copy */
    private function methods(array $manifest, array $copy, string $locale): string
    {
        $methods = is_array($manifest['methods'] ?? null) ? $manifest['methods'] : [];
        if ($methods === []) {
            return $copy['reference.none'];
        }
        $rows = [];
        foreach ($methods as $name => $method) {
            $method = is_array($method) ? $method : [];
            $arguments = [];
            foreach (is_array($method['arguments'] ?? null) ? $method['arguments'] : [] as $argument) {
                if (is_string($argument)) {
                    $arguments[] = $argument;

                    continue;
                }
                if (! is_array($argument)) {
                    continue;
                }
                $types = $this->types($argument['type'] ?? ($argument['schema']['type'] ?? null));
                $arguments[] = (string) ($argument['name'] ?? '?') . (($argument['required'] ?? true) === false ? '?' : '')
                    . ': ' . ($types === [] ? 'any' : implode('|', $types));
            }
            $returns = is_array($method['returns'] ?? null) ? $method['returns'] : null;
            $returnTypes = $returns === null ? [] : $this->types($returns['type'] ?? null);
            $rows[] = [
                $this->code((string) $name . '(' . implode(', ', $arguments) . ')'),
                $returnTypes === [] || $returnTypes === self::UNKNOWN_RETURN ? '—' : $this->typeName($returns ?? []),
                $this->describe($method['summary'] ?? null, $locale),
            ];
        }

        return $this->table([$copy['reference.method'], $copy['reference.returns'], $copy['reference.description']], $rows);
    }

    /** @param array<string,mixed> $component @param array<string,string> $copy */
    private function variables(array $component, array $copy, string $locale): string
    {
        $variables = is_array($component['css-variables'] ?? null) ? $component['css-variables'] : [];
        if ($variables === []) {
            return $copy['reference.none'];
        }
        $dictionary = $this->repository->dictionary($locale);
        $rows = [];
        foreach ($variables as $name => $variable) {
            $variable = is_array($variable) ? $variable : [];
            // component.json keeps no public text for a variable: its
            // description lives in the dictionary under css:<name>.
            $rows[] = [
                $this->code((string) $name),
                isset($variable['type']) ? $this->code((string) $variable['type']) : '—',
                $dictionary === null ? '—' : $this->translate('css:' . $name, $locale),
            ];
        }

        return $this->table([$copy['reference.variable'], $copy['reference.type'], $copy['reference.description']], $rows);
    }

    /** @param array<string,mixed> $manifest @param array<string,string> $copy */
    private function accessibility(array $manifest, array $copy, string $locale): string
    {
        $access = is_array($manifest['accessibility'] ?? null) ? $manifest['accessibility'] : [];
        $terms = [];
        if (is_string($access['role'] ?? null)) {
            $terms[] = [$copy['reference.role'], $this->code($access['role'])];
        }
        if (is_string($access['accessible_name'] ?? null)) {
            $name = $access['accessible_name'];
            $terms[] = [$copy['reference.accessible_name'], $copy['reference.name_' . $name]
                ?? (preg_match('/\A[a-z][a-z_-]*\z/D', $name) === 1 ? $this->code($name) : $this->translate($name, $locale))];
        }
        if (is_array($access['keyboard'] ?? null) && $access['keyboard'] !== []) {
            $keys = [];
            foreach ($access['keyboard'] as $entry) {
                $entry = (string) $entry;
                $keys[] = preg_match('/\A[A-Z][A-Za-z]*(?:\+[A-Za-z]+)*\z/D', $entry) === 1 ? $this->code($entry) : $this->translate($entry, $locale);
            }
            $terms[] = [$copy['reference.keyboard'], ...$keys];
        }
        if (is_string($access['focus'] ?? null) && trim($access['focus']) !== '') {
            $terms[] = [$copy['reference.focus'], $this->translate($access['focus'], $locale)];
        }

        return $terms === [] ? $copy['reference.none'] : $this->definitions($terms);
    }

    private function describe(mixed $text, string $locale): string
    {
        if (! is_string($text) || trim($text) === '') {
            return '—';
        }
        foreach (self::GENERIC as $pattern) {
            if (preg_match($pattern, $text) === 1) {
                return '—';
            }
        }

        return $this->translate($text, $locale);
    }

    /** The locale's text for a manifest string, as inline Markdown. */
    private function translate(string $text, string $locale): string
    {
        $dictionary = $this->repository->dictionary($locale);
        if ($dictionary === null) {
            return $this->prose($text);
        }
        $this->used[$locale][$text] = true;
        if (! isset($dictionary[$text])) {
            $this->missing[$locale][$text] = true;

            return $this->prose($text);
        }

        return $this->prose($dictionary[$text]);
    }

    /** @param list<list<string>> $terms term, then one or more definitions */
    private function definitions(array $terms): string
    {
        $blocks = [];
        foreach ($terms as $term) {
            $blocks[] = $this->line(array_shift($term)) . "\n" . implode("\n", array_map(fn (string $text): string => ': ' . $this->line($text), $term));
        }

        return implode("\n\n", $blocks);
    }

    /** @param list<string> $head @param list<list<string>> $rows */
    private function table(array $head, array $rows): string
    {
        $lines = ['| ' . implode(' | ', array_map(fn (string $cell): string => $this->cell($cell), $head)) . ' |'];
        $lines[] = '|' . str_repeat(' --- |', count($head));
        foreach ($rows as $row) {
            $lines[] = '| ' . implode(' | ', array_map(fn (string $cell): string => $this->cell($cell), $row)) . ' |';
        }

        return implode("\n", $lines);
    }

    /** @param array<string,mixed> $manifest */
    private function shape(array $manifest, mixed $payload): ?string
    {
        $schema = $this->resolve($manifest, $payload);
        if (! is_array($schema['properties'] ?? null) || $schema['properties'] === []) {
            return null;
        }
        $required = is_array($schema['required'] ?? null) ? $schema['required'] : [];
        $keys = [];
        foreach ($schema['properties'] as $key => $value) {
            $inner = $this->resolve($manifest, $value);
            $types = $this->types(is_array($inner) ? ($inner['type'] ?? null) : null);
            $keys[] = $key . (in_array($key, $required, true) ? '' : '?') . ($types === [] ? '' : ': ' . implode('|', $types));
        }

        return $this->code('{ ' . implode(', ', $keys) . ' }');
    }

    /** @param array<string,mixed> $manifest */
    private function resolve(array $manifest, mixed $schema): mixed
    {
        if (! is_array($schema) || ! is_string($schema['$ref'] ?? null) || ! str_starts_with($schema['$ref'], '#/')) {
            return $schema;
        }
        $node = $manifest;
        foreach (explode('/', substr($schema['$ref'], 2)) as $key) {
            if (! is_array($node) || ! array_key_exists($key, $node)) {
                return $schema;
            }
            $node = $node[$key];
        }

        return $node;
    }

    /** @return list<string> */
    private function types(mixed $type): array
    {
        return array_values(array_filter(
            array_map('strval', is_array($type) ? $type : [$type]),
            static fn (string $value): bool => $value !== '',
        ));
    }

    /** @param array<string,mixed> $schema */
    private function typeName(array $schema): string
    {
        $types = $this->types($schema['type'] ?? null);

        return $types === [] ? '—' : implode(' | ', array_map(fn (string $type): string => $this->code($type), $types));
    }

    /** @param array<string,string> $copy */
    private function literal(mixed $value, array $copy): string
    {
        if ($value === '') {
            return $copy['reference.empty'];
        }

        return $this->code(is_string($value) ? $value : (string) json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    private function code(string $text): string
    {
        $text = $this->line($text);
        $fence = str_contains($text, '`') ? '``' : '`';
        $pad = $fence === '``' || str_starts_with($text, '`') || str_ends_with($text, '`') ? ' ' : '';

        return $fence . $pad . $text . $pad . $fence;
    }

    /**
     * Manifest and dictionary text as inline Markdown: `code` spans work,
     * anything that would open a tag, a link or a Docara inline component is
     * shown as written.
     */
    private function prose(string $text): string
    {
        $parts = explode('`', $this->line($text));
        foreach ($parts as $index => $part) {
            $parts[$index] = $index % 2 === 1 && $index < count($parts) - 1
                ? $part
                : strtr($part, ['\\' => '\\\\', '<' => '&lt;', '[' => '\[', ']' => '\]', '*' => '\*', '_' => '\_', '#' => '\#']);
        }

        return implode('`', $parts);
    }

    private function cell(string $text): string
    {
        return str_replace('|', '\|', $text);
    }

    private function line(string $text): string
    {
        return trim(preg_replace('/\s*\R\s*/u', ' ', $text) ?? $text);
    }

    private function clip(string $text): string
    {
        return mb_strlen($text) > 80 ? mb_substr($text, 0, 77) . '…' : $text;
    }
}
