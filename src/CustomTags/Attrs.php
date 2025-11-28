<?php

namespace Simai\Docara\CustomTags;

final class Attrs
{
    /**
     * @return array|string[]
     */
    public static function parseOpenLine(?string $attrStr): array
    {
        $attrStr = trim((string) $attrStr);
        if ($attrStr === '') {
            return [];
        }

        $attrStr = str_replace(
            ["\xC2\xA0", '“', '”', '‘', '’'],
            [' ', '"', '"', "'", "'"],
            $attrStr
        );
        $attrStr = preg_replace('/[ \t\x{00A0}]+/u', ' ', $attrStr);

        $attrs = ['class' => ''];
        $classes = [];

        if (preg_match_all(
            '/([\p{L}\p{N}_][\p{L}\p{N}_:-]*)\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s"\'=<>`]+))/u',
            $attrStr,
            $m, PREG_SET_ORDER
        )) {
            foreach ($m as $mm) {
                $key = $mm[1];
                $val = $mm[2] !== '' ? $mm[2] : ($mm[3] !== '' ? $mm[3] : $mm[4]);
                if ($key === 'class') {
                    array_push($classes, ...preg_split('/\s+/u', trim($val)));
                } else {
                    $attrs[$key] = $val;
                }
            }
            $attrStr = trim(preg_replace(
                '/([\p{L}\p{N}_][\p{L}\p{N}_:-]*)\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s"\'=<>`]+)/u',
                '',
                $attrStr
            ));
        }

        if (preg_match_all('/([.#])([\p{L}\p{N}_:-]+)/u', $attrStr, $m2, PREG_SET_ORDER)) {
            foreach ($m2 as $mm) {
                if ($mm[1] === '.') {
                    $classes[] = $mm[2];
                } else {
                    $attrs['id'] = $mm[2];
                }
            }
        }

        if ($classes) {
            $attrs['class'] = trim(implode(' ', array_unique(array_filter($classes))));
        } else {
            unset($attrs['class']);
        }

        return $attrs;
    }

    public static function merge(array ...$sets): array
    {
        $out = [];
        $classes = [];
        foreach ($sets as $a) {
            foreach ($a as $k => $v) {
                if ($k === 'class') {
                    array_push($classes, ...preg_split('/\s+/', trim((string) $v)));
                } else {
                    $out[$k] = $v;
                }
            }
        }
        if ($classes) {
            $out['class'] = trim(implode(' ', array_unique(array_filter($classes))));
        }

        return $out;
    }
}
