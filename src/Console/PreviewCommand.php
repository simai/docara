<?php

declare(strict_types=1);

namespace Simai\Docara\Console;

use Simai\Docara\Portable\CanonicalJson;
use Simai\Docara\Preview\PreviewKernel;
use Simai\Docara\Preview\PreviewShell;
use Simai\Docara\Preview\PreviewTarget;
use Simai\Docara\Preview\PreviewWatcher;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Throwable;

final class PreviewCommand extends Command
{
    private string $base;

    public function __construct(
        private readonly PreviewKernel $kernel,
        private readonly PreviewShell $shell,
        private readonly PreviewWatcher $watcher = new PreviewWatcher,
    ) {
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
        $this->setName('preview')
            ->setDescription('Render a bounded production-path Smart, region, layout or page preview.')
            ->addArgument('target', InputArgument::REQUIRED, 'smart, region, layout or page')
            ->addOption('page', null, InputOption::VALUE_REQUIRED, 'Public route used as the production context.')
            ->addOption('selector', null, InputOption::VALUE_REQUIRED, 'Smart ID or region name for a focused target.')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Emit stable machine-readable JSON.')
            ->addOption('watch', null, InputOption::VALUE_NONE, 'Watch the target dependency closure using PHP only.')
            ->addOption('interval', null, InputOption::VALUE_REQUIRED, 'Watch interval in milliseconds.', '250')
            ->addOption('max-cycles', null, InputOption::VALUE_REQUIRED, 'Bound polling cycles; zero means until interrupted.', '0');
    }

    protected function fire(): int
    {
        try {
            $target = PreviewTarget::from((string) $this->input->getArgument('target'));
            $page = $this->input->getOption('page');
            if (! is_string($page) || trim($page) === '') {
                throw new \InvalidArgumentException('The --page option is required.');
            }
            $selector = $this->input->getOption('selector');
            $render = fn () => $this->kernel->render(
                $this->base,
                $page,
                $target,
                is_string($selector) && $selector !== '' ? $selector : null,
            );
            $artifact = $render();
            $result = $this->shell->publish($this->base, $artifact);
            if ((bool) $this->input->getOption('watch')) {
                $rebuilt = $this->watcher->run(
                    $this->base,
                    $artifact,
                    function () use ($render, &$result) {
                        $next = $render();
                        $result = $this->shell->publish($this->base, $next);

                        return $next;
                    },
                    (int) $this->input->getOption('interval'),
                    (int) $this->input->getOption('max-cycles'),
                );
                $result['watch'] = ['cycles_rebuilt' => count($rebuilt), 'target_only' => true];
            }
        } catch (Throwable $exception) {
            if ((bool) $this->input->getOption('json')) {
                $this->output->writeln(CanonicalJson::encode([
                    'schema' => 'docara.cli_error.v1',
                    'status' => 'error',
                    'exit_code' => self::FAILURE,
                    'message' => $exception->getMessage(),
                ]));
            } else {
                $this->console->error($exception->getMessage());
            }

            return self::FAILURE;
        }

        if ((bool) $this->input->getOption('json')) {
            $this->output->writeln(CanonicalJson::encode($result));
        } else {
            $this->console->info(sprintf('Previewed %s for %s (%s).', $target->value, $artifact->page, $artifact->sha256()));
            $this->console->comment('Output: ' . $result['output']);
            $this->console->comment('Production receipt: not accepted (isolated preview).');
        }

        return self::SUCCESS;
    }

    protected function printBanner(): void
    {
        if ($this->input->hasOption('json') && (bool) $this->input->getOption('json')) {
            return;
        }
        parent::printBanner();
    }
}
