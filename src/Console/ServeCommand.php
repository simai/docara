<?php

namespace Simai\Docara\Console;

use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class ServeCommand extends Command
{
    private $app;

    public function __construct(Container $app)
    {
        $this->app = $app;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('serve')
            ->setDescription('Serve local site with php built-in server.')
            ->addArgument(
                'environment',
                InputArgument::OPTIONAL,
                'What environment should we serve?',
                'local',
            )
            ->addOption(
                'host',
                null,
                InputOption::VALUE_OPTIONAL,
                'What hostname or ip address should we use?',
                'localhost',
            )
            ->addOption(
                'port',
                'p',
                InputOption::VALUE_REQUIRED,
                'What port should we use?',
                8000,
            )
            ->addOption(
                'no-build',
                null,
                InputOption::VALUE_NONE,
                'Skip build before serving?',
            );
    }

    protected function fire()
    {
        $env = $this->app['env'] = $this->input->getArgument('environment');
        $host = $this->input->getOption('host');
        $port = $this->input->getOption('port');

        if (! $this->input->getOption('no-build')) {
            $buildCmd = $this->getApplication()->find('build');
            $buildArgs = new ArrayInput([
                'env' => $env,
                '--quiet' => $this->input->getOption('quiet'),
                '--verbose' => $this->input->getOption('verbose'),
            ]);
            $buildStatus = $buildCmd->run($buildArgs, $this->output);
            if ($buildStatus !== static::SUCCESS) {
                return $buildStatus;
            }
        }

        $address = $this->serverAddress($host, $port);
        $buildPath = $this->getBuildPath($env);

        $this->console->info("Server started on http://{$address}");

        return $this->runPreviewServer($address, $buildPath);
    }

    protected function runPreviewServer(string $address, string $buildPath): int
    {
        $status = static::FAILURE;
        passthru($this->buildPreviewServerCommand($address, $buildPath), $status);

        return $status;
    }

    protected function buildPreviewServerCommand(string $address, string $buildPath): string
    {
        $arguments = [
            PHP_BINARY,
            '-S',
            $address,
            '-t',
            $buildPath,
            $this->previewRouterPath(),
        ];

        return implode(' ', array_map('escapeshellarg', $arguments));
    }

    private function serverAddress(mixed $host, mixed $port): string
    {
        if (
            ! is_string($host)
            || preg_match(
                '/\A(?:localhost|[A-Za-z0-9](?:[A-Za-z0-9.-]*[A-Za-z0-9])?|\[[0-9A-Fa-f:.]+\])\z/D',
                $host,
            ) !== 1
        ) {
            throw new InvalidArgumentException('The preview host contains unsupported characters.');
        }

        $port = is_int($port) ? (string) $port : $port;
        if (
            ! is_string($port)
            || preg_match('/\A[1-9][0-9]{0,4}\z/D', $port) !== 1
            || (int) $port > 65535
        ) {
            throw new InvalidArgumentException('The preview port must be an integer between 1 and 65535.');
        }

        return "{$host}:{$port}";
    }

    private function previewRouterPath(): string
    {
        $path = dirname(__DIR__, 2) . '/resources/portable/static-router.php';
        if (! is_file($path)) {
            throw new RuntimeException("The Docara preview router is missing: {$path}");
        }

        return $path;
    }

    private function getBuildPath($env)
    {
        $environmentConfigPath = $this->getAbsolutePath("config.{$env}.php");
        $environmentConfig = file_exists($environmentConfigPath) ? include $environmentConfigPath : [];

        $customBuildPath = Arr::get(
            $environmentConfig,
            'build.destination',
            Arr::get($this->app->config, 'build.destination'),
        );

        $buildPath = $customBuildPath ? $this->getAbsolutePath($customBuildPath) : $this->app->buildPath['destination'];

        return str_replace('{env}', $env, $buildPath);
    }

    private function getAbsolutePath($path)
    {
        return $this->app->cwd . '/' . trimPath($path);
    }
}
