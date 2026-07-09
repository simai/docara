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
    'ready_to_code',
    'coding_started',
    'contract_skeleton_review_passed',
];
if (!in_array((string) ($launchContext['status'] ?? ''), $allowedStatuses, true)) {
    $errors[] = 'launch-context status is not allowed for this package stage.';
}

$codingStarted = ($launchContext['coding_started'] ?? null) === true;
$codingAllowed = ($launchContext['coding_allowed'] ?? $codingStarted) === true;
$continuationRepository = ($launchContext['repository_class'] ?? null) === 'continuation_repository'
    && ($launchContext['repository_contains_prior_implementation'] ?? null) === true;
$currentPersistenceLaunchRecord = 'specs/implementation-planning/launch-records/docara-batch-2-db-backed-page-persistence.json';
$legacyContractLaunchRecord = 'specs/implementation-planning/launch-records/docara-batch-1-contract-skeletons-current.json';
$launchRecordRef = (string) ($launchContext['launch_record_ref'] ?? '');

if (($launchContext['status'] ?? null) === 'ready_to_code') {
    if ($launchRecordRef !== $currentPersistenceLaunchRecord) {
        $errors[] = 'ready_to_code requires the current Docara DB-backed persistence launch record.';
    }
    if ($codingStarted || $codingAllowed) {
        $errors[] = 'ready_to_code must keep coding_allowed=false and coding_started=false.';
    }
    if (!$continuationRepository) {
        $errors[] = 'The current Docara persistence batch must identify the existing package as a continuation_repository.';
    }
}

if (!$codingStarted && !$continuationRepository) {
    foreach (['src', 'config', 'database', 'routes', 'resources', 'tests', 'lang'] as $runtimePath) {
        if (is_dir($runtimePath)) {
            $errors[] = "{$runtimePath}/ is not allowed before a coding launch record.";
        }
    }
}

if ($codingStarted) {
    if (!in_array($launchRecordRef, [$legacyContractLaunchRecord, $currentPersistenceLaunchRecord], true)) {
        $errors[] = 'coding_started requires a recognized Docara launch record.';
    }
    if (!$codingAllowed && $launchRecordRef === $currentPersistenceLaunchRecord) {
        $errors[] = 'The current persistence launch requires coding_allowed=true before coding_started.';
    }
}

if ($codingStarted || $continuationRepository) {
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
    : (($launchContext['status'] ?? null) === 'ready_to_code'
        ? "Larena Docara DB-backed persistence launch context is ready to code.\n"
        : "Larena Docara clean pre-codegen baseline is valid.\n");
