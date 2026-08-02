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
        $this->files->put($candidate . '/artifact.html', $artifact->html);
        $result = $artifact->toArray() + [
            'output' => '.docara-preview/output/' . $artifact->target->value . '/artifact.html',
            'manifest' => '.docara-preview/output/' . $artifact->target->value . '/preview.json',
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
}
