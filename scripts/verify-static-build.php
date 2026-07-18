#!/usr/bin/env php
<?php

declare(strict_types=1);

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
        } catch (Throwable $exception) {
            $manifestError = $exception->getMessage();
        }
    }
}

$checked = 0;
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
    preg_match_all('/\b(?:href|src)\s*=\s*(?:"([^"]*)"|\'([^\']*)\')/i', $html, $matches, PREG_SET_ORDER);
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

$result = [
    'schema' => 'docara.static_build_verification.v1',
    'deployment_base' => $deploymentBase,
    'html_pages' => count($htmlFiles),
    'local_references_checked' => $checked,
    'broken' => $broken,
];
fwrite(STDOUT, json_encode($result, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");

exit($broken === [] ? 0 : 1);
