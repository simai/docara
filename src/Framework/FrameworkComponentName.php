<?php

declare(strict_types=1);

namespace Simai\Docara\Framework;

/**
 * The one grammar for component ids, such as `ui.button` or
 * `ui.progress-bar`: dotted segments of lower-case letters, digits and
 * underscores that start with a letter. A segment may also hold single
 * hyphens between its parts, so Framework names are kept exactly as
 * Framework writes them; a hyphen never starts or ends a segment and never
 * comes twice in a row.
 */
final class FrameworkComponentName
{
    public const SEGMENT = '[a-z][a-z0-9_]*(?:-[a-z0-9][a-z0-9_]*)*';

    /** A Framework component id: `ui` and one or more segments. */
    public const FRAMEWORK_PATTERN = '/\Aui(?:\.' . self::SEGMENT . ')+\z/D';

    /** Any dotted component id, such as `ui.badge` or `docara.callout`. */
    public const DOTTED_PATTERN = '/\A' . self::SEGMENT . '(?:\.' . self::SEGMENT . ')+\z/D';

    public static function isFramework(string $name): bool
    {
        return preg_match(self::FRAMEWORK_PATTERN, $name) === 1;
    }

    public static function isDotted(string $name): bool
    {
        return preg_match(self::DOTTED_PATTERN, $name) === 1;
    }
}
