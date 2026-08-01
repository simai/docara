<?php

declare(strict_types=1);

namespace Simai\Docara\Content;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Simai\Docara\I18n\LocaleRegistry;
use Simai\Docara\Portable\PortableConfigurationException;

final readonly class PageSourceLocator
{
    private RouteMapper $routes;

    public function __construct(
        private string $root,
        private LocaleRegistry $locales,
        ?RouteMapper $routes = null,
    ) {
        $this->routes = $routes ?? new RouteMapper($locales);
    }

    /** @return list<PageSource> */
    public function forLocale(string $locale): array
    {
        $definition = $this->locales->get($locale);
        $contentPath = rtrim($this->root, '/\\') . '/' . $definition->contentRoot;
        if (! is_dir($contentPath)) {
            throw new PortableConfigurationException(
                'PAGE_SOURCE_LOCALE_ROOT_MISSING',
                "Locale [$locale] content root [{$definition->contentRoot}] does not exist.",
            );
        }

        $sources = [];
        $routes = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($contentPath, FilesystemIterator::SKIP_DOTS),
        );
        foreach ($iterator as $file) {
            if ($file->isLink()) {
                throw new PortableConfigurationException(
                    'PAGE_SOURCE_SYMLINK_FORBIDDEN',
                    'Public page content cannot contain symbolic links.',
                );
            }
            if (! $file->isFile()) {
                continue;
            }
            if (strtolower($file->getExtension()) === 'markdown') {
                throw new PortableConfigurationException(
                    'PAGE_SOURCE_EXTENSION_INVALID',
                    "Public page source [{$file->getPathname()}] must use the .md extension.",
                );
            }
            if ($file->getExtension() !== 'md') {
                continue;
            }

            $pagePath = str_replace('\\', '/', substr($file->getPathname(), strlen(rtrim($this->root, '/\\')) + 1));
            $source = $this->routes->map($locale, $pagePath);
            if (isset($routes[$source->route])) {
                throw new PortableConfigurationException(
                    'PAGE_SOURCE_ROUTE_AMBIGUOUS',
                    "Page sources [{$routes[$source->route]}] and [{$source->path}] map to the same locale route [{$source->route}].",
                );
            }
            $routes[$source->route] = $source->path;
            $sources[] = $source;
        }

        usort($sources, static fn (PageSource $left, PageSource $right): int => strcmp($left->path, $right->path));

        return $sources;
    }
}
