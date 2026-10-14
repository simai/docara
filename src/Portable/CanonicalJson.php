<?php

namespace Simai\Docara\Portable;

final class CanonicalJson
{
    private const FLAGS = JSON_THROW_ON_ERROR
        | JSON_UNESCAPED_SLASHES
        | JSON_UNESCAPED_UNICODE
        | JSON_PRESERVE_ZERO_FRACTION;

    public static function encode(mixed $value): string
    {
        return json_encode(
            self::normalize($value),
            self::FLAGS,
        );
    }

    public static function encodePretty(mixed $value): string
    {
        return json_encode(
            self::normalize($value),
            JSON_THROW_ON_ERROR
                | JSON_PRETTY_PRINT
                | JSON_UNESCAPED_SLASHES
                | JSON_UNESCAPED_UNICODE
                | JSON_PRESERVE_ZERO_FRACTION,
        ) . "\n";
    }

    /** @param resource $stream */
    public static function writePretty($stream, mixed $value): void
    {
        if (! is_resource($stream)) {
            throw new \InvalidArgumentException('Canonical JSON output requires a writable stream.');
        }

        self::writePrettyValue($stream, $value, 0);
        self::write($stream, "\n");
    }

    /** @param resource $stream */
    private static function writePrettyValue($stream, mixed $value, int $depth): void
    {
        if ($depth > 512) {
            throw new \JsonException('Maximum stack depth exceeded');
        }
        if ($value instanceof \stdClass) {
            $value = get_object_vars($value);
            self::writePrettyObject($stream, $value, $depth);

            return;
        }
        if (! is_array($value)) {
            self::write($stream, json_encode($value, self::FLAGS));

            return;
        }
        if (! array_is_list($value)) {
            self::writePrettyObject($stream, $value, $depth);

            return;
        }
        if ($value === []) {
            self::write($stream, '[]');

            return;
        }

        self::write($stream, "[\n");
        $last = count($value) - 1;
        foreach ($value as $index => $item) {
            self::write($stream, str_repeat(' ', ($depth + 1) * 4));
            self::writePrettyValue($stream, $item, $depth + 1);
            self::write($stream, $index === $last ? "\n" : ",\n");
        }
        self::write($stream, str_repeat(' ', $depth * 4) . ']');
    }

    /** @param resource $stream @param array<string, mixed> $value */
    private static function writePrettyObject($stream, array $value, int $depth): void
    {
        if ($value === []) {
            self::write($stream, '{}');

            return;
        }
        $keys = array_keys($value);
        sort($keys, SORT_STRING);
        self::write($stream, "{\n");
        $last = count($keys) - 1;
        foreach ($keys as $index => $key) {
            self::write($stream, str_repeat(' ', ($depth + 1) * 4));
            self::write($stream, json_encode((string) $key, self::FLAGS) . ': ');
            self::writePrettyValue($stream, $value[$key], $depth + 1);
            self::write($stream, $index === $last ? "\n" : ",\n");
        }
        self::write($stream, str_repeat(' ', $depth * 4) . '}');
    }

    /** @param resource $stream */
    private static function write($stream, string $bytes): void
    {
        if (fwrite($stream, $bytes) !== strlen($bytes)) {
            throw new \RuntimeException('Canonical JSON output could not be written completely.');
        }
    }

    private static function normalize(mixed $value): mixed
    {
        if ($value instanceof \stdClass) {
            $properties = get_object_vars($value);
            ksort($properties, SORT_STRING);
            $normalized = new \stdClass;

            foreach ($properties as $key => $item) {
                $normalized->{$key} = self::normalize($item);
            }

            return $normalized;
        }

        if (! is_array($value)) {
            return $value;
        }

        if (array_is_list($value)) {
            return array_map(self::normalize(...), $value);
        }

        ksort($value, SORT_STRING);

        foreach ($value as $key => $item) {
            $value[$key] = self::normalize($item);
        }

        return $value;
    }
}
