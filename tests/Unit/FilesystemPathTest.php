<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Simai\Docara\Portable\FilesystemPath;

final class FilesystemPathTest extends TestCase
{
    public function test_windows_style_child_path_is_detected_inside_root(): void
    {
        self::assertTrue(FilesystemPath::isWithin(
            'C:\Applications\docara-larena\content',
            'C:\Applications\docara-larena',
        ));
    }

    public function test_similar_windows_style_sibling_path_is_not_detected_inside_root(): void
    {
        self::assertFalse(FilesystemPath::isWithin(
            'C:\Applications\docara-larena2\content',
            'C:\Applications\docara-larena',
        ));
    }

    public function test_mixed_separator_path_is_detected_inside_root(): void
    {
        self::assertTrue(FilesystemPath::isWithin(
            'C:\Applications\docara-larena/content',
            'C:\Applications\docara-larena/',
        ));
    }
}
