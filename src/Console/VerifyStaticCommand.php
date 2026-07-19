<?php

declare(strict_types=1);

namespace Simai\Docara\Console;

use Illuminate\Contracts\Container\Container;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Process\Process;

final class VerifyStaticCommand extends Command
{
    public function __construct(private readonly Container $app)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('verify-static')
            ->setDescription('Verify a portable static build and all of its pinned contracts.')
            ->addArgument(
                'directory',
                InputArgument::OPTIONAL,
                'Build directory, relative to the project root or absolute.',
                'build_production',
            );
    }

    protected function fire(): int
    {
        $script = dirname(__DIR__, 2) . '/scripts/verify-static-build.php';
        if (! is_file($script)) {
            $this->console->error('The packaged static-build verifier is missing.');

            return self::FAILURE;
        }

        $directory = (string) $this->input->getArgument('directory');
        $process = new Process(
            [PHP_BINARY, $script, $directory],
            $this->app->path(),
        );
        $process->run(function (string $type, string $buffer): void {
            $target = $type === Process::ERR && $this->output instanceof ConsoleOutputInterface
                ? $this->output->getErrorOutput()
                : $this->output;
            $target->write($buffer);
        });

        return $process->getExitCode() === self::SUCCESS ? self::SUCCESS : self::FAILURE;
    }
}
