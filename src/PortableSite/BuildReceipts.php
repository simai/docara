<?php

declare(strict_types=1);

namespace Simai\Docara\PortableSite;

/**
 * Where a build keeps its receipts.
 *
 * Receipts (resolved page plans, performance, examples, redirects, locale
 * routes, translation and documentation status, backlinks and the other
 * projections) describe how a site was built. A visitor never needs them, so
 * they live next to the published folder, not in it: `build_production/` is
 * what is published and `build_production.docara/` holds its receipts.
 *
 * While a build runs, the receipts are staged in `<candidate>/.docara/` and
 * leave the candidate when it is promoted. A folder built by Docara 3.0.0 or
 * earlier still carries them in `<output>/.docara/`; readers fall back to
 * that place.
 */
final class BuildReceipts
{
    public const STAGING_DIRECTORY = '.docara';

    public const SIBLING_SUFFIX = '.docara';

    /** The receipt directory that belongs to a published build folder. */
    public static function directoryFor(string $output): string
    {
        return rtrim($output, '/\\') . self::SIBLING_SUFFIX;
    }

    /** The receipt directory staged inside a build candidate. */
    public static function stagingFor(string $output): string
    {
        return rtrim($output, '/\\') . '/' . self::STAGING_DIRECTORY;
    }

    /**
     * The receipt directory to read for a build folder: its sibling when it
     * exists, otherwise the in-tree directory of an older build or a
     * candidate.
     */
    public static function existing(string $output): string
    {
        $sibling = self::directoryFor($output);
        if (is_dir($sibling) && ! is_link($sibling)) {
            return $sibling;
        }

        return self::stagingFor($output);
    }
}
