<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Smart;

use Simai\Docara\Declarative\Document\SmartCallNode;
use Simai\Docara\Declarative\Plan\ResolvedSmartPlan;
use Simai\Docara\Declarative\Rendering\RenderArtifact;
use Simai\Docara\Document\ComponentBlockNode;
use Simai\Docara\Document\ComponentContractNode;
use Simai\Docara\Document\ComponentNode;
use Simai\Docara\Document\ContentComponentRenderer;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\Smart\SmartRegistry;

final readonly class SmartComponentGateway
{
    public function __construct(
        private SmartRegistry $smarts = new SmartRegistry([]),
        private ProviderPlanResolverRegistry $resolvers = new ProviderPlanResolverRegistry([]),
        private ContentComponentRenderer $content = new ContentComponentRenderer,
    ) {}

    /** @param array<string, mixed> $frameworkLock */
    public static function bundled(array $frameworkLock): self
    {
        $smarts = SmartRegistry::bundled();

        return new self($smarts, new ProviderPlanResolverRegistry([
            new DocaraProviderPlanResolver(new CompositeSmartPlanResolver(smarts: $smarts)),
            new FrameworkProviderPlanResolver(SmartPlanResolver::fromLock($frameworkLock)),
        ]));
    }

    public static function content(): self
    {
        $smarts = SmartRegistry::bundled();

        return new self($smarts, new ProviderPlanResolverRegistry([
            new DocaraProviderPlanResolver(new CompositeSmartPlanResolver(smarts: $smarts)),
        ]));
    }

    /** @param array<string,mixed> $frameworkLock */
    public static function withProject(SmartRegistry $smarts, string $projectProviderId, array $frameworkLock): self
    {
        return new self($smarts, new ProviderPlanResolverRegistry([
            new PortableProviderPlanResolver($projectProviderId, $smarts),
            new DocaraProviderPlanResolver(new CompositeSmartPlanResolver(smarts: $smarts)),
            new FrameworkProviderPlanResolver(SmartPlanResolver::fromLock($frameworkLock)),
        ]));
    }

    public function resolve(SmartCallNode $call): ResolvedSmartPlan
    {
        $definition = $this->smarts->definition($call->smart);

        return $this->resolvers->get($definition->providerId)->resolve($call);
    }

    public function renderComponentContract(ComponentContractNode $component, ?string $bodyHtml = null): RenderArtifact
    {
        if ($component instanceof ComponentNode) {
            if ($bodyHtml !== null) {
                throw new PortableConfigurationException(
                    'CONTENT_COMPONENT_INLINE_BODY_FORBIDDEN',
                    "Inline component [{$component->component()}] cannot receive a document body at [{$component->location()->label()}].",
                );
            }

            return $this->content->render($component);
        }
        if ($component instanceof ComponentBlockNode && $bodyHtml !== null) {
            return $this->content->renderBlock($component, $bodyHtml);
        }

        throw new PortableConfigurationException(
            'CONTENT_COMPONENT_DOCUMENT_BODY_REQUIRED',
            "Block component [{$component->component()}] requires a rendered document body at [{$component->location()->label()}].",
        );
    }
}
