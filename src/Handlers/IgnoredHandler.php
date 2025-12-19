<?php

namespace Simai\Docara\Handlers;

use Illuminate\Support\Collection;

class IgnoredHandler
{
    public function shouldHandle($file): bool
    {
        $relative = str_replace('\\', '/', $file->getRelativePathname());
        $basename = $file->getFilename();

        // Skip hidden/dotfiles (.env, .gitignore, etc.)
        if (str_starts_with($basename, '.')) {
            return true;
        }

        // Skip helper classes and config stubs that are not meant for output
        $infraFiles = ['config.php', 'helpers.php'];
        if (in_array($relative, $infraFiles, true)) {
            return true;
        }
        if (str_starts_with($relative, 'Helpers/')) {
            return true;
        }

        $docDir = app('config')->get('docara.docsDir', 'docs');
        $docDir = trim((string) $docDir, '/\\') ?: 'docs';
        $pattern = '#' . preg_quote($docDir, '#') . '#';

        // Skip anything under docs dir (handled by CollectionItemHandler) and underscore-prefixed paths
        return preg_match('/(^\/*_)/', $relative) === 1
            || preg_match($pattern, $relative) === 1;
    }

    public function handle(): Collection
    {
        return collect([]);
    }
}
