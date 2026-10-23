<?php

declare(strict_types=1);

namespace Simai\Docara\Quality;

use Closure;

/**
 * What the analyzer knows about the whole site while it checks one page:
 * which files the build contains and which ids they carry, which URLs the
 * menus and the search index show, which pages are drafts, which pages link
 * to which, and the glossary vocabulary of each locale.
 */
final class QualitySite
{
    /** @var array<string, array<string, true>> */
    private array $idCache = [];

    /**
     * @param  Closure(string):bool  $fileExists  build-relative path → exists
     * @param  Closure(string):array<string,true>  $idsOf  build-relative HTML file → its ids
     * @param  array<string, true>  $navigation  URLs any menu links to
     * @param  array<string, true>|null  $search  URLs in the search index; null when search is off
     * @param  array<string, true>  $drafts  URLs of draft pages
     * @param  array<string, int>  $inbound  URL → number of other pages that link to it from their text
     * @param  array<string, array<string, true>>  $glossaries  locale → lowercase words of its glossary page
     * @param  list<string>  $placeholders  template lines that must not stay in a page
     */
    public function __construct(
        public readonly StaticLinkResolver $links,
        private readonly Closure $fileExists,
        private readonly Closure $idsOf,
        public readonly array $navigation = [],
        public readonly ?array $search = null,
        public readonly array $drafts = [],
        public readonly array $inbound = [],
        public readonly array $glossaries = [],
        public readonly array $placeholders = [],
    ) {}

    public function exists(string $file): bool
    {
        return ($this->fileExists)($file);
    }

    /** The build file a resolved target names, or null: a directory means its index.html. */
    public function file(string $target): ?string
    {
        if ($target !== '' && $this->exists($target)) {
            return $target;
        }
        $index = ltrim(rtrim($target, '/') . '/index.html', '/');

        return $this->exists($index) ? $index : null;
    }

    /** @return array<string, true> */
    public function ids(string $file): array
    {
        return $this->idCache[$file] ??= ($this->idsOf)($file);
    }
}
