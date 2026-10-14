<?php

namespace Simai\Docara\Console;

use Symfony\Component\Console\Output\OutputInterface;

/**
 * Styled lines for Docara's commands: plain text, information, comments and
 * errors, each written as one line and chainable.
 */
final class ConsoleSession
{
    public function __construct(private readonly OutputInterface $output) {}

    public function write(string $text): self
    {
        $this->output->writeln($text);

        return $this;
    }

    public function info(string $text): self
    {
        return $this->styled('info', $text);
    }

    public function comment(string $text): self
    {
        return $this->styled('comment', $text);
    }

    public function error(string $text): self
    {
        return $this->styled('fg=red', $text);
    }

    public function line(): self
    {
        return $this->write('');
    }

    private function styled(string $style, string $text): self
    {
        return $this->write(sprintf('<%s>%s</>', $style, $text));
    }
}
