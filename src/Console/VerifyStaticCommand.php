<?php

declare(strict_types=1);

namespace Simai\Docara\Console;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Process\Process;

final class VerifyStaticCommand extends Command
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
        $this->setName('verify-static')
            ->setDescription('Verify a static build and all pinned contracts.')
            ->addArgument('directory', InputArgument::OPTIONAL, 'Build directory.', 'build_production');
    }

    protected function fire(): int
    {
        $script = dirname(__DIR__, 2) . '/scripts/verify-static-build.php';
        if (! is_file($script)) {
            $this->console->error('The packaged static-build verifier is missing.');

            return self::FAILURE;
        }

        $process = new Process([PHP_BINARY, $script, (string) $this->input->getArgument('directory')], $this->base);
        $process->run(function (string $type, string $buffer): void {
            $target = $type === Process::ERR && $this->output instanceof ConsoleOutputInterface
                ? $this->output->getErrorOutput()
                : $this->output;
            $target->write($buffer);
        });

        return $process->isSuccessful() ? self::SUCCESS : self::FAILURE;
    }
}
