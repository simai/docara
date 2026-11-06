<?php

declare(strict_types=1);

namespace Simai\Docara\PortableSite;

use Simai\Docara\Framework\FrameworkLock;
use Simai\Docara\Framework\FrameworkManifestRepository;
use Simai\Docara\Framework\FrameworkPackageReceipt;
use Simai\Docara\Portable\PortableConfigurationException;

/**
 * The facts a component set's footer line shows (block shell.set-passport),
 * read from the set's passport set.json and its package receipt in the
 * Framework lock. set.json is read through the lock, so its bytes match the
 * hash the receipt names.
 *
 * A set names no release number of its own yet; when its passport gains a
 * `version`, the line shows it, otherwise the package build's short revision.
 */
final readonly class SetPassportCatalogue
{
    public const PASSPORT_SCHEMA = 'simai.framework.component-set';

    /**
     * @param  array<string, array{title:string,version:?string,revision:string,framework:?string,licence:?string,repository:?string}>  $sets
     */
    private function __construct(private array $sets) {}

    /** @param array<string, mixed> $frameworkLock */
    public static function fromLock(array $frameworkLock): self
    {
        return self::fromRepository(FrameworkManifestRepository::bundled(FrameworkLock::fromArray($frameworkLock)));
    }

    public static function fromRepository(FrameworkManifestRepository $repository): self
    {
        $sets = [];
        foreach ($repository->packageProjections() as $package => $projection) {
            if (! isset($projection['files'][FrameworkPackageReceipt::SET_PASSPORT])) {
                continue;
            }
            $bytes = $repository->bundledPackageAsset((string) $package, FrameworkPackageReceipt::SET_PASSPORT);
            $sets[(string) $package] = self::facts(
                (string) $package,
                $bytes,
                is_array($projection['receipt'] ?? null) ? $projection['receipt'] : [],
            );
        }

        return new self($sets);
    }

    /**
     * @param  array<string, string>  $passports  package => set.json bytes
     * @param  array<string, array<string, mixed>>  $receipts  package => receipt
     */
    public static function fromPassports(array $passports, array $receipts): self
    {
        $sets = [];
        foreach ($passports as $package => $bytes) {
            $sets[$package] = self::facts($package, $bytes, $receipts[$package] ?? []);
        }

        return new self($sets);
    }

    /**
     * The localized line of every set, by package name, for one page.
     *
     * @param  array<string, string>  $copy  UiCopy build strings of the page's language
     * @return array<string, array{name:string,framework:?string,licence:?string,source:?array{href:string,text:string},history:string}>
     */
    public function localized(array $copy): array
    {
        $text = static fn (string $id, array $values): string => strtr(
            (string) ($copy[$id] ?? ''),
            array_combine(
                array_map(static fn (string $key): string => '{' . $key . '}', array_keys($values)),
                array_values($values),
            ),
        );
        $lines = [];
        foreach ($this->sets as $package => $set) {
            $framework = null;
            if ($set['framework'] !== null) {
                $framework = preg_match('/\A>=\s*(\d+\.\d+\.\d+)\z/D', $set['framework'], $match) === 1
                    ? $text('set.framework_from', ['version' => $match[1]])
                    : $text('set.framework', ['range' => $set['framework']]);
            }
            $lines[$package] = [
                'name' => $set['version'] !== null
                    ? $text('set.version', ['title' => $set['title'], 'version' => $set['version']])
                    : $text('set.build', ['title' => $set['title'], 'revision' => $set['revision']]),
                'framework' => $framework,
                'licence' => $set['licence'] === null ? null : $text('set.licence', ['licence' => $set['licence']]),
                'source' => $set['repository'] === null ? null : [
                    'href' => 'https://github.com/' . $set['repository'],
                    'text' => (string) ($copy['set.source'] ?? ''),
                ],
                'history' => (string) ($copy['set.history'] ?? ''),
            ];
        }
        ksort($lines, SORT_STRING);

        return $lines;
    }

    /**
     * @param  array<string, mixed>  $receipt
     * @return array{title:string,version:?string,revision:string,framework:?string,licence:?string,repository:?string}
     */
    private static function facts(string $package, string $bytes, array $receipt): array
    {
        try {
            $passport = json_decode($bytes, true, 64, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw self::invalid($package, $exception);
        }
        $title = is_array($passport) ? ($passport['title'] ?? null) : null;
        if (! is_array($passport)
            || ($passport['schema'] ?? null) !== self::PASSPORT_SCHEMA
            || ! is_string($title)
            || trim($title) === ''
            || mb_strlen($title) > 120
        ) {
            throw self::invalid($package);
        }
        $version = $passport['version'] ?? null;
        $revision = $receipt['package_source']['revision'] ?? null;
        $framework = $passport['framework']['core_runtime'] ?? null;
        $licence = $passport['licence']['spdx'] ?? ($receipt['licence']['ref'] ?? null);
        $repository = $passport['repository'] ?? ($receipt['package_source']['repository'] ?? null);

        return [
            'title' => trim($title),
            'version' => is_string($version) && preg_match('/\A\d+\.\d+\.\d+(?:-[0-9A-Za-z.-]+)?\z/D', $version) === 1
                ? $version
                : null,
            'revision' => is_string($revision) && preg_match('/\A[a-f0-9]{40}\z/D', $revision) === 1
                ? substr($revision, 0, 7)
                : throw self::invalid($package),
            'framework' => is_string($framework) && trim($framework) !== '' && mb_strlen($framework) <= 80
                ? trim($framework)
                : null,
            'licence' => is_string($licence) && trim($licence) !== '' && mb_strlen($licence) <= 200
                ? trim($licence)
                : null,
            'repository' => is_string($repository) && preg_match('~\A[a-z0-9-]+/[a-z0-9._-]+\z~D', $repository) === 1
                ? $repository
                : null,
        ];
    }

    private static function invalid(string $package, ?\Throwable $previous = null): PortableConfigurationException
    {
        return new PortableConfigurationException(
            'SET_PASSPORT_INVALID',
            "The passport set.json of component set [$package] is not a readable simai.framework.component-set with a title.",
            previous: $previous,
        );
    }
}
