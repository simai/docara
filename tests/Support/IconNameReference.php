<?php

declare(strict_types=1);

namespace Tests\Support;

use Simai\Docara\Document\IconNameCatalog;

/**
 * The list of every icon name on components/icon.md, built from the bundled
 * icon catalogue (IconNameCatalog), so the page names exactly the icons an
 * `:icon[...]` can draw. Names are grouped by their first letter.
 */
final class IconNameReference
{
    public const PAGE = 'docs/site/content/ru/components/icon.md';

    public const START = '<!-- docara:icon-names:start -->';

    public const END = '<!-- docara:icon-names:end -->';

    public function __construct(private readonly string $root) {}

    public function page(): string
    {
        return (string) file_get_contents($this->root . '/' . self::PAGE);
    }

    /** @return list<string> */
    public function names(): array
    {
        return IconNameCatalog::bundled()->names();
    }

    public function withList(string $page): string
    {
        $start = strpos($page, self::START);
        $end = strpos($page, self::END);
        if ($start === false || $end === false || $end < $start) {
            throw new \RuntimeException(self::PAGE . ' must hold the ' . self::START . ' and ' . self::END . ' markers.');
        }

        return substr($page, 0, $start) . self::START . "\n\n" . $this->list() . "\n" . substr($page, $end);
    }

    public function list(): string
    {
        $groups = [];
        foreach ($this->names() as $name) {
            $groups[$name[0]][] = '`' . $name . '`';
        }
        $lines = [
            ':::details',
            '## Все ' . count($this->names()) . ' имён значков',
            '',
        ];
        foreach ($groups as $letter => $names) {
            $lines[] = '**' . strtoupper((string) $letter) . '.** ' . implode(' · ', $names);
            $lines[] = '';
        }
        $lines[] = ':::';

        return implode("\n", $lines) . "\n";
    }
}
