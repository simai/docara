<?php

declare(strict_types=1);

namespace Simai\Docara\Content;

use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\Portable\SchemaRepository;

final readonly class SourceBoundaryValidator
{
    private const CONFIG_PROSE_KEYS = [
        'article', 'articles', 'body', 'content', 'css', 'html', 'markdown', 'prose',
    ];

    private const MANIFEST_PROSE_KEYS = [
        'article', 'articles', 'body', 'content', 'css', 'description', 'documentation',
        'example', 'examples', 'html', 'limitations', 'markdown', 'prose', 'title',
    ];

    private const LANG_NAMESPACES = [
        'accessibility', 'common', 'copy', 'language', 'navigation', 'reader', 'redirect',
        'search', 'shell', 'toc', 'transitions',
    ];

    public function __construct(private SchemaRepository $schemas = new SchemaRepository) {}

    public function assertPublicInputPath(string $path): void
    {
        $path = trim(str_replace('\\', '/', $path), '/');
        if ($path === 'site.json') {
            throw new PortableConfigurationException(
                'TARGET_SITE_JSON_FORBIDDEN',
                'The target architecture does not provide a site.json compatibility input.',
            );
        }
        foreach (['resources/i18n', 'resources/language-packs', 'resources/system-messages'] as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
                throw new PortableConfigurationException(
                    'TARGET_PUBLIC_INPUT_FORBIDDEN',
                    "Package-owned path [$path] cannot be a public PageBuilder input.",
                );
            }
        }
    }

    /** @param array<string, mixed> $configuration */
    public function assertComposition(array $configuration, string $kind): void
    {
        if (! in_array($kind, ['site', 'section', 'page'], true)) {
            throw new PortableConfigurationException(
                'TARGET_COMPOSITION_KIND_INVALID',
                "Unknown target composition kind [$kind].",
            );
        }
        if ($kind !== 'site') {
            foreach (['title', 'description'] as $key) {
                if (array_key_exists($key, $configuration)) {
                    throw new PortableConfigurationException(
                        'TARGET_CONFIG_PROSE_FORBIDDEN',
                        "Target $kind configuration cannot own [$key] prose.",
                    );
                }
            }
        }
        $this->assertNoProse($configuration, self::CONFIG_PROSE_KEYS, 'TARGET_CONFIG_PROSE_FORBIDDEN');
    }

    /** @param array<string, mixed> $language */
    public function assertLanguage(array $language): void
    {
        $this->schemas->assertValid($language, 'lang.schema.json');
        foreach (array_keys($language) as $namespace) {
            if (in_array($namespace, ['schema', 'version'], true)) {
                continue;
            }
            if (! in_array($namespace, self::LANG_NAMESPACES, true)) {
                throw new PortableConfigurationException(
                    'TARGET_LANG_NAMESPACE_FORBIDDEN',
                    "lang.json namespace [$namespace] is not shared interface copy.",
                );
            }
        }
        $this->assertLeafStrings($language, 'lang.json');
    }

    /** @param array<string, mixed> $manifest */
    public function assertComponentManifest(array $manifest): void
    {
        $this->assertNoProse($manifest, self::MANIFEST_PROSE_KEYS, 'TARGET_COMPONENT_PROSE_FORBIDDEN');
    }

    /** @param array<string, mixed> $value @param list<string> $prohibitedKeys */
    private function assertNoProse(array $value, array $prohibitedKeys, string $errorCode, string $pointer = ''): void
    {
        foreach ($value as $key => $item) {
            $segment = is_string($key) ? strtolower($key) : (string) $key;
            $itemPointer = $pointer . '/' . $segment;
            if (in_array($segment, $prohibitedKeys, true)) {
                throw new PortableConfigurationException(
                    $errorCode,
                    "Public prose field [$itemPointer] is forbidden at this target boundary.",
                );
            }
            if (is_array($item)) {
                $this->assertNoProse($item, $prohibitedKeys, $errorCode, $itemPointer);

                continue;
            }
            if (! is_string($item)) {
                continue;
            }
            if (str_contains($item, "\n")
                || preg_match('/<\/?[a-z][^>]*>/i', $item) === 1
                || preg_match('/(?:^|\s)(?:#{1,6}\s|```|~~~|:::)/m', $item) === 1
                || preg_match('/(?:^|[;{])\s*[a-z-]+\s*:\s*[^\/]/i', $item) === 1
            ) {
                throw new PortableConfigurationException(
                    $errorCode,
                    "Embedded Markdown, HTML or CSS is forbidden at [$itemPointer].",
                );
            }
        }
    }

    /** @param array<string, mixed> $value */
    private function assertLeafStrings(array $value, string $pointer): void
    {
        foreach ($value as $key => $item) {
            if (in_array($key, ['schema', 'version'], true)) {
                continue;
            }
            $itemPointer = $pointer . '/' . $key;
            if (is_array($item)) {
                $this->assertLeafStrings($item, $itemPointer);

                continue;
            }
            if (! is_string($item) || trim($item) === '' || mb_strlen($item) > 280
                || str_contains($item, "\n") || preg_match('/<\/?[a-z][^>]*>/i', $item) === 1
            ) {
                throw new PortableConfigurationException(
                    'TARGET_LANG_VALUE_INVALID',
                    "Shared interface string [$itemPointer] must be a short plain-text string.",
                );
            }
        }
    }
}
