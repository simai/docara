<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Simai\Docara\Application\CapabilitiesService;
use Simai\Docara\Console\ApplicationFactory;
use Tests\TestCase;

final class CapabilitiesServiceTest extends TestCase
{
    #[Test]
    public function contract_is_derived_from_the_actual_application_and_schema_catalog(): void
    {
        $service = new CapabilitiesService(dirname(__DIR__, 2), '2.4.0', 'fixture-revision');
        $first = $service->capabilities($this->tmp, ApplicationFactory::create($this->tmp))->data;
        $second = $service->capabilities($this->tmp, ApplicationFactory::create($this->tmp))->data;

        self::assertSame($first, $second);
        self::assertSame('docara.capabilities.v1', $first['schema']);
        self::assertSame('2.4.0', $first['docara']['version']);
        self::assertSame('fixture-revision', $first['docara']['revision']);
        self::assertContains('upgrade', array_column($first['commands'], 'name'));
        self::assertContains('capabilities.schema.json', array_column($first['schemas'], 'name'));
        self::assertTrue($first['lifecycle']['project_upgrade']);
        self::assertTrue($first['lifecycle']['rollback']);
        self::assertFalse($first['lifecycle']['network_during_build']);
        self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $first['contract_sha256']);
    }
}
