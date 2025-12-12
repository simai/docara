<?php

namespace Simai\Docara;

use Simai\Docara\Support\Layout;

class PageData extends IterableObject
{
    public static function withPageMetaData(IterableObject $siteData, array $meta)
    {
        $page_data = new static($siteData->except('page'));
        $page_data->put('page', (new PageVariable($siteData->page))->put('_meta', new IterableObject($meta)));

        return $page_data;
    }

    public function setPageVariableToCollectionItem($collectionName, $itemName)
    {
        $this->put('page', $this->get($collectionName)->get($itemName));
    }

    public function setExtending($templateToExtend)
    {
        $this->page->_meta->put('extending', $templateToExtend);
    }

    public function setPagePath($path)
    {
        $this->page->_meta->put('path', $path);
        var_dump($path);
        $this->updatePageUrl();
        $this->resolveLayoutForPath($path);
    }

    public function updatePageUrl()
    {
        $this->page->_meta->put('url', rightTrimPath($this->page->getBaseUrl()) . '/' . trimPath($this->page->getPath()));
    }

    /**
     * Resolve layout for the given path using configured base/overrides.
     */
    public function resolveLayoutForPath(string $path): void
    {

        $layoutConfig = $this->page->get('layout');
        if (! $layoutConfig) {
            return;
        }

        $layoutArray = $layoutConfig instanceof IterableObject ? $layoutConfig->toArray() : (array) $layoutConfig;
        $resolved = Layout::resolve($layoutArray, $path);

        $configurator = $this->page->get('configurator');
        if ($configurator && method_exists($configurator, 'getLayoutOverridesForPath')) {
            $locale = method_exists($this->page, 'locale') ? $this->page->locale() : ($this->page->language ?? '');
            $perPageOverrides = $configurator->getLayoutOverridesForPath((string) $locale, $path);
            if (! empty($perPageOverrides)) {
                $resolved = Layout::deepMerge($resolved, $perPageOverrides);
                $this->page->put('layoutResolved', $resolved);
            }
        }

    }
}
