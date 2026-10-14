<?php

namespace Simai\Docara\Portable;

final class FilesystemPath
{
    public static function normalize(string $path): string
    {
        return rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);
    }

    public static function isAbsolute(string $path): bool
    {
        return str_starts_with($path, '/')
            || str_starts_with($path, '\\')
            || preg_match('/^[A-Za-z]:[\/\\\\]/', $path) === 1;
    }

    public static function isWithin(string $path, string $root, bool $allowRoot = true): bool
    {
        $path = self::normalize($path);
        $root = self::normalize($root);

        if ($allowRoot && $path === $root) {
            return true;
        }

        return str_starts_with($path, $root . DIRECTORY_SEPARATOR);
    }
}
