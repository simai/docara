<?php

declare(strict_types=1);

namespace Simai\Docara\Preferences;

use Simai\Docara\Portable\PortableConfigurationException;

final readonly class ReaderPreferenceCompiler
{
    private ReaderPreferenceRegistry $registry;

    public function __construct(
        ?ReaderPreferenceRegistry $registry = null,
    ) {
        $this->registry = $registry ?? ReaderPreferenceRegistry::bundled();
    }

    /** @return array{enabled:bool,view:string,storage_key:string,schema:int,groups:list<array<string,mixed>>,values:array<string,string>} */
    public function compile(
        array $configuration,
        array $authoredValues,
        array $copy,
        string $storageKey,
    ): array {
        $configuration = $configuration !== []
            ? $configuration
            : self::defaultConfiguration();
        $enabled = ($configuration['enabled'] ?? false) === true;
        $view = (string) ($configuration['view'] ?? 'side-panel');
        if ($view !== 'side-panel') {
            throw new PortableConfigurationException(
                'READER_PREFERENCES_VIEW_INVALID',
                "Reader preferences view [$view] is not supported.",
            );
        }

        $groups = [];
        $seenGroups = [];
        $seenFields = [];
        foreach ($configuration['groups'] ?? [] as $group) {
            if (! is_array($group) || ! is_string($group['id'] ?? null) || ! is_array($group['fields'] ?? null)) {
                throw new PortableConfigurationException(
                    'READER_PREFERENCES_GROUP_INVALID',
                    'Reader preference groups must contain an id and a field list.',
                );
            }
            $groupId = $group['id'];
            if (isset($seenGroups[$groupId])) {
                throw new PortableConfigurationException(
                    'READER_PREFERENCES_GROUP_DUPLICATE',
                    "Reader preference group [$groupId] is duplicated.",
                );
            }
            $seenGroups[$groupId] = true;
            $fields = [];
            foreach ($group['fields'] as $fieldId) {
                if (! is_string($fieldId)) {
                    throw new PortableConfigurationException(
                        'READER_PREFERENCES_FIELD_INVALID',
                        'Reader preference field ids must be strings.',
                    );
                }
                if (isset($seenFields[$fieldId])) {
                    throw new PortableConfigurationException(
                        'READER_PREFERENCES_FIELD_DUPLICATE',
                        "Reader preference field [$fieldId] is projected more than once.",
                    );
                }
                try {
                    $definition = $this->registry->get($fieldId);
                } catch (\InvalidArgumentException $exception) {
                    throw new PortableConfigurationException(
                        'READER_PREFERENCES_FIELD_UNKNOWN',
                        "Reader preference field [$fieldId] is not registered.",
                        $exception,
                    );
                }
                if ($definition->group !== $groupId) {
                    throw new PortableConfigurationException(
                        'READER_PREFERENCES_FIELD_GROUP_MISMATCH',
                        "Reader preference field [$fieldId] does not belong to group [$groupId].",
                    );
                }
                $seenFields[$fieldId] = true;
                $configured = (string) ($authoredValues[$fieldId] ?? '');
                if (! in_array($configured, $definition->values, true)) {
                    throw new PortableConfigurationException(
                        'READER_PREFERENCES_AUTHORED_VALUE_INVALID',
                        "Authored value [$configured] is invalid for reader preference [$fieldId].",
                    );
                }
                $options = [];
                if (! is_string($copy[$definition->titleKey] ?? null)
                    || ! is_string($copy[$definition->descriptionKey] ?? null)
                ) {
                    throw new PortableConfigurationException(
                        'READER_PREFERENCES_COPY_MISSING',
                        "Reader preference field copy is missing for [$fieldId].",
                    );
                }
                foreach ($definition->values as $value) {
                    $titleKey = $definition->optionTitleKeys[$value] ?? '';
                    $descriptionKey = $definition->optionDescriptionKeys[$value] ?? '';
                    if (! is_string($copy[$titleKey] ?? null) || ! is_string($copy[$descriptionKey] ?? null)) {
                        throw new PortableConfigurationException(
                            'READER_PREFERENCES_COPY_MISSING',
                            "Reader preference copy is missing for [$fieldId:$value].",
                        );
                    }
                    $options[] = [
                        'value' => $value,
                        'title' => $copy[$titleKey],
                        'description' => $copy[$descriptionKey],
                    ];
                }
                $fields[] = [
                    'id' => $definition->id,
                    'title' => $copy[$definition->titleKey],
                    'description' => $copy[$definition->descriptionKey],
                    'control' => $definition->control,
                    'values' => $definition->values,
                    'configured' => $configured,
                    'effect' => $definition->effect,
                    'apply_phase' => $definition->applyPhase,
                    'storage_scope' => $definition->storageScope,
                    'options' => $options,
                ];
            }
            $groups[] = [
                'id' => $groupId,
                'title' => (string) ($copy['reader.' . $groupId] ?? $groupId),
                'description' => (string) ($copy['reader.' . $groupId . '_description'] ?? $copy['reader.help'] ?? ''),
                'fields' => $fields,
            ];
        }

        return [
            'enabled' => $enabled,
            'view' => $view,
            'storage_key' => $storageKey,
            'schema' => 1,
            'groups' => $groups,
            'values' => $authoredValues,
        ];
    }

    /** @return array{enabled:bool,view:string,groups:list<array{id:string,fields:list<string>}>} */
    public static function defaultConfiguration(): array
    {
        return [
            'enabled' => true,
            'view' => 'side-panel',
            'groups' => [
                ['id' => 'appearance', 'fields' => ['appearance.theme', 'appearance.modal_blur']],
            ],
        ];
    }

    public static function storageKey(array $configuration): string
    {
        $scope = (string) ($configuration['base_url'] ?? '/');

        return 'docara.preferences.' . substr(hash('sha256', $scope), 0, 16) . '.v1';
    }
}
