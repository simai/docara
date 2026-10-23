<?php

declare(strict_types=1);

namespace Simai\Docara\Console;

use Simai\Docara\Application\ScaffoldService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

final class ScaffoldCommand extends ApplicationCommand
{
    public function __construct(private readonly ScaffoldService $service)
    {
        $this->setBase();
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('scaffold')->setDescription('Create a hash-bound Smart, design or page scaffold plan, then explicitly apply it. A page is created in one step unless --dry-run is given.')
            ->addArgument('kind', InputArgument::OPTIONAL, 'smart|design|page')
            ->addArgument('id', InputArgument::OPTIONAL, 'Artifact id or locale-relative page route. For a page it may be omitted: the route is made from the type and the title.')
            ->addOption('locale', null, InputOption::VALUE_REQUIRED, 'Configured locale for page scaffolding. Defaults to the site default locale.')
            ->addOption('title', null, InputOption::VALUE_REQUIRED, 'Page title for page scaffolding.')
            ->addOption('type', null, InputOption::VALUE_REQUIRED, 'Page type: landing|article|tutorial|how_to|reference|explanation. Same as --profile.')
            ->addOption('profile', null, InputOption::VALUE_REQUIRED, 'Built-in page authoring profile.')
            ->addOption('source', null, InputOption::VALUE_REQUIRED, 'Configured documentation source id.')
            ->addOption('entity', null, InputOption::VALUE_REQUIRED, 'Stable source entity key.')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Create and return a deterministic review plan.')
            ->addOption('apply', null, InputOption::VALUE_REQUIRED, 'Apply the exact plan SHA-256 after rechecking every input.')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Emit the stable operation result as JSON.');
    }

    protected function fire(): int
    {
        $apply = $this->input->getOption('apply');
        if (is_string($apply) && $apply !== '') {
            return $this->runOperation(fn () => $this->service->apply($this->base, $apply));
        }
        $kind = (string) $this->input->getArgument('kind');
        $type = $this->input->getOption('type');
        $profile = $this->input->getOption('profile');
        if (is_string($type) && is_string($profile) && $type !== $profile) {
            return $this->runOperation(static fn () => throw new \InvalidArgumentException('SCAFFOLD_PAGE_PROFILE_INVALID:Use either --type or --profile, not two different values.'));
        }
        $options = [
            'locale' => $this->input->getOption('locale'), 'title' => $this->input->getOption('title'), 'profile' => is_string($type) ? $type : $profile,
            'source' => $this->input->getOption('source'), 'entity' => $this->input->getOption('entity'),
        ];
        $plan = fn () => $this->service->plan($this->base, $kind, (string) $this->input->getArgument('id'), $options);
        if (! (bool) $this->input->getOption('dry-run')) {
            if ($kind !== 'page') {
                return $this->runOperation(static fn () => throw new \InvalidArgumentException('SCAFFOLD_DRY_RUN_REQUIRED:Review a dry-run before apply.'));
            }

            // A page scaffold only creates a new Markdown file and refuses an
            // existing one, so the command line plans and applies it in one
            // step. The plan file and every apply recheck stay the same.
            return $this->runOperation(fn () => $this->service->apply($this->base, (string) $plan()->data['plan_id']), 'scaffold.apply');
        }

        return $this->runOperation($plan);
    }
}
