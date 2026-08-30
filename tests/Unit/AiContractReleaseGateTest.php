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
    private function lock(string $revision): array
    {
        return [
            'version' => 'fixture',
            'skills' => [['id' => 'docara', 'path' => 'skills/docara', 'revision' => $revision]],
        ];
    }
}
