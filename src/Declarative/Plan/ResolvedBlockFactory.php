<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Plan;

use Simai\Docara\Declarative\Definition\DefinitionRepository;
use Simai\Docara\Portable\PortableConfigurationException;

final readonly class ResolvedBlockFactory
{
    public function __construct(private DefinitionRepository $definitions) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $section
     */
    public function create(
        string $id,
        string $key,
        string $slot,
        array $data,
        ?ResolvedSmartPlan $smart,
        array $section,
    ): ResolvedBlockPlan {
        if (! in_array($key, $section['allowed_blocks'], true)) {
            throw new PortableConfigurationException(
                'DECLARATIVE_BLOCK_SECTION_FORBIDDEN',
                "Block [$key] is not allowed in section [{$section['key']}].",
            );
        }
        if (! in_array($slot, $section['slots'], true)) {
            throw new PortableConfigurationException(
                'DECLARATIVE_BLOCK_SLOT_FORBIDDEN',
                "Block [$key] cannot target slot [$slot] in section [{$section['key']}].",
            );
        }
        $definition = $this->definitions->block($key);
        if ($smart !== null && ! in_array($smart->smart, $definition['allowed_smart'], true)) {
            throw new PortableConfigurationException(
                'DECLARATIVE_BLOCK_SMART_FORBIDDEN',
                "Smart component [{$smart->smart}] is not allowed by block [$key].",
            );
        }

        return new ResolvedBlockPlan(
            $id,
            $key,
            $slot,
            (string) $definition['renderer'],
            $data,
            $smart,
            [
                'definition' => (string) $definition['_source'],
                'sha256' => (string) $definition['_sha256'],
            ],
        );
    }
}
