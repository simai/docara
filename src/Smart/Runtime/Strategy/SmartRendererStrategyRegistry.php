<?php

declare(strict_types=1);

namespace Simai\Docara\Smart\Runtime\Strategy;

final readonly class SmartRendererStrategyRegistry
{
    /** @var array<string, SmartRendererStrategy> */
    private array $strategies;

    /** @param iterable<SmartRendererStrategy> $strategies */
    public function __construct(iterable $strategies)
    {
        $indexed = [];
        foreach ($strategies as $strategy) {
            if (isset($indexed[$strategy->id()])) {
                throw new \LogicException('SMART_RENDER_STRATEGY_DUPLICATE:' . $strategy->id());
            }
            $indexed[$strategy->id()] = $strategy;
        }
        ksort($indexed, SORT_STRING);
        $this->strategies = $indexed;
    }

    public static function bundled(): self
    {
        return new self([
            new RegisteredTemplateStrategy('server-static'),
            new RegisteredTemplateStrategy('server-first-hydratable'),
            new RegisteredTemplateStrategy('client-owned'),
            new RegisteredTemplateStrategy('shadow-dom-owned'),
        ]);
    }

    public function get(string $id): SmartRendererStrategy
    {
        return $this->strategies[$id]
            ?? throw new \InvalidArgumentException('SMART_RENDER_STRATEGY_UNKNOWN:' . $id);
    }
}
