<?php

namespace Simai\Docara\Console;

use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class Command extends SymfonyCommand
{
    protected InputInterface $input;

    protected OutputInterface $output;

    protected $console;

    private static bool $bannerShown = false;

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;
        $this->output = $output;
        $this->console = new ConsoleSession(
            $this->input,
            $this->output,
            $this->getHelper('question'),
        );

        $this->printBanner();

        return (int) $this->fire();
    }

    abstract protected function fire();

    protected function printBanner(): void
    {
        if (self::$bannerShown) {
            return;
        }

        $art = [
  '██████╗  ██████╗  ██████╗ █████╗ ██████╗  █████╗
██╔══██╗██╔═══██╗██╔════╝██╔══██╗██╔══██╗██╔══██╗
██║  ██║██║   ██║██║     ███████║██████╔╝███████║
██║  ██║██║   ██║██║     ██╔══██║██╔══██╗██╔══██║
██████╔╝╚██████╔╝╚██████╗██║  ██║██║  ██║██║  ██║
╚═════╝  ╚═════╝  ╚═════╝╚═╝  ╚═╝╚═╝  ╚═╝╚═╝  ╚═╝
                                                 '
        ];

        foreach ($art as $line) {
            $this->console->comment($line);
        }
        $this->console->line();
        self::$bannerShown = true;
    }
}
