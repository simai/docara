---
extends: _core._layouts.documentation
section: content
title: Event Listeners
description: Event Listeners
---

# Event Listeners

Docara exposes three events you can hook into to run custom code before and after your build is processed.
!links

- ***A `beforeBuild` event is fired before any `source` files have been processed.*** This gives you an opportunity to
  programmatically modify `config.php` variables, fetch data from external `sources`, or modify files in the source
  folder.

- ***An `afterCollections` event is fired after any collections have been processed, but before any output files are
  built.***
  This gives you access to the parsed contents of collection items.

- ***An `afterBuild` event is fired after the build is complete, and all output files have been written to the `build`
  directory.*** This allows you to obtain a list of the output file paths (to use, for example, when creating a
  `sitemap.xml`
  file), programmatically create output files, or take care of any other post-processing tasks.

!endlinks

---

## Registering event listeners as closures

Add listeners in `bootstrap.php` via the `$events` bus; call the event name as a method:

> bootstrap.php

```php 
$events->beforeBuild(function ($docara) {
// your code here
});

$events->afterCollections(function ($docara) {
// your code here
});

$events->afterBuild(function ($docara) {
// your code here
});
```

Closures receive an instance of `Simai\Docara\Docara`, which includes helper methods to inspect the site and interact
with files and config.

For example, the following listener will fetch the current weather from an external API, and add it as a variable to
`config.php`, where it can be referenced in your templates:

> bootstrap.php

```php
$events->beforeBuild(function ($docara) {
$url = "http://api.openweathermap.org/data/2.5/weather?" . http_build_query([
'q' => $docara->getConfig('city'),
'appid' => $docara->getConfig('openweathermap_api_key'),
'units' => 'imperial',
]);

    $docara->setConfig('current_weather', json_decode(file_get_contents($url))->main);

});
```

---

## Registering event listeners as classes

For more complex event listeners, you can specify the name of a class, or an array of class names, instead of a closure.
These classes can either live directly in `bootstrap.php` or in a separate directory. Listener classes should contain a
`handle()` method that accepts an instance of `Docara`:

> bootstrap.php

```php 
$events->afterBuild(GenerateSitemap::class);

$events->afterBuild([GenerateSitemap::class, SendNotification::class]);
```

> listeners/GenerateSitemap.php

```php 
<?php

namespace App\Listeners;

use Simai\Docara\Docara;
use samdark\sitemap\Sitemap;

class GenerateSitemap
{
    public function handle(Docara $docara)
    {
        $baseUrl = $docara->getConfig('baseUrl');
        $sitemap = new Sitemap($docara->getDestinationPath() . '/sitemap.xml');

        collect($docara->getOutputPaths())->each(function ($path) use ($baseUrl, $sitemap) {
            if (! $this->isAsset($path)) {
                $sitemap->addItem($baseUrl . $path, time(), Sitemap::DAILY);
            }
        });

        $sitemap->write();
    }

    public function isAsset($path)
    {
        return starts_with($path, '/assets');
    }
}
```

If there are multiple listeners defined for a single event, they will be fired in the order in which they were defined.

To call a listener class that lives in a separate directory, the class namespace should be added to a `composer.json`
file:

> composer.json

```json 
{
  "autoload": {
    "psr-4": {
      "App\\Listeners\\": "listeners"
    }
  }
}
```

---

## Helper methods in $jigsaw

The instance of `Docara` available to each event listener includes the following helper methods:
---
`getEnvironment()`

Returns the current environment, e.g. `local` or `production`.

---
`getCollections()`

Returns collection names (keys) present in site data.

---
`getCollection($collection)`

Returns items in a particular collection.

---
`getConfig()`

Returns the settings array from `config.php`.

---
`getConfig($key)`

Returns a specific setting from `config.php` (dot notation supported).

---
`setConfig($key, $value)`

Adds or modifies a setting in `config.php` (dot notation supported).

---
`getSourcePath()`

Returns the absolute path to the `source` directory.

---
`setSourcePath($path)`

Sets the path to the `source` directory.

---
`getDestinationPath()`

Returns the absolute path to the `build` directory.

---
`setDestinationPath($path)`

Sets the path to the `build` directory.

---
`getPages()` (after build)

Returns a collection of all output pages with their `$page` data.

---
`getOutputPaths()` (after build)

Returns a collection of paths to generated output files, relative to the `build` directory.

---
`readSourceFile($fileName)`

Returns the contents of a file in the `source` directory.

---
`writeSourceFile($fileName, $contents)`

Writes a file to the `source` directory.

---
`readOutputFile($fileName)`

Returns the contents of a file in the `build` directory.

---
`writeOutputFile($fileName, $contents)`

Writes a file to the `build` directory.
