<?php

namespace Tests;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Simai\Docara\Console\BuildCommand;
use Simai\Docara\Console\ServeCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Process\Process;

class ServeCommandTest extends TestCase
{
    #[Test]
    public function serve_without_build_uses_current_php_binary_and_package_router(): void
    {
        $this->app->buildPath = [
            ...$this->app->buildPath,
            'destination' => $this->tmpPath('build_{env}'),
        ];

        $command = new CapturingServeCommand($this->app);
        $command->setApplication(new Application);

        $console = new CommandTester($command);
        $exitCode = $console->execute([
            'environment' => 'local',
            '--host' => '127.0.0.1',
            '--port' => '8123',
            '--no-build' => true,
        ]);

        $router = dirname(__DIR__) . '/resources/portable/static-router.php';
        $expected = implode(' ', array_map('escapeshellarg', [
            PHP_BINARY,
            '-S',
            '127.0.0.1:8123',
            '-t',
            $this->tmpPath('build_local'),
            $router,
        ]));

        $this->assertSame(0, $exitCode);
        $this->assertSame($expected, $command->capturedCommand);
        $this->assertStringContainsString('Server started on http://127.0.0.1:8123', $console->getDisplay());
    }

    #[Test]
    public function serve_rejects_shell_control_characters_in_host(): void
    {
        $command = new CapturingServeCommand($this->app);
        $command->setApplication(new Application);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('preview host');

        (new CommandTester($command))->execute([
            '--host' => '127.0.0.1;touch-pwned',
            '--no-build' => true,
        ]);
    }

    #[Test]
    public function serve_rejects_shell_control_characters_in_port(): void
    {
        $command = new CapturingServeCommand($this->app);
        $command->setApplication(new Application);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('preview port');

        (new CommandTester($command))->execute([
            '--port' => '8000;touch-pwned',
            '--no-build' => true,
        ]);
    }

    #[Test]
    public function serve_does_not_expose_a_stale_build_after_the_requested_build_fails(): void
    {
        $application = new Application;
        $application->addCommand(new FailingBuildCommand);
        $command = new CapturingServeCommand($this->app);
        $command->setApplication($application);

        $exitCode = (new CommandTester($command))->execute([
            'environment' => 'local',
            '--host' => '127.0.0.1',
            '--port' => '8123',
        ]);

        $this->assertSame(23, $exitCode);
        $this->assertSame('', $command->capturedCommand);
    }

    #[Test]
    public function serve_does_not_expose_a_stale_custom_build_after_overwrite_is_declined(): void
    {
        $destination = $this->tmpPath('public-output');
        $this->filesystem->ensureDirectoryExists($destination);
        $this->app->config->put('build.destination', $destination);

        $application = new Application;
        $application->addCommand(new BuildCommand($this->app));
        $command = new CapturingServeCommand($this->app);
        $command->setApplication($application);

        $exitCode = (new CommandTester($command))->execute([
            'environment' => 'local',
            '--host' => '127.0.0.1',
            '--port' => '8123',
        ]);

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertSame('', $command->capturedCommand);
    }

    #[Test]
    public function static_router_serves_pretty_and_exact_files_without_executing_php(): void
    {
        $sentinel = $this->tmpPath('php-was-executed');
        $phpPayload = sprintf(
            '<?php file_put_contents(%s, "executed"); echo "unsafe";',
            var_export($sentinel, true),
        );

        $this->createSource([
            'build' => [
                'components' => [
                    'catalog' => [
                        'docara.columns' => [
                            'index.html' => '<h1>Dotted route works</h1>',
                        ],
                    ],
                ],
                'assets' => [
                    'app.css' => 'body { color: rebeccapurple; }',
                ],
                'danger.php' => $phpPayload,
            ],
            'outside.txt' => 'must stay outside the document root',
        ]);

        $port = $this->availablePort();
        $server = new Process([
            PHP_BINARY,
            '-S',
            "127.0.0.1:{$port}",
            '-t',
            $this->tmpPath('build'),
            dirname(__DIR__) . '/resources/portable/static-router.php',
        ]);
        $server->setTimeout(null);
        $server->start();

        try {
            $this->waitForServer($port, $server);

            $pretty = $this->request($server, $port, '/components/catalog/docara.columns/');
            $exactIndex = $this->request($server, $port, '/components/catalog/docara.columns/index.html');
            $exact = $this->request($server, $port, '/assets/app.css');
            $missing = $this->request($server, $port, '/missing/');
            $traversal = $this->request($server, $port, '/%2e%2e/outside.txt');
            $php = $this->request($server, $port, '/danger.php');
            $write = $this->request($server, $port, '/assets/app.css', 'POST');

            $this->assertSame(200, $pretty['status']);
            $this->assertSame('<h1>Dotted route works</h1>', $pretty['body']);
            $this->assertSame(200, $exactIndex['status']);
            $this->assertSame('<h1>Dotted route works</h1>', $exactIndex['body']);
            $this->assertSame(200, $exact['status']);
            $this->assertSame('body { color: rebeccapurple; }', $exact['body']);
            $this->assertStringContainsString('text/css', $exact['headers']);
            $this->assertSame(404, $missing['status']);
            $this->assertSame(404, $traversal['status']);
            $this->assertStringNotContainsString('must stay outside', $traversal['body']);
            $this->assertSame(404, $php['status']);
            $this->assertFileDoesNotExist($sentinel);
            $this->assertSame(405, $write['status']);
        } finally {
            $server->stop(1);
        }
    }

    private function availablePort(): int
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0', $errorCode, $errorMessage);
        if ($socket === false) {
            $this->fail("Unable to reserve a preview port: {$errorCode} {$errorMessage}");
        }

        $address = stream_socket_get_name($socket, false);
        fclose($socket);

        return (int) substr(strrchr($address, ':'), 1);
    }

    private function waitForServer(int $port, Process $server): void
    {
        $deadline = microtime(true) + 5;

        do {
            if (! $server->isRunning()) {
                $this->fail('Preview server stopped during startup: ' . $server->getErrorOutput());
            }

            $socket = @stream_socket_client(
                "tcp://127.0.0.1:{$port}",
                $errorCode,
                $errorMessage,
                0.1,
            );
            if ($socket !== false) {
                fclose($socket);

                return;
            }

            usleep(25_000);
        } while (microtime(true) < $deadline);

        $this->fail("Preview server did not start: {$errorCode} {$errorMessage}");
    }

    /**
     * @return array{status: int, headers: string, body: string}
     */
    private function request(Process $server, int $port, string $target, string $method = 'GET'): array
    {
        $server->getIncrementalOutput();
        $server->getIncrementalErrorOutput();

        $socket = stream_socket_client(
            "tcp://127.0.0.1:{$port}",
            $errorCode,
            $errorMessage,
            2,
        );
        if ($socket === false) {
            $this->fail("Unable to connect to preview server: {$errorCode} {$errorMessage}");
        }

        stream_set_timeout($socket, 2);
        fwrite(
            $socket,
            "{$method} {$target} HTTP/1.1\r\nHost: 127.0.0.1\r\nConnection: close\r\n\r\n",
        );
        $response = stream_get_contents($socket);
        fclose($socket);

        $parts = explode("\r\n\r\n", $response, 2);
        $headers = $parts[0] ?? '';
        $body = $parts[1] ?? '';

        $this->assertMatchesRegularExpression('/\AHTTP\/1\.[01] [0-9]{3}/', $headers);
        preg_match('/\AHTTP\/1\.[01] ([0-9]{3})/', $headers, $matches);

        return [
            'status' => (int) $matches[1],
            'headers' => $headers,
            'body' => $body,
        ];
    }
}

class CapturingServeCommand extends ServeCommand
{
    public string $capturedCommand = '';

    protected function runPreviewServer(string $address, string $buildPath): int
    {
        $this->capturedCommand = $this->buildPreviewServerCommand($address, $buildPath);

        return static::SUCCESS;
    }
}

class FailingBuildCommand extends Command
{
    protected static $defaultName = 'build';

    protected function configure(): void
    {
        $this->setName('build')->addArgument('env', InputArgument::OPTIONAL);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return 23;
    }
}
