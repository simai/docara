<?php

    use Illuminate\Container\Container;
    use Illuminate\Support\Arr;
    use Illuminate\Support\HtmlString;
    use Illuminate\Support\Str;
    use Symfony\Component\VarDumper\VarDumper;
    use Simai\Docara\Support\Vite;

    if (! function_exists('app')) {
        /**
         * Get the available container instance.
         *
         * @template TClass of object
         *
         * @param  string|class-string<TClass>|null  $abstract
         * @return ($abstract is class-string<TClass> ? TClass : ($abstract is null ? \Simai\Docara\Container : mixed))
         */
        function app(?string $abstract = null, array $parameters = []): mixed
        {
            if (is_null($abstract)) {
                return \Simai\Docara\Container::getInstance();
            }

            return \Simai\Docara\Container::getInstance()->make($abstract, $parameters);
        }
    }

    function leftTrimPath($path)
    {
        return ltrim($path, ' \\/');
    }

    function rightTrimPath($path)
    {
        return rtrim($path ?? '', ' .\\/');
    }

    function trimPath($path)
    {
        return rightTrimPath(leftTrimPath($path));
    }

    function resolvePath($path)
    {
        $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        $segments = [];

        collect(explode(DIRECTORY_SEPARATOR, $path))
            ->filter(fn ($part) => (string) $part !== '')
            ->each(function ($part) use (&$segments) {
                if ($part == '..') {
                    array_pop($segments);
                } elseif ($part != '.') {
                    $segments[] = $part;
                }
            });

        return implode(DIRECTORY_SEPARATOR, $segments);
    }

    /**
     * Get the path to the public folder.
     */
    function public_path($path = '')
    {
        $c = Container::getInstance();
        $source = Arr::get($c['config'], 'build.source', 'source');

        return $source . ($path ? '/' . ltrim($path, '/') : $path);
    }

    /**
     * Get the full path to the source folder.
     */
    function source_path($path = '')
    {
        $c = Container::getInstance();
        $source = Arr::get($c['buildPath'], 'source', 'source');

        return $source . ($path ? '/' . ltrim($path, '/') : $path);
    }

    /**
     * Get the path to a versioned Elixir file.
     */
    function elixir($file, $buildDirectory = 'build')
    {
        static $manifest;
        static $manifestPath;

        if (is_null($manifest) || $manifestPath !== $buildDirectory) {
            $manifest = json_decode(file_get_contents(public_path($buildDirectory . '/rev-manifest.json')), true);

            $manifestPath = $buildDirectory;
        }

        if (isset($manifest[$file])) {
            return '/' . trim($buildDirectory . '/' . $manifest[$file], '/');
        }

        throw new InvalidArgumentException("File {$file} not defined in asset manifest.");
    }

    /**
     * Get the path to a versioned Mix file.
     */
    function mix($path, $manifestDirectory = 'assets')
    {
        static $manifests = [];

        if (! Str::startsWith($path, '/')) {
            $path = "/{$path}";
        }

        if ($manifestDirectory && ! Str::startsWith($manifestDirectory, '/')) {
            $manifestDirectory = "/{$manifestDirectory}";
        }

        if (file_exists(public_path($manifestDirectory . '/hot'))) {
            return new HtmlString("//localhost:8080{$path}");
        }

        $manifestPath = public_path($manifestDirectory . '/mix-manifest.json');

        if (! isset($manifests[$manifestPath])) {
            if (! file_exists($manifestPath)) {
                throw new Exception('The Mix manifest does not exist.');
            }

            $manifests[$manifestPath] = json_decode(file_get_contents($manifestPath), true);
        }

        $manifest = $manifests[$manifestPath];

        if (! isset($manifest[$path])) {
            throw new InvalidArgumentException("Unable to locate Mix file: {$path}.");
        }

        return new HtmlString($manifestDirectory . $manifest[$path]);
    }

    if (! function_exists('url')) {
        function url(string $path): string
        {
            $c = Container::getInstance();

            return trim($c['config']['baseUrl'], '/') . '/' . trim($path, '/');
        }
    }

    if (! function_exists('dd')) {
        function dd(...$args)
        {
            foreach ($args as $x) {
                (new VarDumper)->dump($x);
            }

            exit(1);
        }
    }

    function inline($assetPath)
    {
        preg_match('/^\/assets\/build\/(css|js)\/.*\.(css|js)/', $assetPath, $matches);

        if (! count($matches)) {
            throw new InvalidArgumentException("Given asset path is not valid: {$assetPath}");
        }

        $pathParts = explode('?', $assetPath);

        return new HtmlString(file_get_contents("source{$pathParts[0]}"));
    }

    if (! function_exists('vite_refresh')) {
        function vite_refresh()
        {
            return app(Vite::class)->devServer();
        }
    }

    if (! function_exists('vite')) {
        function vite(string $asset, string $assetPath = '/assets/build'): string
        {
            return app(Vite::class)->url($asset, $assetPath);
        }
    }

    if (! function_exists('layout_section')) {
        /**
         * Get a layout section by key from the resolved layout on the page.
         */
        function layout_section($page, string $key, $default = [])
        {
            if (! $page) {
                return $default;
            }

            $resolved = $page->layoutResolved ?? null;
            $config = $page->layout ?? null;
            $path = method_exists($page, 'getPath') ? (string) $page->getPath() : ($page->_meta->path ?? '/');

            $toArray = static function ($value) {
                return $value instanceof \Simai\Docara\IterableObject ? $value->toArray() : (array) $value;
            };

            // Если layoutResolved отсутствует — соберём из конфига.
            if (! $resolved && $config) {
                $resolved = \Simai\Docara\Support\Layout::resolve($toArray($config), $path);
            }

            $layoutArray = $toArray($resolved);
            $result = data_get($layoutArray, $key, null);

            // Если ключ лежит внутри layout['base'] (например, когда resolve не сработал) — попробуем достать оттуда.
            if ($result === null && isset($layoutArray['base'])) {
                $result = data_get($layoutArray['base'], $key, null);
            }

            // Если ключа нет (например, layoutResolved устарел) — пересоберём из конфига.
            if ($result === null && $config) {
                $fresh = \Simai\Docara\Support\Layout::resolve($toArray($config), $path);
                $layoutArray = $toArray($fresh);
                $result = data_get($layoutArray, $key, $default);
            }

            return $result === null ? $default : $result;
        }
    }

    if (! function_exists('layout_enabled')) {
        /**
         * Check if a layout section (by key) is enabled.
         */
        function layout_enabled($page, string $key): bool
        {
            $section = layout_section($page, $key, null);

            if ($section === null) {
                return false;
            }

            if ($section instanceof \Simai\Docara\IterableObject) {
                return (bool) $section->get('enabled', true);
            }

            if (is_array($section)) {
                return array_key_exists('enabled', $section) ? (bool) $section['enabled'] : true;
            }

            return (bool) $section;
        }
    }
