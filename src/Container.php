<?php

    namespace Simai\Docara;

    use Dotenv\Dotenv;
    use Dotenv\Exception\InvalidFileException;
    use Illuminate\Container\Container as Illuminate;
    use Illuminate\Support\Env;
    use Illuminate\Support\Str;
    use Symfony\Component\Console\Output\ConsoleOutput;
    use Simai\Docara\ShaResolver;

    class Container extends Illuminate
    {
        private string $path;

        private bool $bootstrapped = false;

        private bool $booted = false;

        private bool $skipConfigLoading = false;

        /** @var callable[] */
        private array $bootingCallbacks = [];

        /** @var callable[] */
        private array $bootedCallbacks = [];

        private array $providers = [];

        public function __construct()
        {
            $this->path = getcwd();

            static::setInstance($this);
            $this->instance('app', $this);

            $this->registerCoreProviders();
            $this->registerCoreAliases();
        }

        public function bootstrapWith(array $bootstrappers): void
        {
            $this->bootstrapped = true;

            $this->loadEnvironmentVariables();
            $this->loadConfiguration();
            $this->registerShaResolver();
            $this->registerRuleLoader();

            foreach ($bootstrappers as $bootstrapper) {
                $this->make($bootstrapper)->bootstrap($this);
            }

            $this->registerConfiguredProviders();
            $this->boot();
        }

        public function path(string ...$path): string
        {
            return implode('/', array_filter([$this->path, ...$path]));
        }

        public function cachePath(string ...$path): string
        {
            return $this->path('.cache', ...$path);
        }

        public function isBooted(): bool
        {
            return $this->booted;
        }

        public function booting(callable $callback): void
        {
            $this->bootingCallbacks[] = $callback;
        }

        public function booted(callable $callback): void
        {
            $this->bootedCallbacks[] = $callback;

            if ($this->isBooted()) {
                $callback($this);
            }
        }

        /**
         * Get or check the current site environment.
         * Equivalent to Laravel's `app()->environment()`.
         *
         * @param  string|array  ...$environments
         */
        public function environment(...$environments): bool|string
        {
            if (count($environments) > 0) {
                $patterns = is_array($environments[0]) ? $environments[0] : $environments;

                return Str::is($patterns, $this['env']);
            }

            return $this['env'];
        }

        private function loadEnvironmentVariables(): void
        {
            try {
                Dotenv::create(Env::getRepository(), $this->path)->safeLoad();
            } catch (InvalidFileException $e) {
                $output = (new ConsoleOutput)->getErrorOutput();

                $output->writeln('The environment file is invalid!');
                $output->writeln($e->getMessage());

                exit(1);
            }
        }

        private function loadConfiguration(): void
        {
            $config = collect();
            $this->instance('config', $config);
            if (! $this->skipConfigLoading) {
                $files = array_filter([
                    $this->path('config.php'),
                    $this->path('helpers.php'),
                ], 'file_exists');

                foreach ($files as $path) {
                    $config = $config->merge(require $path);
                }

                if ($collections = value($config->get('collections'))) {
                    $config->put('collections', collect($collections)->flatMap(
                        fn ($value, $key) => is_array($value) ? [$key => $value] : [$value => []],
                    ));
                }
            }

            $this->instance('buildPath', [
                'source' => $this->path('source'),
                'destination' => $this->path('build_{env}'),
            ]);
            $docsDir = trim($_ENV['DOCS_DIR'] ?? getenv('DOCS_DIR') ?? 'docs', '/\\') ?: 'docs';
            $config->put('docara.docsDir', $docsDir);
            $custom = $this->config;

            if ($custom instanceof \Illuminate\Support\Collection) {
                $custom = $custom->all();
            } elseif (is_object($custom)) {
                $custom = get_object_vars($custom);
            } elseif (!is_array($custom)) {
                $custom = (array) $custom;
            }
            foreach ($custom as $k => $v) {
                $config->put($k, $v);
            }
            $config->put('view.compiled', $this->cachePath());
            $custom = $this->config instanceof \Illuminate\Support\Collection
                ? $this->config->all()
                : (array) $this->config;

            $this->instance('config', $config->merge($custom));

            setlocale(LC_ALL, 'en_US.UTF8');
        }

        public function skipConfigLoading(bool $skip = true): void
        {
            $this->skipConfigLoading = $skip;
        }

        private function boot(): void
        {
            $this->fireAppCallbacks($this->bootingCallbacks);

            array_walk($this->providers, function ($provider) {
                if (method_exists($provider, 'boot')) {
                    $this->call([$provider, 'boot']);
                }
            });

            $this->booted = true;

            $this->fireAppCallbacks($this->bootedCallbacks);
        }

        /** @param callable[] $callbacks */
        private function fireAppCallbacks(array &$callbacks): void
        {
            $index = 0;

            while ($index < count($callbacks)) {
                $callbacks[$index]($this);

                $index++;
            }
        }

        private function registerCoreProviders(): void
        {
            foreach ([
                         Providers\EventServiceProvider::class,
                     ] as $provider) {
                ($provider = new $provider($this))->register();

                $this->providers[] = $provider;
            }
        }

        private function registerConfiguredProviders(): void
        {
            foreach ([
                         Providers\ExceptionServiceProvider::class,
                         Providers\FilesystemServiceProvider::class,
                         Providers\MarkdownServiceProvider::class,
                         Providers\ViewServiceProvider::class,
                         Providers\CollectionServiceProvider::class,
                         Providers\CompatibilityServiceProvider::class,
                         Providers\CacheServiceProvider::class,
                         Providers\BootstrapFileServiceProvider::class,
                         Providers\CustomTagServiceProvider::class,
                         Providers\DocaraEventsServiceProvider::class,
                         Providers\ConfiguratorServiceProvider::class,
                     ] as $provider) {
                ($provider = new $provider($this))->register();

                $this->providers[] = $provider;
            }

            if ($this->bound(RuleLoader::class) && $this->bound('consoleOutput')) {
                $this->make(RuleLoader::class)->setConsole($this['consoleOutput']);
            }
        }

        private function registerCoreAliases(): void
        {
            foreach ([
                         'app' => [self::class, \Illuminate\Contracts\Container\Container::class, \Psr\Container\ContainerInterface::class],
                         'view' => [\Illuminate\View\Factory::class, \Illuminate\Contracts\View\Factory::class],
                         'ruleLoader' => [RuleLoader::class],
                         'shaResolver' => [ShaResolver::class],
                     ] as $key => $aliases) {
                foreach ($aliases as $alias) {
                    $this->alias($key, $alias);
                }
            }
        }

        private function registerRuleLoader(): void
        {
            $sha = $this['config']->get('sha');
            $defaultUrl = 'https://cdn.jsdelivr.net/gh/simai/ui@' . ($sha ?: 'latest') . '/distr';
            $url = Env::get('RULE_JSON_URL', $defaultUrl);
            $cachePath = $this->cachePath();
            $ttl = (int) Env::get('RULE_JSON_TTL', 900);
            $useModuleCache = filter_var(
                Env::get('DOCARA_MODULE_CACHE', $this['config']->get('moduleCache', true)),
                FILTER_VALIDATE_BOOLEAN
            );

            $loader = new RuleLoader($url, $cachePath, $ttl, '/', $useModuleCache);
            $this->instance(RuleLoader::class, $loader);
            $this->instance('ruleLoader', $loader);
        }

        private function registerShaResolver(): void
        {
            $resolver = new ShaResolver($this);
            $resolver->resolve();

            $this->instance(ShaResolver::class, $resolver);
            $this->instance('shaResolver', $resolver);
        }
    }
