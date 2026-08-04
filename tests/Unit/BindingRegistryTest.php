<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Simai\Docara\Declarative\Binding\BindingDescriptor;
use Simai\Docara\Declarative\Binding\BindingInvocation;
use Simai\Docara\Declarative\Binding\BindingProvider;
use Simai\Docara\Declarative\Binding\BindingRegistry;
use Simai\Docara\Declarative\Binding\BindingResolver;
use Simai\Docara\Declarative\Composition\PageCompositionContext;
use Simai\Docara\Portable\PortableConfigurationException;
use Tests\TestCase;

final class BindingRegistryTest extends TestCase
{
    #[Test]
    public function builtins_are_typed_deterministic_and_provenance_aware(): void
    {
        $first = BindingRegistry::bundled();
        $second = BindingRegistry::bundled();

        self::assertSame($first->fingerprint(), $second->fingerprint());
        self::assertSame(
            ['docara.branding', 'docara.navigation', 'docara.outline'],
            array_map(static fn (BindingDescriptor $descriptor): string => $descriptor->id, $first->all()),
        );
        $navigation = $first->get('docara.navigation');
        self::assertSame('docara.builtin-bindings', $navigation->provider);
        self::assertSame(['shell.primary-navigation', 'shell.secondary-navigation'], $navigation->capabilities);
        self::assertSame('binding-navigation-props.schema.json', $navigation->outputSchema);
        self::assertSame('docara.navigation', $navigation->provenance()['id']);
    }

    #[Test]
    public function one_navigation_binding_resolves_header_tree_and_compact_presentations(): void
    {
        $context = $this->context();
        $registry = BindingRegistry::bundled();
        foreach (['tree', 'compact'] as $view) {
            $props = $registry->resolve(
                'docara.navigation',
                new BindingInvocation('docara.navigation', $view, ['maximum_depth' => 4], '@test'),
                $context,
            );
            self::assertSame('Sections', $props['label']);
            self::assertSame('docs', $props['items'][0]['key']);
        }

        $header = $registry->resolve(
            'docara.navigation',
            new BindingInvocation('docara.navigation', 'header', ['maximum_depth' => 4], '@test'),
            $context,
        );
        self::assertSame('Primary navigation', $header['label']);
        self::assertSame('home', $header['items'][0]['key']);
    }

    #[Test]
    public function binding_owned_prop_spoofing_and_target_mismatch_fail_closed(): void
    {
        $registry = BindingRegistry::bundled();
        foreach ([
            new BindingInvocation('docara.navigation', 'tree', ['items' => [], 'maximum_depth' => 4], '@test'),
            new BindingInvocation('ui.button', 'tree', ['maximum_depth' => 4], '@test'),
        ] as $invocation) {
            try {
                $registry->resolve('docara.navigation', $invocation, $this->context());
                self::fail('Unsafe binding invocation unexpectedly passed.');
            } catch (PortableConfigurationException $exception) {
                self::assertContains($exception->errorCode, ['BINDING_OWNED_PROP_COLLISION', 'BINDING_SMART_MISMATCH']);
            }
        }
    }

    #[Test]
    public function duplicate_provider_namespace_and_binding_are_rejected(): void
    {
        $provider = $this->provider('package.one', 'acme');
        foreach ([
            [$provider, $provider],
            [$provider, $this->provider('package.two', 'acme')],
        ] as $providers) {
            try {
                new BindingRegistry($providers);
                self::fail('Conflicting provider set unexpectedly passed.');
            } catch (PortableConfigurationException $exception) {
                self::assertContains($exception->errorCode, ['BINDING_PROVIDER_DUPLICATE', 'BINDING_NAMESPACE_COLLISION']);
            }
        }
    }

    private function context(): PageCompositionContext
    {
        return PageCompositionContext::fromBuilder(
            ['title' => 'Docara'],
            '/',
            [[
                'key' => 'docs',
                'title' => 'Docs',
                'url' => '/docs/',
                'active' => true,
                'active_ancestor' => false,
                'current_section' => false,
                'open' => true,
                'children' => [],
            ]],
            [],
            [],
            [
                'enabled' => true,
                'items' => [['id' => 'home', 'label' => 'Home', 'href' => '/']],
            ],
            '/',
        );
    }

    private function provider(string $id, string $namespace): BindingProvider
    {
        return new class($id, $namespace) implements BindingProvider
        {
            public function __construct(private string $providerId, private string $namespace) {}

            public function id(): string
            {
                return $this->providerId;
            }

            public function revision(): string
            {
                return 'fixture-v1';
            }

            public function priority(): int
            {
                return 10;
            }

            public function namespaces(): array
            {
                return [$this->namespace];
            }

            public function descriptors(): array
            {
                return [new BindingDescriptor(
                    $this->namespace . '.fixture',
                    $this->namespace,
                    $this->providerId,
                    'fixture-v1',
                    ['shell.footer'],
                    'project.notice',
                    ['default'],
                    ['label'],
                    'binding-outline-props.schema.json',
                    '@fixture',
                    str_repeat('a', 64),
                    new class implements BindingResolver
                    {
                        public function resolve(BindingInvocation $invocation, PageCompositionContext $context): array
                        {
                            return ['items' => [], 'label' => 'Fixture'];
                        }
                    },
                    [],
                )];
            }
        };
    }
}
