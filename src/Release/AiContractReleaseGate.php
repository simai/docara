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
        $previous = $this->capabilities($previous, 'previous');
        $current = $this->capabilities($current, 'current');
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

        $previousProjection = $this->publicProjection($previous);
        $currentProjection = $this->publicProjection($current);
        $changed = ! hash_equals(
            hash('sha256', CanonicalJson::encode($previousProjection)),
            hash('sha256', CanonicalJson::encode($currentProjection)),
        );
        if ($changed && ($previous['ai_contract']['version'] ?? null) === ($current['ai_contract']['version'] ?? null)) {
            throw new RuntimeException('AI_CONTRACT_VERSION_NOT_BUMPED:Public AI capabilities changed without a docara.ai_contract version change.');
        }

        return [
            'schema' => 'docara.ai_release_gate.v1',
            'status' => 'pass',
            'public_contract_changed' => $changed,
            'previous_ai_contract' => $previous['ai_contract']['version'],
            'current_ai_contract' => $current['ai_contract']['version'],
            'skill_contract' => $range,
            'skill_revision' => $skillRevision,
            'federation_version' => $federationLock['version'] ?? null,
            'current_contract_sha256' => $current['contract_sha256'],
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
