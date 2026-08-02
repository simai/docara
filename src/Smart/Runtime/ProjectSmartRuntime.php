<?php

declare(strict_types=1);

namespace Simai\Docara\Smart\Runtime;

use Simai\Docara\Declarative\Rendering\SmartRenderer;
use Simai\Docara\Declarative\Rendering\TrustedTemplateRegistry;
use Simai\Docara\Declarative\Smart\SmartComponentGateway;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\Smart\SmartRegistry;

final readonly class ProjectSmartRuntime
{
    public function __construct(
        public SmartRegistry $registry,
        public SmartComponentGateway $gateway,
        public TrustedTemplateRegistry $templates,
        public SmartRenderer $renderer,
        public string $providerId,
    ) {}

    /** @param array<string,mixed> $site @param array<string,mixed> $frameworkLock */
    public static function fromSite(string $root, array $site, array $frameworkLock): ?self
    {
        $configuration = $site['smart'] ?? null;
        if ($configuration === null) {
            return null;
        }
        if (! is_array($configuration) || ! is_string($configuration['namespace'] ?? null)) {
            throw new PortableConfigurationException('PROJECT_SMART_CONFIGURATION_INVALID', 'Project Smart configuration is invalid.');
        }
        $namespace = $configuration['namespace'];
        $smartRoot = rtrim($root, DIRECTORY_SEPARATOR) . '/smart';
        $revision = self::treeRevision($smartRoot);
        $registry = SmartRegistry::withProject($namespace, $smartRoot, $revision);
        $providerId = 'project.' . $namespace;
        $gateway = SmartComponentGateway::withProject($registry, $providerId, $frameworkLock);
        $templates = new TrustedTemplateRegistry(smarts: $registry);
        $renderer = new SmartRenderer($templates);

        return new self($registry, $gateway, $templates, $renderer, $providerId);
    }

    private static function treeRevision(string $root): string
    {
        $real = realpath($root);
        if ($real === false || ! is_dir($real) || is_link($root)) {
            throw new PortableConfigurationException('PROJECT_SMART_ROOT_UNSAFE', 'The fixed project Smart root [smart/] is missing or unsafe.');
        }
        $records = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($real, \FilesystemIterator::SKIP_DOTS),
        );
        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->isLink()) {
                throw new PortableConfigurationException('PROJECT_SMART_TREE_UNSAFE', 'Project Smart artifacts cannot contain links or non-files.');
            }
            $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($real) + 1));
            $records[$relative] = hash_file('sha256', $file->getPathname());
        }
        ksort($records, SORT_STRING);

        return 'sha256:' . hash('sha256', json_encode($records, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
    }
}
