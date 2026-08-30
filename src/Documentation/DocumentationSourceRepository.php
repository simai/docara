<?php

declare(strict_types=1);

namespace Simai\Docara\Documentation;

use JsonException;
use Simai\Docara\Application\ProjectRuntime;
use Simai\Docara\Portable\CanonicalJson;
use Simai\Docara\Portable\FilesystemPath;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\Portable\SchemaRepository;

final class DocumentationSourceRepository
{
    /** @return list<array<string,mixed>> */
    public function all(string $root): array
    {
        $runtime = ProjectRuntime::load($root);
        $tracking = $runtime->site['documentation_tracking'] ?? null;
        if (! is_array($tracking) || ($tracking['enabled'] ?? false) !== true) {
            return [];
        }
        $sources = [];
        $seen = [];
        foreach ($tracking['sources'] as $configuration) {
            $id = (string) $configuration['id'];
            if (isset($seen[$id])) {
                throw new PortableConfigurationException('DOCUMENTATION_SOURCE_DUPLICATE', "Documentation source [$id] is configured more than once.");
            }
            $seen[$id] = true;
            $source = match ($configuration['provider']) {
                'contract_json' => $this->jsonContract($runtime->root, $configuration),
                'simai_framework' => $this->frameworkContract($runtime->root, $configuration),
                default => throw new PortableConfigurationException('DOCUMENTATION_PROVIDER_UNKNOWN', "Unknown documentation provider [{$configuration['provider']}]."),
            };
            $this->assertUniqueEntities($source);
            $sources[] = $source;
        }
        usort($sources, static fn (array $a, array $b): int => strcmp($a['id'], $b['id']));

        return $sources;
    }

    /** @return array<string,mixed> */
    public function source(string $root, string $id): array
    {
        foreach ($this->all($root) as $source) {
            if ($source['id'] === $id) {
                return $source;
            }
        }
        throw new PortableConfigurationException('DOCUMENTATION_SOURCE_UNKNOWN', "Documentation source [$id] is not configured.");
    }

    /** @return array<string,mixed> */
    public function entity(string $root, string $sourceId, string $key): array
    {
        foreach ($this->source($root, $sourceId)['entities'] as $entity) {
            if ($entity['key'] === $key) {
                return $entity;
            }
        }
        throw new PortableConfigurationException('DOCUMENTATION_ENTITY_UNKNOWN', "Documentation entity [$sourceId:$key] is unknown.");
    }

    /** @param array<string,mixed> $configuration @return array<string,mixed> */
    private function jsonContract(string $root, array $configuration): array
    {
        $contract = $this->decode($root, (string) $configuration['file'], 'DOCUMENTATION_SOURCE_CONTRACT_INVALID');
        (new SchemaRepository)->assertValid($contract, 'documentation-source.schema.json');
        if ($contract['id'] !== $configuration['id']) {
            throw new PortableConfigurationException('DOCUMENTATION_SOURCE_ID_MISMATCH', 'Configured source id differs from its contract.');
        }

        return $this->finalize($contract, (string) $configuration['file'], false);
    }

    /** @param array<string,mixed> $configuration @return array<string,mixed> */
    private function frameworkContract(string $root, array $configuration): array
    {
        $lockPath = (string) $configuration['framework_lock'];
        $lock = $this->decode($root, $lockPath, 'DOCUMENTATION_FRAMEWORK_LOCK_INVALID');
        (new SchemaRepository)->assertValid($lock, 'framework-lock.schema.json');
        $runtime = $lock['runtime'];
        $documentationPointer = $runtime['framework_registry']['documentation_source'] ?? null;
        if (is_array($documentationPointer)) {
            $contractPath = (string) $documentationPointer['relative_path'];
            $absolute = $this->regularFile($root, $contractPath, 'DOCUMENTATION_SOURCE_CONTRACT_INVALID');
            if (! hash_equals((string) $documentationPointer['file_sha256'], hash_file('sha256', $absolute) ?: '')) {
                throw new PortableConfigurationException('DOCUMENTATION_SOURCE_HASH_MISMATCH', 'Framework documentation source does not match its pinned SHA-256.');
            }
            $contract = $this->decode($root, $contractPath, 'DOCUMENTATION_SOURCE_CONTRACT_INVALID');
            (new SchemaRepository)->assertValid($contract, 'documentation-source.schema.json');
            if ($contract['id'] !== $configuration['id'] || $contract['provider'] !== 'simai_framework') {
                throw new PortableConfigurationException('DOCUMENTATION_SOURCE_ID_MISMATCH', 'Framework documentation source identity differs from its configuration.');
            }

            return $this->finalize($contract, $contractPath, false);
        }
        $uiCommit = (string) $runtime['ui']['commit'];
        $ruleRelative = 'resources/portable/vendor/simai-framework/runtime/' . $uiCommit . '/distr/rule/rule.json';
        $rulePath = dirname(__DIR__, 2) . '/' . $ruleRelative;
        if (! is_file($rulePath) || is_link($rulePath)) {
            throw new PortableConfigurationException('DOCUMENTATION_FRAMEWORK_RULES_MISSING', "Pinned Framework rules [$uiCommit] are not bundled with Docara.");
        }
        $rules = json_decode((string) file_get_contents($rulePath), true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($rules)) {
            throw new PortableConfigurationException('DOCUMENTATION_FRAMEWORK_RULES_INVALID', 'Pinned Framework rules must be a JSON array.');
        }
        $groups = [];
        foreach ($rules as $rule) {
            if (! is_array($rule) || ! is_string($rule['name'] ?? null)) {
                throw new PortableConfigurationException('DOCUMENTATION_FRAMEWORK_RULES_INVALID', 'Every Framework rule must have a name.');
            }
            $kind = match ($rule['type'] ?? null) {
                'component' => 'component',
                'smart' => 'smart_component',
                'attribute' => 'core',
                default => 'utility',
            };
            $name = (string) $rule['name'];
            $family = $kind === 'utility' ? explode('/', $name, 2)[0] : $name;
            $keyPrefix = $kind === 'smart_component' ? 'smart' : $kind;
            $key = $keyPrefix . '.' . $this->slug($family);
            $groups[$key]['kind'] = $kind;
            $groups[$key]['title'] = $this->title($family);
            $groups[$key]['rules'][] = $this->publicRule($rule);
        }
        $typographyTag = (string) ($lock['typography_projection']['candidate'] ?? ltrim((string) $runtime['tag'], 'v'));
        $coreCss = dirname(__DIR__, 2) . '/resources/portable/vendor/simai-framework/typography/' . $typographyTag . '/core.css';
        if (is_file($coreCss) && ! is_link($coreCss)) {
            $css = (string) file_get_contents($coreCss);
            preg_match_all('/(--sf-[a-zA-Z0-9\\/_-]+)\s*:\s*([^;{}]+);/', $css, $matches, PREG_SET_ORDER);
            $tokens = [];
            foreach ($matches as $match) {
                $tokens[$match[1]] = trim($match[2]);
            }
            ksort($tokens, SORT_STRING);
            $groups['core.design-tokens'] = [
                'kind' => 'core',
                'title' => 'Design tokens',
                'rules' => [[
                    'tokens' => $tokens,
                    'semantic_radius' => [
                        '--sf-radius--ui' => ['scope' => 'compact_controls'],
                        '--sf-radius-default' => ['scope' => 'large_surfaces'],
                        'square' => ['role' => 'explicit_override'],
                        'rounded' => ['role' => 'explicit_override'],
                    ],
                ]],
            ];
        }
        ksort($groups, SORT_STRING);
        $entities = [];
        foreach ($groups as $key => $group) {
            usort($group['rules'], static fn (array $a, array $b): int => strcmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? '')));
            $entities[] = [
                'key' => $key,
                'kind' => $group['kind'],
                'title' => $group['title'],
                'public_contract' => ['rules' => $group['rules']],
                'example_cases' => $group['kind'] === 'core' ? [] : ['default'],
                'provenance' => [$lockPath, $ruleRelative],
            ];
        }
        $contract = [
            'schema' => 'docara.documentation_source.v1',
            'id' => (string) $configuration['id'],
            'provider' => 'simai_framework',
            'revision' => (string) $runtime['pair_id'],
            'entities' => $entities,
        ];
        (new SchemaRepository)->assertValid($contract, 'documentation-source.schema.json');

        return $this->finalize($contract, $lockPath, true);
    }

    /** @param array<string,mixed> $source @return array<string,mixed> */
    private function finalize(array $source, string $path, bool $compatibilityAdapter): array
    {
        foreach ($source['entities'] as &$entity) {
            $entity['source_sha256'] = hash('sha256', CanonicalJson::encode($entity['public_contract']));
        }
        unset($entity);
        usort($source['entities'], static fn (array $a, array $b): int => strcmp($a['key'], $b['key']));
        $source['path'] = $path;
        $source['contract_sha256'] = hash('sha256', CanonicalJson::encode([
            'id' => $source['id'], 'provider' => $source['provider'], 'revision' => $source['revision'], 'entities' => $source['entities'],
        ]));
        $source['compatibility_adapter'] = $compatibilityAdapter;

        return $source;
    }

    /** @param array<string,mixed> $source */
    private function assertUniqueEntities(array $source): void
    {
        $seen = [];
        foreach ($source['entities'] as $entity) {
            if (isset($seen[$entity['key']])) {
                throw new PortableConfigurationException('DOCUMENTATION_ENTITY_DUPLICATE', "Entity [{$source['id']}:{$entity['key']}] is declared more than once.");
            }
            $seen[$entity['key']] = true;
        }
    }

    /** @return array<string,mixed> */
    private function decode(string $root, string $relative, string $code): array
    {
        $path = $this->regularFile($root, $relative, $code);
        try {
            $value = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new PortableConfigurationException($code, "JSON source [$relative] is invalid.", $exception);
        }
        if (! is_array($value)) {
            throw new PortableConfigurationException($code, "JSON source [$relative] must be an object.");
        }

        return $value;
    }

    private function regularFile(string $root, string $relative, string $code): string
    {
        if ($relative === '' || str_starts_with($relative, '/') || str_contains($relative, '..') || str_contains($relative, '\\')
            || strtolower((string) pathinfo($relative, PATHINFO_EXTENSION)) !== 'json'
        ) {
            throw new PortableConfigurationException($code, "Path [$relative] must remain inside the project.");
        }
        $root = FilesystemPath::normalize((string) realpath($root));
        $segments = explode('/', $relative);
        $current = $root;
        foreach ($segments as $index => $segment) {
            $entries = scandir($current);
            if (! is_array($entries)) {
                throw new PortableConfigurationException($code, "Path [$relative] cannot be inspected safely.");
            }
            foreach ($entries as $entry) {
                if ($entry !== $segment && strcasecmp($entry, $segment) === 0) {
                    throw new PortableConfigurationException($code, "Path [$relative] conflicts by case with [$entry].");
                }
            }
            $current .= '/' . $segment;
            if ($index < count($segments) - 1 && (is_link($current) || ! is_dir($current))) {
                throw new PortableConfigurationException($code, "Path [$relative] contains an unsafe directory.");
            }
        }
        $path = $root . '/' . $relative;
        $stat = @lstat($path);
        $real = realpath($path);
        if (! is_array($stat) || $real === false || is_link($path) || ! is_file($real) || ($stat['nlink'] ?? 1) !== 1
            || ($stat['size'] ?? 10485761) > 10485760 || ! FilesystemPath::isWithin(FilesystemPath::normalize($real), $root)
        ) {
            throw new PortableConfigurationException($code, "File [$relative] is missing or unsafe.");
        }
        $contents = file_get_contents($real);
        if (! is_string($contents) || preg_match('//u', $contents) !== 1) {
            throw new PortableConfigurationException($code, "File [$relative] must be valid UTF-8.");
        }

        return $real;
    }

    /** @param array<string,mixed> $rule @return array<string,mixed> */
    private function publicRule(array $rule): array
    {
        $allowed = array_intersect_key($rule, array_flip(['name', 'type', 'regex', 'css', 'js', 'relation']));
        ksort($allowed, SORT_STRING);

        return $allowed;
    }

    private function slug(string $value): string
    {
        $value = preg_replace('/([a-z0-9])([A-Z])/', '$1-$2', $value) ?? $value;
        $value = strtolower(str_replace(['/', '_'], '-', $value));

        return preg_match('/^[a-z]/', $value) === 1 ? $value : 'item-' . $value;
    }

    private function title(string $value): string
    {
        return ucfirst(str_replace(['-', '_', '/'], ' ', $value));
    }
}
