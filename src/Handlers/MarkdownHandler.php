<?php

    namespace Simai\Docara\Handlers;

    use Simai\Docara\Configurator;
    use Simai\Docara\File\OutputFile;
    use Simai\Docara\File\TemporaryFilesystem;
    use Simai\Docara\PageData;
    use Simai\Docara\Parsers\FrontMatterParser;
    use Simai\Docara\View\ViewRenderer;
    use Simai\Docara\Providers\DocaraEventsServiceProvider;

    class MarkdownHandler
    {
        private $temporaryFilesystem;

        private $parser;

        private $view;

        public function __construct(TemporaryFilesystem $temporaryFilesystem, FrontMatterParser $parser, ViewRenderer $viewRenderer)
        {
            $this->temporaryFilesystem = $temporaryFilesystem;
            $this->parser = $parser;
            $this->view = $viewRenderer;
        }

        public function shouldHandle($file): bool
        {
            return in_array($file->getExtension(), ['markdown', 'md', 'mdown']);
        }

        public function handleCollectionItem($file, PageData $pageData)
        {
            return $this->buildOutput($file, $pageData);
        }

        public function handle($file, $pageData)
        {

            $pageData->page->addVariables($this->getPageVariables($file));
            return $this->buildOutput($file, $pageData);
        }

        private function getPageVariables($file): array
        {
            return array_merge(['section' => 'content'], $this->parseFrontMatter($file));
        }

        private function buildOutput($file, PageData $pageData)
        {
            return collect($pageData->page->extends)
                ->map(function ($extends, $templateToExtend) use ($file, $pageData) {
                    if ($templateToExtend) {
                        $pageData->setExtending($templateToExtend);
                    }

                    $extension = $this->view->getExtension($extends);

                    return new OutputFile(
                        $file,
                        $file->getRelativePath(),
                        $file->getFileNameWithoutExtension(),
                        $extension == 'php' ? 'html' : $extension,
                        $this->render($file, $pageData, $extends),
                        $pageData,
                    );
                });
        }

        private function render($file, $pageData, $extends)
        {

            $uniqueFileName = $file->getPathname() . $extends;

            if ($cached = $this->getValidCachedFile($file, $uniqueFileName)) {
                return $this->view->render($cached->getPathname(), $pageData);
            } elseif ($file->isBladeFile()) {
                return $this->renderBladeMarkdownFile($file, $uniqueFileName, $pageData, $extends);
            }

            return $this->renderMarkdownFile($file, $uniqueFileName, $pageData, $extends);
        }

        private function renderMarkdownFile($file, $uniqueFileName, $pageData, $extends)
        {
            $html = $this->parser->parseMarkdownWithoutFrontMatter(
                $this->getEscapedMarkdownContent($file),
            );

            $this->collectTranslateContent($pageData, $html);
            $html = $this->injectAnchorsInline($pageData, $html);

            $wrapper = $this->view->renderString(
                "@extends('{$extends}')\n" .
                "@section('{$pageData->page->section}'){$html}@endsection",
            );

            return $this->view->render(
                $this->temporaryFilesystem->put($wrapper, $uniqueFileName, '.php'),
                $pageData,
            );
        }

        private function renderBladeMarkdownFile($file, $uniqueFileName, $pageData, $extends)
        {
            $contentPath = $this->renderMarkdownContent($file);

            // For blade markdown we already produced HTML when rendering content; collect it here.
            $renderedHtml = $this->parser->parseMarkdownWithoutFrontMatter(
                $this->getEscapedMarkdownContent($file),
            );
            $this->collectTranslateContent($pageData, $renderedHtml);
            $renderedHtml = $this->injectAnchorsInline($pageData, $renderedHtml);

            return $this->view->render(
                $this->renderBladeWrapper(
                    $uniqueFileName,
                    basename($contentPath, '.blade.md'),
                    $pageData,
                    $extends,
                ),
                $pageData,
            );
        }

        private function renderMarkdownContent($file)
        {
            return $this->temporaryFilesystem->put(
                $this->getEscapedMarkdownContent($file),
                $file->getPathname(),
                '.blade.md',
            );
        }

        private function renderBladeWrapper($sourceFileName, $contentFileName, $pageData, $extends)
        {
            return $this->temporaryFilesystem->put(
                $this->makeBladeWrapper($contentFileName, $pageData, $extends),
                $sourceFileName,
                '.blade.php',
            );
        }

        private function makeBladeWrapper($path, $pageData, $extends)
        {
            return collect([
                "@extends('{$extends}')",
                "@section('{$pageData->page->section}')",
                "@include('{$path}')",
                '@endsection',
            ])->implode("\n");
        }

        private function getValidCachedFile($file, $uniqueFileName)
        {
            $extension = $file->isBladeFile() ? '.blade.md' : '.php';
            $cached = $this->temporaryFilesystem->get($uniqueFileName, $extension);

            if ($cached && $cached->getLastModifiedTime() >= $file->getLastModifiedTime()) {
                return $cached;
            }
        }

        private function getEscapedMarkdownContent($file)
        {
            $replacements = ['<?php' => "<{{'?php'}}"];

            if (in_array($file->getFullExtension(), ['markdown', 'md', 'mdown'])) {
                $replacements = array_merge([
                    '@' => "{{'@'}}",
                    '{@' => '{@',  // Preserve {@ to avoid {{{'@'}} which is invalid Blade (e.g. {@inheritDoc})
                    '{{' => '@{{',
                    '{!!' => '@{!!',
                ], $replacements);
            }

            return strtr($file->getContents(), $replacements);
        }

        private function injectAnchors(Configurator $configurator, string $relativePath, string $html): string
        {
            $count = 0;
            $html = preg_replace('/<!--.*?-->/s', '', $html);
            return preg_replace_callback(
                '/<(h[1-6])( [^>]*)?>(.*?)<\/\1>/si',
                function ($match) use (&$count, $relativePath, $configurator) {
                    [$inlineAnchorId, $cleanHeadingHtml] = $configurator->extractInlineHeadingAnchor($match[3]);
                    $fingerPrint = $configurator->mkFingerprint($cleanHeadingHtml);
                    if (! isset($configurator->fingerPrint[$fingerPrint])) {
                        return $match[0];
                    }
                    $tag = $match[1];
                    $attrs = $match[2] ?? '';
                    if (str_contains($attrs, 'id=')) {
                        return $match[0];
                    }
                    $id = $configurator->makeUniqueHeadingId($relativePath, $tag, $count);
                    if ($inlineAnchorId !== null && $inlineAnchorId !== '') {
                        $id = $inlineAnchorId;
                    }
                    $count++;
                    $cleanHeadingHtml = preg_replace(
                        '/(\S+)$/u',
                        '<span class="nowrap">$1<span class="sf-icon sf-icon--rotate-135">link</span></span>',
                        $cleanHeadingHtml
                    );

                    return "<$tag$attrs id=\"$id\"><a href='#{$id}' onclick='copyAnchor(this)' aria-disabled='false' class='header-anchor'>{$cleanHeadingHtml}</a></$tag>";
                },
                $html
            );
        }

        /**
         * Collects per-page HTML for translation before layout is applied.
         */
        private function collectTranslateContent($pageData, string $html): void
        {
            $configurator = $pageData->page->get('configurator') ?? null;
            if (! $configurator || ! method_exists($configurator, 'addTranslateSource')) {
                return;
            }

            $configurator->addTranslateSource($pageData, $html);
        }

        /**
         * Inject heading anchors early (before Blade/layout) to avoid afterBuild scan.
         */
        private function injectAnchorsInline($pageData, string $html): string
        {
            $configurator = $pageData->page->get('configurator') ?? null;
            if (! $configurator) {
                return $html;
            }

            $relativePath = $pageData->page->getPath() ?? '';
            try {
                $html = $this->injectAnchors($configurator, $relativePath, $html);
            } catch (\Throwable $e) {
                // Non-critical; return original html.
            }

            return $html;
        }

        private function parseFrontMatter($file)
        {
            return $this->parser->getFrontMatter($file->getContents());
        }
    }
