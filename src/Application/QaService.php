<?php

declare(strict_types=1);

namespace Simai\Docara\Application;

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
            'schema' => 'docara.qa_plan.v1',
            'subject' => $kind . ':' . $id,
            'preview' => (string) $published['preview'],
            'artifact_sha256' => $artifact->sha256(),
            'target' => $this->targetContract($kind, $id, $artifact->sha256()),
            'reference' => $this->referenceContract($kind, $id, $artifact->sha256(), $artifact->pageSha256()),
            'scenarios' => $scenarios,
            'assertions' => ['local_assets_200', 'a11y_violations_zero', 'console_errors_zero', 'console_warnings_zero', 'horizontal_overflow_zero', 'keyboard_focus_escape', 'reduced_motion', 'visual_diff_zero'],
        ];
        $planId = hash('sha256', CanonicalJson::encode($core));
        $plan = ['plan_id' => $planId] + $core;
        (new SchemaRepository)->assertValid($plan, 'qa-plan.schema.json');
        $encoded = CanonicalJson::encodePretty($plan);
        $this->writes->putNewOrIdentical(
            $runtime->root,
            '.docara-qa/plans/' . $planId . '.json',
            $encoded,
            'QA_PLAN_COLLISION',
        );

        return OperationResult::success('qa.plan', $kind . ':' . $id, [
            'plan_id' => $planId,
            'plan_path' => '.docara-qa/plans/' . $planId . '.json',
            'preview' => $published['preview'],
            'artifact_sha256' => $artifact->sha256(),
            'scenarios' => $scenarios,
            'runner' => 'optional_external_browser',
        ], $runtime->provenance());
    }

    public function verify(string $root, string $planId): OperationResult
    {
        $runtime = ProjectRuntime::load($root);
        if (preg_match('/^[a-f0-9]{64}$/D', $planId) !== 1) {
            throw new PortableConfigurationException('QA_PLAN_ID_INVALID', 'QA verify requires the exact plan SHA-256.');
        }
        $plan = $this->read($runtime->root, '.docara-qa/plans/' . $planId . '.json', 'qa-plan.schema.json');
        $report = $this->read($runtime->root, '.docara-qa/results/' . $planId . '/report.json', 'qa-report.schema.json');
        $referenceId = (string) ($plan['reference']['reference_id'] ?? '');
        $reference = $this->read($runtime->root, '.docara-qa/references/' . $referenceId . '/reference.json', 'qa-reference.schema.json');
        if (($plan['plan_id'] ?? null) !== $planId || ($report['plan_id'] ?? null) !== $planId
            || ($report['artifact_sha256'] ?? null) !== ($plan['artifact_sha256'] ?? null)
            || ($report['target'] ?? null) !== ($plan['target'] ?? null)
            || ($report['reference'] ?? null) !== ($plan['reference'] ?? null)
            || ($reference['reference_id'] ?? null) !== $referenceId
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

    /** @return array{contract:string,reference_id:string,page_html_sha256:string,artifact_sha256:string} */
    private function referenceContract(string $kind, string $id, string $artifactSha256, string $pageHtmlSha256): array
    {
        $identity = ['contract' => 'docara.production_target_reference.v1', 'kind' => $kind, 'id' => $id, 'page_html_sha256' => $pageHtmlSha256, 'artifact_sha256' => $artifactSha256];

        return $identity + ['reference_id' => hash('sha256', CanonicalJson::encode($identity))];
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
