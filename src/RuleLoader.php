<?php

    namespace Simai\Docara;

    use Simai\Docara\Console\ConsoleOutput;

    class RuleLoader
    {
        private string $url;

        private string $cachePath;

        private int $ttlSeconds;

        public array $rules = [];

        public array $modules = [];

        public array $loadedPlugins = [];


        private string $cdnUrl;

        public array $perPageModuleHash = [];

        public array $extPerHash = [];

        public string $delimeterUrl;

        public PageManifest $manifest;

        private ?ConsoleOutput $console = null;

        private bool $useModuleCache;

        public function __construct(
            string $url,
            string $cachePath = __DIR__ . '/../storage/cache/rules.json',
            int    $ttlSeconds = 900,
            string $delimeterUrl = '/',
            bool   $useModuleCache = true
        )
        {
            $this->cdnUrl = $url;
            $this->url = $this->cdnUrl . '/rule/rule.json';
            $this->cachePath = $cachePath;
            $this->ttlSeconds = $ttlSeconds;
            $this->delimeterUrl = $delimeterUrl;
            $this->manifest = new PageManifest($this->cachePath . '/page-manifest.json');
            $this->useModuleCache = $useModuleCache;
        }

        /**
         * Get rules from cache or remote source.
         */
        public function getRules(): void
        {
            $cached = $this->loadCache();
            if ($cached && !$this->isExpired($cached['ts'])) {
                $this->rules = $this->normalizeRules($cached['rules']);
                return;
            }

            $fresh = $this->fetchRemote();
            if ($fresh) {
                $this->saveCache($fresh);

                $this->rules = $this->normalizeRules($fresh);
                return;
            }

            $this->rules = $this->normalizeRules($cached['rules'] ?? []);
        }

        function getFirstAndLastPart(string $value, string $delimiter = '/'): array|false
        {
            if (!is_string($value)) {
                return false;
            }


            $normalized = trim($value, $delimiter);

            if ($normalized === '') {
                return false;
            }


            $parts = explode($delimiter, $normalized);

            return [
                'first' => $parts[0],
                'last' => $parts[count($parts) - 1],
            ];
        }

        private function normalizeRules(array $rules): array
        {
            $map = [];
            foreach ($rules as $rule) {
                if (!is_array($rule) || empty($rule['name'])) {
                    continue;
                }
                if (!isset($rule['type'])) {
                    $rule['type'] = 'utility';
                }
                $map[$rule['name']] = $rule;
            }
            return $map;
        }

        public function findModules(string $body): array
        {
            $body = $this->stripIgnoredBlocks($body);
            $attributeBlob = $this->collectAttributeBlob($body);
            $pageModules = [];
            $loadedModules = [];
            if (empty($this->rules)) return [];

            foreach ($this->rules as $rule) {
                if (!isset($rule['regex']) || !is_string($rule['regex']) || $rule['regex'] === '') {
                    continue;
                }
                $matched = @preg_match($rule['regex'], $attributeBlob);
                if ($matched !== 1) {
                    continue;
                }

                $arr = $this->getFirstAndLastPart($rule['name'] ?? '', $this->delimeterUrl);
                if (!$arr) continue;
                if (!isset($rule['type'])) {
                    $rule['type'] = 'utility';
                }
                $rule['baseName'] = $arr['first'];
                $rule['fileName'] = $arr['first'] === $arr['last'] ? $arr['first'] : $arr['last'];
                if ($arr['first'] === $arr['last']) {
                    $pageModules[$arr['first']] = $rule;
                    $this->modules[$arr['first']] = $rule;
                } else {
                    $this->modules[$arr['first']][$arr['last']] = $rule;
                    $pageModules[$arr['first']][$arr['last']] = $rule;
                }
                $loadedModules[] = $rule['name'];
            }
            if (! $this->useModuleCache) {
                return [];
            }

            $hash = md5(serialize($pageModules));
            if (!isset($this->extPerHash[$hash])) {
                $this->extPerHash[$hash] = ['js' => false, 'css' => false];
            }
            $this->perPageModuleHash[$hash] = $pageModules;
            $entry = $this->manifest->get($hash);
            if ($entry) {
                $this->log("Modules cache hit [{$hash}]");
                $arOut = [];
                $jsOk = isset($entry['js']) ? (bool) $entry['js'] : false;
                $cssOk = isset($entry['css']) ? (bool) $entry['css'] : false;
                if ($jsOk) {
                    $arOut['js'] = $this->cachePath . '/' . $hash . '.js';
                }
                if ($cssOk) {
                    $arOut['css'] = $this->cachePath . '/' . $hash . '.css';
                }
                if (!empty($entry['modules'])) {
                    $arOut['modules'] = $this->getExtPerModule($entry['modules']);
                }
                return $arOut;
            }
            $this->log("Modules cache miss [{$hash}], loading assets...");
            $this->loadModules($pageModules, $hash);

            $this->extPerHash[$hash]['js'] = $this->extPerHash[$hash]['js'] ?? false;
            $this->extPerHash[$hash]['css'] = $this->extPerHash[$hash]['css'] ?? false;
            $this->manifest->put($hash, [
                'js' => (bool) $this->extPerHash[$hash]['js'],
                'css' => (bool) $this->extPerHash[$hash]['css'],
                'modules' => $loadedModules,
            ]);
            $this->log(sprintf(
                "Modules built [{$hash}] js:%s css:%s",
                $this->extPerHash[$hash]['js'] ? 'yes' : 'no',
                $this->extPerHash[$hash]['css'] ? 'yes' : 'no'
            ));
            $arOut = [];
            if ($this->extPerHash[$hash]['js']) {
                $arOut['js'] = $this->cachePath . '/' . $hash . '.js';
            }
            if ($this->extPerHash[$hash]['css']) {
                $arOut['css'] = $this->cachePath . '/' . $hash . '.css';
            }
            if (!empty($loadedModules)) {
                $arOut['modules'] = $this->getExtPerModule($loadedModules);
            }
            return $arOut;
        }

        private function getExtPerModule($modules): array
        {
            $arr = [];
            foreach ($modules as $module) {
                if (!isset($this->rules[$module])) {
                    continue;
                }
                $rule = $this->rules[$module];
                $isUtility = ($rule['type'] ?? '') === 'utility';

                // Utilities should never request JS; keep JS disabled even if defined.
                $arr[$module] = [
                    'js'  => $isUtility ? false : ($rule['js'] ?? false),
                    'css' => $rule['css'] ?? ($isUtility ? true : false),
                ];
            }
            return $arr;
        }


        private function isRule(array $rule): bool
        {
            return isset($rule['name']);
        }


        private function loadModules(array $modules, string $hash): void
        {
            $flat = $this->flattenModules($modules);
            $ordered = $this->sortModulesForLoading($flat);

            foreach ($ordered as $item) {
                $this->loadPluginByRule($item['key'], $item['rule'], $hash);
            }
        }

        private function loadPluginByRule(string $key, array $rule, string $hash): void
        {
            $exts = $this->getRuleExtsToLoad($rule);

            foreach ($exts as $ext) {
                $this->extPerHash[$hash][$ext] = true;
                $loadedKey = $key . ':' . $ext;
                if (isset($this->loadedPlugins[$loadedKey])) {
                    $this->log("Using cached {$ext} for {$key} ({$rule['type']})");
                } else {
                    $this->log("Loading {$ext} for {$key} ({$rule['type']})");
                }
                $this->loadAndAppendToCache($key, $rule, $hash, $ext);

            }
        }

        /**
         * Возвращает список расширений, которые нужно загрузить по правилу.
         */
        private function getRuleExtsToLoad(array $rule): array
        {
            $type = $rule['type'] ?? null;

            // utility: у тебя раньше это всегда было css
            if ($type === 'utility') {
                return ['css'];
            }

            // component (и прочие не-utility): учитываем флаги css/js
            $exts = [];

            if (!empty($rule['css'])) {
                $exts[] = 'css';
            }

            if (!empty($rule['js'])) {
                $exts[] = 'js';
            }

            return $exts;
        }

        /**
         * Загружает конкретный ресурс (css/js) и записывает/дописывает в кеш-файл.
         */
        private function loadAndAppendToCache(string $key, array $rule, string $hash, string $ext): void
        {
            $loadedKey = $key . ':' . $ext;
            $hashPath = $this->cachePath . '/' . $hash . '.' . $ext;
            if (isset($this->loadedPlugins[$loadedKey])) {
                file_put_contents($hashPath, $this->loadedPlugins[$key . ':' . $ext], FILE_APPEND);
            } else {
                $url = $this->cdnUrl . '/' . $rule['type'] . '/' . $key . '/' . $ext . '/' . $rule['fileName'] . '.' . $ext;

                $context = stream_context_create([
                    'http' => [
                        'timeout' => 5,
                    ],
                ]);

                $newContent = @file_get_contents($url, false, $context);
                if ($newContent === false) {
                    return;
                }

                // гарантируем директорию
                $dir = dirname($hashPath);
                if (!is_dir($dir)) {
                    @mkdir($dir, 0777, true);
                }


                file_put_contents($hashPath, $newContent, FILE_APPEND);
                $this->loadedPlugins[$loadedKey] = $newContent;
            }
        }

        public function getRuleById(string $id): ?array
        {
            foreach ($this->rules as $rule) {
                if (!is_array($rule)) {
                    continue;
                }
                if (($rule['id'] ?? null) === $id || ($rule['name'] ?? null) === $id) {
                    return $rule;
                }
            }

            return null;
        }

        private function fetchRemote(): ?array
        {
            try {
                $context = stream_context_create([
                    'http' => [
                        'timeout' => 5,
                    ],
                ]);
                $json = @file_get_contents($this->url, false, $context);
                if ($json === false) {
                    return null;
                }
                $data = json_decode($json, true);

                return is_array($data) ? $data : null;
            } catch (\Throwable) {
                return null;
            }
        }

        private function loadCache(): ?array
        {
            if (!is_file($this->cachePath . '/rules.json')) {
                return null;
            }
            try {
                $raw = file_get_contents($this->cachePath . '/rules.json');
                if ($raw === false) {
                    return null;
                }
                $decoded = json_decode($raw, true);
                if (!is_array($decoded)) {
                    return null;
                }
                $rules = $decoded['rules'] ?? null;
                $ts = $decoded['ts'] ?? null;
                if (!is_array($rules) || !is_int($ts)) {
                    return null;
                }

                return ['rules' => $rules, 'ts' => $ts];
            } catch (\Throwable) {
                return null;
            }
        }

        private function saveCache(array $rules): void
        {
            $payload = [
                'ts' => time(),
                'rules' => $rules,
            ];

            $dir = dirname($this->cachePath . '/rules.json');
            if (!is_dir($dir)) {
                @mkdir($dir, 0777, true);
            }

            file_put_contents(
                $this->cachePath . '/rules.json',
                json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            );
        }

        private function isExpired(int $ts): bool
        {
            return (time() - $ts) > $this->ttlSeconds;
        }

        public function setConsole(ConsoleOutput $console): void
        {
            $this->console = $console;
        }

        public function setUseModuleCache(bool $value): void
        {
            $this->useModuleCache = $value;
        }

        private function log(string $message): void
        {
            if ($this->console) {
                $this->console->writeln('<comment>' . $message . '</comment>');
            }
        }

        private function buildModuleResponse(string $hash, array $loadedModules): array
        {
            $this->extPerHash[$hash]['js'] = $this->extPerHash[$hash]['js'] ?? false;
            $this->extPerHash[$hash]['css'] = $this->extPerHash[$hash]['css'] ?? false;

            $arOut = [];
            if ($this->extPerHash[$hash]['js']) {
                $arOut['js'] = $this->cachePath . '/' . $hash . '.js';
            }
            if ($this->extPerHash[$hash]['css']) {
                $arOut['css'] = $this->cachePath . '/' . $hash . '.css';
            }
            if (!empty($loadedModules)) {
                $arOut['modules'] = $this->getExtPerModule($loadedModules);
            }

            return $arOut;
        }

        private function stripIgnoredBlocks(string $html): string
        {
            $patterns = [
                '~<code\\b[^>]*>.*?</code>~is',
                '~<pre\\b[^>]*>.*?</pre>~is',
                '~<(div|section)\\b[^>]*class=["\\\'][^"\\\'>]*monaco[^"\\\'>]*["\\\'][^>]*>.*?</\\1>~is',
            ];

            return preg_replace($patterns, '', $html);
        }

        private function collectAttributeBlob(string $html): string
        {
            if (trim($html) === '') {
                return '';
            }

            $doc = new \DOMDocument();
            $prev = libxml_use_internal_errors(true);
            $loaded = $doc->loadHTML(
                '<?xml encoding="UTF-8">' . $html,
                LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
            );
            libxml_clear_errors();
            libxml_use_internal_errors($prev);
            if (! $loaded || ! $doc->documentElement) {
                return $html;
            }

            $attributes = [];
            $xpath = new \DOMXPath($doc);
            foreach ($xpath->query('//@*') as $attr) {
                /** @var \DOMAttr $attr */
                $attributes[] = $attr->nodeName . '="' . $attr->nodeValue . '"';
            }

            return implode(' ', $attributes);
        }

        private function flattenModules(array $modules): array
        {
            $list = [];
            foreach ($modules as $first => $value) {
                if (is_array($value) && $this->isRule($value)) {
                    $list[] = ['key' => $first, 'rule' => $value];
                    continue;
                }

                if (is_array($value)) {
                    foreach ($value as $last => $rule) {
                        if (is_array($rule) && $this->isRule($rule)) {
                            $list[] = ['key' => $first . '/' . $last, 'rule' => $rule];
                        }
                    }
                }
            }

            return $list;
        }

        private function sortModulesForLoading(array $modules): array
        {
            $utility = [];
            $nonUtility = [];

            foreach ($modules as $item) {
                $type = $item['rule']['type'] ?? 'utility';
                if ($type === 'utility') {
                    $utility[] = $item;
                } else {
                    $nonUtility[] = $item;
                }
            }

            usort($nonUtility, fn ($a, $b) => strcmp($a['key'], $b['key']));

            usort($utility, function ($a, $b) {
                $bpA = $this->breakpointWeight($a['rule']['name'] ?? $a['key'], $a['rule']['fileName'] ?? null);
                $bpB = $this->breakpointWeight($b['rule']['name'] ?? $b['key'], $b['rule']['fileName'] ?? null);
                if ($bpA !== $bpB) {
                    return $bpA <=> $bpB;
                }

                return strcmp($a['key'], $b['key']);
            });


            return array_merge($nonUtility, $utility);
        }

        /**
         * Breakpoint ordering for utility classes: default < sm < md < lg < xl < 2xl.
         * Higher weight means later insertion.
         */
        private function breakpointWeight(string $name, ?string $fileName = null): int
        {
            $weights = [
                'default' => 0,
                'base' => 0,
                'sm' => 10,
                'md' => 20,
                'lg' => 30,
                'xl' => 40,
                '2xl' => 50,
            ];

            $lower = strtolower($name);
            $prefix = 'default';
            if ($fileName && isset($weights[strtolower($fileName)])) {
                $prefix = strtolower($fileName);
            } else {
                foreach (['sm', 'md', 'lg', 'xl', '2xl'] as $bp) {
                    $match = str_contains($lower, $bp . ':') || str_contains($lower, $bp . '/') || str_contains($lower, $bp . '\\:');
                    if ($match) {
                        $prefix = $bp;
                        break;
                    }
                }
            }

            return $weights[$prefix] ?? $weights['default'];
        }
    }
