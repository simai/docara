<?php

declare(strict_types=1);

namespace Simai\Docara\Portable;

final class JsonDiagnosticLocator
{
    /** @return array{line:int,column:int,pointer:string} */
    public static function locate(string $json): array
    {
        $offset = self::failureOffset($json);
        $before = substr($json, 0, $offset);
        $line = substr_count($before, "\n") + 1;
        $lastNewline = strrpos($before, "\n");
        $column = $offset - ($lastNewline === false ? -1 : $lastNewline);

        return ['line' => $line, 'column' => max(1, $column), 'pointer' => '/'];
    }

    private static function failureOffset(string $json): int
    {
        foreach (['/,\s*[}\]]/s', '/(?:^|[{,])\s*([A-Za-z_][A-Za-z0-9_-]*)\s*:/m', '/:\s*([^\s"\[{0-9tfn-])/m'] as $index => $pattern) {
            if (preg_match($pattern, $json, $matches, PREG_OFFSET_CAPTURE) !== 1) {
                continue;
            }
            if ($index === 0) {
                return (int) $matches[0][1];
            }

            return (int) $matches[1][1];
        }

        $trimmed = rtrim($json);

        return max(0, strlen($trimmed) - 1);
    }
}
