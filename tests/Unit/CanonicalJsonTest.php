<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Simai\Docara\Portable\CanonicalJson;

final class CanonicalJsonTest extends TestCase
{
    public function test_streamed_pretty_output_is_byte_identical_to_the_canonical_encoder(): void
    {
        $value = [
            'zeta' => [3, ['beta' => true, 'alpha' => null], 1.0],
            'alpha' => (object) ['delta' => 'Пример', 'beta' => 'a/b'],
            'empty_list' => [],
        ];
        $stream = fopen('php://temp', 'w+b');
        self::assertIsResource($stream);

        CanonicalJson::writePretty($stream, $value);
        rewind($stream);
        $actual = stream_get_contents($stream);
        fclose($stream);

        self::assertSame(CanonicalJson::encodePretty($value), $actual);
    }
}
