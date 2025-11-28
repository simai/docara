<?php

namespace Simai\Docara\Handlers;

use Illuminate\Support\Collection;

class IgnoredHandler
{
    public function shouldHandle($file): bool
    {
        $pattern = '#' . preg_quote($_ENV['DOCS_DIR'], '#') . '#';
        return preg_match('/(^\/*_)/', $file->getRelativePathname()) === 1 || preg_match($pattern, str_replace('\\', '/', $file->getRelativePathname())) === 1;
    }

    public function handle(): Collection
    {
        return collect([]);
    }
}
