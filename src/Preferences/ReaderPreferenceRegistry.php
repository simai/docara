<?php

declare(strict_types=1);

namespace Simai\Docara\Preferences;

final readonly class ReaderPreferenceRegistry
{
    /** @param array<string, ReaderPreferenceDefinition> $definitions */
    public function __construct(private array $definitions) {}

    public static function bundled(): self
    {
        $builder = new ReaderPreferenceRegistryBuilder;
        (new CoreReaderPreferenceContribution)->contribute($builder);

        return new self($builder->definitions());
    }

    /**
     * The bundled fields plus the ones a site brings (for example its own colour choices).
     */
    public function with(ReaderPreferenceDefinition ...$definitions): self
    {
        $all = $this->definitions;
        foreach ($definitions as $definition) {
            $all[$definition->id] = $definition;
        }
        ksort($all, SORT_STRING);

        return new self($all);
    }

    public function has(string $id): bool
    {
        return isset($this->definitions[$id]);
    }

    public function get(string $id): ReaderPreferenceDefinition
    {
        return $this->definitions[$id]
            ?? throw new \InvalidArgumentException('READER_PREFERENCE_UNKNOWN:' . $id);
    }
}
