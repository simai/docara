<?php

namespace Simai\Docara\Handlers;

use Illuminate\Support\Str;
use Simai\Docara\File\CopyFile;

class DocsAssetHandler
{
    /**
     * Copy non-markdown/non-blade files under DOCS_DIR as-is.
     */
    public function shouldHandle($file): bool
    {
        $relative = str_replace('\\', '/', $file->getRelativePathname());
        $basename = $file->getFilename();
        $docDir = app('config')->get('docara.docsDir', 'docs');
        $docDir = trim((string) $docDir, '/');

        if (Str::startsWith($basename, ['.', '_'])) {
            return false;
        }

        // Only handle files inside the docs dir
        if (! Str::startsWith($relative, $docDir . '/')) {
            return false;
        }

        // Skip markdown/blade docs (handled elsewhere)
        if (Str::contains($basename, '.blade.')) {
            return false;
        }
        if (in_array(strtolower($file->getExtension()), ['markdown', 'md', 'mdown', 'php'], true)) {
            return false;
        }

        return true;
    }

    public function handle($file, $pageData)
    {
        $relativePath = str_replace('\\', '/', $file->getRelativePath());
        $docDir = app('config')->get('docara.docsDir', 'docs');
        $docDir = trim((string) $docDir, '/');
        if (Str::startsWith($relativePath, $docDir . '/')) {
            $relativePath = substr($relativePath, strlen($docDir) + 1);
        }

        return collect([
            new CopyFile(
                $file,
                $file->getPathName(),
                $relativePath,
                $file->getBasename('.' . $file->getExtension()),
                $file->getExtension(),
                $pageData,
            ),
        ]);
    }
}
