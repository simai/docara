<?php

declare(strict_types=1);

namespace Simai\Docara\PortableSite;

use Symfony\Component\Process\Process;

/**
 * The last Git commit of every page source, read in one `git log` pass.
 *
 * Page metadata, sitemap.xml `lastmod` and the page's structured data
 * (`dateModified`) all take the date from here, so one page never reports
 * two different dates. A source tree outside Git, a shallow clone without
 * the commit, or a file not yet committed simply has no revision.
 */
final class PageRevisions
{
    /** @var array<string, array{updated_at:string,revision:string,author:string}>|null */
    private ?array $revisions = null;

    public function __construct(private readonly string $root) {}

    /**
     * The last commit of a source path relative to the site root.
     *
     * @return array{updated_at:string,revision:string,author:string}|null
     */
    public function of(string $source): ?array
    {
        if ($source === '' || str_starts_with($source, '@')) {
            return null;
        }
        $this->revisions ??= $this->read();

        return $this->revisions[ltrim(str_replace('\\', '/', $source), '/')] ?? null;
    }

    /** @return array<string, array{updated_at:string,revision:string,author:string}> */
    private function read(): array
    {
        try {
            $process = new Process([
                'git', '-C', $this->root, '-c', 'core.quotePath=false', 'log',
                '--format=%x01%cI%x00%h%x00%an', '--name-only', '--no-renames', '--relative', '--', '.',
            ]);
            $process->setTimeout(60);
            $process->run();
        } catch (\Throwable) {
            // A portable source tree may intentionally be outside Git.
            return [];
        }
        if (! $process->isSuccessful()) {
            return [];
        }
        $revisions = [];
        foreach (explode("\x01", $process->getOutput()) as $entry) {
            $lines = preg_split('/\R/', trim($entry)) ?: [];
            $header = explode("\0", (string) array_shift($lines), 3);
            if (count($header) !== 3) {
                continue;
            }
            foreach ($lines as $path) {
                $path = trim($path);
                // git log lists the newest commit first: the first one wins.
                if ($path !== '' && ! isset($revisions[$path])) {
                    $revisions[$path] = ['updated_at' => $header[0], 'revision' => $header[1], 'author' => $header[2]];
                }
            }
        }

        return $revisions;
    }
}
