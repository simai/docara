<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Simai\Docara\Framework\FrameworkComponentException;
use Simai\Docara\Framework\FrameworkComponentRuntime;
use Simai\Docara\Framework\FrameworkLock;

final class FrameworkComponentRuntimeTest extends TestCase
{
    public function test_it_records_gateway_calls_without_rendering_or_placeholders(): void
    {
        $runtime = FrameworkComponentRuntime::fromLock($this->lock());
        $document = $runtime->recordGatewayCalls(':::ui.alert', [[
            'schema' => 'docara.component_call.v1',
            'id' => 'ui.alert',
            'props' => ['title' => 'Portable'],
            'ordinal' => 1,
            'line' => 1,
        ]]);

        self::assertSame('shared_smart_gateway', $document->diagnostics['mode']);
        self::assertSame([], $document->renderedHtml);
        self::assertSame(':::ui.alert', $document->markdownWithPlaceholders);
        self::assertSame(['ui.alert'], array_column($document->normalizedCalls, 'id'));
        self::assertNotSame([], $document->assetPlan->assets);
    }

    public function test_asset_planning_is_deterministic_and_deduplicated(): void
    {
        $runtime = FrameworkComponentRuntime::fromLock($this->lock());
        $first = $runtime->planAssets(['ui.button', 'ui.alert', 'ui.button']);
        $second = $runtime->planAssets(['ui.alert', 'ui.button']);

        self::assertSame($first->toArray(), $second->toArray());
        self::assertSame(
            count(array_column($first->assets, 'key')),
            count(array_unique(array_column($first->assets, 'key'))),
        );
    }

    public function test_gateway_call_record_fails_closed_on_invalid_identity(): void
    {
        $this->expectException(FrameworkComponentException::class);
        $this->expectExceptionMessage('FRAMEWORK_GATEWAY_CALL_INVALID');

        FrameworkComponentRuntime::fromLock($this->lock())->recordGatewayCalls('', [['id' => '']]);
    }

    public function test_moving_framework_reference_fails_closed(): void
    {
        $lock = $this->lock();
        $lock['runtime']['source_ref'] = 'main';

        $this->expectException(FrameworkComponentException::class);
        FrameworkComponentRuntime::fromLock($lock);
    }

    public function test_the_retired_preprocessor_and_host_renderer_are_absent(): void
    {
        self::assertFalse(method_exists(FrameworkComponentRuntime::class, 'extract'));
        self::assertFileDoesNotExist(dirname(__DIR__, 2) . '/src/Framework/FrameworkHostRenderer.php');
        $pageBuilder = file_get_contents(dirname(__DIR__, 2) . '/src/PortableSite/PageBuilder.php');
        self::assertIsString($pageBuilder);
        self::assertStringNotContainsString('->extract(', $pageBuilder);
        self::assertStringNotContainsString('->hydrate(', $pageBuilder);
    }

    /** @return array<string,mixed> */
    private function lock(): array
    {
        return FrameworkLock::fromJsonFile(
            dirname(__DIR__, 2) . '/docs/site/simai-framework.lock.json',
        )->toArray();
    }
}
