<?php

declare(strict_types=1);

namespace Simai\Docara\Release;

use RuntimeException;
use Simai\Docara\Portable\CanonicalJson;

final readonly class AiContractReleaseGate
{
    /** @return array<string, mixed> */
    public function verify(array $previous, array $current, array $skillDna, array $federationLock, string $skillRevision): array
    {
        $current = $this->capabilities($current, 'current');
        $bootstrap = $this->bootstrap($previous, (string) $current['ai_contract']['version']);
        if ($bootstrap === null) {
            $previous = $this->capabilities($previous, 'previous');
        }
        if (preg_match('/^[a-f0-9]{40}$/D', $skillRevision) !== 1) {
            throw new RuntimeException('AI_RELEASE_SKILL_REVISION_INVALID:Exact 40-character canonical skill revision is required.');
        }
        $range = $skillDna['product_contracts']['docara.ai_contract'] ?? null;
        if (! is_string($range) || ! $this->supports($range, (string) $current['ai_contract']['version'])) {
            throw new RuntimeException('AI_RELEASE_SKILL_INCOMPATIBLE:Canonical skill does not support the current docara.ai_contract version.');
        }
        $binding = null;
        foreach ($federationLock['skills'] ?? [] as $skill) {
            if (is_array($skill) && ($skill['id'] ?? null) === 'docara') {
                $binding = $skill;
                break;
            }
        }
        if (! is_array($binding) || ($binding['revision'] ?? null) !== $skillRevision || ($binding['path'] ?? null) !== 'skills/docara') {
            throw new RuntimeException('AI_RELEASE_FEDERATION_BINDING_STALE:Federation stable release lock does not pin the exact compatible Docara skill revision.');
        }

        $changed = true;
        if ($bootstrap === null) {
            $changed = ! hash_equals(
                hash('sha256', CanonicalJson::encode($this->publicProjection($previous))),
                hash('sha256', CanonicalJson::encode($this->publicProjection($current))),
            );
        }
        if ($bootstrap === null && $changed && ($previous['ai_contract']['version'] ?? null) === ($current['ai_contract']['version'] ?? null)) {
            throw new RuntimeException('AI_CONTRACT_VERSION_NOT_BUMPED:Public AI capabilities changed without a docara.ai_contract version change.');
        }

        return [
            'schema' => 'docara.ai_release_gate.v1',
            'status' => 'pass',
            'public_contract_changed' => $changed,
            'bootstrap_contract' => $bootstrap !== null,
            'previous_release' => $bootstrap,
            'previous_ai_contract' => $bootstrap === null ? $previous['ai_contract']['version'] : null,
            'current_ai_contract' => $current['ai_contract']['version'],
            'skill_contract' => $range,
            'skill_revision' => $skillRevision,
            'federation_version' => $federationLock['version'] ?? null,
            'current_contract_sha256' => $current['contract_sha256'],
        ];
    }

    /** @return array<string, mixed>|null */
    private function bootstrap(array $value, string $currentContract): ?array
    {
        if (($value['schema'] ?? null) !== 'docara.ai_contract_bootstrap.v1') {
            return null;
        }
        $release = $value['previous_release'] ?? null;
        if (! is_array($release)
            || preg_match('/^[0-9]+\.[0-9]+\.[0-9]+$/D', (string) ($release['version'] ?? '')) !== 1
            || ($release['tag'] ?? null) !== 'v' . $release['version']
            || preg_match('/^[a-f0-9]{40}$/D', (string) ($release['revision'] ?? '')) !== 1
            || ($release['capabilities'] ?? null) !== 'absent') {
            throw new RuntimeException('AI_RELEASE_BOOTSTRAP_INVALID:Previous-release bootstrap evidence is incomplete or invalid.');
        }
        if (($value['introduced_ai_contract'] ?? null) !== $currentContract) {
            throw new RuntimeException('AI_RELEASE_BOOTSTRAP_CONTRACT_MISMATCH:Bootstrap evidence is valid only for its declared first AI contract version.');
        }
        $baselinePath = dirname(__DIR__, 2) . '/resources/release-baselines/docara-2.4.1-ai-contract-absent.json';
        $baselineBytes = file_get_contents($baselinePath);
        $baseline = is_string($baselineBytes) ? json_decode($baselineBytes, true) : null;
        if (! is_array($baseline)
            || ! hash_equals(
                hash('sha256', CanonicalJson::encode($baseline)),
                hash('sha256', CanonicalJson::encode($value)),
            )) {
            throw new RuntimeException('AI_RELEASE_BOOTSTRAP_UNTRUSTED:Only the exact package-owned historical bootstrap record is accepted.');
        }

        return [
            'version' => $release['version'],
            'tag' => $release['tag'],
            'revision' => $release['revision'],
            'capabilities' => 'absent',
        ];
    }

    /** @return array<string, mixed> */
    private function capabilities(array $value, string $label): array
    {
        if (($value['schema'] ?? null) === 'docara.operation_result.v1') {
            $value = is_array($value['data'] ?? null) ? $value['data'] : [];
        }
        if (($value['schema'] ?? null) !== 'docara.capabilities.v1') {
            throw new RuntimeException('AI_RELEASE_CAPABILITIES_INVALID:' . ucfirst($label) . ' capabilities are invalid.');
        }

        return $value;
    }

    /** @return array<string, mixed> */
    private function publicProjection(array $capabilities): array
    {
        return [
            'commands' => $capabilities['commands'] ?? [],
            'schemas' => $capabilities['schemas'] ?? [],
            'operation_types' => $capabilities['operation_types'] ?? [],
            'tracking' => $capabilities['tracking'] ?? [],
            'receipts' => $capabilities['receipts'] ?? [],
            'lifecycle' => $capabilities['lifecycle'] ?? [],
        ];
    }

    private function supports(string $range, string $version): bool
    {
        if (preg_match('/^>=([0-9]+\.[0-9]+\.[0-9]+) <([0-9]+\.[0-9]+\.[0-9]+)$/D', $range, $matches) !== 1) {
            return false;
        }

        return version_compare($version, $matches[1], '>=') && version_compare($version, $matches[2], '<');
    }
}
