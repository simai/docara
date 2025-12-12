<?php

    namespace Simai\Docara\Handlers;

    use Illuminate\Support\Str;
    use Simai\Docara\Collection\CollectionPaginator;
    use Simai\Docara\File\OutputFile;
    use Simai\Docara\File\TemporaryFilesystem;
    use Simai\Docara\PageData;
    use Simai\Docara\Parsers\FrontMatterParser;
    use Simai\Docara\View\ViewRenderer;

    class PaginatedPageHandler
    {
        private $paginator;

        private $parser;

        private $temporaryFilesystem;

        private $view;

        public function __construct(
            CollectionPaginator $paginator,
            FrontMatterParser $parser,
            TemporaryFilesystem $temporaryFilesystem,
            ViewRenderer $viewRenderer,
        ) {
            $this->paginator = $paginator;
            $this->parser = $parser;
            $this->temporaryFilesystem = $temporaryFilesystem;
            $this->view = $viewRenderer;
        }

        public function shouldHandle($file)
        {
            if (! Str::endsWith($file->getFilename(), ['.blade.md', '.blade.php'])) {
                return false;
            }
            $content = $this->parser->parse($file->getContents());

            return isset($content->frontMatter['pagination']);
        }

        public function handle($file, PageData $pageData)
        {
            $page = $pageData->page;
            $page->addVariables($this->getPageVariables($file));
            $collection = $page->pagination->collection;
            $prefix = $page->pagination->prefix
                ?: $page->collections->{$collection}->prefix
                    ?: $page->prefix
                        ?: '';

            return $this->paginator->paginate(
                $file,
                $pageData->get($collection),
                $page->pagination->perPage
                    ?: $page->collections->{$collection}->perPage
                    ?: $page->perPage
                        ?: 10,
                $prefix,
            )->map(function ($page) use ($file, $pageData, $prefix) {
                $pageData->setPagePath($page->current);
                $pageData->put('pagination', $page);
                $extension = strtolower($file->getExtension());

                return new OutputFile(
                    $file,
                    $file->getRelativePath(),
                    $file->getFilenameWithoutExtension(),
                    ($extension == 'php' || $extension == 'md') ? 'html' : $extension,
                    $this->render($file, $pageData),
                    $pageData,
                    $page->currentPage,
                    $prefix,
                );
            });
        }

        private function getPageVariables($file)
        {
            return $this->parser->getFrontMatter($file->getContents());
        }

        private function render($file, $pageData)
        {
            $bladeContent = $this->parser->getBladeContent($file->getContents());
            $bladeFilePath = $this->temporaryFilesystem->put(
                $bladeContent,
                $file->getPathname(),
                '.blade.php',
            );

            return $this->view->render($bladeFilePath, $pageData);
        }
    }
