<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Simai\Docara\Declarative\Adapter\LarenaContractAdapter;
use Simai\Docara\Declarative\DeclarativePageCompiler;
use Simai\Docara\Declarative\Document\DocumentParser;

final class LarenaContractAdapterTest extends TestCase
{
    public function test_the_same_fixture_projects_to_a_larena_contract_without_semantic_drift(): void
    {
        $document = (new DocumentParser)->parse(<<<'MD'
# Installation

Read the [guide](/guide/).

:::ui.alert
{"type":"info","title":"Before you begin","supporting-text":"Create a backup."}
:::

## Next step

Run the installer.
MD, 'content/install.md');
        $plan = DeclarativePageCompiler::bundled($this->frameworkLock())
            ->compile($document, 'install', 'Installation', 3);

        $larena = (new LarenaContractAdapter)->adapt($plan);
        $payload = $larena->toArray();

        self::assertSame('larena.layout.resolved_render_plan.v1', $payload['schema']);
        self::assertSame('docara.docs', $payload['layout']['key']);
        self::assertSame(
            ['header', 'sidebar', 'main', 'outline', 'footer'],
            $payload['layout']['regions'],
        );
        self::assertSame('docara.article', $payload['regions']['main'][0]['section']);
        self::assertSame(
            ['content.markdown', 'content.smart', 'content.markdown'],
            array_column($payload['regions']['main'][0]['blocks'], 'block'),
        );
        self::assertSame(
            'ui.alert',
            $payload['regions']['main'][0]['blocks'][1]['smart']['key'],
        );
        self::assertSame($plan->canonicalHash(), $payload['source']['docara_plan_hash']);
        self::assertSame($plan->semanticProjection(), $larena->semantics);
        self::assertSame(
            $larena->canonicalHash(),
            (new LarenaContractAdapter)->adapt($plan)->canonicalHash(),
        );
    }

    /** @return array<string, mixed> */
    private function frameworkLock(): array
    {
        return json_decode(
            (string) file_get_contents(dirname(__DIR__, 2) . '/stubs/portable/simai-framework.lock.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
    }
}
