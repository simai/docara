<?php

namespace Simai\Docara\Handlers;

use Simai\Docara\File\CopyFile;
use Simai\Docara\File\Filesystem;

class DefaultHandler
{
    private $files;

    public function __construct(Filesystem $files)
    {
        $this->files = $files;
    }

    public function shouldHandle($file)
    {

        return true;
    }

    public function handle($file, $pageData)
    {
        $extension = $file->getExtension();
        $name = $extension ? $file->getBasename('.' . $extension) : $file->getBasename();

        return collect([
            new CopyFile(
                $file,
                $file->getPathName(),
                $file->getRelativePath(),
                $name,
                $extension,
                $pageData,
            ),
        ]);
    }
}
