<?php

declare(strict_types=1);

/**
 * Writes resources/component-catalog/source-metadata.json: for every file the
 * component catalogue cites (docs pages, definitions, manifests), its SHA-256
 * and its address in the release that ships it.
 *
 * The snapshot names the release (VERSION) and links to the files under the
 * release tag (v<VERSION>), never to a commit: a commit cannot record its own
 * hash, and the snapshot is part of the commit that gets tagged. Run this
 * script after changing a cited file or VERSION; the output depends only on
 * the working tree, so running it twice gives the same bytes.
 *
 * Usage: php scripts/capture-component-source-metadata.php [--check]
 *   --check  exit 1 if the committed snapshot differs from a fresh capture.
 */

use Simai\Docara\Portable\CanonicalJson;

require dirname(__DIR__) . '/vendor/autoload.php';

const DOCARA_REPOSITORY = 'https://github.com/simai/docara';

$root = dirname(__DIR__);
$check = in_array('--check', array_slice($argv, 1), true);
$release = trim((string) file_get_contents($root . '/VERSION'));
if (preg_match('/^[0-9]+\.[0-9]+\.[0-9]+(?:-[0-9A-Za-z.-]+)?$/D', $release) !== 1) {
    fwrite(STDERR, "VERSION does not hold a release number.\n");
    exit(1);
}
$tag = 'v' . $release;

$references = [];
foreach (['native', 'typed', 'inline', 'smart', 'requirements'] as $directory) {
    foreach (glob($root . '/resources/component-catalog/' . $directory . '/*.json') ?: [] as $path) {
        $decoded = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        foreach ([
            $decoded['docs_ref'] ?? null,
            $decoded['provenance']['definition_ref'] ?? null,
            $decoded['provenance']['manifest_ref'] ?? null,
        ] as $reference) {
            if (is_string($reference) && $reference !== '') {
                $references[$reference] = true;
            }
        }
    }
}
ksort($references, SORT_STRING);

$entries = [];
foreach (array_keys($references) as $sourceRef) {
    $absolute = $root . '/' . $sourceRef;
    if (! is_file($absolute) || is_link($absolute)) {
        fwrite(STDERR, "Unsafe or missing component source [$sourceRef].\n");
        exit(1);
    }
    $entries[] = [
        'source_ref' => $sourceRef,
        'source_sha256' => hash_file('sha256', $absolute),
        'release' => $release,
        'source_url' => DOCARA_REPOSITORY . '/blob/' . $tag . '/' . $sourceRef,
        'history_url' => DOCARA_REPOSITORY . '/commits/' . $tag . '/' . $sourceRef,
        'history_exact' => false,
    ];
}
$payload = [
    'repository' => DOCARA_REPOSITORY,
    'release' => $release,
    'entries' => $entries,
];
$snapshot = [
    'schema' => 'docara.component_source_metadata.v1',
    'version' => 1,
    ...$payload,
    'content_sha256' => hash('sha256', CanonicalJson::encode($payload)),
];
$json = json_encode($snapshot, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
$target = $root . '/resources/component-catalog/source-metadata.json';
if ($check) {
    if (! is_file($target) || (string) file_get_contents($target) !== $json) {
        fwrite(STDERR, "resources/component-catalog/source-metadata.json is out of date; run scripts/capture-component-source-metadata.php.\n");
        exit(1);
    }
    fwrite(STDOUT, 'Component source metadata is current: ' . count($entries) . " records for $release.\n");
    exit(0);
}
file_put_contents($target, $json);
fwrite(STDOUT, 'Captured ' . count($entries) . " component source records for $release.\n");
