<?php

declare(strict_types=1);

namespace Simai\Docara\Framework;

/**
 * The runtime closure of Smart components, shared by the asset planner and the
 * Smart sync script so the two cannot disagree.
 *
 * The admission preflight plans the shell tags together with the tag of every
 * admitted component manifest and requires the eager asset projection to equal
 * exactly what that plan reaches. The sync derives the eager projection from
 * the same roots and the same expansion through `requires`, instead of keeping
 * the list by hand.
 */
final class FrameworkRuntimeClosure
{
    /**
     * Every tag the given roots reach through `requires`, each dependency
     * before the tag that needs it.
     *
     * @param  array<string, mixed>  $runtime
     * @param  list<string>  $tags
     * @return list<string>
     */
    public static function orderedTags(array $runtime, array $tags): array
    {
        $ordered = [];
        $visiting = [];
        $visited = [];
        foreach ($tags as $tag) {
            self::visit($tag, $runtime, $ordered, $visiting, $visited);
        }

        return $ordered;
    }

    /**
     * The roots the admission preflight plans: the Docara shell tags and the
     * tag of each admitted component manifest.
     *
     * @param  list<string>  $manifestTags
     * @return list<string>
     */
    public static function admissionRootTags(array $manifestTags): array
    {
        $tags = array_values(array_unique([
            ...FrameworkAssetPlanner::DOCARA_SHELL_RUNTIME_TAGS,
            ...$manifestTags,
        ]));
        sort($tags, SORT_STRING);

        return $tags;
    }

    /**
     * The Smart entrypoints the admitted closure loads eagerly, as paths
     * relative to the Smart runtime root (for example
     * `smart/alert/js/alert.js`): the stylesheet, when there is one, and the
     * script of every tag the roots reach.
     *
     * @param  array<string, mixed>  $runtime
     * @param  list<string>  $manifestTags
     * @return list<string>
     */
    public static function eagerSmartFiles(array $runtime, array $manifestTags): array
    {
        $files = [];
        foreach (self::orderedTags($runtime, self::admissionRootTags($manifestTags)) as $tag) {
            $component = $runtime['components'][$tag];
            foreach (['css', 'javascript'] as $kind) {
                $path = $component[$kind] ?? null;
                if ($path === null || $path === '') {
                    continue;
                }
                if (! is_string($path) || ! str_starts_with($path, 'smart/')) {
                    throw new FrameworkComponentException('FRAMEWORK_SMART_ASSET_PATH_INVALID', (string) $tag);
                }
                $files[substr($path, strlen('smart/'))] = true;
            }
        }
        $files = array_keys($files);
        sort($files, SORT_STRING);

        return $files;
    }

    /**
     * @param  array<string, mixed>  $runtime
     * @param  list<string>  $ordered
     * @param  array<string, true>  $visiting
     * @param  array<string, true>  $visited
     */
    private static function visit(
        string $tag,
        array $runtime,
        array &$ordered,
        array &$visiting,
        array &$visited,
    ): void {
        if (isset($visited[$tag])) {
            return;
        }
        if (isset($visiting[$tag])) {
            throw new FrameworkComponentException('FRAMEWORK_RUNTIME_DEPENDENCY_CYCLE', $tag);
        }

        $component = $runtime['components'][$tag] ?? null;
        if (! is_array($component)) {
            throw new FrameworkComponentException('FRAMEWORK_RUNTIME_COMPONENT_MISSING', $tag);
        }
        $requires = $component['requires'] ?? [];
        if (! is_array($requires) || ! array_is_list($requires)) {
            throw new FrameworkComponentException('FRAMEWORK_RUNTIME_DEPENDENCY_INVALID', $tag);
        }
        foreach ($requires as $dependency) {
            if (! is_string($dependency) || preg_match('/^sf-[a-z][a-z0-9-]*$/D', $dependency) !== 1) {
                throw new FrameworkComponentException('FRAMEWORK_RUNTIME_DEPENDENCY_INVALID', $tag);
            }
        }
        $requires = array_values(array_unique($requires));
        sort($requires, SORT_STRING);

        $visiting[$tag] = true;
        foreach ($requires as $dependency) {
            self::visit($dependency, $runtime, $ordered, $visiting, $visited);
        }
        unset($visiting[$tag]);
        $visited[$tag] = true;
        $ordered[] = $tag;
    }
}
