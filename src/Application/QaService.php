<?php

declare(strict_types=1);

namespace Simai\Docara\Application;

use Simai\Docara\File\Filesystem;
use Simai\Docara\File\ProjectFilesystemGuard;
use Simai\Docara\Portable\CanonicalJson;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\Portable\SchemaRepository;
use Simai\Docara\Preview\PreviewKernel;
use Simai\Docara\Preview\PreviewShell;
use Simai\Docara\Preview\PreviewTarget;

final readonly class QaService
{
    public function __construct(
        private PreviewKernel $preview,
        private PreviewShell $shell,
        private ProjectFilesystemGuard $writes = new ProjectFilesystemGuard,
        private Filesystem $files = new Filesystem,
    ) {}

    public function plan(string $root, string $kind, string $id, string $page): OperationResult
    {
        $runtime = ProjectRuntime::load($root);
        $this->writes->directoryPath($runtime->root, '.docara-preview');
        $this->writes->directoryPath($runtime->root, '.docara-qa/plans');
        $target = match ($kind) {
            'smart' => PreviewTarget::Smart,
            'region' => PreviewTarget::Region,
            'layout' => PreviewTarget::Layout,
            default => throw new PortableConfigurationException('QA_KIND_INVALID', 'QA kind must be smart, region or layout.'),
        };
        $artifact = $this->preview->render($root, $page, $target, $target === PreviewTarget::Layout ? null : $id);
        if ($target === PreviewTarget::Layout && ($artifact->provenance['layout_id'] ?? null) !== $id) {
            throw new PortableConfigurationException(
                'QA_LAYOUT_CONTEXT_MISMATCH',
                "QA page [$page] uses layout [" . ($artifact->provenance['layout_id'] ?? 'unknown') . "] instead of [$id].",
            );
        }
        $published = $this->shell->publish($root, $artifact);
        $scenarios = [];
        foreach ([['desktop', 1440, 900], ['mobile', 390, 844]] as [$viewport, $width, $height]) {
            foreach (['light', 'dark'] as $theme) {
                foreach (['ltr', 'rtl'] as $direction) {
                    $scenarioId = implode('-', [$viewport, $theme, $direction]);
                    $scenarios[] = [
                        'id' => $scenarioId,
                        'viewport' => ['width' => $width, 'height' => $height],
                        'theme' => $theme,
                        'direction' => $direction,
                        'reduced_motion' => true,
                        'screenshot' => 'screenshots/' . $scenarioId . '.png',
                    ];
                }
            }
        }
        $core = [
            'schema' => 'docara.qa_plan_draft.v1',
            'phase' => 'draft',
            'subject' => $kind . ':' . $id,
            'preview' => (string) $published['preview'],
            'artifact_sha256' => $artifact->sha256(),
            'target' => $this->targetContract($kind, $id, $artifact->sha256()),
            'reference' => $this->referenceDraftContract($kind, $id, $artifact->sha256(), $artifact->pageSha256()),
            'scenarios' => $scenarios,
            'assertions' => ['local_assets_200', 'a11y_violations_zero', 'console_errors_zero', 'console_warnings_zero', 'horizontal_overflow_zero', 'keyboard_focus_escape', 'reduced_motion', 'visual_diff_zero'],
        ];
        $planId = QaIdentity::planId($core);
        $plan = ['plan_id' => $planId] + $core;
        (new SchemaRepository)->assertValid($plan, 'qa-plan-draft.schema.json');
        $encoded = CanonicalJson::encodePretty($plan);
        $this->writes->putNewOrIdentical(
            $runtime->root,
            '.docara-qa/plans/' . $planId . '.json',
            $encoded,
            'QA_PLAN_COLLISION',
        );

        return OperationResult::success('qa.plan', $kind . ':' . $id, [
            'draft_plan_id' => $planId,
            'plan_path' => '.docara-qa/plans/' . $planId . '.json',
            'preview' => $published['preview'],
            'artifact_sha256' => $artifact->sha256(),
            'scenarios' => $scenarios,
            'runner' => 'optional_external_browser',
            'next_operation' => 'qa.finalize_reference',
        ], $runtime->provenance());
    }

    public function finalizeReference(string $root, string $draftPlanId): OperationResult
    {
        $runtime = ProjectRuntime::load($root);
        $this->assertPlanId($draftPlanId);
        $draft = $this->read($runtime->root, '.docara-qa/plans/' . $draftPlanId . '.json', 'qa-plan-draft.schema.json');
        if (! hash_equals($draftPlanId, QaIdentity::planId($draft))) {
            throw new PortableConfigurationException('QA_PLAN_ID_MISMATCH', 'QA draft plan content does not match its content-addressed plan id.');
        }
        $this->assertPreviewBinding($runtime->root, $draft);
        $referenceDraft = $this->read(
            $runtime->root,
            '.docara-qa/reference-drafts/' . $draftPlanId . '/reference.json',
            'qa-reference-draft.schema.json',
        );
        $this->assertReferenceDraftBinding($runtime->root, $draft, $referenceDraft);

        $reference = $referenceDraft;
        unset($reference['draft_plan_id']);
        $reference['schema'] = 'docara.qa_reference.v2';
        $reference['source_plan_id'] = $draftPlanId;
        $referenceId = QaIdentity::referenceId($reference);
        $reference['reference_id'] = $referenceId;
        $manifestSha256 = QaIdentity::referenceManifestSha256($reference);
        (new SchemaRepository)->assertValid($reference, 'qa-reference.schema.json');

        $candidate = '.docara-qa/reference-candidates/' . $referenceId . '-' . bin2hex(random_bytes(8));
        try {
            foreach ($reference['scenarios'] as $scenario) {
                $source = $this->writes->regularFile(
                    $runtime->root,
                    '.docara-qa/reference-drafts/' . $draftPlanId . '/' . $scenario['screenshot'],
                );
                $this->writes->copyNew(
                    $runtime->root,
                    $source,
                    $candidate . '/' . $scenario['screenshot'],
                    'QA_REFERENCE_COLLISION',
                );
            }
            $this->writes->putNew(
                $runtime->root,
                $candidate . '/reference.json',
                CanonicalJson::encodePretty($reference),
                'QA_REFERENCE_COLLISION',
            );
            $this->writes->assertSafeTree($runtime->root, $candidate);
            $this->assertFinalReferenceIdentical($runtime->root, $candidate, $reference);
            $finalRoot = '.docara-qa/references/' . $referenceId;
            if (file_exists($this->writes->writablePath($runtime->root, $finalRoot))) {
                $this->assertFinalReferenceIdentical($runtime->root, $finalRoot, $reference);
                $this->writes->deleteDirectory($runtime->root, $candidate, $this->files);
            } else {
                $this->writes->ensureDirectory($runtime->root, '.docara-qa/references');
                $this->writes->moveDirectory($runtime->root, $candidate, $finalRoot);
            }
        } catch (\Throwable $exception) {
            if (file_exists($this->writes->writablePath($runtime->root, $candidate))) {
                $this->writes->deleteDirectory($runtime->root, $candidate, $this->files);
            }
            throw $exception;
        }

        $referenceBinding = [
            'contract' => 'docara.production_target_reference.v2',
            'reference_id' => $referenceId,
            'manifest_sha256' => $manifestSha256,
            'kind' => $reference['target']['kind'],
            'id' => $reference['target']['id'],
            'page_html_sha256' => $reference['page_html_sha256'],
            'artifact_sha256' => $reference['artifact_sha256'],
        ];
        $core = $draft;
        unset($core['plan_id']);
        $core['schema'] = 'docara.qa_plan.v2';
        $core['phase'] = 'finalized';
        $core['source_plan_id'] = $draftPlanId;
        $core['reference'] = $referenceBinding;
        $planId = QaIdentity::planId($core);
        $plan = ['plan_id' => $planId] + $core;
        (new SchemaRepository)->assertValid($plan, 'qa-plan.schema.json');
        $this->writes->putNewOrIdentical(
            $runtime->root,
            '.docara-qa/plans/' . $planId . '.json',
            CanonicalJson::encodePretty($plan),
            'QA_PLAN_COLLISION',
        );

        return OperationResult::success('qa.finalize_reference', (string) $plan['subject'], [
            'draft_plan_id' => $draftPlanId,
            'plan_id' => $planId,
            'plan_path' => '.docara-qa/plans/' . $planId . '.json',
            'reference_id' => $referenceId,
            'reference_manifest_sha256' => $manifestSha256,
            'scenarios' => count($reference['scenarios']),
        ], $runtime->provenance());
    }

    public function verify(string $root, string $planId): OperationResult
    {
        $runtime = ProjectRuntime::load($root);
        $this->assertPlanId($planId);
        $plan = $this->read($runtime->root, '.docara-qa/plans/' . $planId . '.json', 'qa-plan.schema.json');
        if (! hash_equals($planId, QaIdentity::planId($plan))) {
            throw new PortableConfigurationException('QA_PLAN_ID_MISMATCH', 'QA plan content does not match its content-addressed plan id.');
        }
        $this->assertPreviewBinding($runtime->root, $plan);
        $referenceId = (string) ($plan['reference']['reference_id'] ?? '');
        $reference = $this->read($runtime->root, '.docara-qa/references/' . $referenceId . '/reference.json', 'qa-reference.schema.json');
        if (! hash_equals($referenceId, QaIdentity::referenceId($reference))) {
            throw new PortableConfigurationException('QA_REFERENCE_ID_MISMATCH', 'Finalized QA reference manifest does not match the immutable reference id anchored by the plan.');
        }
        if (! hash_equals((string) ($plan['reference']['manifest_sha256'] ?? ''), QaIdentity::referenceManifestSha256($reference))) {
            throw new PortableConfigurationException('QA_REFERENCE_MANIFEST_MISMATCH', 'Finalized QA reference manifest does not match the manifest seal anchored by the plan.');
        }
        $report = $this->read($runtime->root, '.docara-qa/results/' . $planId . '/report.json', 'qa-report.schema.json');
        if (($plan['plan_id'] ?? null) !== $planId || ($report['plan_id'] ?? null) !== $planId
            || ($report['artifact_sha256'] ?? null) !== ($plan['artifact_sha256'] ?? null)
            || ($report['target'] ?? null) !== ($plan['target'] ?? null)
            || ($report['reference'] ?? null) !== ($plan['reference'] ?? null)
            || ($reference['reference_id'] ?? null) !== $referenceId
            || ($reference['source_plan_id'] ?? null) !== ($plan['source_plan_id'] ?? null)
            || ($reference['subject'] ?? null) !== ($plan['subject'] ?? null)
            || ($reference['target'] ?? null) !== ($plan['target'] ?? null)
            || ($reference['artifact_sha256'] ?? null) !== ($plan['artifact_sha256'] ?? null)
            || ($reference['page_html_sha256'] ?? null) !== ($plan['reference']['page_html_sha256'] ?? null)) {
            throw new PortableConfigurationException('QA_REPORT_BINDING_INVALID', 'QA report is not bound to the exact plan and preview artifact.');
        }
        $expected = array_column($plan['scenarios'], 'id');
        $actual = array_column($report['scenarios'], 'id');
        sort($expected, SORT_STRING);
        sort($actual, SORT_STRING);
        if ($expected !== $actual) {
            throw new PortableConfigurationException('QA_REPORT_SCENARIOS_INCOMPLETE', 'QA report does not cover the exact planned scenario matrix.');
        }
        foreach ($report['scenarios'] as $scenario) {
            $referenceScenario = null;
            foreach ($reference['scenarios'] as $candidate) {
                if (($candidate['id'] ?? null) === $scenario['id']) {
                    $referenceScenario = $candidate;
                    break;
                }
            }
            if (! is_array($referenceScenario)
                || ($scenario['reference_sha256'] ?? null) !== ($referenceScenario['screenshot_sha256'] ?? null)) {
                throw new PortableConfigurationException('QA_REFERENCE_BINDING_INVALID', "QA scenario [{$scenario['id']}] is not bound to its production reference.");
            }
            $referenceScreenshot = $this->writes->regularFile(
                $runtime->root,
                '.docara-qa/references/' . $referenceId . '/' . $referenceScenario['screenshot'],
            );
            if (! hash_equals((string) $referenceScenario['screenshot_sha256'], hash_file('sha256', $referenceScreenshot) ?: '')) {
                throw new PortableConfigurationException('QA_REFERENCE_INVALID', "QA reference [{$scenario['id']}] does not match its declared hash.");
            }
            $screenshot = $this->writes->regularFile(
                $runtime->root,
                '.docara-qa/results/' . $planId . '/' . $scenario['screenshot'],
            );
            if (! hash_equals((string) $scenario['screenshot_sha256'], hash_file('sha256', $screenshot) ?: '')
                || (string) file_get_contents($screenshot, false, null, 0, 8) !== "\x89PNG\r\n\x1a\n") {
                throw new PortableConfigurationException('QA_SCREENSHOT_INVALID', "QA screenshot [{$scenario['id']}] is missing or does not match its hash.");
            }
            if (! hash_equals(hash_file('sha256', $referenceScreenshot) ?: '', hash_file('sha256', $screenshot) ?: '')) {
                throw new PortableConfigurationException('QA_VISUAL_DIFF_MISMATCH', "QA screenshot [{$scenario['id']}] differs from its production reference.");
            }
            if ($scenario['a11y_violations'] !== 0 || $scenario['console_errors'] !== 0 || $scenario['console_warnings'] !== 0
                || $scenario['overflow'] !== 0 || $scenario['keyboard'] !== 'pass' || $scenario['reduced_motion'] !== 'pass'
                || $scenario['visual_diff_pixels'] !== 0) {
                throw new PortableConfigurationException('QA_ACCEPTANCE_FAILED', "QA scenario [{$scenario['id']}] failed one or more acceptance assertions.");
            }
        }

        return OperationResult::success('qa.verify', (string) $plan['subject'], [
            'plan_id' => $planId,
            'artifact_sha256' => $plan['artifact_sha256'],
            'scenarios_verified' => count($report['scenarios']),
            'status' => 'pass',
        ], $runtime->provenance());
    }

    /** @return array{kind:string,id:string,scope:string,locator:string,html_sha256:string} */
    private function targetContract(string $kind, string $id, string $htmlSha256): array
    {
        $locator = match ($kind) {
            'layout' => 'body',
            'region' => '[data-docara-region="' . $id . '"]',
            'smart' => '[data-docara-smart="' . $id . '"],[data-docara-block="' . preg_replace('/^.*\./', '', $id) . '"]',
            default => throw new \LogicException('Unsupported QA target.'),
        };

        return ['kind' => $kind, 'id' => $id, 'scope' => $kind === 'layout' ? 'document' : 'element', 'locator' => $locator, 'html_sha256' => $htmlSha256];
    }

    /** @return array{contract:string,kind:string,id:string,page_html_sha256:string,artifact_sha256:string} */
    private function referenceDraftContract(string $kind, string $id, string $artifactSha256, string $pageHtmlSha256): array
    {
        return ['contract' => 'docara.production_target_reference_draft.v1', 'kind' => $kind, 'id' => $id, 'page_html_sha256' => $pageHtmlSha256, 'artifact_sha256' => $artifactSha256];
    }

    /** @param array<string, mixed> $plan */
    private function assertPreviewBinding(string $root, array $plan): void
    {
        $preview = (string) ($plan['preview'] ?? '');
        $previewRoot = preg_replace('~/index\.html$~', '', $preview);
        if (! is_string($previewRoot) || $previewRoot === $preview) {
            throw new PortableConfigurationException('QA_PREVIEW_BINDING_INVALID', 'QA plan preview path is invalid.');
        }
        $artifact = $this->writes->regularFile($root, $previewRoot . '/artifact.html');
        $page = $this->writes->regularFile($root, $preview);
        if (! hash_equals((string) ($plan['artifact_sha256'] ?? ''), hash_file('sha256', $artifact) ?: '')
            || ! hash_equals((string) ($plan['target']['html_sha256'] ?? ''), hash_file('sha256', $artifact) ?: '')
            || ! hash_equals((string) ($plan['reference']['artifact_sha256'] ?? ''), hash_file('sha256', $artifact) ?: '')
            || ! hash_equals((string) ($plan['reference']['page_html_sha256'] ?? ''), hash_file('sha256', $page) ?: '')) {
            throw new PortableConfigurationException('QA_PREVIEW_BINDING_INVALID', 'QA plan is not bound to the current immutable preview artifact and page bytes.');
        }
        $subject = explode(':', (string) ($plan['subject'] ?? ''), 2);
        if (count($subject) !== 2 || $subject[0] !== ($plan['target']['kind'] ?? null) || $subject[1] !== ($plan['target']['id'] ?? null)) {
            throw new PortableConfigurationException('QA_TARGET_BINDING_INVALID', 'QA subject and target identity do not match.');
        }
    }

    private function assertPlanId(string $planId): void
    {
        if (preg_match('/^[a-f0-9]{64}$/D', $planId) !== 1) {
            throw new PortableConfigurationException('QA_PLAN_ID_INVALID', 'QA operation requires the exact plan SHA-256.');
        }
    }

    /** @param array<string,mixed> $draft @param array<string,mixed> $reference */
    private function assertReferenceDraftBinding(string $root, array $draft, array $reference): void
    {
        if (($reference['draft_plan_id'] ?? null) !== ($draft['plan_id'] ?? null)
            || ($reference['subject'] ?? null) !== ($draft['subject'] ?? null)
            || ($reference['target'] ?? null) !== ($draft['target'] ?? null)
            || ($reference['artifact_sha256'] ?? null) !== ($draft['artifact_sha256'] ?? null)
            || ($reference['page_html_sha256'] ?? null) !== ($draft['reference']['page_html_sha256'] ?? null)) {
            throw new PortableConfigurationException('QA_REFERENCE_DRAFT_BINDING_INVALID', 'QA reference draft is not bound to the exact immutable draft plan.');
        }
        $expected = array_column($draft['scenarios'], 'id');
        $actual = array_column($reference['scenarios'], 'id');
        if ($expected !== $actual) {
            throw new PortableConfigurationException('QA_REFERENCE_DRAFT_SCENARIOS_INVALID', 'QA reference draft does not preserve the exact ordered scenario matrix.');
        }
        foreach ($reference['scenarios'] as $index => $scenario) {
            if (($scenario['screenshot'] ?? null) !== ($draft['scenarios'][$index]['screenshot'] ?? null)) {
                throw new PortableConfigurationException('QA_REFERENCE_DRAFT_SCENARIOS_INVALID', 'QA reference draft screenshot paths do not match the immutable draft plan.');
            }
            $path = $this->writes->regularFile($root, '.docara-qa/reference-drafts/' . $draft['plan_id'] . '/' . $scenario['screenshot']);
            if (! hash_equals((string) $scenario['screenshot_sha256'], hash_file('sha256', $path) ?: '')
                || (string) file_get_contents($path, false, null, 0, 8) !== "\x89PNG\r\n\x1a\n") {
                throw new PortableConfigurationException('QA_REFERENCE_DRAFT_INVALID', "QA reference draft [{$scenario['id']}] is missing or does not match its declared hash.");
            }
        }
    }

    /** @param array<string,mixed> $reference */
    private function assertFinalReferenceIdentical(string $root, string $relativeRoot, array $reference): void
    {
        $existing = $this->read($root, $relativeRoot . '/reference.json', 'qa-reference.schema.json');
        if (! hash_equals(QaIdentity::referenceManifestSha256($reference), QaIdentity::referenceManifestSha256($existing))) {
            throw new PortableConfigurationException('QA_REFERENCE_COLLISION', 'Finalized QA reference id already contains different manifest contents.');
        }
        foreach ($reference['scenarios'] as $scenario) {
            $path = $this->writes->regularFile($root, $relativeRoot . '/' . $scenario['screenshot']);
            if (! hash_equals((string) $scenario['screenshot_sha256'], hash_file('sha256', $path) ?: '')) {
                throw new PortableConfigurationException('QA_REFERENCE_COLLISION', 'Finalized QA reference id already contains different screenshot bytes.');
            }
        }
    }

    /** @return array<string, mixed> */
    private function read(string $root, string $relative, string $schema): array
    {
        $path = $this->writes->regularFile($root, $relative);
        $value = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($value)) {
            throw new PortableConfigurationException('QA_ARTIFACT_INVALID', 'QA plan/report must contain a JSON object.');
        }
        (new SchemaRepository)->assertValid($value, $schema);

        return $value;
    }
}
