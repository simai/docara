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

final readonly class SmartComponentGateway
{
    public function __construct(
        private ?SmartPlanResolver $framework = null,
        private CompositeSmartPlanResolver $product = new CompositeSmartPlanResolver,
        private ContentComponentRenderer $content = new ContentComponentRenderer,
    ) {}

    /** @param array<string, mixed> $frameworkLock */
    public static function bundled(array $frameworkLock): self
    {
        return new self(SmartPlanResolver::fromLock($frameworkLock));
    }

    public static function content(): self
    {
        return new self;
    }

    public function resolve(SmartCallNode $call): ResolvedSmartPlan
    {
        if (str_starts_with($call->smart, 'ui.')) {
            if (! $this->framework instanceof SmartPlanResolver) {
                throw new PortableConfigurationException(
                    'DECLARATIVE_FRAMEWORK_GATEWAY_UNAVAILABLE',
                    'The Framework Smart resolver is unavailable in this content-only gateway.',
                );
            }

            return $this->framework->resolve($call);
        }
        if (str_starts_with($call->smart, 'docara.')) {
            return $this->product->resolve(
                $call->smart,
                $call->id(),
                $call->props,
                $call->view,
            );
        }

        throw new PortableConfigurationException(
            'DECLARATIVE_SMART_NAMESPACE_UNSUPPORTED',
            "Smart component namespace [{$call->smart}] is unsupported.",
        );
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
