<?php

declare(strict_types=1);

namespace Simai\Docara\Console;

use Simai\Docara\Application\LocaleAddService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

final class LocaleCommand extends ApplicationCommand
{
    public function __construct(private readonly LocaleAddService $service)
    {
        $this->setBase();
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('locale')->setDescription('Add a locale to the site: docara.json, its content folder with lang.json, and translation tracking. --default also makes it the default locale and keeps the old addresses as redirects.')
            ->addArgument('action', InputArgument::REQUIRED, 'add')
            ->addArgument('code', InputArgument::REQUIRED, 'BCP 47 language code, such as en, de or fr-CA.')
            ->addOption('default', null, InputOption::VALUE_NONE, 'Also make the locale default_locale; its content folder must already have index.md.')
            ->addOption('label', null, InputOption::VALUE_REQUIRED, 'Name in the language switch. Defaults to the language\'s own name, such as English or Deutsch.')
            ->addOption('direction', null, InputOption::VALUE_REQUIRED, 'ltr|rtl. Defaults to the usual direction of the language.')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show every change without writing anything.')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Emit the stable operation result as JSON.');
    }

    protected function fire(): int
    {
        $code = (string) $this->input->getArgument('code');
        $arguments = ['id' => $code];
        if ((string) $this->input->getArgument('action') !== 'add') {
            return $this->runOperation(static fn () => throw new \InvalidArgumentException('LOCALE_ACTION_INVALID:The locale command knows one action: add.'), 'locale', $arguments);
        }

        return $this->runOperation(fn () => $this->service->add($this->base, $code, [
            'default' => (bool) $this->input->getOption('default'),
            'label' => $this->input->getOption('label'),
            'direction' => $this->input->getOption('direction'),
            'dry_run' => (bool) $this->input->getOption('dry-run'),
        ]), 'locale.add', $arguments);
    }
}
