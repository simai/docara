<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Binding;

use Simai\Docara\Declarative\Composition\PageCompositionContext;
use Simai\Docara\Portable\CanonicalJson;

final class BuiltinBindingProvider implements BindingProvider
{
    public function id(): string
    {
        return 'docara.builtin-bindings';
    }

    public function revision(): string
    {
        return 'shell-binding-v1';
    }

    public function priority(): int
    {
        return 100;
    }

    public function namespaces(): array
    {
        return ['docara'];
    }

    public function descriptors(): array
    {
        return [
            $this->descriptor(
                'docara.branding',
                ['shell.brand'],
                'docara.brand',
                ['default', 'compact', 'logo', 'text'],
                ['branding', 'preset'],
                'binding-branding-props.schema.json',
                new class implements BindingResolver
                {
                    public function resolve(BindingInvocation $invocation, PageCompositionContext $context): array
                    {
                        return [
                            'branding' => $context->branding,
                            'preset' => $context->branding['mode'] === 'full' ? 'default' : $context->branding['mode'],
                        ];
                    }
                },
                ['branding'],
            ),
            $this->descriptor(
                'docara.navigation',
                ['shell.primary-navigation', 'shell.secondary-navigation'],
                'docara.navigation',
                ['default', 'header', 'tree', 'compact'],
                ['items', 'label', 'expand_label', 'collapse_label', 'contains_current_label'],
                'binding-navigation-props.schema.json',
                new class implements BindingResolver
                {
                    public function resolve(BindingInvocation $invocation, PageCompositionContext $context): array
                    {
                        $header = $invocation->view === 'header';

                        return [
                            'items' => $header ? $context->headerNavigation : $context->navigation,
                            'label' => $header ? $context->headerNavigationLabel : $context->navigationCopy['label'],
                            'expand_label' => $context->navigationCopy['expand'],
                            'collapse_label' => $context->navigationCopy['collapse'],
                            'contains_current_label' => $context->navigationCopy['contains_current'],
                        ];
                    }
                },
                ['navigation', 'header_navigation'],
            ),
            $this->descriptor(
                'docara.outline',
                ['shell.outline'],
                'docara.toc',
                ['default'],
                ['items', 'label'],
                'binding-outline-props.schema.json',
                new class implements BindingResolver
                {
                    public function resolve(BindingInvocation $invocation, PageCompositionContext $context): array
                    {
                        return ['items' => $context->outline, 'label' => $context->tocLabel];
                    }
                },
                ['outline'],
            ),
        ];
    }

    /** @param list<string> $capabilities @param list<string> $presentations @param list<string> $ownedProps */
    private function descriptor(
        string $id,
        array $capabilities,
        string $smart,
        array $presentations,
        array $ownedProps,
        string $outputSchema,
        BindingResolver $resolver,
        array $storageCompatibilityAliases,
    ): BindingDescriptor {
        $contract = [
            'id' => $id,
            'capabilities' => $capabilities,
            'smart' => $smart,
            'presentations' => $presentations,
            'owned_props' => $ownedProps,
            'output_schema' => $outputSchema,
        ];

        return new BindingDescriptor(
            $id,
            'docara',
            $this->id(),
            $this->revision(),
            $capabilities,
            $smart,
            $presentations,
            $ownedProps,
            $outputSchema,
            '@package:bindings/' . $id,
            hash('sha256', CanonicalJson::encode($contract)),
            $resolver,
            $storageCompatibilityAliases,
        );
    }
}
