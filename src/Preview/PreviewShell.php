<?php

declare(strict_types=1);

namespace Simai\Docara\Preview;

use Simai\Docara\File\Filesystem;
use Simai\Docara\Portable\CanonicalJson;
use Simai\Docara\Portable\PortableConfigurationException;

final readonly class PreviewShell
{
    public function __construct(private Filesystem $files) {}

    /** @return array<string, mixed> */
    public function publish(string $projectRoot, PreviewArtifact $artifact): array
    {
        $root = realpath($projectRoot);
        if ($root === false || is_link($projectRoot)) {
            throw new PortableConfigurationException('PREVIEW_ROOT_INVALID', 'Preview root must be a real project directory.');
        }
        $previewRoot = rtrim($root, '/\\') . '/.docara-preview';
        $destination = $previewRoot . '/output/' . $artifact->target->value;
        $candidate = $previewRoot . '/.candidate-' . $artifact->target->value;
        $previous = $previewRoot . '/.previous-' . $artifact->target->value;
        $this->files->deleteDirectory($candidate);
        $this->files->ensureDirectoryExists(dirname($destination));
        $this->files->ensureDirectoryExists($candidate);
        $publishedFiles = $this->publishRuntimeFiles($root, $candidate, $artifact->publicRoot);
        $this->files->put($candidate . '/artifact.html', $artifact->html);
        $this->files->put($candidate . '/index.html', $artifact->pageHtml);
        $result = $artifact->toArray() + [
            'output' => '.docara-preview/output/' . $artifact->target->value . '/artifact.html',
            'manifest' => '.docara-preview/output/' . $artifact->target->value . '/preview.json',
            'preview' => '.docara-preview/output/' . $artifact->target->value . '/index.html',
            'published_files' => $publishedFiles,
        ];
        $this->files->put($candidate . '/preview.json', CanonicalJson::encodePretty($result));
        $this->files->deleteDirectory($previous);
        if (is_dir($destination) && ! rename($destination, $previous)) {
            throw new PortableConfigurationException('PREVIEW_OUTPUT_SWAP_FAILED', 'Previous preview output could not be staged.');
        }
        if (! rename($candidate, $destination)) {
            if (is_dir($previous)) {
                rename($previous, $destination);
            }
            throw new PortableConfigurationException('PREVIEW_OUTPUT_SWAP_FAILED', 'Preview output could not be published atomically.');
        }
        $this->files->deleteDirectory($previous);

        return $result;
    }

    /** @return array{count:int,sha256:string} */
    private function publishRuntimeFiles(string $projectRoot, string $candidate, string $publicRoot): array
    {
        $source = realpath($publicRoot);
        if ($source === false
            || is_link($publicRoot)
            || ! is_dir($source)
            || ($source !== $projectRoot && ! str_starts_with($source, $projectRoot . DIRECTORY_SEPARATOR))
        ) {
            throw new PortableConfigurationException(
                'PREVIEW_PUBLIC_ROOT_INVALID',
                'Preview public source must be a real generated directory inside the project root.',
            );
        }
        $receipt = $source . '/.docara/resolved-page-plans.json';
        $manifest = json_decode((string) @file_get_contents($receipt), true);
        if (! is_array($manifest) || ($manifest['build']['purpose'] ?? null) !== 'preview') {
            throw new PortableConfigurationException(
                'PREVIEW_PUBLIC_ROOT_PURPOSE_INVALID',
                'Preview public source must carry a preview-purpose build receipt.',
            );
        }

        $hashes = [];
        foreach ($this->files->allFiles($source) as $file) {
            $path = $file->getPathname();
            $real = $file->getRealPath();
            $relative = str_replace(DIRECTORY_SEPARATOR, '/', substr($path, strlen($source) + 1));
            if ($relative === ''
                || str_starts_with($relative, '.docara/')
                || strtolower((string) pathinfo($relative, PATHINFO_EXTENSION)) === 'html'
            ) {
                continue;
            }
            $stat = @lstat($path);
            if ($real === false
                || is_link($path)
                || ! str_starts_with($real, $source . DIRECTORY_SEPARATOR)
                || ! is_array($stat)
                || (($stat['mode'] ?? 0) & 0170000) !== 0100000
                || ($stat['nlink'] ?? 1) > 1
            ) {
                throw new PortableConfigurationException(
                    'PREVIEW_PUBLIC_FILE_INVALID',
                    "Preview public file [$relative] is unsafe.",
                );
            }
            $target = $candidate . '/' . $relative;
            $this->files->ensureDirectoryExists(dirname($target));
            if (! $this->files->copy($path, $target)) {
                throw new PortableConfigurationException(
                    'PREVIEW_PUBLIC_FILE_COPY_FAILED',
                    "Preview public file [$relative] could not be copied.",
                );
            }
            $hashes[$relative] = hash_file('sha256', $path);
        }
        ksort($hashes, SORT_STRING);

        return [
            'count' => count($hashes),
            'sha256' => hash('sha256', CanonicalJson::encode($hashes)),
        ];
    }
}
