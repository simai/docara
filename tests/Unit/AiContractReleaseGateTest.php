<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Simai\Docara\Release\AiContractReleaseGate;
use Tests\TestCase;

final class AiContractReleaseGateTest extends TestCase
{
    #[Test]
    public function changed_public_contract_requires_version_bump_and_exact_federation_binding(): void
    {
        $previous = $this->capabilities('1.0.0', []);
        $current = $this->capabilities('1.1.0', [['name' => 'upgrade']]);
        $revision = str_repeat('a', 40);
        $result = (new AiContractReleaseGate)->verify($previous, $current, $this->dna(), $this->lock($revision), $revision);

        self::assertSame('pass', $result['status']);
        self::assertTrue($result['public_contract_changed']);
        self::assertSame($revision, $result['skill_revision']);
        self::assertFalse($result['bootstrap_contract']);
    }

    #[Test]
    public function first_contract_accepts_exact_previous_release_absence_evidence(): void
    {
        $revision = str_repeat('a', 40);
        $result = (new AiContractReleaseGate)->verify(
            $this->bootstrap('1.1.0'),
            $this->capabilities('1.1.0', [['name' => 'capabilities'], ['name' => 'upgrade']]),
            $this->dna(),
            $this->lock($revision),
            $revision,
        );

        self::assertSame('pass', $result['status']);
        self::assertTrue($result['bootstrap_contract']);
        self::assertTrue($result['public_contract_changed']);
        self::assertNull($result['previous_ai_contract']);
        self::assertSame('2.4.1', $result['previous_release']['version']);
        self::assertSame('1.1.0', $result['current_ai_contract']);
    }

    #[Test]
    public function malformed_previous_release_absence_evidence_is_rejected(): void
    {
        $bootstrap = $this->bootstrap('1.1.0');
        $bootstrap['previous_release']['revision'] = 'not-a-commit';

        $this->expectExceptionMessage('AI_RELEASE_BOOTSTRAP_INVALID');
        (new AiContractReleaseGate)->verify(
            $bootstrap,
            $this->capabilities('1.1.0', []),
            $this->dna(),
            $this->lock(str_repeat('a', 40)),
            str_repeat('a', 40),
        );
    }

    #[Test]
    public function previous_release_absence_evidence_cannot_be_reused_for_a_later_contract(): void
    {
        $this->expectExceptionMessage('AI_RELEASE_BOOTSTRAP_CONTRACT_MISMATCH');
        (new AiContractReleaseGate)->verify(
            $this->bootstrap('1.1.0'),
            $this->capabilities('1.2.0', []),
            $this->dna(),
            $this->lock(str_repeat('a', 40)),
            str_repeat('a', 40),
        );
    }

    #[Test]
    public function another_well_formed_release_record_cannot_replace_the_packaged_baseline(): void
    {
        $bootstrap = $this->bootstrap('1.1.0');
        $bootstrap['previous_release']['version'] = '2.4.0';
        $bootstrap['previous_release']['tag'] = 'v2.4.0';

        $this->expectExceptionMessage('AI_RELEASE_BOOTSTRAP_UNTRUSTED');
        (new AiContractReleaseGate)->verify(
            $bootstrap,
            $this->capabilities('1.1.0', []),
            $this->dna(),
            $this->lock(str_repeat('a', 40)),
            str_repeat('a', 40),
        );
    }

    #[Test]
    public function unchanged_ai_version_blocks_a_changed_public_surface(): void
    {
        $this->expectExceptionMessage('AI_CONTRACT_VERSION_NOT_BUMPED');
        (new AiContractReleaseGate)->verify(
            $this->capabilities('1.0.0', []),
            $this->capabilities('1.0.0', [['name' => 'upgrade']]),
            $this->dna(),
            $this->lock(str_repeat('a', 40)),
            str_repeat('a', 40),
        );
    }

    #[Test]
    public function stale_federation_skill_revision_blocks_package_release(): void
    {
        $this->expectExceptionMessage('AI_RELEASE_FEDERATION_BINDING_STALE');
        (new AiContractReleaseGate)->verify(
            $this->capabilities('1.0.0', []),
            $this->capabilities('1.0.0', []),
            $this->dna(),
            $this->lock(str_repeat('b', 40)),
            str_repeat('a', 40),
        );
    }

    /** @return array<string, mixed> */
    private function capabilities(string $version, array $commands): array
    {
        return [
            'schema' => 'docara.capabilities.v1',
            'ai_contract' => ['version' => $version],
            'commands' => $commands,
            'schemas' => [],
            'operation_types' => [],
            'tracking' => [],
            'receipts' => [],
            'lifecycle' => [],
            'contract_sha256' => hash('sha256', $version . json_encode($commands)),
        ];
    }

    /** @return array<string, mixed> */
    private function dna(): array
    {
        return ['product_contracts' => ['docara.ai_contract' => '>=1.0.0 <2.0.0']];
    }

    /** @return array<string, mixed> */
    private function bootstrap(string $contract): array
    {
        $contents = file_get_contents(dirname(__DIR__, 2) . '/resources/release-baselines/docara-2.4.1-ai-contract-absent.json');
        self::assertIsString($contents);
        $bootstrap = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($bootstrap);
        $bootstrap['introduced_ai_contract'] = $contract;

        return $bootstrap;
    }

    /** @return array<string, mixed> */
    private function lock(string $revision): array
    {
        return [
            'version' => 'fixture',
            'skills' => [['id' => 'docara', 'path' => 'skills/docara', 'revision' => $revision]],
        ];
    }
}
