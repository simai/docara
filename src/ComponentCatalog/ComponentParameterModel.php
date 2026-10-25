<?php

declare(strict_types=1);

namespace Simai\Docara\ComponentCatalog;

use JsonException;
use Simai\Docara\Portable\PortableConfigurationException;

/**
 * The component parameters as the catalogue declares them, keyed by the name
 * a page uses to call the component (`details`, `badge`, `image`, `quote`).
 *
 * resources/component-catalog/{typed,inline,native}/*.json is the single
 * source: the Markdown renderer takes its accepted parameter names and enum
 * values from here, and a parameter's former names and values
 * (`deprecated_names`, `deprecated_values`) are mapped to the current ones
 * before any renderer sees them.
 */
final class ComponentParameterModel
{
    /**
     * Native Markdown entries are called through Markdown syntax, not by
     * name; the attribute block after an image or an attributed quote
     * carries these names.
     */
    public const NATIVE_SCOPES = [
        'native.links_and_images' => 'image',
        'native.lists_and_quotes' => 'quote',
    ];

    private static ?self $bundled = null;

    /**
     * @param  array<string, array{id:string, parameters:list<array<string, mixed>>}>  $scopes
     */
    public function __construct(private readonly array $scopes) {}

    public static function bundled(): self
    {
        return self::$bundled ??= self::fromCatalogRoot(dirname(__DIR__, 2) . '/resources/component-catalog');
    }

    public static function fromCatalogRoot(string $root): self
    {
        $scopes = [];
        foreach (['typed', 'inline', 'native'] as $directory) {
            $paths = glob(rtrim($root, '/\\') . '/' . $directory . '/*.json') ?: [];
            sort($paths, SORT_STRING);
            foreach ($paths as $path) {
                try {
                    $entry = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
                } catch (JsonException $exception) {
                    throw new PortableConfigurationException(
                        'COMPONENT_CATALOG_SOURCE_INVALID',
                        'A component catalogue source record is not valid JSON.',
                        $exception,
                    );
                }
                if (! is_array($entry) || ! is_string($entry['id'] ?? null)) {
                    continue;
                }
                $scope = match ($directory) {
                    'typed' => is_string($entry['name'] ?? null) ? $entry['name'] : null,
                    'inline' => is_string($entry['authoring']['call'] ?? null) ? ltrim($entry['authoring']['call'], ':') : null,
                    default => self::NATIVE_SCOPES[$entry['id']] ?? null,
                };
                if ($scope === null || $scope === '') {
                    continue;
                }
                $parameters = $entry['authoring']['parameters'] ?? [];
                $scopes[$scope] = [
                    'id' => $entry['id'],
                    'parameters' => is_array($parameters) ? array_values(array_filter($parameters, 'is_array')) : [],
                ];
            }
        }
        ksort($scopes, SORT_STRING);

        return new self($scopes);
    }

    /**
     * The same model with these typed definitions in place of the bundled
     * ones, for a renderer built over its own definition set.
     *
     * @param  list<array<string, mixed>>  $definitions
     */
    public function withTypedDefinitions(array $definitions): self
    {
        $scopes = $this->scopes;
        foreach ($definitions as $definition) {
            if (! is_string($definition['name'] ?? null) || ! is_string($definition['id'] ?? null)) {
                continue;
            }
            $parameters = $definition['authoring']['parameters'] ?? [];
            $scopes[$definition['name']] = [
                'id' => $definition['id'],
                'parameters' => is_array($parameters) ? array_values(array_filter($parameters, 'is_array')) : [],
            ];
        }
        ksort($scopes, SORT_STRING);

        return new self($scopes);
    }

    public function has(string $component): bool
    {
        return isset($this->scopes[$component]);
    }

    /** @return list<string> */
    public function components(): array
    {
        return array_keys($this->scopes);
    }

    public function componentId(string $component): ?string
    {
        return $this->scopes[$component]['id'] ?? null;
    }

    /** @return list<string> the current parameter names, in catalogue order */
    public function names(string $component): array
    {
        return array_map(
            static fn (array $parameter): string => (string) ($parameter['name'] ?? ''),
            $this->scopes[$component]['parameters'] ?? [],
        );
    }

    /** @return list<string>|null the enum values as the attribute parser sees them, or null for a non-enum parameter */
    public function values(string $component, string $name): ?array
    {
        $values = $this->parameter($component, $name)['values'] ?? null;

        return is_array($values)
            ? array_map(static fn (mixed $value): string => is_bool($value) ? ($value ? 'true' : 'false') : (string) $value, $values)
            : null;
    }

    public function default(string $component, string $name): ?string
    {
        $parameter = $this->parameter($component, $name);
        if ($parameter === null || ! array_key_exists('default', $parameter)) {
            return null;
        }
        $default = $parameter['default'];

        return is_bool($default) ? ($default ? 'true' : 'false') : (string) $default;
    }

    /** @return array<string, string> former name => current name */
    public function deprecatedNames(string $component): array
    {
        $names = [];
        foreach ($this->scopes[$component]['parameters'] ?? [] as $parameter) {
            foreach (($parameter['deprecated_names'] ?? []) as $former) {
                $names[(string) $former] = (string) $parameter['name'];
            }
        }

        return $names;
    }

    /** @return array<string, string> former value => current value */
    public function deprecatedValues(string $component, string $name): array
    {
        $values = $this->parameter($component, $name)['deprecated_values'] ?? [];
        $map = [];
        foreach (is_array($values) ? $values : [] as $former => $current) {
            $map[(string) $former] = is_bool($current) ? ($current ? 'true' : 'false') : (string) $current;
        }

        return $map;
    }

    /**
     * Maps former parameter names and values to the current ones.
     *
     * @param  array<string, string>  $attributes
     * @return array{0: array<string, string>, 1: list<ParameterDeprecation>}
     */
    public function normalize(string $component, array $attributes): array
    {
        if (! isset($this->scopes[$component]) || $attributes === []) {
            return [$attributes, []];
        }
        $deprecations = [];
        $formerNames = $this->deprecatedNames($component);
        $normalized = [];
        foreach ($attributes as $name => $value) {
            $current = $formerNames[$name] ?? null;
            if ($current === null) {
                $normalized[$name] = $value;

                continue;
            }
            if (array_key_exists($current, $attributes)) {
                throw new PortableConfigurationException(
                    'MARKDOWN_COMPONENT_ATTRIBUTE_DUPLICATE',
                    "Markdown component [$component] declares both [$current] and its former name [$name]; keep only [$current].",
                );
            }
            $normalized[$current] = $value;
            $deprecations[] = new ParameterDeprecation($component, 'name', $current, $name, $current);
        }
        foreach ($normalized as $name => $value) {
            $currentValue = $this->deprecatedValues($component, $name)[$value] ?? null;
            if ($currentValue !== null) {
                $normalized[$name] = $currentValue;
                $deprecations[] = new ParameterDeprecation($component, 'value', $name, $value, $currentValue);
            }
        }
        ksort($normalized, SORT_STRING);

        return [$normalized, $deprecations];
    }

    /** @return array<string, mixed>|null */
    private function parameter(string $component, string $name): ?array
    {
        foreach ($this->scopes[$component]['parameters'] ?? [] as $parameter) {
            if (($parameter['name'] ?? null) === $name) {
                return $parameter;
            }
        }

        return null;
    }
}
