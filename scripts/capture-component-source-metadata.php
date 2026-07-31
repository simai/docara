<?php

declare(strict_types=1);

use Simai\Docara\Portable\CanonicalJson;

require dirname(__DIR__) . '/vendor/autoload.php';

$root = dirname(__DIR__);
$run = static function (array $arguments) use ($root): string {
    $command = implode(' ', array_map('escapeshellarg', $arguments));
    $output = [];
    $code = 0;
    exec('cd ' . escapeshellarg($root) . ' && ' . $command . ' 2>/dev/null', $output, $code);
    if ($code !== 0) {
        return '';
    }

    return trim(implode("\n", $output));
};

$revision = $run(['git', 'rev-parse', 'HEAD']);
$repository = $run(['git', 'remote', 'get-url', 'origin']);
if (preg_match('/^[a-f0-9]{40}$/D', $revision) !== 1 || $repository === '') {
    fwrite(STDERR, "Git repository metadata is unavailable.\n");
    exit(1);
}
$webRepository = preg_replace('/\.git$/D', '', $repository) ?? $repository;
if (str_starts_with($webRepository, 'git@github.com:')) {
    $webRepository = 'https://github.com/' . substr($webRepository, strlen('git@github.com:'));
}

$references = [];
$directories = ['native', 'typed', 'inline', 'smart', 'requirements'];
foreach ($directories as $directory) {
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

$fallback = explode("\0", $run(['git', 'show', '-s', '--format=%H%x00%an%x00%aI', 'HEAD']));
$entries = [];
foreach (array_keys($references) as $sourceRef) {
    $absolute = $root . '/' . $sourceRef;
    if (! is_file($absolute) || is_link($absolute)) {
        fwrite(STDERR, "Unsafe or missing component source [$sourceRef].\n");
        exit(1);
    }
    $history = $run(['git', 'log', '-1', '--format=%H%x00%an%x00%aI', '--', $sourceRef]);
    $historyExact = $history !== '';
    $parts = $historyExact ? explode("\0", $history) : $fallback;
    if (count($parts) !== 3 || preg_match('/^[a-f0-9]{40}$/D', $parts[0]) !== 1) {
        fwrite(STDERR, "Invalid Git history for [$sourceRef].\n");
        exit(1);
    }
    $entries[] = [
        'source_ref' => $sourceRef,
        'source_sha256' => hash_file('sha256', $absolute),
        'revision' => $parts[0],
        'author' => $parts[1],
        'changed_at' => $parts[2],
        'source_url' => $historyExact ? $webRepository . '/blob/' . $parts[0] . '/' . $sourceRef : null,
        'history_url' => $historyExact ? $webRepository . '/commits/' . $revision . '/' . $sourceRef : null,
        'history_exact' => $historyExact,
    ];
}
$payload = [
    'repository' => $webRepository,
    'revision' => $revision,
    'entries' => $entries,
];
$snapshot = [
    'schema' => 'docara.component_source_metadata.v1',
    'version' => 1,
    ...$payload,
    'content_sha256' => hash('sha256', CanonicalJson::encode($payload)),
];
$json = json_encode($snapshot, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
file_put_contents($root . '/resources/component-catalog/source-metadata.json', $json);
fwrite(STDOUT, "Captured " . count($entries) . " component source records at $revision.\n");
