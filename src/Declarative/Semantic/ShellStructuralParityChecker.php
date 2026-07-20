<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Semantic;

use Simai\Docara\Declarative\Composition\PageCompositionContext;
use Simai\Docara\Declarative\Plan\ResolvedRenderPlan;
use Simai\Docara\Declarative\Plan\ResolvedSmartPlan;
use Simai\Docara\Portable\PortableConfigurationException;

final class ShellStructuralParityChecker
{
    public function assertEquivalent(
        PageCompositionContext $context,
        ResolvedRenderPlan $plan,
    ): ShellStructuralParityResult {
        $expected = $context->toArray();
        $actual = [
            'branding' => $this->props($plan, 'header', 'docara.header')['branding'] ?? null,
            'navigation' => $this->props($plan, 'sidebar', 'docara.navigation')['items'] ?? null,
            'outline' => $this->props($plan, 'outline', 'docara.outline')['items'] ?? null,
        ];
        $result = new ShellStructuralParityResult($expected === $actual, $expected, $actual);
        if (! $result->passed) {
            throw new PortableConfigurationException(
                'DECLARATIVE_SHELL_STRUCTURAL_PARITY_FAILED',
                'Builder shell data and declarative shell Smart props differ.',
            );
        }

        return $result;
    }

    /** @return array<string, mixed> */
    private function props(ResolvedRenderPlan $plan, string $region, string $smart): array
    {
        $matches = [];
        foreach ($plan->regions[$region] ?? [] as $section) {
            foreach ($section->blocks as $block) {
                if ($block->smart instanceof ResolvedSmartPlan && $block->smart->smart === $smart) {
                    $matches[] = $block->smart->props;
                }
            }
        }
        if (count($matches) !== 1) {
            throw new PortableConfigurationException(
                'DECLARATIVE_SHELL_SMART_REQUIRED',
                "Region [$region] must contain exactly one [$smart] Smart plan.",
            );
        }

        return $matches[0];
    }
}
