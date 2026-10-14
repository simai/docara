<?php

declare(strict_types=1);

namespace Simai\Docara\PortableSite;

use FilesystemIterator;
use InvalidArgumentException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use Simai\Docara\File\Filesystem;

final readonly class PortableProjectInitializer
{
    public const DEFAULT_TEMPLATE = 'minimal';

    public const DEFAULT_LANGUAGE = 'en';

    /**
     * The starter templates and the content languages each one is written in.
     *
     * minimal:  a home page and two pages (Markdown, an example, Framework components).
     * showcase: the component catalogue, a landing page and project Smart demos.
     *
     * @var array<string, list<string>>
     */
    public const TEMPLATES = [
        'minimal' => ['en', 'ru'],
        'showcase' => ['ru'],
    ];

    /**
     * Files every template takes from the showcase starter: the pinned
     * Framework lock, its generated contracts, the brand assets and the
     * redirect list.
     */
    private const SHARED = ['assets', 'contracts', 'redirects.json', 'simai-framework.lock.json'];

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

    /**
     * @return array{copied:int,preserved:int,engine_files:int,engine_sha256:string,template:string,language:string}
     */
    public function initialize(
        string $root,
        string $template = self::DEFAULT_TEMPLATE,
        string $language = self::DEFAULT_LANGUAGE,
    ): array {
        self::assertSupported($template, $language);
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
                $this->files->copy($path, $destination);
                $copied++;
            }
        }

        $engine = (new PortableProjectUpdater($this->files))->installEngineState($root);

        return [
            'copied' => $copied,
            'preserved' => $preserved,
            'engine_files' => (int) $engine['engine_files'],
            'engine_sha256' => (string) $engine['current_sha256'],
            'template' => $template,
            'language' => $language,
        ];
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
