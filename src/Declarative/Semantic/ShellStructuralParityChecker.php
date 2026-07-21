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
        $expected = [];
        $actual = [];
        foreach ([
            'header' => ['key' => 'branding', 'smart' => 'docara.brand', 'prop' => 'branding'],
            'sidebar' => ['key' => 'navigation', 'smart' => 'docara.navigation', 'prop' => 'items'],
            'outline' => ['key' => 'outline', 'smart' => 'docara.toc', 'prop' => 'items'],
        ] as $region => $binding) {
            if (! $plan->layout->regions[$region]->enabled) {
                continue;
            }
            $expected[$binding['key']] = $context->toArray()[$binding['key']];
            $actual[$binding['key']] = $this->props(
                $plan,
                $region,
                $binding['smart'],
            )[$binding['prop']] ?? null;
        }
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
