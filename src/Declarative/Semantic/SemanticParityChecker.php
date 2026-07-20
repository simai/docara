<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Semantic;

use Simai\Docara\Declarative\DeclarativePageResult;
use Simai\Docara\Portable\PortableConfigurationException;

final class SemanticParityChecker
{
    /**
     * @param  list<array<string, mixed>>  $legacySmartCalls
     */
    public function assertEquivalent(
        string $title,
        string $legacyContentHtml,
        array $legacySmartCalls,
        DeclarativePageResult $declarative,
    ): SemanticParityResult {
        $regions = ['header', 'sidebar', 'main', 'outline', 'footer'];
        $legacy = SemanticPageProjection::fromHtml(
            $title,
            $legacyContentHtml,
            $regions,
            $legacySmartCalls,
        );
        $smart = [];
        foreach ($declarative->plan->semanticProjection()['smart'] as $call) {
            $smart[] = [
                'id' => $call['smart'],
                'view' => $call['view'],
                'props' => $call['props'],
            ];
        }
        $current = SemanticPageProjection::fromHtml(
            $declarative->plan->title,
            $declarative->artifact->html,
            array_keys($declarative->plan->regions),
            $smart,
        );
        $result = new SemanticParityResult(
            $legacy->toArray() === $current->toArray(),
            $legacy->toArray(),
            $current->toArray(),
        );
        if (! $result->passed) {
            throw new PortableConfigurationException(
                'DECLARATIVE_SEMANTIC_PARITY_FAILED',
                'Legacy and declarative page semantics differ.',
            );
        }

        return $result;
    }
}
