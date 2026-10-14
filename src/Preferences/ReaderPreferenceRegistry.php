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

    public function get(string $id): ReaderPreferenceDefinition
    {
        return $this->definitions[$id]
            ?? throw new \InvalidArgumentException('READER_PREFERENCE_UNKNOWN:' . $id);
    }
}
