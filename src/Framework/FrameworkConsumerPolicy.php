<?php

declare(strict_types=1);

namespace Simai\Docara\Framework;

final readonly class FrameworkConsumerPolicy
{
    /**
     * These records only narrow exact manifests. They never admit a component
     * or add a prop, state, asset, event, renderer, or readiness claim.
     *
     * @var array<string, array{
     *     managed: array<string, string>,
     *     blocked: list<array{prop: string, value: mixed, code: string}>,
     *     omitted_assets: array<string, string>,
     *     excluded_states: array<string, array{prop: string, value: mixed}>
     * }>
     */
    private array $policies;

    /** @param null|array<string, array<string, mixed>> $policies */
    public function __construct(?array $policies = null)
    {
        if ($policies === null) {
            $lock = FrameworkLock::fromJsonFile(
                dirname(__DIR__, 2) . '/stubs/portable/simai-framework.lock.json',
            );
            $policies = $lock->consumerPolicies();
        }
        ksort($policies, SORT_STRING);
        foreach ($policies as $component => $policy) {
            if (! is_string($component) || ! is_array($policy)) {
                throw new FrameworkComponentException(
                    'FRAMEWORK_CONSUMER_POLICY_INVALID',
                    (string) $component,
                );
            }
            $this->assertPolicyShape($component, $policy);
        }
        $this->policies = $policies;
    }

    public static function fromLock(FrameworkLock $lock): self
    {
        return new self($lock->consumerPolicies());
    }

    /** @param array<string, mixed> $manifest */
    public function assertNarrowing(string $component, array $manifest): void
    {
        $properties = $manifest['props']['properties'] ?? null;
        if (! is_array($properties)) {
            throw new FrameworkComponentException('FRAMEWORK_MANIFEST_INVALID', $component);
        }
        $policy = $this->policy($component);
        foreach (array_keys($policy['managed']) as $prop) {
            if (! array_key_exists($prop, $properties)) {
                throw new FrameworkComponentException('FRAMEWORK_CONSUMER_POLICY_WIDENS_MANIFEST', $component . ':' . $prop);
            }
        }
        foreach ($policy['blocked'] as $blocked) {
            if (! array_key_exists($blocked['prop'], $properties)) {
                throw new FrameworkComponentException(
                    'FRAMEWORK_CONSUMER_POLICY_WIDENS_MANIFEST',
                    $component . ':' . $blocked['prop'],
                );
            }
            (new FrameworkManifestContract)->assertPropertyValue(
                $component,
                $manifest,
                $blocked['prop'],
                $blocked['value'],
                'FRAMEWORK_CONSUMER_POLICY_INVALID',
            );
        }
        $manifestAssets = [];
        foreach (is_array($manifest['assets'] ?? null) ? $manifest['assets'] : [] as $asset) {
            if (is_array($asset) && is_string($asset['key'] ?? null)) {
                $manifestAssets[$asset['key']] = $asset;
            }
        }
        foreach ($policy['omitted_assets'] as $assetKey => $reason) {
            if (! isset($manifestAssets[$assetKey])
                || ($manifestAssets[$assetKey]['critical'] ?? null) !== true
                || trim($reason) === ''
            ) {
                throw new FrameworkComponentException(
                    'FRAMEWORK_CONSUMER_POLICY_WIDENS_MANIFEST',
                    $component . ':' . $assetKey,
                );
            }
        }
        $manifestStates = $manifest['atlas']['states'] ?? null;
        if (! is_array($manifestStates) || ! array_is_list($manifestStates)) {
            throw new FrameworkComponentException(
                'FRAMEWORK_CONSUMER_POLICY_WIDENS_MANIFEST',
                $component . ':states',
            );
        }
        $this->admittedStates($component, $manifestStates);
    }

    /** @param array<string, mixed> $authorProps */
    public function assertAuthorProps(string $component, array $authorProps): void
    {
        foreach (array_keys($this->policy($component)['managed']) as $prop) {
            if (array_key_exists($prop, $authorProps)) {
                throw new FrameworkComponentException(
                    'FRAMEWORK_PROP_MANAGED',
                    "$component:$prop is generated deterministically by Docara",
                );
            }
        }
    }

    /**
     * @param  array<string, mixed>  $props
     * @return array<string, mixed>
     */
    public function apply(string $component, array $props, string $pagePath, int $ordinal): array
    {
        $policy = $this->policy($component);
        foreach ($policy['managed'] as $prop => $strategy) {
            if ($strategy !== 'deterministic_id') {
                throw new FrameworkComponentException('FRAMEWORK_CONSUMER_POLICY_INVALID', $component . ':' . $prop);
            }
            $prefix = 'docara-' . str_replace(['ui.', '.', '_'], ['', '-', '-'], $component);
            $props[$prop] = $prefix . '-' . substr(hash('sha256', $pagePath . "\0" . $ordinal), 0, 16);
        }
        foreach ($policy['blocked'] as $blocked) {
            if (array_key_exists($blocked['prop'], $props)
                && $props[$blocked['prop']] === $blocked['value']
            ) {
                throw new FrameworkComponentException(
                    $blocked['code'],
                    $component . ':' . $blocked['prop'],
                );
            }
        }

        return $props;
    }

    /** @return list<mixed> */
    public function blockedValues(string $component, string $property): array
    {
        $values = [];
        foreach ($this->policy($component)['blocked'] as $blocked) {
            if ($blocked['prop'] === $property) {
                $values[] = $blocked['value'];
            }
        }

        return $values;
    }

    /** @return list<string> */
    public function managedProperties(string $component): array
    {
        $properties = array_keys($this->policy($component)['managed']);
        sort($properties, SORT_STRING);

        return $properties;
    }

    /** @param list<string> $manifestStates @return list<string> */
    public function admittedStates(string $component, array $manifestStates): array
    {
        $policy = $this->policy($component);
        foreach ($policy['excluded_states'] as $state => $restriction) {
            $matched = false;
            foreach ($policy['blocked'] as $blocked) {
                if ($blocked['prop'] === $restriction['prop']
                    && $blocked['value'] === $restriction['value']
                ) {
                    $matched = true;
                    break;
                }
            }
            if (! $matched || ! in_array($state, $manifestStates, true)) {
                throw new FrameworkComponentException(
                    'FRAMEWORK_CONSUMER_POLICY_INVALID',
                    $component . ':' . $state,
                );
            }
        }

        return array_values(array_filter(
            $manifestStates,
            static fn (string $state): bool => ! isset($policy['excluded_states'][$state]),
        ));
    }

    /**
     * @return array{
     *     can_admit: false,
     *     managed_properties: list<string>,
     *     forbidden_inputs: list<string>,
     *     omitted_assets: list<string>,
     *     excluded_states: list<string>
     * }
     */
    public function catalogMetadata(string $component): array
    {
        $policy = $this->policy($component);
        $forbidden = array_keys($policy['managed']);
        foreach ($policy['blocked'] as $blocked) {
            $value = match (true) {
                is_bool($blocked['value']) => $blocked['value'] ? 'true' : 'false',
                is_string($blocked['value']), is_int($blocked['value']), is_float($blocked['value']) => (string) $blocked['value'],
                default => throw new FrameworkComponentException(
                    'FRAMEWORK_CONSUMER_POLICY_INVALID',
                    $component . ':' . $blocked['prop'],
                ),
            };
            $forbidden[] = $blocked['prop'] . '=' . $value;
        }
        $forbidden = array_values(array_unique($forbidden));
        sort($forbidden, SORT_STRING);

        return [
            'can_admit' => false,
            'managed_properties' => $this->managedProperties($component),
            'forbidden_inputs' => $forbidden,
            'omitted_assets' => $this->omittedAssets($component),
            'excluded_states' => $this->excludedStates($component),
        ];
    }

    /** @return list<string> */
    public function excludedStates(string $component): array
    {
        $states = array_keys($this->policy($component)['excluded_states']);
        sort($states, SORT_STRING);

        return $states;
    }

    /** @return list<string> */
    public function omittedAssets(string $component): array
    {
        $assets = array_keys($this->policy($component)['omitted_assets']);
        sort($assets, SORT_STRING);

        return $assets;
    }

    /**
     * @return array{
     *     managed: array<string, string>,
     *     blocked: list<array{prop: string, value: mixed, code: string}>,
     *     omitted_assets: array<string, string>,
     *     excluded_states: array<string, array{prop: string, value: mixed}>
     * }
     */
    private function policy(string $component): array
    {
        $policy = $this->policies[$component] ?? null;
        if (! is_array($policy)) {
            throw new FrameworkComponentException('FRAMEWORK_CONSUMER_POLICY_MISSING', $component);
        }

        return $policy;
    }

    /** @param array<string, mixed> $policy */
    private function assertPolicyShape(string $component, array $policy): void
    {
        $keys = array_keys($policy);
        sort($keys, SORT_STRING);
        if ($keys !== ['blocked', 'excluded_states', 'managed', 'omitted_assets']
            || ! is_array($policy['managed'])
            || ! is_array($policy['blocked'])
            || ! array_is_list($policy['blocked'])
            || ! is_array($policy['omitted_assets'])
            || ! is_array($policy['excluded_states'])
        ) {
            throw new FrameworkComponentException('FRAMEWORK_CONSUMER_POLICY_INVALID', $component);
        }
        foreach ($policy['managed'] as $prop => $strategy) {
            if (! is_string($prop) || $prop === '' || $strategy !== 'deterministic_id') {
                throw new FrameworkComponentException('FRAMEWORK_CONSUMER_POLICY_INVALID', $component . ':managed');
            }
        }
        foreach ($policy['blocked'] as $blocked) {
            if (! is_array($blocked)
                || ! $this->hasExactKeys($blocked, ['prop', 'value', 'code'])
                || ! is_string($blocked['prop'])
                || $blocked['prop'] === ''
                || ! is_string($blocked['code'])
                || $blocked['code'] === ''
            ) {
                throw new FrameworkComponentException('FRAMEWORK_CONSUMER_POLICY_INVALID', $component . ':blocked');
            }
        }
        foreach ($policy['omitted_assets'] as $asset => $reason) {
            if (! is_string($asset) || $asset === '' || ! is_string($reason) || $reason === '') {
                throw new FrameworkComponentException('FRAMEWORK_CONSUMER_POLICY_INVALID', $component . ':assets');
            }
        }
        foreach ($policy['excluded_states'] as $state => $restriction) {
            if (! is_string($state)
                || $state === ''
                || ! is_array($restriction)
                || ! $this->hasExactKeys($restriction, ['prop', 'value'])
                || ! is_string($restriction['prop'])
                || $restriction['prop'] === ''
            ) {
                throw new FrameworkComponentException('FRAMEWORK_CONSUMER_POLICY_INVALID', $component . ':states');
            }
        }
    }

    /** @param list<string> $expected */
    private function hasExactKeys(array $value, array $expected): bool
    {
        $keys = array_keys($value);
        sort($keys, SORT_STRING);
        sort($expected, SORT_STRING);

        return $keys === $expected;
    }
}
