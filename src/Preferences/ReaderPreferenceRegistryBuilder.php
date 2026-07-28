<?php

declare(strict_types=1);

namespace Simai\Docara\Preferences;

final class ReaderPreferenceRegistryBuilder
{
    /** @var array<string, ReaderPreferenceDefinition> */
    private array $definitions = [];

    public function add(ReaderPreferenceDefinition $definition): void
    {
        if (isset($this->definitions[$definition->id])) {
            throw new \LogicException('READER_PREFERENCE_DUPLICATE:' . $definition->id);
        }

        $this->definitions[$definition->id] = $definition;
    }

    /** @return array<string, ReaderPreferenceDefinition> */
    public function definitions(): array
    {
        ksort($this->definitions, SORT_STRING);

        return $this->definitions;
    }
}
