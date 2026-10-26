<?php

declare(strict_types=1);

namespace Tests\Support;

/**
 * The «Параметры» tables on the component pages, built from the component
 * catalogue (typed, inline and native entries) and the Russian descriptions
 * in resources/component-catalog/descriptions/ru.json. A page holds the table
 * of every component whose catalogue entry names it in docs_ref, between
 * START and END; only the descriptions are written by hand.
 */
final class ComponentParameterReference
{
    public const START = '<!-- docara:component-parameters:start -->';

    public const END = '<!-- docara:component-parameters:end -->';

    private const CATALOGUES = [
        'resources/component-catalog/typed',
        'resources/component-catalog/inline',
        'resources/component-catalog/native',
    ];

    private const DESCRIPTIONS = 'resources/component-catalog/descriptions/ru.json';

    private const PAGES = 'docs/site/content/ru/components/';

    public function __construct(private readonly string $root) {}

    /** @return list<array<string, mixed>> every typed, inline and native catalogue entry */
    public function entries(): array
    {
        $entries = [];
        foreach (self::CATALOGUES as $directory) {
            foreach (glob($this->root . '/' . $directory . '/*.json') ?: [] as $file) {
                $entry = json_decode((string) file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
                if (is_array($entry)) {
                    $entries[] = $entry;
                }
            }
        }

        return $entries;
    }

    /** @return array<string, list<array<string, mixed>>> catalogue entries per component page, by page path */
    public function entriesByPage(): array
    {
        $pages = [];
        foreach ($this->entries() as $entry) {
            $page = (string) ($entry['docs_ref'] ?? '');
            if (str_starts_with($page, self::PAGES)) {
                $pages[$page][] = $entry;
            }
        }
        ksort($pages);
        foreach ($pages as &$entries) {
            usort($entries, static fn (array $a, array $b): int => strcmp((string) $a['id'], (string) $b['id']));
        }

        return $pages;
    }

    /**
     * The generated Markdown for one page. A page with one component gets its
     * table; a page with several gets one H3 per component.
     *
     * @param  list<array<string, mixed>>  $entries
     */
    public function markdown(array $entries): string
    {
        if (count($entries) === 1) {
            return $this->table($entries[0]);
        }
        $out = '';
        foreach ($entries as $entry) {
            $call = (string) ($entry['authoring']['call'] ?? $entry['name']);
            $out .= '### `' . $call . "`\n\n" . $this->table($entry) . "\n";
        }

        return rtrim($out) . "\n";
    }

    /**
     * The page text with every table rewritten, by page path. Pages move to
     * the component page standard one by one: a page without the markers is
     * not yet moved and is listed by pendingPages().
     *
     * @return array<string, string>
     */
    public function updatedPages(): array
    {
        $updated = [];
        foreach ($this->entriesByPage() as $page => $entries) {
            $text = (string) file_get_contents($this->root . '/' . $page);
            $start = strpos($text, self::START);
            $end = strpos($text, self::END);
            if ($start === false || $end === false || $end < $start) {
                continue;
            }
            $updated[$page] = substr($text, 0, $start + strlen(self::START)) . "\n\n"
                . $this->markdown($entries) . "\n" . substr($text, $end);
        }

        return $updated;
    }

    /** @return list<string> component pages that do not hold the parameter table yet */
    public function pendingPages(): array
    {
        return array_values(array_diff(array_keys($this->entriesByPage()), array_keys($this->updatedPages())));
    }

    /** @param array<string, mixed> $entry */
    private function table(array $entry): string
    {
        $parameters = $entry['authoring']['parameters'] ?? [];
        if (! is_array($parameters) || $parameters === []) {
            return "Параметров нет.\n";
        }
        $descriptions = $this->descriptions();
        $rows = "| Параметр | Значения | По умолчанию | Что задаёт |\n| --- | --- | --- | --- |\n";
        foreach ($parameters as $parameter) {
            $name = (string) $parameter['name'];
            $key = $entry['id'] . '.' . $name;
            $description = $descriptions[$key] ?? throw new \RuntimeException("No description for component parameter [$key].");
            if (($parameter['deprecated_names'] ?? []) !== []) {
                $description .= ' Старое имя ' . implode(', ', array_map(
                    static fn (string $old): string => '`' . $old . '`',
                    $parameter['deprecated_names'],
                )) . ' принимается с предупреждением.';
            }
            $rows .= '| `' . $name . '`' . (($parameter['required'] ?? false) === true ? ' (обязательный)' : '')
                . ' | ' . $this->values($parameter)
                . ' | ' . $this->defaultValue($parameter)
                . ' | ' . str_replace('|', '\|', $description) . " |\n";
        }

        return $rows;
    }

    /** @param array<string, mixed> $parameter */
    private function values(array $parameter): string
    {
        return match ((string) $parameter['type']) {
            'enum' => implode(', ', array_map(static fn ($value): string => '`' . $value . '`', $parameter['values'] ?? [])),
            'boolean' => '`true`, `false`',
            'integer' => 'целое число',
            default => 'строка',
        };
    }

    /** @param array<string, mixed> $parameter */
    private function defaultValue(array $parameter): string
    {
        $default = $parameter['default'] ?? null;
        if ($default === null || $default === '') {
            return '—';
        }
        if (is_bool($default)) {
            return '`' . ($default ? 'true' : 'false') . '`';
        }

        return '`' . $default . '`';
    }

    /** @return array<string, string> */
    private function descriptions(): array
    {
        static $descriptions = null;
        if ($descriptions === null) {
            $file = json_decode((string) file_get_contents($this->root . '/' . self::DESCRIPTIONS), true, 512, JSON_THROW_ON_ERROR);
            $descriptions = is_array($file['descriptions'] ?? null) ? $file['descriptions'] : [];
        }

        return $descriptions;
    }
}
