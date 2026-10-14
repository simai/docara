<?php

declare(strict_types=1);

namespace Simai\Docara\Smart\Runtime\Strategy;

use Simai\Docara\Declarative\Rendering\TrustedTemplateRegistry;
use Simai\Docara\Smart\Runtime\SmartInvocation;
use Simai\Docara\Smart\Runtime\SmartTemplateContext;

final readonly class RegisteredTemplateStrategy implements SmartRendererStrategy
{
    public function __construct(private string $strategy) {}

    public function id(): string
    {
        return $this->strategy;
    }

    public function render(SmartInvocation $invocation, SmartTemplateContext $context, TrustedTemplateRegistry $templates): string
    {
        return match ($invocation->templateAbi) {
            'sf5.smart.template.v1' => $templates->renderPortable(
                $invocation->template,
                $context->portableVariables(),
            ),
            'docara.legacy.object-view.v1' => $templates->render(
                $invocation->template,
                $context->legacyVariables(),
            ),
            default => throw new \InvalidArgumentException(
                'SMART_TEMPLATE_ABI_UNKNOWN:' . $invocation->templateAbi,
            ),
        };
    }
}
