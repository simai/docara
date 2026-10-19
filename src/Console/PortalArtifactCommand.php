<?php

declare(strict_types=1);

namespace Simai\Docara\Console;

use Simai\Docara\PortableSite\PortalArtifactWriter;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Throwable;

/**
 * Packs a portal build (docara.json `portal`) into the archive, checksum and
 * receipt a portal assembles. See PortalArtifactWriter.
 */
final class PortalArtifactCommand extends Command
{
    private string $base;

    public function __construct(private readonly PortalArtifactWriter $writer = new PortalArtifactWriter)
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
        $this->setName('portal-artifact')
            ->setDescription('Pack a portal build into a release archive, its sha256 and a receipt.')
            ->addArgument('environment', InputArgument::OPTIONAL, 'Build output suffix (build_<environment>).', 'production')
            ->addOption('release', null, InputOption::VALUE_REQUIRED, 'Release tag of the product, such as v3.4.0.')
            ->addOption('product-version', null, InputOption::VALUE_REQUIRED, 'Version in the file names (default: the release without a leading v).')
            ->addOption('repository', null, InputOption::VALUE_REQUIRED, 'Source repository as owner/name.')
            ->addOption('commit', null, InputOption::VALUE_REQUIRED, 'Full source commit (40 hex).')
            ->addOption('output', null, InputOption::VALUE_REQUIRED, 'Folder for the three files (default: build_<environment>.portal).');
    }

    protected function fire(): int
    {
        $environment = (string) $this->input->getArgument('environment');
        if (preg_match('/\A[A-Za-z0-9][A-Za-z0-9_-]*\z/D', $environment) !== 1) {
            $this->console->error('The build environment may contain only letters, digits, underscores and hyphens.');

            return self::FAILURE;
        }
        $release = (string) $this->input->getOption('release');
        $version = $this->input->getOption('product-version');
        $output = $this->input->getOption('output');
        try {
            $receipt = $this->writer->write(
                $this->base,
                $this->base . '/build_' . $environment,
                [
                    'version' => is_string($version) && $version !== '' ? $version : (string) preg_replace('/\Av(?=[0-9])/', '', $release),
                    'release' => $release,
                    'repository' => (string) $this->input->getOption('repository'),
                    'commit' => (string) $this->input->getOption('commit'),
                ],
                is_string($output) && $output !== ''
                    ? (str_starts_with($output, '/') ? $output : $this->base . '/' . $output)
                    : $this->base . '/build_' . $environment . '.portal',
            );
        } catch (Throwable $exception) {
            $this->console->error($exception->getMessage());

            return self::FAILURE;
        }
        $this->console->info(sprintf(
            'Packed %d file(s) for %s at %s into %s (sha256 %s, Framework pair %s).',
            $receipt['files'],
            $receipt['product'],
            $receipt['base_path'],
            $receipt['archive'],
            $receipt['sha256'],
            $receipt['framework_pair'],
        ));

        return self::SUCCESS;
    }
}
