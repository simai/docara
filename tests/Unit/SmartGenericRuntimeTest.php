<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Simai\Docara\Declarative\Document\SmartCallNode;
use Simai\Docara\Declarative\Document\SourceSpan;
use Simai\Docara\Declarative\Plan\ResolvedSmartPlan;
use Simai\Docara\Smart\Runtime\Context\GenericPropsContextAdapter;
use Simai\Docara\Smart\Runtime\Context\SmartContextAdapterRegistry;
use Simai\Docara\Smart\Runtime\SmartInvocation;
use Simai\Docara\Smart\Runtime\Strategy\RegisteredTemplateStrategy;
use Simai\Docara\Smart\Runtime\Strategy\SmartRendererStrategyRegistry;
use Simai\Docara\Smart\SmartRegistry;

final class SmartGenericRuntimeTest extends TestCase
{
    public function test_renderer_source_contains_no_component_identity_dispatch(): void
    {
        $source = (string) file_get_contents(dirname(__DIR__, 2) . '/src/Declarative/Rendering/SmartRenderer.php');

        foreach (['ui.alert', 'ui.button', 'docara.brand', 'docara.navigation', 'docara.toc', 'docara.preferences'] as $id) {
            self::assertStringNotContainsString($id, $source);
        }
        self::assertStringNotContainsString('match ($plan->smart)', $source);
    }

    public function test_goal_one_runtime_search_and_admission_sources_have_no_component_id_lists(): void
    {
        $root = dirname(__DIR__, 2);
        $componentIds = array_values(array_unique([
            ...SmartRegistry::bundled()->keys(),
            'fixture.notice',
            'variant.card',
        ]));
        sort($componentIds, SORT_STRING);
        foreach ([
            'src/Declarative/DeclarativePageCompiler.php',
            'src/Declarative/Document/DocumentParser.php',
            'src/Declarative/Rendering/SmartRenderer.php',
            'src/Declarative/Smart/CompositeSmartPlanResolver.php',
            'src/Declarative/Smart/PortableProviderPlanResolver.php',
            'src/Declarative/Smart/ProviderPlanResolverRegistry.php',
            'src/Declarative/Smart/SmartComponentGateway.php',
            'src/Declarative/Smart/SmartPlanResolver.php',
            'src/PortableSite/PortableSearchTextExtractor.php',
            'src/Framework/FrameworkConsumerPolicy.php',
            'src/Smart/Provider/SmartRegistryCompiler.php',
            'src/Smart/SmartRegistry.php',
        ] as $relative) {
            $source = (string) file_get_contents($root . '/' . $relative);
            foreach ($componentIds as $id) {
                self::assertStringNotContainsString($id, $source, $relative);
            }
            self::assertStringNotContainsString('defaultCompositeView', $source, $relative);
        }
    }

    public function test_unknown_context_adapter_fails_closed(): void
    {
        $registry = new SmartContextAdapterRegistry([new GenericPropsContextAdapter]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('SMART_CONTEXT_ADAPTER_UNKNOWN:missing.adapter');

        $registry->get('missing.adapter');
    }

    public function test_unknown_render_strategy_fails_closed(): void
    {
        $registry = new SmartRendererStrategyRegistry([new RegisteredTemplateStrategy('server-static')]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('SMART_RENDER_STRATEGY_UNKNOWN:missing-strategy');

        $registry->get('missing-strategy');
    }

    public function test_portable_slot_is_a_bounded_typed_value(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('DOCUMENT_SMART_CALL_NODE_INVALID');

        new SmartCallNode(
            'fixture-notice',
            'fixture.notice',
            'default',
            [],
            1,
            new SourceSpan('content/fixture.md', 1, 1),
            '../outside',
        );
    }

    public function test_invocation_is_normalized_from_provenance_without_component_dispatch(): void
    {
        $invocation = SmartInvocation::fromPlan(new ResolvedSmartPlan(
            'local-notice',
            'fixture.notice',
            'default',
            'smart.fixture.notice.default',
            ['title' => 'Portable'],
            [],
            [
                'portable_strategy' => 'server-static',
                'input_adapter' => 'smart.props',
                'template_abi' => 'sf5.smart.template.v1',
                'preset' => 'compact',
            ],
        ));

        self::assertSame('fixture.notice', $invocation->smart);
        self::assertSame('server-static', $invocation->strategy);
        self::assertSame('smart.props', $invocation->adapter);
        self::assertSame('sf5.smart.template.v1', $invocation->templateAbi);
        self::assertSame('compact', $invocation->preset);
    }
}
