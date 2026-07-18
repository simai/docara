<?php

namespace Simai\Docara\Portable;

use JsonException;
use Simai\Docara\Framework\FrameworkComponentException;
use Simai\Docara\Framework\FrameworkLock;

final class PortableConfigurationLoader
{
    private readonly string $root;

    public function __construct(
        string $root,
        private readonly SchemaRepository $schemas = new SchemaRepository,
        private readonly ConfigurationMerger $merger = new ConfigurationMerger,
    ) {
        $segments = preg_split('~[\\\\/]~', $root) ?: [];
        if ($root === '' || str_contains($root, "\0") || in_array('..', $segments, true)) {
            throw new PortableConfigurationException(
                'ROOT_PATH_INVALID',
                'The portable site root must not be empty or contain parent traversal.',
            );
        }
        if ($this->pathTraversesSymlink($root)) {
            throw new PortableConfigurationException('ROOT_SYMLINK_FORBIDDEN', 'The portable site root cannot be a symlink.');
        }

        $resolved = realpath($root);

        if ($resolved === false || ! is_dir($resolved)) {
            throw new PortableConfigurationException('ROOT_NOT_FOUND', "Portable site root [$root] does not exist.");
        }

        $this->root = rtrim($resolved, DIRECTORY_SEPARATOR);
    }

    private function pathTraversesSymlink(string $path): bool
    {
        $absolute = str_starts_with($path, DIRECTORY_SEPARATOR)
            ? $path
            : (string) getcwd() . DIRECTORY_SEPARATOR . $path;
        $segments = array_values(array_filter(
            explode(DIRECTORY_SEPARATOR, trim($absolute, DIRECTORY_SEPARATOR)),
            static fn (string $segment): bool => $segment !== '' && $segment !== '.',
        ));
        $lexicalRoot = DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $segments);

        return is_link($lexicalRoot);
    }

    public function resolve(string $page): ResolvedPagePlan
    {
        $page = $this->normalizeRelativePath($page);

        if (! in_array(strtolower((string) pathinfo($page, PATHINFO_EXTENSION)), ['md', 'markdown'], true)) {
            throw new PortableConfigurationException('PAGE_EXTENSION_INVALID', 'Portable pages must use .md or .markdown.');
        }

        $pagePath = $this->confinedFile($page, true);
        $trace = [];
        $provenance = [];
        $configuration = [];

        [$site, $siteTrace] = $this->loadJson('docara.json', 'site.schema.json', 'site', true);
        $trace[] = $siteTrace;

        $contentRoot = (string) ($site['content_root'] ?? 'content');

        if (! str_starts_with($page, $contentRoot . '/')) {
            throw new PortableConfigurationException(
                'PAGE_OUTSIDE_CONTENT_ROOT',
                "Portable page [$page] is outside configured content root [$contentRoot].",
            );
        }

        $frameworkLockPath = (string) ($site['framework_lock'] ?? '');
        [$frameworkLock, $frameworkTrace] = $this->loadJson(
            $frameworkLockPath,
            'framework-lock.schema.json',
            'framework-lock',
            true,
        );
        $trace[] = $frameworkTrace;
        $this->assertFrameworkLockSemantics($frameworkLock);

        $result = $this->merger->merge([], ['content_root' => 'content'], '@defaults');
        $configuration = $result->configuration;
        $provenance = $result->provenance;

        $result = $this->merger->merge(
            $configuration,
            $this->configurationPayload($site),
            'docara.json',
            $provenance,
        );
        $configuration = $result->configuration;
        $provenance = $result->provenance;

        foreach ($this->sectionFilesFor($page) as $sectionFile) {
            [$section, $sectionTrace] = $this->loadJson($sectionFile, 'section.schema.json', 'section', false);

            if ($section === null) {
                continue;
            }

            $trace[] = $sectionTrace;
            $result = $this->merger->merge(
                $configuration,
                $this->configurationPayload($section),
                $sectionFile,
                $provenance,
            );
            $configuration = $result->configuration;
            $provenance = $result->provenance;
        }

        $sidecar = $this->pageSidecar($page);
        [$pageConfiguration, $pageTrace] = $this->loadJson($sidecar, 'page.schema.json', 'page', false);

        if ($pageConfiguration !== null) {
            $trace[] = $pageTrace;
            $result = $this->merger->merge(
                $configuration,
                $this->configurationPayload($pageConfiguration),
                $sidecar,
                $provenance,
            );
            $configuration = $result->configuration;
            $provenance = $result->provenance;
        }

        $markdown = @file_get_contents($pagePath);
        if (! is_string($markdown)) {
            throw new PortableConfigurationException(
                'PORTABLE_FILE_READ_FAILED',
                "Portable page [$page] could not be read.",
            );
        }
        $trace[] = $this->trace('content', $page, $markdown, null);

        return new ResolvedPagePlan(
            $page,
            $markdown,
            $configuration,
            $frameworkLock,
            $trace,
            $provenance,
        );
    }

    /**
     * @return array{0: array<string, mixed>|null, 1: array<string, mixed>|null}
     */
    private function loadJson(string $relative, string $schema, string $role, bool $required): array
    {
        $relative = $this->normalizeRelativePath($relative);
        $path = $this->confinedFile($relative, $required);

        if ($path === null) {
            return [null, null];
        }

        $contents = @file_get_contents($path);
        if (! is_string($contents)) {
            throw new PortableConfigurationException(
                'PORTABLE_FILE_READ_FAILED',
                "Portable input [$relative] could not be read.",
            );
        }

        try {
            $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new PortableConfigurationException(
                'JSON_INVALID',
                "File [$relative] is not valid JSON: {$exception->getMessage()}",
                $exception,
            );
        }

        $this->schemas->assertValid($decoded, $schema);

        if (! is_array($decoded)) {
            throw new PortableConfigurationException('JSON_OBJECT_REQUIRED', "File [$relative] must contain a JSON object.");
        }

        return [$decoded, $this->trace($role, $relative, $contents, (string) $decoded['schema'])];
    }

    /**
     * @param  array<string, mixed>  $configuration
     * @return array<string, mixed>
     */
    private function configurationPayload(array $configuration): array
    {
        unset($configuration['schema'], $configuration['version']);

        return $configuration;
    }

    /**
     * @return list<string>
     */
    private function sectionFilesFor(string $page): array
    {
        $files = ['_section.json'];
        $directory = (string) pathinfo($page, PATHINFO_DIRNAME);

        if ($directory === '.' || $directory === '') {
            return $files;
        }

        $current = '';

        foreach (explode('/', $directory) as $segment) {
            $current = $current === '' ? $segment : "$current/$segment";
            $files[] = "$current/_section.json";
        }

        return $files;
    }

    private function pageSidecar(string $page): string
    {
        $directory = (string) pathinfo($page, PATHINFO_DIRNAME);
        $filename = (string) pathinfo($page, PATHINFO_FILENAME) . '.page.json';

        return $directory === '.' || $directory === '' ? $filename : "$directory/$filename";
    }

    private function confinedFile(string $relative, bool $required): ?string
    {
        $candidate = $this->root;

        foreach (explode('/', $relative) as $segment) {
            $candidate .= DIRECTORY_SEPARATOR . $segment;

            if (is_link($candidate)) {
                throw new PortableConfigurationException(
                    'SYMLINK_FORBIDDEN',
                    "Portable input [$relative] traverses a symbolic link.",
                );
            }
        }

        if (! file_exists($candidate)) {
            if ($required) {
                throw new PortableConfigurationException('FILE_NOT_FOUND', "Required portable input [$relative] was not found.");
            }

            return null;
        }

        $resolved = realpath($candidate);

        if ($resolved === false || ! is_file($resolved)) {
            throw new PortableConfigurationException('FILE_INVALID', "Portable input [$relative] is not a regular file.");
        }

        if (! str_starts_with($resolved, $this->root . DIRECTORY_SEPARATOR)) {
            throw new PortableConfigurationException('PATH_ESCAPE_FORBIDDEN', "Portable input [$relative] escapes the site root.");
        }

        return $resolved;
    }

    private function normalizeRelativePath(string $path): string
    {
        if ($path === '' || str_contains($path, "\0") || str_contains($path, '\\')) {
            throw new PortableConfigurationException('RELATIVE_PATH_INVALID', "Portable path [$path] is invalid.");
        }

        if (str_starts_with($path, '/') || preg_match('/^[A-Za-z]:/', $path) === 1) {
            throw new PortableConfigurationException('ABSOLUTE_PATH_FORBIDDEN', "Portable path [$path] must be relative.");
        }

        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                throw new PortableConfigurationException('PATH_ESCAPE_FORBIDDEN', "Portable path [$path] contains a forbidden segment.");
            }
        }

        return $path;
    }

    /**
     * @param  array<string, mixed>  $lock
     */
    private function assertFrameworkLockSemantics(array $lock): void
    {
        try {
            FrameworkLock::fromArray($lock);
        } catch (FrameworkComponentException $exception) {
            throw new PortableConfigurationException(
                $exception->errorCode,
                'The Framework lock failed its centralized semantic contract.',
                $exception,
            );
        }

        $runtime = $lock['runtime'];
        $ui = $runtime['ui'];
        $smart = $runtime['ui_smart'];
        $registry = $runtime['framework_registry'];
        $pair = sprintf(
            'sf-%s-%s-%s',
            $runtime['tag'],
            substr($ui['commit'], 0, 8),
            substr($smart['commit'], 0, 8),
        );
        $bundle = $pair
            . '-registry-' . substr($registry['file_sha256'], 0, 8)
            . '-' . $runtime['publication_profile'];

        if ($runtime['pair_id'] !== $pair
            || $runtime['bundle_id'] !== $bundle
            || $runtime['ui']['tag'] !== $runtime['tag']
            || $registry['compatibility_id'] !== $pair
        ) {
            throw new PortableConfigurationException(
                'FRAMEWORK_LOCK_IDENTITY_INVALID',
                'The embedded Larena runtime lock has inconsistent pair, bundle, tag, or registry identity.',
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function trace(string $role, string $source, string $contents, ?string $schema): array
    {
        return array_filter([
            'role' => $role,
            'source' => $source,
            'schema' => $schema,
            'sha256' => hash('sha256', $contents),
        ], static fn (mixed $value): bool => $value !== null);
    }
}
