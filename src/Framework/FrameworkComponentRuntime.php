<?php

declare(strict_types=1);

namespace Simai\Docara\Framework;

use Simai\Docara\Portable\CanonicalJson;

final readonly class FrameworkComponentRuntime
{
    private function __construct(
        private FrameworkManifestRepository $manifests,
        private FrameworkAssetPlanner $assetPlanner,
        private FrameworkConsumerPolicy $consumerPolicy,
    ) {}

    /** @param array<string, mixed> $lock */
    public static function fromLock(array $lock, string $assetBase = '/_docara/framework'): self
    {
        $frameworkLock = FrameworkLock::fromArray($lock);

        return self::create(
            FrameworkManifestRepository::bundled($frameworkLock),
            FrameworkConsumerPolicy::fromLock($frameworkLock),
            $assetBase,
        );
    }

    public static function fromLockFile(string $path, string $assetBase = '/_docara/framework'): self
    {
        $frameworkLock = FrameworkLock::fromJsonFile($path);

        return self::create(
            FrameworkManifestRepository::bundled($frameworkLock),
            FrameworkConsumerPolicy::fromLock($frameworkLock),
            $assetBase,
        );
    }

    private static function create(
        FrameworkManifestRepository $repository,
        FrameworkConsumerPolicy $consumerPolicy,
        string $assetBase,
    ): self {
        $propsValidator = new FrameworkPropsValidator;
        $assetPlanner = new FrameworkAssetPlanner($repository, $assetBase);
        (new FrameworkAdmissionPreflight(
            $repository,
            $consumerPolicy,
            $propsValidator,
            $assetPlanner,
        ))->assertReady();

        return new self(
            $repository,
            $assetPlanner,
            $consumerPolicy,
        );
    }

    /**
     * @param  list<string>  $componentKeys
     * @param  list<string>  $additionalRuntimeTags
     */
    public function planAssets(
        array $componentKeys,
        array $additionalRuntimeTags = [],
    ): FrameworkAssetPlan {
        return $this->assetPlanner->plan($componentKeys, $additionalRuntimeTags);
    }

    /**
     * @param  list<string>  $componentKeys
     * @param  list<string>  $additionalRuntimeTags
     */
    public function planAssetsForHtml(
        string $html,
        array $componentKeys = [],
        array $additionalRuntimeTags = [],
    ): FrameworkAssetPlan {
        return $this->assetPlanner->planForHtml($html, $componentKeys, $additionalRuntimeTags);
    }

    /**
     * Records Framework calls that have already been resolved and rendered by
     * the shared SmartComponentGateway. This method never renders page HTML.
     *
     * @param  list<array<string,mixed>>  $calls
     */
    public function recordGatewayCalls(string $markdown, array $calls): ComponentDirectiveDocument
    {
        $components = [];
        foreach ($calls as $call) {
            $component = $call['id'] ?? null;
            if (! is_string($component) || $component === '') {
                throw new FrameworkComponentException('FRAMEWORK_GATEWAY_CALL_INVALID', 'component');
            }
            $components[] = $component;
        }

        return new ComponentDirectiveDocument(
            $markdown,
            [],
            $calls,
            $this->assetPlanner->plan($components),
            [
                'schema' => 'docara.framework_component_runtime.v1',
                'mode' => 'shared_smart_gateway',
                'runtime_pair' => $this->manifests->pairId(),
                'provider' => 'larena/ui',
                'provider_revision' => $this->manifests->providerRevision($this->manifests->keys()[0]),
                'supported_components' => $this->manifests->keys(),
                'consumer_policy_sha256' => $this->consumerPolicyHash(),
                'nonclaims' => $this->manifests->nonclaims(),
            ],
        );
    }

    private function consumerPolicyHash(): string
    {
        $policies = [];
        foreach ($this->manifests->keys() as $key) {
            $policies[$key] = $this->consumerPolicy->catalogMetadata($key);
        }

        return hash('sha256', CanonicalJson::encode($policies));
    }
}
