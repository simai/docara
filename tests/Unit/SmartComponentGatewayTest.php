<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Simai\Docara\Declarative\Document\SmartCallNode;
use Simai\Docara\Declarative\Document\SourceSpan;
use Simai\Docara\Declarative\Smart\SmartComponentGateway;

final class SmartComponentGatewayTest extends TestCase
{
    public function test_it_routes_framework_and_product_components_through_one_gateway(): void
    {
        $gateway = SmartComponentGateway::bundled($this->frameworkLock());

        $framework = $gateway->resolve(new SmartCallNode(
            'framework-alert',
            'ui.alert',
            'default',
            [
                'type' => 'info',
                'title' => 'Framework',
                'supporting-text' => 'Rendered by SIMAI Framework.',
            ],
            1,
            new SourceSpan('content/gateway.md', 1, 3),
        ));
        $product = $gateway->resolve(new SmartCallNode(
            'product-toc',
            'docara.toc',
            'default',
            [
                'items' => [],
                'label' => 'Contents',
            ],
            2,
            new SourceSpan('content/gateway.md', 5, 7),
        ));

        self::assertSame('ui.alert', $framework->smart);
        self::assertSame('docara.toc', $product->smart);
        self::assertSame('simai/docara', $product->provenance['provider']);
        self::assertContains('docara.smart.toc.css', $product->assets);
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
