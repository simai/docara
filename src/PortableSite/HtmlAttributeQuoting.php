<?php

declare(strict_types=1);

namespace Simai\Docara\PortableSite;

/**
 * Makes libxml's HTML serialization independent of the libxml version.
 *
 * libxml before 2.14 writes an attribute whose value contains a double quote
 * in single quotes (title='Say "hi"'); 2.14 and later write it in double
 * quotes with &quot; (title="Say &quot;hi&quot;"). The same page therefore
 * built to different bytes on different machines. Docara settles on the
 * newer form: every single-quoted attribute value in a start tag is rewritten
 * in double quotes, with each double quote as &quot;. Text, comments and the
 * contents of script and style elements are left as they are.
 */
final class HtmlAttributeQuoting
{
    public static function normalize(string $html): string
    {
        if (! str_contains($html, "='")) {
            return $html;
        }

        $length = strlen($html);
        $out = '';
        $i = 0;
        while ($i < $length) {
            $open = strpos($html, '<', $i);
            if ($open === false) {
                $out .= substr($html, $i);
                break;
            }
            $out .= substr($html, $i, $open - $i);
            $i = $open;

            if (substr($html, $i, 4) === '<!--') {
                $end = strpos($html, '-->', $i + 4);
                $end = $end === false ? $length : $end + 3;
                $out .= substr($html, $i, $end - $i);
                $i = $end;

                continue;
            }

            if ($i + 1 >= $length || ! ctype_alpha($html[$i + 1])) {
                $out .= '<';
                $i++;

                continue;
            }

            [$tag, $name, $i] = self::startTag($html, $i, $length);
            $out .= $tag;

            if ($name === 'script' || $name === 'style') {
                $close = stripos($html, '</' . $name, $i);
                $close = $close === false ? $length : $close;
                $out .= substr($html, $i, $close - $i);
                $i = $close;
            }
        }

        return $out;
    }

    /** @return array{0: string, 1: string, 2: int} */
    private static function startTag(string $html, int $i, int $length): array
    {
        $j = $i + 1;
        while ($j < $length && (ctype_alnum($html[$j]) || $html[$j] === '-' || $html[$j] === ':')) {
            $j++;
        }
        $name = strtolower(substr($html, $i + 1, $j - $i - 1));
        $tag = substr($html, $i, $j - $i);

        while ($j < $length) {
            $char = $html[$j];
            if ($char === '>') {
                return [$tag . '>', $name, $j + 1];
            }
            if ($char === '=' && $j + 1 < $length && ($html[$j + 1] === '"' || $html[$j + 1] === "'")) {
                $quote = $html[$j + 1];
                $end = strpos($html, $quote, $j + 2);
                $end = $end === false ? $length : $end;
                $value = substr($html, $j + 2, $end - $j - 2);
                $tag .= $quote === "'"
                    ? '="' . str_replace('"', '&quot;', $value) . '"'
                    : '="' . $value . '"';
                $j = min($length, $end + 1);

                continue;
            }
            $tag .= $char;
            $j++;
        }

        return [$tag, $name, $length];
    }
}
