<?php

declare(strict_types=1);

namespace Simai\Docara\PortableSite;

use Simai\Docara\File\Filesystem;
use Simai\Docara\I18n\LocaleRegistry;
use Simai\Docara\I18n\LocaleRoutingPolicy;
use Simai\Docara\I18n\LocaleUrlProjector;
use Simai\Docara\Portable\CanonicalJson;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\Portable\SchemaRepository;

final readonly class PortableLocaleRoutePublisher
{
    public function __construct(private Filesystem $files) {}

    /**
     * @param  list<array<string, mixed>>  $pages
     * @param  array<string, string>  $copy
     * @return array{receipt:array<string,mixed>,pages:list<array{output:string,bytes:string}>}
     */
    public function plan(
        array $pages,
        LocaleRegistry $locales,
        LocaleUrlProjector $urls,
        string $documentationVersion,
        array $copy,
    ): array {
        $policy = $urls->policy;
        $default = $locales->default();
        $pageOutputs = [];
        foreach ($pages as $page) {
            if (is_string($page['output'] ?? null)) {
                $pageOutputs[(string) $page['output']] = true;
            }
        }

        $records = [];
        if ($policy->root === LocaleRoutingPolicy::ROOT_REDIRECT) {
            $records[] = [
                'kind' => 'root',
                'from' => '',
                'to' => $default->publicPrefix,
                'url' => $urls->rootUrl(),
                'target_url' => $urls->defaultLocaleUrl(),
                'output' => 'index.html',
            ];
        }

        if ($policy->legacyUnprefixedRedirects) {
            $prefix = trim($default->publicPrefix, '/') . '/';
            foreach ($pages as $page) {
                if (($page['locale'] ?? null) !== $default->tag->value()) {
                    continue;
                }
                $output = (string) ($page['output'] ?? '');
                if (! str_starts_with($output, $prefix) || ! str_ends_with($output, '/index.html')) {
                    continue;
                }
                $legacyOutput = substr($output, strlen($prefix));
                if ($legacyOutput === 'index.html') {
                    continue;
                }
                if (isset($pageOutputs[$legacyOutput])) {
                    throw new PortableConfigurationException(
                        'LEGACY_LOCALE_REDIRECT_COLLISION',
                        "Legacy locale redirect output [$legacyOutput] collides with a generated page.",
                    );
                }
                $from = substr($legacyOutput, 0, -strlen('/index.html'));
                $to = substr($output, 0, -strlen('/index.html'));
                $records[] = [
                    'kind' => 'legacy_unprefixed',
                    'from' => $from,
                    'to' => $to,
                    'url' => $urls->deploymentBase() . $from . '/',
                    'target_url' => (string) $page['url'],
                    'output' => $legacyOutput,
                ];
            }
        }

        usort($records, static fn (array $left, array $right): int => [$left['output'], $left['to']] <=> [$right['output'], $right['to']]);
        $seen = [];
        foreach ($records as $record) {
            if (isset($seen[$record['output']])) {
                throw new PortableConfigurationException(
                    'LOCALE_REDIRECT_OUTPUT_DUPLICATE',
                    "Locale redirect output [{$record['output']}] is duplicated.",
                );
            }
            $seen[$record['output']] = true;
        }

        $receipt = [
            'schema' => 'docara.locale_route_receipt.v1',
            'version' => 1,
            'routing' => $policy->toArray(),
            'content_sha256' => hash('sha256', CanonicalJson::encode($records)),
            'redirects' => $records,
        ];
        (new SchemaRepository)->assertValid($receipt, 'locale-route-receipt.schema.json');

        return [
            'receipt' => $receipt,
            'pages' => array_map(
                static fn (array $record): array => [
                    'output' => $record['output'],
                    'bytes' => PortableRedirectPublisher::renderPage(
                        $record,
                        $default->tag->value(),
                        $documentationVersion,
                        $copy,
                        $default->direction,
                    ),
                ],
                $records,
            ),
        ];
    }

    /** @param array{receipt:array<string,mixed>,pages:list<array{output:string,bytes:string}>} $plan */
    public function publish(array $plan, string $destination): void
    {
        foreach ($plan['pages'] as $page) {
            $target = rtrim($destination, '/\\') . '/' . $page['output'];
            $this->files->ensureDirectoryExists(dirname($target));
            if ($this->files->put($target, $page['bytes']) === false) {
                throw new PortableConfigurationException(
                    'LOCALE_REDIRECT_PUBLICATION_FAILED',
                    "Locale redirect output [{$page['output']}] could not be published.",
                );
            }
        }
        $receiptPath = rtrim($destination, '/\\') . '/.docara/locale-routes.json';
        $this->files->ensureDirectoryExists(dirname($receiptPath));
        $bytes = CanonicalJson::encodePretty($plan['receipt']);
        if ($this->files->put($receiptPath, $bytes) === false) {
            throw new PortableConfigurationException(
                'LOCALE_REDIRECT_RECEIPT_PUBLICATION_FAILED',
                'The locale route receipt could not be published.',
            );
        }
    }
}
