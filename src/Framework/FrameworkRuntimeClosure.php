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
     * `requires` lists the Smart elements a component may render, which the
     * Loader resolves lazily, so two components may name each other (a context
     * menu with tabs, tabs whose overflow is a context menu). Custom elements
     * upgrade whenever they are defined, so such a cycle is legal: its tags
     * are all reached, in depth-first order from the sorted roots, and the
     * back edge is not followed again.
     *
     * A dependency the Loader's rule registry reaches only lazily from the
     * tag (see {@see lazyTags()}) is not followed: the Loader fetches it when
     * the element first needs it.
     *
     * @param  array<string, mixed>  $runtime
     * @param  list<string>  $tags
     * @param  array<string, array<string, true>>  $lazy
     * @return list<string>
     */
    public static function orderedTags(array $runtime, array $tags, array $lazy = []): array
    {
        $ordered = [];
        $visiting = [];
        $visited = [];
        foreach ($tags as $tag) {
            self::visit($tag, $runtime, $lazy, $ordered, $visiting, $visited);
        }

        return $ordered;
    }

    /**
     * The Smart tags each tag reaches only lazily, read from the Loader's
     * rule registry (rule/rule.json of the release): a `relation` marked
     * `lazy` from a Smart rule to another Smart rule. From ui-994f9db407fa
     * sf-button `requires` sf-menu-context and sf-button-icon for its split
     * menu, while its rule loads them lazily; following them eagerly put the
     * context menu, tabs, checkbox, help mark and sortable list on every
     * page and left the shell tags without their preload.
     *
     * @param  list<mixed>  $rules
     * @return array<string, array<string, true>>
     */
    public static function lazyTags(array $rules): array
    {
        $tagsByRule = [];
        foreach ($rules as $rule) {
            if (is_array($rule) && ($rule['type'] ?? null) === 'smart' && is_string($rule['name'] ?? null)) {
                $tagsByRule[$rule['name']] = array_values(array_filter(
                    is_array($rule['tags'] ?? null) ? $rule['tags'] : [],
                    'is_string',
                ));
            }
        }
        $lazy = [];
        foreach ($rules as $rule) {
            if (! is_array($rule) || ! isset($tagsByRule[$rule['name'] ?? ''])) {
                continue;
            }
            foreach (is_array($rule['relation'] ?? null) ? $rule['relation'] : [] as $relation) {
                $target = is_array($relation) && ($relation['lazy'] ?? false) === true ? ($relation['name'] ?? null) : null;
                if (! is_string($target) || ! isset($tagsByRule[$target])) {
                    continue;
                }
                foreach ($tagsByRule[$rule['name']] as $tag) {
                    foreach ($tagsByRule[$target] as $lazyTag) {
                        $lazy[$tag][$lazyTag] = true;
                    }
                }
            }
        }

        return $lazy;
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
     * @param  array<string, array<string, true>>  $lazy
     * @return list<string>
     */
    public static function eagerSmartFiles(array $runtime, array $manifestTags, array $lazy = []): array
    {
        $files = [];
        foreach (self::orderedTags($runtime, self::admissionRootTags($manifestTags), $lazy) as $tag) {
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
     * @param  array<string, array<string, true>>  $lazy
     * @param  list<string>  $ordered
     * @param  array<string, true>  $visiting
     * @param  array<string, true>  $visited
     */
    private static function visit(
        string $tag,
        array $runtime,
        array $lazy,
        array &$ordered,
        array &$visiting,
        array &$visited,
    ): void {
        if (isset($visited[$tag])) {
            return;
        }
        if (isset($visiting[$tag])) {
            return;
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
        $requires = array_values(array_unique(array_filter(
            $requires,
            static fn (string $dependency): bool => ! isset($lazy[$tag][$dependency]),
        )));
        sort($requires, SORT_STRING);

        $visiting[$tag] = true;
        foreach ($requires as $dependency) {
            self::visit($dependency, $runtime, $lazy, $ordered, $visiting, $visited);
        }
        unset($visiting[$tag]);
        $visited[$tag] = true;
        $ordered[] = $tag;
    }
}
