<?php

declare(strict_types=1);

namespace Simai\Docara\Preview;

use Simai\Docara\File\Filesystem;
use Simai\Docara\File\ProjectFilesystemGuard;
use Simai\Docara\Portable\CanonicalJson;
use Simai\Docara\Portable\PortableConfigurationException;

final readonly class PreviewShell
{
    public function __construct(
        private Filesystem $files,
        private ProjectFilesystemGuard $writes = new ProjectFilesystemGuard,
    ) {}

    /** @return array<string, mixed> */
    public function publish(string $projectRoot, PreviewArtifact $artifact): array
    {
        $root = $this->writes->root($projectRoot);
        $destination = '.docara-preview/output/' . $artifact->target->value;
        $candidate = '.docara-preview/.candidate-' . $artifact->target->value;
        $previous = '.docara-preview/.previous-' . $artifact->target->value;
        foreach ([$destination, $candidate, $previous] as $generated) {
            $this->writes->directoryPath($root, $generated);
        }
        $this->writes->deleteDirectory($root, $candidate, $this->files);
        $this->writes->ensureDirectory($root, dirname($destination));
        $this->writes->ensureDirectory($root, $candidate);
        $publishedFiles = $this->publishRuntimeFiles($root, $candidate, $artifact->publicRoot);
        $this->writes->putNew($root, $candidate . '/artifact.html', $artifact->html, 'PREVIEW_OUTPUT_COLLISION');
        $this->writes->putNew($root, $candidate . '/index.html', $artifact->pageHtml, 'PREVIEW_OUTPUT_COLLISION');
        $result = $artifact->toArray() + [
            'output' => '.docara-preview/output/' . $artifact->target->value . '/artifact.html',
            'manifest' => '.docara-preview/output/' . $artifact->target->value . '/preview.json',
            'preview' => '.docara-preview/output/' . $artifact->target->value . '/index.html',
            'published_files' => $publishedFiles,
        ];
        $this->writes->putNew($root, $candidate . '/preview.json', CanonicalJson::encodePretty($result), 'PREVIEW_OUTPUT_COLLISION');
        $this->writes->deleteDirectory($root, $previous, $this->files);
        $destinationPath = $this->writes->directoryPath($root, $destination);
        $hadPrevious = is_dir($destinationPath);
        if ($hadPrevious) {
            $this->writes->moveDirectory($root, $destination, $previous);
        }
        try {
            $this->writes->moveDirectory($root, $candidate, $destination);
        } catch (PortableConfigurationException $exception) {
            if ($hadPrevious && is_dir($this->writes->directoryPath($root, $previous))) {
                $this->writes->moveDirectory($root, $previous, $destination);
            }
            throw $exception;
        }
        $this->writes->deleteDirectory($root, $previous, $this->files);

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
            $this->writes->copyNew($projectRoot, $path, $candidate . '/' . $relative, 'PREVIEW_OUTPUT_COLLISION');
            $hashes[$relative] = hash_file('sha256', $path);
        }
        ksort($hashes, SORT_STRING);

        return [
            'count' => count($hashes),
            'sha256' => hash('sha256', CanonicalJson::encode($hashes)),
        ];
    }
}
