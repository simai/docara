<?php

declare(strict_types=1);

namespace Simai\Docara\Document;

use Simai\Docara\Portable\PortableConfigurationException;

/**
 * The space below a block (space=0…8), one step of the SIMAI Framework
 * spacing ladder printed as its m-bottom-N utility. Every page block takes
 * it; a block without it keeps its m-bottom-1. The block that sets it also
 * decides the distance to a heading right after it: the shell drops that
 * heading's own top space (data-docara-space), so one place owns the gap.
 */
final class BlockSpace
{
    /** @var list<string> */
    public const VALUES = ['0', '1', '2', '3', '4', '5', '6', '7', '8'];

    /**
     * Removes space from the attributes the block's own renderer sees and
     * returns its checked value, or null when the author did not write it.
     *
     * @param  array<string,string>  $attributes
     */
    public static function take(array &$attributes, string $component): ?string
    {
        if (! array_key_exists('space', $attributes)) {
            return null;
        }
        $space = (string) $attributes['space'];
        unset($attributes['space']);
        if (! in_array($space, self::VALUES, true)) {
            throw new PortableConfigurationException(
                'MARKDOWN_COMPONENT_ATTRIBUTE_VALUE_INVALID',
                "Markdown component [$component] has invalid [space] value [$space].",
            );
        }

        return $space;
    }

    /** Puts the space on the block's outer element, replacing its bottom margin step. */
    public static function apply(string $html, ?string $space): string
    {
        if ($space === null) {
            return $html;
        }
        if (preg_match('/^(?<lead>\s*(?:<!--.*?-->\s*)*)<(?<tag>[a-z][a-z0-9-]*)(?<attributes>(?:\s[^>]*)?)>/su', $html, $match) !== 1) {
            throw new \LogicException('A block with space rendered no outer element.');
        }
        $attributes = (string) $match['attributes'];
        if (preg_match('/\sclass="(?<classes>[^"]*)"/u', $attributes, $class) === 1) {
            $classes = trim((string) preg_replace('/(?:^|\s)m-bottom-\S+/u', '', (string) $class['classes']));
            $attributes = str_replace(
                $class[0],
                ' class="' . trim($classes . ' m-bottom-' . $space) . '"',
                $attributes,
            );
        } else {
            $attributes .= ' class="m-bottom-' . $space . '"';
        }
        $tag = '<' . $match['tag'] . $attributes . ' data-docara-space="' . $space . '">';

        return $match['lead'] . $tag . substr($html, strlen($match[0]));
    }
}
