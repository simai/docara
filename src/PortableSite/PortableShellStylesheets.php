<?php

declare(strict_types=1);

namespace Simai\Docara\PortableSite;

use Simai\Docara\Framework\FrameworkComponentException;
use Simai\Docara\Framework\FrameworkComponentRuntime;
use Simai\Docara\Framework\ShellStylesheetBundler;
use Simai\Docara\Framework\ShellStylesheetCascade;
use Simai\Docara\Portable\PortableConfigurationException;

/**
 * Turns the first-frame stylesheet segments of every page into the few
 * shared stylesheets the pages link (see {@see ShellStylesheetBundler}).
 *
 * Pages are rendered linking one URL per segment; once every page's closure
 * is known, this plans the bundles for the whole site, and the builder
 * publishes them and replaces each page's segment links with its bundle
 * links.
 */
final class PortableShellStylesheets
{
    public const SCHEMA = 'docara.shell_stylesheets.v1';

    /** @var array<string, array{universal: bool, global: array<string, true>, layers: array<string, array{self: array<string, true>, anc: array<string, true>}>}> */
    private array $signatures = [];

    private ShellStylesheetCascade $cascade;

    public function __construct(private readonly FrameworkComponentRuntime $runtime)
    {
        $this->cascade = new ShellStylesheetCascade($runtime->shellCascadeLayers());
    }

    /**
     * @param  array<string, list<string>>  $closures  page output => segment sha256s in cascade order, for the pages rendered now
     * @param  array<string, array<string, mixed>>  $segments  segment sha256 => generated shell asset with content
     * @param  array<string, mixed>|null  $existing  the stylesheet receipt of the complete build a single page is rebuilt into
     * @param  array<string, mixed>  $existingPlans  that build's page asset-plan receipts
     * @return array{receipt: array<string, mixed>, publish: list<array<string, mixed>>, remove: list<string>, links: array<string, list<string>>}
     */
    public function plan(array $closures, array $segments, ?array $existing = null, array $existingPlans = []): array
    {
        $bytes = [];
        foreach ($segments as $sha256 => $segment) {
            $bytes[$sha256] = strlen((string) $segment['content']);
        }
        $bundler = new ShellStylesheetBundler($this->conflicts($segments));
        if ($existing === null) {
            $result = $bundler->bundle($closures, $bytes);
            $assets = [];
            $pages = [];
            foreach ($result['pages'] as $output => $bundleKeys) {
                foreach ($bundleKeys as $bundleKey) {
                    $assets[$bundleKey] ??= $this->runtime->shellStylesheet(array_map(
                        static fn (string $sha256): array => $segments[$sha256],
                        $result['bundles'][$bundleKey],
                    ));
                    $pages[$output][] = $assets[$bundleKey]['filename'];
                }
            }
            $bundles = [];
            foreach ($assets as $asset) {
                $bundles[$asset['filename']] = $this->bundleReceipt($asset);
            }

            return $this->result($bundles, $pages, $result['request_weight'], array_values($assets), [], $closures);
        }

        $bundles = is_array($existing['bundles'] ?? null) ? $existing['bundles'] : [];
        $pages = is_array($existing['pages'] ?? null) ? $existing['pages'] : [];
        $byInputs = [];
        foreach ($bundles as $filename => $bundle) {
            $byInputs[$this->inputsKey($bundle['inputs'])] = (string) $filename;
        }
        $publish = [];
        foreach ($closures as $output => $closure) {
            $previous = array_map(
                static fn (array $asset): string => (string) ($asset['sha256'] ?? ''),
                is_array($existingPlans[$output]['generated_assets'] ?? null) ? $existingPlans[$output]['generated_assets'] : [],
            );
            if ($previous === $closure && is_array($pages[$output] ?? null)) {
                continue;
            }
            $existingBundles = array_map(
                static fn (array $bundle): array => array_column($bundle['inputs'], 'sha256'),
                array_values($bundles),
            );
            $links = [];
            foreach ($bundler->cover($closure, $existingBundles, $bytes) as $token) {
                $asset = $this->runtime->shellStylesheet(array_map(
                    static fn (string $sha256): array => $segments[$sha256],
                    $token,
                ));
                $key = $this->inputsKey($asset['inputs']);
                if (! isset($byInputs[$key])) {
                    $byInputs[$key] = $asset['filename'];
                    $bundles[$asset['filename']] = $this->bundleReceipt($asset);
                    $publish[] = $asset;
                }
                $links[] = $byInputs[$key];
            }
            $pages[$output] = $links;
        }
        $referenced = [];
        foreach ($pages as $links) {
            foreach ($links as $filename) {
                $referenced[$filename] = true;
            }
        }
        $remove = [];
        foreach (array_keys($bundles) as $filename) {
            if (! isset($referenced[$filename])) {
                $remove[] = (string) $filename;
                unset($bundles[$filename]);
            }
        }

        return $this->result(
            $bundles,
            $pages,
            is_int($existing['request_weight'] ?? null) ? $existing['request_weight'] : 0,
            $publish,
            $remove,
            $closures,
        );
    }

    /**
     * Replace a rendered page's segment links with its stylesheet links.
     *
     * @param  list<string>  $from  the segment URLs the page was rendered with
     * @param  list<string>  $to  the stylesheet URLs it links instead
     */
    public static function relink(string $html, array $from, array $to): string
    {
        $block = static fn (array $urls): string => implode('', array_map(
            static fn (string $url): string => '    <link rel="stylesheet" href="'
                . htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                . '" data-docara-declarative-shell-style>' . "\n",
            $urls,
        ));
        $search = $block($from);
        if ($from === [] || $to === [] || substr_count($html, $search) !== 1) {
            throw new PortableConfigurationException(
                'DECLARATIVE_SHELL_STYLESHEET_LINKS_INVALID',
                'A page does not hold exactly one block of its generated shell stylesheet links.',
            );
        }

        return str_replace($search, $block($to), $html);
    }

    /**
     * @param  array<string, array<string, mixed>>  $segments
     * @return \Closure(string, string): bool
     */
    private function conflicts(array $segments): \Closure
    {
        return function (string $a, string $b) use ($segments): bool {
            foreach ([$a, $b] as $sha256) {
                if (! isset($this->signatures[$sha256])) {
                    if (! isset($segments[$sha256])) {
                        throw new FrameworkComponentException('FRAMEWORK_SHELL_CSS_INVALID', $sha256);
                    }
                    $this->signatures[$sha256] = $this->cascade->signature((string) $segments[$sha256]['content']);
                }
            }

            return ShellStylesheetCascade::conflicts($this->signatures[$a], $this->signatures[$b]);
        };
    }

    /**
     * @param  array<string, array<string, mixed>>  $bundles
     * @param  array<string, list<string>>  $pages
     * @param  list<array<string, mixed>>  $publish
     * @param  list<string>  $remove
     * @param  array<string, list<string>>  $closures
     * @return array{receipt: array<string, mixed>, publish: list<array<string, mixed>>, remove: list<string>, links: array<string, list<string>>}
     */
    private function result(array $bundles, array $pages, int $requestWeight, array $publish, array $remove, array $closures): array
    {
        ksort($bundles, SORT_STRING);
        ksort($pages, SORT_STRING);
        $links = [];
        foreach (array_keys($closures) as $output) {
            $links[$output] = array_map(
                static function (string $filename) use ($bundles): string {
                    if (! isset($bundles[$filename]['url'])) {
                        throw new FrameworkComponentException('FRAMEWORK_SHELL_CSS_INVALID', $filename);
                    }

                    return (string) $bundles[$filename]['url'];
                },
                $pages[$output],
            );
        }

        return [
            'receipt' => [
                'schema' => self::SCHEMA,
                'target_requests' => ShellStylesheetBundler::TARGET_REQUESTS,
                'byte_budget_factor' => ShellStylesheetBundler::BYTE_BUDGET_FACTOR,
                'request_weight' => $requestWeight,
                'bundles' => $bundles,
                'pages' => $pages,
            ],
            'publish' => $publish,
            'remove' => $remove,
            'links' => $links,
        ];
    }

    /** @param array<string, mixed> $asset @return array<string, mixed> */
    private function bundleReceipt(array $asset): array
    {
        return [
            'url' => $asset['url'],
            'sha256' => $asset['sha256'],
            'inputs' => $asset['inputs'],
        ];
    }

    /** @param list<array<string, mixed>> $inputs */
    private function inputsKey(array $inputs): string
    {
        return implode(',', array_map(static fn (array $input): string => (string) $input['sha256'], $inputs));
    }
}
