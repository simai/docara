<?php

namespace Simai\Docara\Console;

use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Base of every Docara command. It keeps the input and output, prints the
 * Docara banner once per process unless --json asks for machine output, and
 * hands over to fire(), whose result is the exit code.
 */
abstract class Command extends SymfonyCommand
{
    private const BANNER = <<<'TEXT'
██████╗  ██████╗  ██████╗ █████╗ ██████╗  █████╗
██╔══██╗██╔═══██╗██╔════╝██╔══██╗██╔══██╗██╔══██╗
██║  ██║██║   ██║██║     ███████║██████╔╝███████║
██║  ██║██║   ██║██║     ██╔══██║██╔══██╗██╔══██║
██████╔╝╚██████╔╝╚██████╗██║  ██║██║  ██║██║  ██║
╚═════╝  ╚═════╝  ╚═════╝╚═╝  ╚═╝╚═╝  ╚═╝╚═╝  ╚═╝
                                                 
TEXT;

    private static bool $bannerPrinted = false;

    protected InputInterface $input;

    protected OutputInterface $output;

    protected ConsoleSession $console;

    abstract protected function fire();

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;
        $this->output = $output;
        $this->console = new ConsoleSession($output);

        $this->printBanner();

        return (int) $this->fire();
    }

    /**
     * Commands that must stay silent (for example an update writing machine
     * output) override this and call the parent when the banner may show.
     */
    protected function printBanner(): void
    {
        if (self::$bannerPrinted || $this->wantsJson()) {
            return;
        }

        $this->console->comment(self::BANNER)->line();
        self::$bannerPrinted = true;
    }

    private function wantsJson(): bool
    {
        return $this->input->hasOption('json') && (bool) $this->input->getOption('json');
    }
}
