<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Smart;

use Simai\Docara\Declarative\Definition\DefinitionRepository;
use Simai\Docara\Declarative\Document\SmartCallNode;
use Simai\Docara\Declarative\Plan\ResolvedSmartPlan;
use Simai\Docara\Framework\FrameworkConsumerPolicy;
use Simai\Docara\Framework\FrameworkLock;
use Simai\Docara\Framework\FrameworkManifestContract;
use Simai\Docara\Framework\FrameworkManifestRepository;
use Simai\Docara\Framework\FrameworkPropsValidator;

final readonly class SmartPlanResolver
{
    public function __construct(
        private FrameworkManifestRepository $manifests,
        private DefinitionRepository $definitions = new DefinitionRepository,
        private FrameworkConsumerPolicy $consumerPolicy = new FrameworkConsumerPolicy,
        private FrameworkPropsValidator $validator = new FrameworkPropsValidator,
        private FrameworkManifestContract $manifestContract = new FrameworkManifestContract,
    ) {}

    /** @param array<string, mixed> $lock */
    public static function fromLock(array $lock): self
    {
        return new self(
            FrameworkManifestRepository::bundled(FrameworkLock::fromArray($lock)),
        );
    }

    public function resolve(SmartCallNode $call): ResolvedSmartPlan
    {
        $manifest = $this->manifests->get($call->smart);
        $view = $this->definitions->smartView($call->smart, $call->view);
        $this->consumerPolicy->assertNarrowing($call->smart, $manifest);
        $this->consumerPolicy->assertAuthorProps($call->smart, $call->props);

        $props = $manifest['atlas']['example_props'];
        $props = array_replace($props, $call->props);
        foreach ($this->manifestContract->mirrorMap($call->smart, $manifest) as $source => $targets) {
            if (! array_key_exists($source, $call->props)) {
                continue;
            }
            foreach ($targets as $target) {
                if (! array_key_exists($target, $call->props)) {
                    $props[$target] = $call->props[$source];
                }
            }
        }
        $props = $this->consumerPolicy->apply(
            $call->smart,
            $props,
            $call->span()->source,
            $call->ordinal,
        );
        $this->validator->validate($manifest, $props);

        $omitted = array_fill_keys($this->consumerPolicy->omittedAssets($call->smart), true);
        $assets = [];
        foreach ($manifest['assets'] as $asset) {
            $key = $asset['key'] ?? null;
            if (is_string($key) && ! isset($omitted[$key])) {
                $assets[] = $key;
            }
        }

        return new ResolvedSmartPlan(
            $call->id(),
            $call->smart,
            $call->view,
            (string) $view['template'],
            $props,
            $assets,
            [
                'manifest' => $this->manifests->manifestReference($call->smart),
                'manifest_version' => (string) $manifest['version'],
                'provider' => (string) $manifest['owner_package'],
                'provider_revision' => $this->manifests->providerRevision($call->smart),
                'view' => (string) $view['_source'],
                'view_sha256' => (string) $view['_sha256'],
                'runtime_pair' => $this->manifests->pairId(),
            ],
        );
    }
}
