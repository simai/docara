<?php

declare(strict_types=1);

namespace Simai\Docara\PortableSite;

use FilesystemIterator;
use InvalidArgumentException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use Simai\Docara\Authoring\DocumentationStandard;
use Simai\Docara\File\Filesystem;

final readonly class PortableProjectInitializer
{
    public const DEFAULT_TEMPLATE = 'minimal';

    public const DEFAULT_LANGUAGE = 'en';

    /** The component set id the set template writes when none is given. */
    public const DEFAULT_SET = 'my-set';

    /**
     * The starter templates and the content languages each one is written in.
     *
     * minimal:  a home page and two pages (Markdown, an example, Framework components).
     * showcase: the component catalogue, a landing page and project Smart demos.
     * set:      the documentation site of a SIMAI Framework component set, in
     *           the five sections every set has: overview, connection,
     *           components, demos and changes.
     *
     * @var array<string, list<string>>
     */
    public const TEMPLATES = [
        'minimal' => ['en', 'ru'],
        'showcase' => ['ru'],
        'set' => ['en', 'ru'],
    ];

    /**
     * Files every template takes from the showcase starter: the pinned
     * Framework lock, its generated contracts, the brand assets and the
     * redirect list.
     */
    private const SHARED = ['assets', 'contracts', 'redirects.json', 'simai-framework.lock.json'];

    /**
     * The guide for AI assistants. Written at the project root unless a file
     * of that name already exists; then the fallback path is used, so a
     * user's own AGENTS.md is never overwritten.
     */
    public const AGENTS_FILE = 'AGENTS.md';

    public const AGENTS_FALLBACK = 'docs/AGENTS.docara.md';

    public function __construct(private Filesystem $files) {}

    /** Throws when the template is unknown or not written in the language. */
    public static function assertSupported(string $template, string $language): void
    {
        if (! isset(self::TEMPLATES[$template])) {
            throw new InvalidArgumentException(sprintf(
                'Unknown template "%s". Available templates: %s.',
                $template,
                implode(', ', array_keys(self::TEMPLATES)),
            ));
        }
        if (! in_array($language, self::TEMPLATES[$template], true)) {
            $alternatives = [];
            foreach (self::TEMPLATES as $name => $languages) {
                if (in_array($language, $languages, true)) {
                    $alternatives[] = "--template={$name} --lang={$language}";
                }
            }
            throw new InvalidArgumentException(sprintf(
                'The "%s" template is available only in: %s. Use --lang=%s%s.',
                $template,
                implode(', ', self::TEMPLATES[$template]),
                self::TEMPLATES[$template][0],
                $alternatives === [] ? '' : ', or ' . implode(' or ', $alternatives),
            ));
        }
    }

    /** Throws when the id is not a component set id (the id field of set.json). */
    public static function assertSetId(string $set): void
    {
        if (preg_match('/\A[a-z][a-z0-9-]{0,39}\z/D', $set) !== 1) {
            throw new InvalidArgumentException(sprintf(
                'The set id "%s" is not valid. Use the id from the set\'s set.json: lowercase Latin letters, digits and hyphens, starting with a letter, such as --set=view.',
                $set,
            ));
        }
    }

    /**
     * Every template also gets the documentation standard of its language
     * from stubs/standard: docara.authoring.json and one example page per
     * page type (getting started, how-to guides, reference, explanation),
     * except the set template, whose five sections are its own skeleton.
     * The set template writes the set id in place of {set} in its pages.
     *
     * @return array{copied:int,preserved:int,engine_files:int,engine_sha256:string,template:string,language:string,agents:?string,agents_existing:bool}
     */
    public function initialize(
        string $root,
        string $template = self::DEFAULT_TEMPLATE,
        string $language = self::DEFAULT_LANGUAGE,
        string $set = self::DEFAULT_SET,
    ): array {
        self::assertSupported($template, $language);
        self::assertSetId($set);
        $stubs = dirname(__DIR__, 2) . '/stubs';
        $showcase = $stubs . '/portable';
        if (! $this->files->isDirectory($showcase)) {
            throw new RuntimeException("Portable scaffold was not found at {$showcase}.");
        }

        $root = rtrim($root, '/\\');
        $sources = [];
        if ($template === 'showcase') {
            $sources[] = [$showcase, null];
        } else {
            $own = $stubs . '/' . $template . '/' . $language;
            if (! $this->files->isDirectory($own)) {
                throw new RuntimeException("Starter template was not found at {$own}.");
            }
            $sources[] = [$own, null];
            $sources[] = [$showcase, self::SHARED];
        }
        if ($template !== 'set') {
            $standard = $stubs . '/standard/' . $language;
            if (! $this->files->isDirectory($standard)) {
                throw new RuntimeException("Documentation standard starter was not found at {$standard}.");
            }
            $sources[] = [$standard, null];
        }
        $ownRoot = $stubs . '/' . $template . '/' . $language;

        $copied = 0;
        $preserved = 0;
        foreach ($sources as [$source, $only]) {
            foreach ($this->filesIn($source) as $relative => $path) {
                if ($only !== null && ! in_array(explode('/', $relative, 2)[0], $only, true)) {
                    continue;
                }
                $destination = $root . '/' . $relative;
                if ($this->files->exists($destination)) {
                    $preserved++;

                    continue;
                }

                $this->files->ensureDirectoryExists(dirname($destination));
                if ($template === 'set' && $source === $ownRoot && preg_match('/\.(?:md|json)\z/', $relative) === 1) {
                    $this->files->put($destination, str_replace('{set}', $set, (string) file_get_contents($path)));
                } else {
                    $this->files->copy($path, $destination);
                }
                $copied++;
            }
        }

        [$agents, $agentsExisting] = $this->writeAgentsGuide($root);
        $engine = (new PortableProjectUpdater($this->files))->installEngineState($root);

        return [
            'copied' => $copied,
            'preserved' => $preserved,
            'engine_files' => (int) $engine['engine_files'],
            'engine_sha256' => (string) $engine['current_sha256'],
            'template' => $template,
            'language' => $language,
            'agents' => $agents,
            'agents_existing' => $agentsExisting,
        ];
    }

    /**
     * Writes AGENTS.md, or docs/AGENTS.docara.md when the project already has
     * an AGENTS.md. Returns the written path (null when both exist) and
     * whether an AGENTS.md was already there.
     *
     * @return array{?string, bool}
     */
    private function writeAgentsGuide(string $root): array
    {
        $existing = file_exists($root . '/' . self::AGENTS_FILE) || is_link($root . '/' . self::AGENTS_FILE);
        $target = $existing ? self::AGENTS_FALLBACK : self::AGENTS_FILE;
        $absolute = $root . '/' . $target;
        if (file_exists($absolute) || is_link($absolute)) {
            return [null, $existing];
        }
        $this->files->ensureDirectoryExists(dirname($absolute));
        $this->files->put($absolute, (new DocumentationStandard)->agentsMarkdownForProject($root));

        return [$target, $existing];
    }

    /** @return array<string, string> relative path => absolute path, sorted */
    private function filesIn(string $directory): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
        );
        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }
            $relative = ltrim(str_replace('\\', '/', substr($file->getPathname(), strlen($directory))), '/');
            $files[$relative] = $file->getPathname();
        }
        ksort($files, SORT_STRING);

        return $files;
    }
}
