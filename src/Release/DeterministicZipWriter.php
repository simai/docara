<?php

declare(strict_types=1);

namespace Simai\Docara\Release;

use RuntimeException;

final class DeterministicZipWriter
{
    /**
     * @param  array<string, array{contents: string, executable: bool}>  $files
     */
    public function write(string $path, array $files): void
    {
        ksort($files, SORT_STRING);
        $local = '';
        $central = '';
        $offset = 0;
        $count = 0;

        foreach ($files as $name => $file) {
            $this->assertSafeName($name);
            $contents = $file['contents'];
            $crc = (int) hexdec(hash('crc32b', $contents));
            $size = strlen($contents);
            $nameLength = strlen($name);
            $flags = 0x0800;
            $dosTime = 0;
            $dosDate = 33; // 1980-01-01 00:00:00
            $mode = $file['executable'] ? 0100755 : 0100644;

            $header = pack('VvvvvvVVVvv', 0x04034B50, 20, $flags, 0, $dosTime, $dosDate, $crc, $size, $size, $nameLength, 0);
            $local .= $header . $name . $contents;

            $central .= pack(
                'VvvvvvvVVVvvvvvVV',
                0x02014B50,
                0x0314,
                20,
                $flags,
                0,
                $dosTime,
                $dosDate,
                $crc,
                $size,
                $size,
                $nameLength,
                0,
                0,
                0,
                0,
                $mode << 16,
                $offset,
            ) . $name;
            $offset += strlen($header) + $nameLength + $size;
            $count++;
        }

        if ($count > 65535 || strlen($local) > 0xFFFFFFFF || strlen($central) > 0xFFFFFFFF) {
            throw new RuntimeException('Release package exceeds the supported ZIP32 limits.');
        }

        $eocd = pack('VvvvvVVv', 0x06054B50, 0, 0, $count, $count, strlen($central), strlen($local), 0);
        $bytes = $local . $central . $eocd;
        if (file_put_contents($path, $bytes, LOCK_EX) !== strlen($bytes)) {
            throw new RuntimeException("Could not write release archive [{$path}].");
        }
    }

    private function assertSafeName(string $name): void
    {
        if ($name === '' || str_starts_with($name, '/') || str_contains($name, '\\')
            || preg_match('#(^|/)\.\.(/|$)#', $name) === 1 || str_contains($name, "\0")) {
            throw new RuntimeException("Unsafe release archive path [{$name}].");
        }
    }
}
