<?php

declare(strict_types=1);

$context = json_decode((string) file_get_contents('.larena/launch-context.json'), true, 512, JSON_THROW_ON_ERROR);
$evidencePath = rtrim((string) $context['evidence_path'], '/') . '/';
$proposalPath = (string) $context['graph_sync_proposal_path'];
$requiredEvidenceFiles = $context['required_evidence_files'] ?? [];
$errors = [];

if (!is_array($requiredEvidenceFiles) || $requiredEvidenceFiles === []) {
    $errors[] = 'required_evidence_files must declare the post-implementation evidence contract.';
}
if (!str_starts_with($proposalPath, $evidencePath)) {
    $errors[] = 'graph_sync_proposal_path must be inside evidence_path.';
}

$evidenceRequired = !in_array(
    (string) ($context['status'] ?? ''),
    ['ready_to_code', 'coding_started'],
    true
);
if (!$evidenceRequired && $errors === []) {
    echo "Post-implementation evidence contract is declared; files are not required before implementation_written.\n";
    exit(0);
}

foreach ($requiredEvidenceFiles as $required) {
    if (!is_file($evidencePath . $required)) {
        $errors[] = "Missing evidence file: {$evidencePath}{$required}";
    }
}
if (!is_file($proposalPath)) {
    $errors[] = "Missing graph sync proposal: {$proposalPath}";
} else {
    $proposal = json_decode((string) file_get_contents($proposalPath), true, 512, JSON_THROW_ON_ERROR);
    if (($proposal['canonical_update_allowed'] ?? null) !== false) {
        $errors[] = 'graph-sync-proposal must keep canonical_update_allowed=false';
    }
}
if ($errors !== []) {
    foreach ($errors as $error) {
        fwrite(STDERR, $error . PHP_EOL);
    }
    exit(1);
}
echo "Evidence contract is valid for the current repository state.\n";
