#!/usr/bin/env php
<?php

declare(strict_types=1);

/** @param list<string> $expected */
function docaraExactKeys(array $value, array $expected): bool
{
    $keys = array_keys($value);
    sort($keys, SORT_STRING);
    sort($expected, SORT_STRING);

    return $keys === $expected;
}

function docaraCanonicalValue(mixed $value): mixed
{
    if (! is_array($value)) {
        return $value;
    }
    if (array_is_list($value)) {
        return array_map(docaraCanonicalValue(...), $value);
    }
    ksort($value, SORT_STRING);
    foreach ($value as $key => $item) {
        $value[$key] = docaraCanonicalValue($item);
    }

    return $value;
}

function docaraCanonicalJson(mixed $value): string
{
    return json_encode(
        docaraCanonicalValue($value),
        JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION,
    );
}

function docaraSearchUrlIsSafe(string $url, string $deploymentBase): bool
{
    return preg_match('#\A/(?:(?!\.{1,2}/)[A-Za-z0-9._~-]+/)*\z#D', $url) === 1
        && str_starts_with($url, $deploymentBase);
}

function docaraSafeRegularFile(string $path): bool
{
    $stat = @lstat($path);

    return ! is_link($path)
        && is_array($stat)
        && (($stat['mode'] ?? 0) & 0170000) === 0100000
        && ($stat['nlink'] ?? 1) === 1;
}

$rootInput = $argv[1] ?? '';
if ($rootInput === '' || count($argv) !== 2) {
    fwrite(STDERR, "Usage: php scripts/verify-static-build.php <build-directory>\n");

    exit(2);
}

$lexicalInput = str_replace('\\', '/', rtrim($rootInput, '/\\'));
foreach (explode('/', $lexicalInput) as $segment) {
    if ($segment === '.' || $segment === '..') {
        fwrite(STDERR, "Build directory is missing or unsafe.\n");

        exit(1);
    }
}
$ancestor = rtrim($rootInput, '/\\');
while ($ancestor !== '' && $ancestor !== '.' && $ancestor !== DIRECTORY_SEPARATOR) {
    if (is_link($ancestor)) {
        fwrite(STDERR, "Build directory is missing or unsafe.\n");

        exit(1);
    }
    $parent = dirname($ancestor);
    if ($parent === $ancestor) {
        break;
    }
    $ancestor = rtrim($parent, '/\\');
}

$inputStat = @lstat($rootInput);
if (is_link($rootInput)
    || ! is_array($inputStat)
    || (($inputStat['mode'] ?? 0) & 0170000) !== 0040000
) {
    fwrite(STDERR, "Build directory is missing or unsafe.\n");

    exit(1);
}

$root = realpath($rootInput);
if ($root === false || ! is_dir($root)) {
    fwrite(STDERR, "Build directory is missing or unsafe.\n");

    exit(1);
}

$htmlFiles = [];
$unsafeArtifactEntries = [];
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST,
);
foreach ($iterator as $file) {
    $path = $file->getPathname();
    $relativeEntry = str_replace('\\', '/', substr($path, strlen($root) + 1));
    if ($file->isLink() || is_link($path)) {
        $unsafeArtifactEntries[] = $relativeEntry;

        continue;
    }
    if ($file->isFile()) {
        $stat = @lstat($path);
        if (! is_array($stat)
            || (($stat['mode'] ?? 0) & 0170000) !== 0100000
            || ($stat['nlink'] ?? 1) > 1
        ) {
            $unsafeArtifactEntries[] = $relativeEntry;

            continue;
        }

        if (strtolower($file->getExtension()) === 'html') {
            $htmlFiles[] = $path;
        }

        continue;
    }
    if (! $file->isDir()) {
        $unsafeArtifactEntries[] = $relativeEntry;
    }
}
sort($htmlFiles, SORT_STRING);
sort($unsafeArtifactEntries, SORT_STRING);

$deploymentBase = '/';
$manifestDirectory = $root . '/.docara';
$manifestPath = $root . '/.docara/resolved-page-plans.json';
$manifestError = null;
$manifestOutputs = [];
$manifestPageRecords = [];
$searchEnabled = false;
$expectedSearchSurfaceCount = 0;
$expectedSearchDocuments = [];
$manifestDirectoryStat = @lstat($manifestDirectory);
if (is_link($manifestDirectory)
    || ! is_array($manifestDirectoryStat)
    || (($manifestDirectoryStat['mode'] ?? 0) & 0170000) !== 0040000
) {
    $manifestError = 'Resolved page-plan manifest is missing or unsafe.';
} else {
    $manifestStat = @lstat($manifestPath);
    if (is_link($manifestPath)
        || ! is_array($manifestStat)
        || (($manifestStat['mode'] ?? 0) & 0170000) !== 0100000
        || ($manifestStat['nlink'] ?? 1) > 1
    ) {
        $manifestError = 'Resolved page-plan manifest is missing or unsafe.';
    } else {
        try {
            $manifest = json_decode((string) file_get_contents($manifestPath), true, 512, JSON_THROW_ON_ERROR);
            if (! is_array($manifest) || ($manifest['schema'] ?? null) !== 'docara.resolved_page_plans.v1') {
                throw new RuntimeException('Resolved page-plan manifest has an unsupported schema.');
            }
            $pages = $manifest['pages'] ?? null;
            if (! is_array($pages) || ! array_is_list($pages) || $pages === []) {
                throw new RuntimeException('Resolved page-plan manifest must contain a non-empty page list.');
            }
            $bases = [];
            $outputs = [];
            foreach ($pages as $index => $page) {
                if (! is_array($page)) {
                    throw new RuntimeException("Resolved page-plan record [$index] must be an object.");
                }
                $base = $page['resolved_page_plan']['configuration']['base_url'] ?? null;
                if (! is_string($base) || $base === '') {
                    throw new RuntimeException("Resolved page-plan record [$index] is missing base_url.");
                }
                $bases[$base] = true;

                $output = $page['output'] ?? null;
                if (! is_string($output)
                    || preg_match('#\A(?:[A-Za-z0-9][A-Za-z0-9._~-]*/)*[A-Za-z0-9][A-Za-z0-9._~-]*\.html\z#', $output) !== 1
                ) {
                    throw new RuntimeException("Resolved page-plan record [$index] has an unsafe output.");
                }
                if (isset($outputs[$output])) {
                    throw new RuntimeException("Resolved page-plan output [$output] is duplicated.");
                }
                $outputs[$output] = true;
                $manifestPageRecords[] = $page;

                $outputPath = $root . '/' . $output;
                $outputStat = @lstat($outputPath);
                $realOutput = realpath($outputPath);
                if (is_link($outputPath)
                    || ! is_array($outputStat)
                    || (($outputStat['mode'] ?? 0) & 0170000) !== 0100000
                    || ($outputStat['nlink'] ?? 1) > 1
                    || $realOutput === false
                    || ($realOutput !== $root && ! str_starts_with($realOutput, $root . DIRECTORY_SEPARATOR))
                ) {
                    throw new RuntimeException("Resolved page-plan output [$output] is missing or unsafe.");
                }
            }
            $manifestOutputs = array_keys($outputs);
            sort($manifestOutputs, SORT_STRING);
            if (count($bases) !== 1) {
                throw new RuntimeException('Resolved plans must contain one deployment base.');
            }
            $configuredBase = (string) array_key_first($bases);
            if (preg_match('#\A(?:/|/[A-Za-z0-9._~-]+(?:/[A-Za-z0-9._~-]+)*/?)\z#', $configuredBase) !== 1) {
                throw new RuntimeException('Resolved deployment base is unsafe.');
            }
            foreach (explode('/', trim($configuredBase, '/')) as $segment) {
                if ($segment === '.' || $segment === '..') {
                    throw new RuntimeException('Resolved deployment base is unsafe.');
                }
            }
            $deploymentBase = $configuredBase === '/' ? '/' : '/' . trim($configuredBase, '/') . '/';

            foreach ($manifestPageRecords as $index => $page) {
                $search = $page['resolved_page_plan']['configuration']['search'] ?? null;
                if (is_array($search) && ($search['enabled'] ?? false) === true) {
                    $searchEnabled = true;
                    $expectedSearchSurfaceCount++;
                }
            }
            if ($searchEnabled) {
                foreach ($manifestPageRecords as $index => $page) {
                    $configuration = $page['resolved_page_plan']['configuration'] ?? null;
                    $search = is_array($configuration) ? ($configuration['search'] ?? null) : null;
                    if (! is_array($search)
                        || ! is_bool($search['enabled'] ?? null)
                        || ! is_bool($search['indexed'] ?? null)
                    ) {
                        throw new RuntimeException("Resolved page-plan record [$index] has an incomplete search contract.");
                    }
                    if ($search['indexed'] !== true) {
                        continue;
                    }
                    $url = $page['url'] ?? null;
                    $locale = $configuration['locale'] ?? null;
                    if (! is_string($url)
                        || ! docaraSearchUrlIsSafe($url, $deploymentBase)
                        || ! is_string($locale)
                        || preg_match('/\A[a-z]{2}(?:-[A-Z]{2})?\z/D', $locale) !== 1
                    ) {
                        throw new RuntimeException("Resolved page-plan record [$index] has unsafe search identity fields.");
                    }
                    $expectedSearchDocuments[] = $locale . "\0" . $url;
                }
                sort($expectedSearchDocuments, SORT_STRING);
                if ($expectedSearchDocuments === []) {
                    throw new RuntimeException('Search is enabled but the manifest has no indexed page.');
                }
            }
        } catch (Throwable $exception) {
            $manifestError = $exception->getMessage();
        }
    }
}

$checked = 0;
$searchIndexReferences = [];
$searchRuntimeReferences = [];
$broken = array_map(
    static fn (string $entry): array => [
        'page' => '@build',
        'reference' => '@unsafe-artifact-entry',
        'target' => $entry,
    ],
    $unsafeArtifactEntries,
);
if ($manifestError !== null) {
    $broken[] = ['page' => '@build', 'reference' => '@resolved-page-plans', 'target' => $manifestError];
} else {
    $actualHtmlOutputs = array_map(
        static fn (string $path): string => str_replace('\\', '/', substr($path, strlen($root) + 1)),
        $htmlFiles,
    );
    sort($actualHtmlOutputs, SORT_STRING);
    if ($actualHtmlOutputs !== $manifestOutputs) {
        $broken[] = [
            'page' => '@build',
            'reference' => '@resolved-page-plans',
            'target' => 'Resolved page-plan outputs do not exactly match generated HTML files.',
        ];
    }
}
foreach ($htmlFiles as $htmlFile) {
    $html = file_get_contents($htmlFile);
    if (! is_string($html)) {
        $broken[] = ['page' => $htmlFile, 'reference' => '@unreadable'];

        continue;
    }
    if (preg_match('/<\s*base\b/i', $html) === 1) {
        $broken[] = [
            'page' => str_replace('\\', '/', substr($htmlFile, strlen($root) + 1)),
            'reference' => '@html-base-element',
        ];
    }
    preg_match_all(
        '/\bdata-docara-search-index\s*=\s*(?:"([^"]*)"|\'([^\']*)\')/i',
        $html,
        $searchMatches,
        PREG_SET_ORDER,
    );
    foreach ($searchMatches as $match) {
        $searchIndexReferences[] = html_entity_decode(
            $match[1] !== '' ? $match[1] : $match[2],
            ENT_QUOTES | ENT_HTML5,
        );
    }
    preg_match_all(
        '/<script\b(?=[^>]*\bdata-docara-search-runtime\b)[^>]*\bsrc\s*=\s*(?:"([^"]*)"|\'([^\']*)\')[^>]*>/i',
        $html,
        $runtimeMatches,
        PREG_SET_ORDER,
    );
    foreach ($runtimeMatches as $match) {
        $searchRuntimeReferences[] = html_entity_decode(
            $match[1] !== '' ? $match[1] : $match[2],
            ENT_QUOTES | ENT_HTML5,
        );
    }
    preg_match_all('/\b(?:href|src|data-docara-search-index)\s*=\s*(?:"([^"]*)"|\'([^\']*)\')/i', $html, $matches, PREG_SET_ORDER);
    foreach ($matches as $match) {
        $reference = html_entity_decode($match[1] !== '' ? $match[1] : $match[2], ENT_QUOTES | ENT_HTML5);
        if ($reference === ''
            || str_starts_with($reference, '#')
            || str_starts_with($reference, '//')
            || preg_match('/\A[a-z][a-z0-9+.-]*:/i', $reference) === 1
        ) {
            continue;
        }

        $checked++;
        $encodedPath = preg_split('/[?#]/', $reference, 2)[0] ?? '';
        if (str_contains($encodedPath, '\\')
            || preg_match('/%(?![0-9A-Fa-f]{2})/', $encodedPath) === 1
        ) {
            $broken[] = [
                'page' => str_replace('\\', '/', substr($htmlFile, strlen($root) + 1)),
                'reference' => $reference,
                'target' => '@unsafe-percent-encoding',
            ];

            continue;
        }
        $decodedSegments = [];
        $unsafeDecodedSegment = false;
        foreach (explode('/', $encodedPath) as $encodedSegment) {
            $decodedSegment = rawurldecode($encodedSegment);
            if (str_contains($decodedSegment, '/')
                || str_contains($decodedSegment, '\\')
                || str_contains($decodedSegment, "\0")
                || preg_match('//u', $decodedSegment) !== 1
                || (($decodedSegment === '.' || $decodedSegment === '..') && $decodedSegment !== $encodedSegment)
            ) {
                $unsafeDecodedSegment = true;
                break;
            }
            $decodedSegments[] = $decodedSegment;
        }
        if ($unsafeDecodedSegment) {
            $broken[] = [
                'page' => str_replace('\\', '/', substr($htmlFile, strlen($root) + 1)),
                'reference' => $reference,
                'target' => '@unsafe-decoded-path-segment',
            ];

            continue;
        }
        $path = implode('/', $decodedSegments);
        $relativePage = str_replace('\\', '/', substr($htmlFile, strlen($root) + 1));
        if ($path === '') {
            $candidate = $relativePage;
        } elseif (str_starts_with($path, '/')) {
            $baseWithoutSlash = rtrim($deploymentBase, '/');
            if ($deploymentBase === '/') {
                $candidate = ltrim($path, '/');
            } elseif ($path === $baseWithoutSlash) {
                $candidate = '';
            } elseif (str_starts_with($path, $deploymentBase)) {
                $candidate = substr($path, strlen($deploymentBase));
            } else {
                $broken[] = [
                    'page' => $relativePage,
                    'reference' => $reference,
                    'target' => '@outside-deployment-base',
                ];

                continue;
            }
        } else {
            $candidate = dirname($relativePage) . '/' . $path;
        }
        $segments = [];
        foreach (explode('/', str_replace('\\', '/', $candidate)) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }
            if ($segment === '..') {
                if ($segments === []) {
                    $segments = ['@outside-root'];
                    break;
                }
                array_pop($segments);

                continue;
            }
            $segments[] = $segment;
        }
        $relativeTarget = implode('/', $segments);
        if ($relativeTarget === '' || str_ends_with($path, '/')) {
            $relativeTarget = rtrim($relativeTarget, '/') . '/index.html';
            $relativeTarget = ltrim($relativeTarget, '/');
        }
        $target = $root . '/' . $relativeTarget;
        if (is_dir($target) && is_file($target . '/index.html')) {
            $relativeTarget = rtrim($relativeTarget, '/') . '/index.html';
            $target = $root . '/' . $relativeTarget;
        }
        $realTarget = realpath($target);
        if ($realTarget === false
            || ! is_file($realTarget)
            || ($realTarget !== $root && ! str_starts_with($realTarget, $root . DIRECTORY_SEPARATOR))
        ) {
            $broken[] = [
                'page' => $relativePage,
                'reference' => $reference,
                'target' => $relativeTarget,
            ];
        }
    }
}

if ($manifestError === null) {
    $searchIndexPath = $root . '/_docara/search-index.json';
    $searchRuntimePath = $root . '/_docara/search.js';
    if (! $searchEnabled) {
        if (file_exists($searchIndexPath)
            || file_exists($searchRuntimePath)
            || $searchIndexReferences !== []
            || $searchRuntimeReferences !== []
        ) {
            $broken[] = [
                'page' => '@build',
                'reference' => '@search-artifacts-unexpected',
                'target' => 'Search artifacts or references exist while search is disabled.',
            ];
        }
    } elseif (! docaraSafeRegularFile($searchIndexPath) || ! docaraSafeRegularFile($searchRuntimePath)) {
        $broken[] = [
            'page' => '@build',
            'reference' => '@search-artifacts-missing',
            'target' => 'Enabled search requires safe search-index.json and search.js files.',
        ];
    } else {
        try {
            $searchIndex = json_decode(
                (string) file_get_contents($searchIndexPath),
                true,
                512,
                JSON_THROW_ON_ERROR,
            );
            if (! is_array($searchIndex)
                || array_is_list($searchIndex)
                || ! docaraExactKeys(
                    $searchIndex,
                    ['schema', 'version', 'algorithm', 'content_sha256', 'documents'],
                )
                || ($searchIndex['schema'] ?? null) !== 'docara.search_index.v1'
                || ($searchIndex['version'] ?? null) !== 1
                || ($searchIndex['algorithm'] ?? null) !== 'docara-prefix-v1'
                || ! is_string($searchIndex['content_sha256'] ?? null)
                || preg_match('/\A[a-f0-9]{64}\z/D', $searchIndex['content_sha256']) !== 1
                || ! is_array($searchIndex['documents'] ?? null)
                || ! array_is_list($searchIndex['documents'])
                || $searchIndex['documents'] === []
            ) {
                throw new RuntimeException('Search index root contract is invalid.');
            }

            $actualSearchDocuments = [];
            $documentIds = [];
            $previousIdentity = null;
            foreach ($searchIndex['documents'] as $index => $document) {
                if (! is_array($document)
                    || array_is_list($document)
                    || ! docaraExactKeys(
                        $document,
                        ['id', 'url', 'locale', 'title', 'description', 'trail', 'headings', 'text'],
                    )
                    || ! is_string($document['id'] ?? null)
                    || preg_match('/\A[a-f0-9]{64}\z/D', $document['id']) !== 1
                    || ! is_string($document['url'] ?? null)
                    || ! docaraSearchUrlIsSafe($document['url'], $deploymentBase)
                    || ! is_string($document['locale'] ?? null)
                    || preg_match('/\A[a-z]{2}(?:-[A-Z]{2})?\z/D', $document['locale']) !== 1
                    || ! is_string($document['title'] ?? null)
                    || $document['title'] === ''
                    || ! is_string($document['description'] ?? null)
                    || ! is_array($document['trail'] ?? null)
                    || ! array_is_list($document['trail'])
                    || ! is_array($document['headings'] ?? null)
                    || ! array_is_list($document['headings'])
                    || ! is_string($document['text'] ?? null)
                ) {
                    throw new RuntimeException("Search document [$index] has an invalid contract.");
                }
                foreach ($document['trail'] as $part) {
                    if (! is_string($part) || $part === '') {
                        throw new RuntimeException("Search document [$index] has an invalid trail.");
                    }
                }
                foreach ($document['headings'] as $headingIndex => $heading) {
                    if (! is_array($heading)
                        || array_is_list($heading)
                        || ! docaraExactKeys($heading, ['level', 'text'])
                        || ! is_int($heading['level'] ?? null)
                        || $heading['level'] < 1
                        || $heading['level'] > 6
                        || ! is_string($heading['text'] ?? null)
                        || $heading['text'] === ''
                    ) {
                        throw new RuntimeException(
                            "Search document [$index] heading [$headingIndex] has an invalid contract.",
                        );
                    }
                }

                $identity = $document['locale'] . "\0" . $document['url'];
                if ($previousIdentity !== null && strcmp($previousIdentity, $identity) >= 0) {
                    throw new RuntimeException('Search documents are not strictly sorted or contain duplicates.');
                }
                $previousIdentity = $identity;
                if (isset($documentIds[$document['id']])) {
                    throw new RuntimeException('Search document id is duplicated.');
                }
                $documentIds[$document['id']] = true;
                if ($document['id'] !== hash('sha256', $identity)) {
                    throw new RuntimeException("Search document [$index] id does not match locale and URL.");
                }
                $actualSearchDocuments[] = $identity;
            }

            if ($actualSearchDocuments !== $expectedSearchDocuments) {
                throw new RuntimeException('Search documents do not exactly match indexed manifest pages.');
            }
            $calculatedContentHash = hash('sha256', docaraCanonicalJson($searchIndex['documents']));
            if (! hash_equals($calculatedContentHash, $searchIndex['content_sha256'])) {
                throw new RuntimeException('Search content_sha256 does not match canonical documents.');
            }
            $runtimeHash = hash_file('sha256', $searchRuntimePath);
            if (! is_string($runtimeHash)) {
                throw new RuntimeException('Search runtime hash could not be calculated.');
            }
            $expectedIndexReference = $deploymentBase . '_docara/search-index.json?docara_v='
                . $searchIndex['content_sha256'];
            $expectedRuntimeReference = $deploymentBase . '_docara/search.js?docara_v=' . $runtimeHash;
            if (count($searchIndexReferences) !== $expectedSearchSurfaceCount
                || array_values(array_unique($searchIndexReferences)) !== [$expectedIndexReference]
            ) {
                throw new RuntimeException('Search index references do not match the generated content revision.');
            }
            if (count($searchRuntimeReferences) !== $expectedSearchSurfaceCount
                || array_values(array_unique($searchRuntimeReferences)) !== [$expectedRuntimeReference]
            ) {
                throw new RuntimeException('Search runtime references do not match the generated byte revision.');
            }
        } catch (Throwable $exception) {
            $broken[] = [
                'page' => '@build',
                'reference' => '@search-index-contract',
                'target' => $exception->getMessage(),
            ];
        }
    }
}

$result = [
    'schema' => 'docara.static_build_verification.v1',
    'deployment_base' => $deploymentBase,
    'html_pages' => count($htmlFiles),
    'local_references_checked' => $checked,
    'broken' => $broken,
];
fwrite(STDOUT, json_encode($result, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");

exit($broken === [] ? 0 : 1);
