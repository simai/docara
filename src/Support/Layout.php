<?php

    namespace Simai\Docara\Support;

    use Illuminate\Support\Str;

    class Layout
    {
        public static function deepMerge(array $base, array $override): array
        {
            foreach ($override as $key => $value) {
                if (array_key_exists($key, $base) && is_array($base[$key]) && is_array($value)) {
                    $base[$key] = self::deepMerge($base[$key], $value);
                } else {
                    $base[$key] = $value;
                }
            }

            return $base;
        }

        public static function resolve(array $layout, string $path): array
        {
            $resolved = $layout['base'] ?? [];
            $overrides = $layout['overrides'] ?? [];

            $normalizedPath = '/' . trim(str_replace('\\', '/', $path), '/');

            uksort($overrides, static function ($a, $b) {
                return strlen((string)$a) <=> strlen((string)$b);
            });

            foreach ($overrides as $prefix => $override) {
                $normalizedPrefix = '/' . trim((string)$prefix, '/');
                $match = $normalizedPrefix === '/' || Str::startsWith($normalizedPath, $normalizedPrefix);

                if ($match) {
                    $resolved = self::deepMerge($resolved, (array)$override);
                }
            }
            return $resolved;
        }
    }
