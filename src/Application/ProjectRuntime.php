<?php

declare(strict_types=1);

namespace Simai\Docara\Application;

use JsonException;
use Simai\Docara\Declarative\Binding\BindingRegistry;
use Simai\Docara\Design\Registry\DesignRegistry;
use Simai\Docara\Portable\FilesystemPath;
use Simai\Docara\Portable\JsonDiagnosticLocator;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\PortableSite\PortableRuntimeMetadata;
use Simai\Docara\Smart\SmartRegistry;

final readonly class ProjectRuntime
{
    /** @param array<string, mixed> $site */
    private function __construct(
        public string $root,
        public array $site,
        public ?string $namespace,
        public SmartRegistry $smarts,
        public DesignRegistry $designs,
        public BindingRegistry $bindings,
    ) {}

    public static function load(string $root): self
    {
        $real = realpath($root);
        if ($real === false || ! is_dir($real) || is_link($root)) {
            throw new PortableConfigurationException('SDK_PROJECT_ROOT_INVALID', 'Project root must be a real directory and not a symlink.');
        }
        $real = FilesystemPath::normalize($real);
        $config = $real . '/docara.json';
        if (! is_file($config) || is_link($config)) {
            throw new PortableConfigurationException('SDK_PROJECT_CONFIG_MISSING', 'Project root must contain a regular docara.json.');
        }
        try {
            $contents = (string) file_get_contents($config);
            $site = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            $location = JsonDiagnosticLocator::locate($contents ?? '');
            throw new PortableConfigurationException(
                'SDK_PROJECT_CONFIG_INVALID',
                'docara.json must be valid JSON.',
                $exception,
                'docara.json',
                $location['pointer'],
                $location['line'],
                $location['column'],
            );
        }
        if (! is_array($site)) {
            throw new PortableConfigurationException('SDK_PROJECT_CONFIG_INVALID', 'docara.json must contain a JSON object.');
        }
        $namespace = $site['smart']['namespace'] ?? null;
        if ($namespace !== null && (! is_string($namespace) || preg_match('/^[a-z][a-z0-9_-]*$/D', $namespace) !== 1)) {
            throw new PortableConfigurationException('SDK_PROJECT_NAMESPACE_INVALID', 'docara.json smart.namespace must be a safe project namespace.');
        }
        $revision = hash_file('sha256', $config) ?: 'unavailable';
        foreach (['smart', 'design'] as $ownedRoot) {
            if (is_link($real . '/' . $ownedRoot)) {
                throw new PortableConfigurationException('SDK_PROJECT_OWNED_ROOT_UNSAFE', "Project-owned root [$ownedRoot] cannot be a symlink.");
            }
        }

        return new self(
            $real,
            $site,
            $namespace,
            $namespace === null || ! is_dir($real . '/smart')
                ? SmartRegistry::bundled()
                : SmartRegistry::withProject($namespace, $real . '/smart', $revision),
            DesignRegistry::bundled($real, $namespace),
            BindingRegistry::bundled(),
        );
    }

    /** @return array{engine_revision:string,project_root:string,input_sha256:string} */
    public function provenance(): array
    {
        $package = (new PortableRuntimeMetadata(dirname(__DIR__, 2)))->package();

        return [
            'engine_revision' => (string) ($package['source_revision'] ?? 'unresolved'),
            'project_root' => '.',
            'input_sha256' => hash_file('sha256', $this->root . '/docara.json') ?: str_repeat('0', 64),
        ];
    }
}
