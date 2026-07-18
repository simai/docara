<?php

namespace Simai\Docara\Portable;

final class ConfigurationMerger
{
    /**
     * Objects are merged recursively, lists replace the inherited list, and
     * {"$reset": true, ...} starts an object branch from an empty value.
     * {"$reset": true, "$value": ...} replaces a branch with any JSON value.
     *
     * @param  array<string, mixed>  $base
     * @param  array<string, mixed>  $override
     * @param  array<string, string>  $provenance
     */
    public function merge(array $base, array $override, string $source, array $provenance = []): MergeResult
    {
        $configuration = $this->mergeValue($base, $override, $source, '', $provenance);

        if (! is_array($configuration)) {
            throw new PortableConfigurationException(
                'CONFIGURATION_ROOT_INVALID',
                'A configuration layer must resolve to a JSON object.',
            );
        }

        ksort($provenance, SORT_STRING);

        return new MergeResult($configuration, $provenance);
    }

    /**
     * @param  array<string, string>  $provenance
     */
    private function mergeValue(
        mixed $base,
        mixed $override,
        string $source,
        string $pointer,
        array &$provenance,
    ): mixed {
        $resetToEmptyObject = false;

        if (! is_array($override)) {
            $this->clearProvenance($provenance, $pointer);
            $this->recordProvenance($provenance, $pointer, $source);

            return $override;
        }

        if (array_key_exists('$reset', $override)) {
            if ($override['$reset'] !== true) {
                throw new PortableConfigurationException(
                    'RESET_DIRECTIVE_INVALID',
                    "The $pointer reset directive must be boolean true.",
                );
            }

            $this->clearProvenance($provenance, $pointer);

            if (array_key_exists('$value', $override)) {
                if (count($override) !== 2) {
                    throw new PortableConfigurationException(
                        'RESET_DIRECTIVE_INVALID',
                        "The $pointer reset value cannot be combined with sibling keys.",
                    );
                }

                $value = $override['$value'];
                $this->recordValueProvenance($provenance, $pointer, $value, $source);

                return $value;
            }

            unset($override['$reset']);
            $base = [];
            $resetToEmptyObject = $override === [];
        } elseif (array_key_exists('$value', $override)) {
            throw new PortableConfigurationException(
                'RESET_DIRECTIVE_INVALID',
                "The $pointer \$value directive requires \$reset=true.",
            );
        }

        // json_decode(..., true) represents both {} and [] as an empty PHP
        // array. When the inherited branch is an object, an empty override is
        // therefore the non-destructive object no-op; clearing it requires the
        // explicit $reset directive.
        if ($override === [] && is_array($base) && ! array_is_list($base)) {
            return $base;
        }

        if ($resetToEmptyObject) {
            $this->recordProvenance($provenance, $pointer, $source);

            return new \stdClass;
        }

        if (array_is_list($override)) {
            $this->clearProvenance($provenance, $pointer);
            $this->recordProvenance($provenance, $pointer, $source);

            return $override;
        }

        if (! is_array($base) || array_is_list($base)) {
            $base = [];
        }

        if ($override === []) {
            $this->recordProvenance($provenance, $pointer, $source);
        }

        foreach ($override as $key => $value) {
            $childPointer = $this->pointer($pointer, (string) $key);
            $base[$key] = $this->mergeValue(
                $base[$key] ?? null,
                $value,
                $source,
                $childPointer,
                $provenance,
            );
        }

        return $base;
    }

    /**
     * @param  array<string, string>  $provenance
     */
    private function clearProvenance(array &$provenance, string $pointer): void
    {
        if ($pointer === '') {
            $provenance = [];

            return;
        }

        foreach (array_keys($provenance) as $candidate) {
            if ($candidate === $pointer || str_starts_with($candidate, $pointer . '/')) {
                unset($provenance[$candidate]);
            }
        }
    }

    /**
     * @param  array<string, string>  $provenance
     */
    private function recordValueProvenance(array &$provenance, string $pointer, mixed $value, string $source): void
    {
        if (! is_array($value) || array_is_list($value) || $value === []) {
            $this->recordProvenance($provenance, $pointer, $source);

            return;
        }

        foreach ($value as $key => $item) {
            $this->recordValueProvenance($provenance, $this->pointer($pointer, (string) $key), $item, $source);
        }
    }

    /**
     * @param  array<string, string>  $provenance
     */
    private function recordProvenance(array &$provenance, string $pointer, string $source): void
    {
        $provenance[$pointer === '' ? '/' : $pointer] = $source;
    }

    private function pointer(string $parent, string $key): string
    {
        $escaped = str_replace(['~', '/'], ['~0', '~1'], $key);

        return $parent . '/' . $escaped;
    }
}
