<?php

declare(strict_types=1);

$requiredFiles = [
    '.gitignore',
    '.env.example',
    '.github/workflows/larena-package-ci.yml',
    '.githooks/pre-commit',
    '.githooks/pre-push',
    'composer.json',
    'module.yaml',
    'phpstan.neon.dist',
    '.larena/spec-ref.json',
    '.larena/launch-context.json',
    'tools/larena-scope-check.php',
];
$errors = [];
foreach ($requiredFiles as $file) {
    if (!is_file($file)) {
        $errors[] = "Missing required enforcement file: {$file}";
    }
}
$specRef = is_file('.larena/spec-ref.json')
    ? json_decode((string) file_get_contents('.larena/spec-ref.json'), true, 512, JSON_THROW_ON_ERROR)
    : [];
$launchContext = is_file('.larena/launch-context.json')
    ? json_decode((string) file_get_contents('.larena/launch-context.json'), true, 512, JSON_THROW_ON_ERROR)
    : [];
if (($specRef['canonical_update_allowed'] ?? null) !== false) {
    $errors[] = '.larena/spec-ref.json must keep canonical_update_allowed=false';
}
if (($launchContext['package'] ?? null) !== 'larena/docara') {
    $errors[] = '.larena/launch-context.json package must be larena/docara';
}
if (!str_starts_with((string) ($launchContext['evidence_path'] ?? ''), 'docs/project-management/evidence/')) {
    $errors[] = 'launch-context evidence_path must start with docs/project-management/evidence/';
}
if (!str_starts_with((string) ($launchContext['graph_sync_proposal_path'] ?? ''), (string) ($launchContext['evidence_path'] ?? '__missing__'))) {
    $errors[] = 'graph_sync_proposal_path must be inside evidence_path';
}
$allowedStatuses = [
    'repository_prepared_pending_review',
    'coding_started',
    'contract_skeleton_review_passed',
];
if (!in_array((string) ($launchContext['status'] ?? ''), $allowedStatuses, true)) {
    $errors[] = 'launch-context status is not allowed for this package stage.';
}

$codingStarted = ($launchContext['coding_started'] ?? null) === true;
if (!$codingStarted) {
    foreach (['src', 'config', 'database', 'routes', 'resources', 'tests', 'lang'] as $runtimePath) {
        if (is_dir($runtimePath)) {
            $errors[] = "{$runtimePath}/ is not allowed before a coding launch record.";
        }
    }
}

if ($codingStarted) {
    if (($launchContext['launch_record_ref'] ?? null) !== 'specs/implementation-planning/launch-records/docara-batch-1-contract-skeletons-current.json') {
        $errors[] = 'coding_started requires the current Docara batch 1 launch record.';
    }

    $requiredContractFiles = [
        'src/Contracts/DocumentationAssetRef.php',
        'src/Contracts/DocumentationPage.php',
        'src/Contracts/DocumentationSection.php',
        'src/Contracts/DocaraAdminContribution.php',
        'src/Contracts/DocaraGateway.php',
        'src/Contracts/PublicationState.php',
        'src/Contracts/SearchProjection.php',
        'src/Enums/DocumentationVisibility.php',
        'src/Enums/PublicationStatus.php',
        'tests/Unit/DocaraContractTest.php',
        'tests/Unit/DocaraFailsClosedTest.php',
    ];
    foreach ($requiredContractFiles as $file) {
        if (!is_file($file)) {
            $errors[] = "Missing required Docara contract skeleton file: {$file}";
        }
    }
}
if ($errors !== []) {
    foreach ($errors as $error) {
        fwrite(STDERR, $error . PHP_EOL);
    }
    exit(1);
}
echo $codingStarted
    ? "Larena Docara contract skeleton launch context is valid.\n"
    : "Larena Docara clean pre-codegen baseline is valid.\n";
