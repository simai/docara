<?php

declare(strict_types=1);

namespace Simai\Docara\PortableSite;

use Simai\Docara\Markdown\CommonMarkInspector;
use Simai\Docara\Portable\PortableConfigurationException;

final readonly class PortableColumnRegionParser
{
    public function __construct(
        private CommonMarkInspector $inspector = new CommonMarkInspector,
    ) {}

    /** @return list<string> */
    public function parse(string $markdown): array
    {
        $lines = preg_split('/\r\n|\n|\r/u', $markdown);
        if (! is_array($lines)) {
            throw new PortableConfigurationException(
                'MARKDOWN_BLOCK_INPUT_INVALID',
                'A columns block body could not be split into lines.',
            );
        }

        $inspection = $this->inspector->inspect($markdown);
        $separatorLines = [];
        foreach (array_keys($inspection['top_level_thematic_break_lines']) as $lineNumber) {
            if (($lines[$lineNumber - 1] ?? null) !== '---') {
                continue;
            }
            if ($lineNumber > 1
                && $lineNumber < count($lines)
                && (trim($lines[$lineNumber - 2]) !== ''
                    || trim($lines[$lineNumber]) !== '')
            ) {
                throw new PortableConfigurationException(
                    'MARKDOWN_COLUMNS_SEPARATOR_INVALID',
                    'Columns regions must be separated by an exact top-level [---] line surrounded by blank lines.',
                );
            }
            $separatorLines[] = $lineNumber;
        }

        $regionCount = count($separatorLines) + 1;
        if ($regionCount < 2 || $regionCount > 4) {
            throw new PortableConfigurationException(
                'MARKDOWN_COLUMNS_REGION_COUNT_INVALID',
                'A columns block must contain between two and four Markdown regions.',
            );
        }

        $regions = [];
        $startIndex = 0;
        foreach ($separatorLines as $lineNumber) {
            $regions[] = trim(implode("\n", array_slice(
                $lines,
                $startIndex,
                ($lineNumber - 1) - $startIndex,
            )));
            $startIndex = $lineNumber;
        }
        $regions[] = trim(implode("\n", array_slice($lines, $startIndex)));

        foreach ($regions as $region) {
            if ($region === '') {
                throw new PortableConfigurationException(
                    'MARKDOWN_COLUMNS_REGION_EMPTY',
                    'Every columns region must contain Markdown content.',
                );
            }
        }

        return $regions;
    }
}
