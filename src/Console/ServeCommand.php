<?php

declare(strict_types=1);

namespace Simai\Docara\Console;

use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class ServeCommand extends Command
{
    private string $base;

    public function __construct()
    {
        $this->setBase();
        parent::__construct();
    }

    public function setBase(?string $cwd = null): self
    {
        $this->base = $cwd ?: (getcwd() ?: '.');

        return $this;
    }

    protected function configure(): void
    {
        $this->setName('serve')
            ->setDescription('Build and serve the local static site.')
            ->addArgument('environment', InputArgument::OPTIONAL, 'Build output suffix.', 'local')
            ->addOption('host', null, InputOption::VALUE_OPTIONAL, 'Preview host.', 'localhost')
            ->addOption('port', 'p', InputOption::VALUE_REQUIRED, 'Preview port.', 8000)
            ->addOption('no-build', null, InputOption::VALUE_NONE, 'Serve an existing build without rebuilding.');
    }

    protected function fire(): int
    {
        $environment = (string) $this->input->getArgument('environment');
        if (preg_match('/\A[A-Za-z0-9][A-Za-z0-9_-]*\z/D', $environment) !== 1) {
            throw new InvalidArgumentException('The build environment contains unsupported characters.');
        }

        if (! $this->input->getOption('no-build')) {
            $build = $this->getApplication()?->find('build');
            if ($build === null) {
                throw new RuntimeException('The build command is unavailable.');
            }
            $status = $build->run(new ArrayInput([
                'environment' => $environment,
                '--quiet' => $this->input->getOption('quiet'),
                '--verbose' => $this->input->getOption('verbose'),
            ]), $this->output);
            if ($status !== self::SUCCESS) {
                return $status;
            }
        }

        $address = $this->serverAddress($this->input->getOption('host'), $this->input->getOption('port'));
        $buildPath = $this->base . '/build_' . $environment;
        if (! is_dir($buildPath)) {
            $this->console->error("Build directory does not exist: {$buildPath}");

            return self::FAILURE;
        }

        $this->console->info("Server started on http://{$address}");

        return $this->runPreviewServer($address, $buildPath);
    }

    protected function runPreviewServer(string $address, string $buildPath): int
    {
        $status = self::FAILURE;
        passthru($this->buildPreviewServerCommand($address, $buildPath), $status);

        return $status;
    }

    protected function buildPreviewServerCommand(string $address, string $buildPath): string
    {
        return implode(' ', array_map('escapeshellarg', [
            PHP_BINARY,
            '-S',
            $address,
            '-t',
            $buildPath,
            $this->previewRouterPath(),
        ]));
    }

    private function serverAddress(mixed $host, mixed $port): string
    {
        if (! is_string($host) || preg_match('/\A(?:localhost|[A-Za-z0-9](?:[A-Za-z0-9.-]*[A-Za-z0-9])?|\[[0-9A-Fa-f:.]+\])\z/D', $host) !== 1) {
            throw new InvalidArgumentException('The preview host contains unsupported characters.');
        }

        $port = is_int($port) ? (string) $port : $port;
        if (! is_string($port) || preg_match('/\A[1-9][0-9]{0,4}\z/D', $port) !== 1 || (int) $port > 65535) {
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
}
