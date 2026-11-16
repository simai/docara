<?php

declare(strict_types=1);

namespace Simai\Docara\PortableSite;

/**
 * Draws the picture a link preview shows for a site that sets no
 * branding.share_image: one 1200x630 PNG per language with the site title,
 * the language's description and the site address. The bundled Inter font
 * covers Latin, Cyrillic and Greek; text in other scripts is left out, and
 * the preview still shows the page title and description below the picture.
 */
final class ShareCardRenderer
{
    public const WIDTH = 1200;

    public const HEIGHT = 630;

    private const MARGIN = 88;

    private const TEXT_WIDTH = 720;

    private const SCALE = 2;

    /** Docara red. */
    private const ACCENT = 0xE81223;

    public static function available(): bool
    {
        return function_exists('imagettftext')
            && function_exists('imagepng')
            && (imagetypes() & IMG_PNG) !== 0;
    }

    /** @return string PNG bytes */
    public static function render(string $title, ?string $description, ?string $address): string
    {
        // Drawn at twice the size and scaled down, so the ring and the
        // letters get smooth edges.
        $s = self::SCALE;
        $canvas = imagecreatetruecolor(self::WIDTH * $s, self::HEIGHT * $s);
        if ($canvas === false) {
            throw new \RuntimeException('The share picture could not be created.');
        }
        imagealphablending($canvas, true);
        imagefilledrectangle($canvas, 0, 0, self::WIDTH * $s - 1, self::HEIGHT * $s - 1, self::color($canvas, 0xFFFFFF));
        // Docara's mark: the left half of a pale ring on the right edge and a
        // red bar on the left.
        imagefilledellipse($canvas, self::WIDTH * $s, 315 * $s, 772 * $s, 772 * $s, self::color($canvas, 0xFDE6E9));
        imagefilledellipse($canvas, self::WIDTH * $s, 315 * $s, 360 * $s, 360 * $s, self::color($canvas, 0xFFFFFF));
        imagefilledrectangle($canvas, 0, 0, 12 * $s - 1, self::HEIGHT * $s - 1, self::color($canvas, self::ACCENT));

        $bold = self::font('Inter-Bold.ttf');
        $regular = self::font('Inter-Regular.ttf');
        $x = self::MARGIN * $s;
        $top = 64;
        $footer = self::HEIGHT - 64;
        $bottom = $footer - 64;

        // Measure first, then centre the title and description between
        // the top edge and the address line.
        $title = self::covered($title) ? trim($title) : '';
        $textSize = 28;
        $textLine = 42;
        [$titleSize, $titleLines] = self::fit($title, $bold);
        $titleLine = (int) round($titleSize * 1.28);
        $height = count($titleLines) * $titleLine;
        $descriptionLines = [];
        if ($description !== null && self::covered($description)) {
            $room = intdiv($bottom - $top - $height - 28, $textLine);
            $descriptionLines = self::lines($description, $regular, $textSize * $s, self::TEXT_WIDTH * $s, max(0, min(3, $room)));
        }
        if ($descriptionLines !== []) {
            $height += 28 + count($descriptionLines) * $textLine;
        }
        $y = ($top + max(0, intdiv($bottom - $top - $height, 2))) * $s;

        foreach ($titleLines as $line) {
            // The baseline sits about 0.95 of the size below the line top.
            imagettftext($canvas, $titleSize * $s, 0, $x, $y + (int) round($titleSize * 1.02 * $s), self::color($canvas, 0x232323), $bold, $line);
            $y += $titleLine * $s;
        }
        if ($descriptionLines !== []) {
            $y += 28 * $s;
            foreach ($descriptionLines as $line) {
                imagettftext($canvas, $textSize * $s, 0, $x, $y + ($textSize + 4) * $s, self::color($canvas, 0x3F3F46), $regular, $line);
                $y += $textLine * $s;
            }
        }

        if ($address !== null && $address !== '' && self::covered($address)) {
            imagefilledellipse($canvas, ($x + 6 * $s), ($footer - 8) * $s, 12 * $s, 12 * $s, self::color($canvas, self::ACCENT));
            imagettftext($canvas, 20 * $s, 0, $x + 28 * $s, $footer * $s, self::color($canvas, 0x52525B), $regular, $address);
        }

        $image = imagecreatetruecolor(self::WIDTH, self::HEIGHT);
        imagecopyresampled($image, $canvas, 0, 0, 0, 0, self::WIDTH, self::HEIGHT, self::WIDTH * $s, self::HEIGHT * $s);
        imagedestroy($canvas);
        ob_start();
        imagepng($image, null, 9);
        $bytes = (string) ob_get_clean();
        imagedestroy($image);

        return $bytes;
    }

    /**
     * The largest title size that fits in two lines, then in three; a title
     * longer than that ends with an ellipsis.
     *
     * @return array{0: int, 1: list<string>}
     */
    private static function fit(string $title, string $font): array
    {
        if ($title === '') {
            return [0, []];
        }
        $words = preg_split('/\s+/u', $title, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        foreach ([[76, 2], [64, 2], [54, 2], [54, 3], [46, 3]] as [$size, $maxLines]) {
            // A size that would break a word is tried only when nothing else fits.
            $widest = max(array_map(static fn (string $word): int => self::width($word, $font, $size * self::SCALE), $words));
            if ($widest > self::TEXT_WIDTH * self::SCALE && $size !== 46) {
                continue;
            }
            $lines = self::lines($title, $font, $size * self::SCALE, self::TEXT_WIDTH * self::SCALE, 99);
            if (count($lines) <= $maxLines) {
                return [$size, $lines];
            }
        }

        return [46, self::lines($title, $font, 46 * self::SCALE, self::TEXT_WIDTH * self::SCALE, 3)];
    }

    /** Whether the bundled font has letters for the whole text. */
    public static function covered(string $text): bool
    {
        return preg_match('/^[\p{Latin}\p{Cyrillic}\p{Greek}\p{Common}\p{Inherited}]*$/u', $text) === 1;
    }

    private static function font(string $name): string
    {
        return dirname(__DIR__, 2) . '/resources/share-card/' . $name;
    }

    private static function color(\GdImage $image, int $rgb): int
    {
        return (int) imagecolorallocate($image, ($rgb >> 16) & 0xFF, ($rgb >> 8) & 0xFF, $rgb & 0xFF);
    }

    /** @return list<string> */
    private static function lines(string $text, string $font, int $size, int $width, int $maxLines): array
    {
        if ($maxLines < 1) {
            return [];
        }
        $words = preg_split('/\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        // A word wider than the line is carried over letter by letter.
        $pieces = [];
        foreach ($words as $word) {
            while (self::width($word, $font, $size) > $width && mb_strlen($word) > 1) {
                $cut = mb_strlen($word) - 1;
                while ($cut > 1 && self::width(mb_substr($word, 0, $cut), $font, $size) > $width) {
                    $cut--;
                }
                $pieces[] = mb_substr($word, 0, $cut);
                $word = mb_substr($word, $cut);
            }
            $pieces[] = $word;
        }
        $words = $pieces;
        $lines = [];
        $current = '';
        foreach ($words as $word) {
            $candidate = $current === '' ? $word : $current . ' ' . $word;
            if ($current !== '' && self::width($candidate, $font, $size) > $width) {
                $lines[] = $current;
                $current = $word;
                if (count($lines) === $maxLines) {
                    $lines[$maxLines - 1] = self::ellipsis($lines[$maxLines - 1], $font, $size, $width);

                    return $lines;
                }
            } else {
                $current = $candidate;
            }
        }
        if ($current !== '') {
            $lines[] = self::width($current, $font, $size) > $width
                ? self::ellipsis($current, $font, $size, $width)
                : $current;
        }

        return $lines;
    }

    private static function ellipsis(string $line, string $font, int $size, int $width): string
    {
        $line = rtrim($line, ' ,.;:—-');
        while ($line !== '' && self::width($line . '…', $font, $size) > $width) {
            $line = rtrim(mb_substr($line, 0, -1));
        }

        return $line . '…';
    }

    private static function width(string $text, string $font, int $size): int
    {
        $box = imagettfbbox($size, 0, $font, $text);

        return $box === false ? 0 : $box[2] - $box[0];
    }
}
