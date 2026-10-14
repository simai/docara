<?php

declare(strict_types=1);

namespace Simai\Docara\Content;

use Simai\Docara\I18n\LocaleRegistry;
use Simai\Docara\Portable\PortableConfigurationException;

final readonly class RouteMapper
{
    public function __construct(private LocaleRegistry $locales) {}

    public function map(string $locale, string $pagePath): PageSource
    {
        $definition = $this->locales->get($locale);
        $path = trim(str_replace('\\', '/', $pagePath), '/');
        $contentRoot = rtrim($definition->contentRoot, '/');

        if ($path === '' || ! str_starts_with($path, $contentRoot . '/')) {
            throw new PortableConfigurationException(
                'PAGE_SOURCE_OUTSIDE_LOCALE_ROOT',
                "Page source [$pagePath] is outside locale [$locale] content root [$contentRoot].",
            );
        }
        if (preg_match('#(?:^|/)\.\.(?:/|$)#', $path) === 1) {
            throw new PortableConfigurationException(
                'PAGE_SOURCE_PATH_INVALID',
                "Page source [$pagePath] contains a parent-directory segment.",
            );
        }
        if (! str_ends_with(strtolower($path), '.md')) {
            throw new PortableConfigurationException(
                'PAGE_SOURCE_EXTENSION_INVALID',
                "Public page source [$pagePath] must use the .md extension.",
            );
        }

        $relative = substr($path, strlen($contentRoot) + 1, -strlen('.md'));
        if ($relative === 'index') {
            $route = '';
        } elseif (str_ends_with($relative, '/index')) {
            $route = substr($relative, 0, -strlen('/index'));
        } else {
            $route = $relative;
        }

        return new PageSource($definition->tag->value(), $path, $route);
    }
}
