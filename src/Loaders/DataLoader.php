<?php

namespace Simai\Docara\Loaders;

use Illuminate\Support\Collection;
use Simai\Docara\SiteData;

class DataLoader
{
    private CollectionDataLoader $collectionDataLoader;

    public function __construct(CollectionDataLoader $collectionDataLoader)
    {
        $this->collectionDataLoader = $collectionDataLoader;
    }

    public function loadSiteData(Collection $config): SiteData
    {
        return SiteData::build($config);
    }

    public function loadCollectionData($siteData, $source): array
    {
        return $this->collectionDataLoader->load($siteData, $source);
    }
}
