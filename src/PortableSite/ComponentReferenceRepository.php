<?php

declare(strict_types=1);

namespace Simai\Docara\PortableSite;

use JsonException;
use Simai\Docara\Portable\PortableConfigurationException;

/**
 * The inputs of the :::reference block, read from fixed places in the site
 * project so a build never depends on anything outside it:
 *
 * - components/<folder>/smart.manifest.json and, when present,
 *   components/<folder>/component.json, where <folder> is the tag without a
 *   leading "sf-" (sf-view-table → components/view-table/), the layout of a
 *   SIMAI Framework or set distribution's smart/ folder;
 * - <locale content root>/component-reference.json, the locale's dictionary
 *   of manifest strings, next to the locale's lang.json.
 */
final class ComponentReferenceRepository
{
    public const ROOT = 'components';

    public const MANIFEST = 'smart.manifest.json';

    public const COMPONENT = 'component.json';

    public const DICTIONARY = 'component-reference.json';

    public const DICTIONARY_SCHEMA = 'docara.component_reference.v1';

    private const MAX_BYTES = 4194304;

    /** @var array<string, array{manifest: array<string,mixed>, component: array<string,mixed>, path: string}> */
    private array $components = [];

    /** @var array<string, array<string,string>|null> */
    private array $dictionaries = [];

    /** @param array<string,string> $contentRoots locale => content root relative to the project */
    public function __construct(
        private readonly string $root,
        private readonly array $contentRoots,
    ) {}

    public static function isTag(string $tag): bool
    {
        return preg_match('/\A[a-z][a-z0-9]*(?:-[a-z0-9]+)+\z/D', $tag) === 1 && strlen($tag) <= 128;
    }

    /** The folder under components/ that holds a tag's manifests. */
    public static function folder(string $tag): string
    {
        return str_starts_with($tag, 'sf-') ? substr($tag, 3) : $tag;
    }

    /** @return list<string> locales of this site */
    public function locales(): array
    {
        return array_map('strval', array_keys($this->contentRoots));
    }

    /** @return array{manifest: array<string,mixed>, component: array<string,mixed>, path: string} */
    public function component(string $tag): array
    {
        if (isset($this->components[$tag])) {
            return $this->components[$tag];
        }
        $directory = self::ROOT . '/' . self::folder($tag);
        $manifestPath = $directory . '/' . self::MANIFEST;
        $manifest = $this->readJson($manifestPath, 'COMPONENT_REFERENCE_MANIFEST_INVALID');
        if ($manifest === null) {
            throw new PortableConfigurationException(
                'COMPONENT_REFERENCE_MANIFEST_MISSING',
                "The reference of [$tag] needs its manifest at [$manifestPath].",
            );
        }
        if (($manifest['custom_element'] ?? null) !== $tag) {
            throw new PortableConfigurationException(
                'COMPONENT_REFERENCE_MANIFEST_INVALID',
                "Manifest [$manifestPath] describes [" . (is_string($manifest['custom_element'] ?? null) ? $manifest['custom_element'] : '?')
                    . "], not [$tag].",
            );
        }
        foreach (['inputs', 'events', 'methods', 'accessibility'] as $key) {
            if (isset($manifest[$key]) && ! is_array($manifest[$key])) {
                throw new PortableConfigurationException(
                    'COMPONENT_REFERENCE_MANIFEST_INVALID',
                    "Manifest [$manifestPath] field [$key] must be an object.",
                );
            }
        }
        if (isset($manifest['inputs']['properties']) && ! is_array($manifest['inputs']['properties'])) {
            throw new PortableConfigurationException(
                'COMPONENT_REFERENCE_MANIFEST_INVALID',
                "Manifest [$manifestPath] field [inputs.properties] must be an object.",
            );
        }
        $componentPath = $directory . '/' . self::COMPONENT;
        $component = $this->readJson($componentPath, 'COMPONENT_REFERENCE_MANIFEST_INVALID') ?? [];
        if (isset($component['css-variables']) && ! is_array($component['css-variables'])) {
            throw new PortableConfigurationException(
                'COMPONENT_REFERENCE_MANIFEST_INVALID',
                "Component description [$componentPath] field [css-variables] must be an object.",
            );
        }

        return $this->components[$tag] = ['manifest' => $manifest, 'component' => $component, 'path' => $directory];
    }

    /**
     * The locale's dictionary: manifest text => the locale's text. Null when
     * the locale keeps no component-reference.json; its pages then show the
     * manifest text as written.
     *
     * @return array<string,string>|null
     */
    public function dictionary(string $locale): ?array
    {
        if (array_key_exists($locale, $this->dictionaries)) {
            return $this->dictionaries[$locale];
        }
        $path = $this->dictionaryPath($locale);
        if ($path === null) {
            return $this->dictionaries[$locale] = null;
        }
        $file = $this->readJson($path, 'COMPONENT_REFERENCE_DICTIONARY_INVALID');
        if ($file === null) {
            return $this->dictionaries[$locale] = null;
        }
        $unknown = array_diff(array_keys($file), ['schema', 'strings', '$comment']);
        if (($file['schema'] ?? null) !== self::DICTIONARY_SCHEMA || ! is_array($file['strings'] ?? null) || $unknown !== []) {
            throw new PortableConfigurationException(
                'COMPONENT_REFERENCE_DICTIONARY_INVALID',
                "Dictionary [$path] must hold {\"schema\": \"" . self::DICTIONARY_SCHEMA . '", "strings": {…}} and nothing else but $comment.',
            );
        }
        $strings = [];
        foreach ($file['strings'] as $source => $text) {
            if (! is_string($text) || trim($text) === '' || trim((string) $source) === '') {
                throw new PortableConfigurationException(
                    'COMPONENT_REFERENCE_DICTIONARY_INVALID',
                    "Dictionary [$path] must map each manifest string to a non-empty string.",
                );
            }
            $strings[(string) $source] = $text;
        }

        return $this->dictionaries[$locale] = $strings;
    }

    public function dictionaryPath(string $locale): ?string
    {
        $contentRoot = $this->contentRoots[$locale] ?? null;

        return $contentRoot === null ? null : trim($contentRoot, '/') . '/' . self::DICTIONARY;
    }

    /** @return array<string,mixed>|null null when the file is absent */
    private function readJson(string $relative, string $invalidCode): ?array
    {
        $root = realpath($this->root);
        $path = $this->root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
        if (! file_exists($path) && ! is_link($path)) {
            return null;
        }
        $real = realpath($path);
        if ($root === false || $real === false || is_link($path) || ! is_file($real)
            || ! str_starts_with($real, rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR)
            || $this->linkedParent($root, $relative)
        ) {
            throw new PortableConfigurationException(
                $invalidCode,
                "[$relative] is not a regular file inside the project.",
            );
        }
        if (filesize($real) > self::MAX_BYTES) {
            throw new PortableConfigurationException($invalidCode, "[$relative] is larger than 4 MB.");
        }
        try {
            $decoded = json_decode((string) file_get_contents($real), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new PortableConfigurationException($invalidCode, "[$relative] is not valid JSON.", $exception);
        }
        if (! is_array($decoded) || ($decoded !== [] && array_is_list($decoded))) {
            throw new PortableConfigurationException($invalidCode, "[$relative] must hold a JSON object.");
        }

        return $decoded;
    }

    private function linkedParent(string $root, string $relative): bool
    {
        $current = rtrim($root, DIRECTORY_SEPARATOR);
        foreach (array_slice(explode('/', $relative), 0, -1) as $segment) {
            $current .= DIRECTORY_SEPARATOR . $segment;
            if (is_link($current)) {
                return true;
            }
        }

        return false;
    }
}
