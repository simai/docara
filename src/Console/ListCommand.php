<?php

declare(strict_types=1);

namespace Simai\Docara\Console;

use Simai\Docara\Application\DiscoveryService;
use Symfony\Component\Console\Helper\DescriptorHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * `docara list <kind>` lists registered SDK subjects. The command takes the
 * name Symfony gives its own command list, which is also the default command,
 * so `docara` and `docara list` without a kind print the available commands.
 */
final class ListCommand extends ApplicationCommand
{
    public function __construct(private readonly DiscoveryService $service)
    {
        $this->setBase();
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('list')->setDescription('List registered SDK subjects, including physical Markdown pages; without a kind, list the commands.')
            ->addArgument('kind', InputArgument::OPTIONAL, 'page|source|smart|binding|layout|view|section|block|provider|fixture|state|schema')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Emit the stable operation result as JSON.');
    }

    protected function fire(): int
    {
        $kind = $this->input->getArgument('kind');
        if (! is_string($kind) || $kind === '') {
            $application = $this->getApplication();
            if ($application === null) {
                return self::FAILURE;
            }
            (new DescriptorHelper)->describe($this->output, $application, [
                'format' => (bool) $this->input->getOption('json') ? 'json' : 'txt',
            ]);

            return self::SUCCESS;
        }

        return $this->runOperation(fn () => $this->service->list($this->base, $kind));
    }
}
