<?php

declare(strict_types=1);

namespace Simai\Docara\Preferences;

final readonly class ReaderPreferenceDefinition
{
    /**
     * @param  list<string>  $values
     * @param  array<string, string>  $optionTitleKeys
     * @param  array<string, string>  $optionDescriptionKeys
     */
    public function __construct(
        public string $id,
        public string $group,
        public string $control,
        public array $values,
        public string $effect,
        public string $applyPhase,
        public string $storageScope,
        public array $optionTitleKeys,
        public array $optionDescriptionKeys,
    ) {}
}
