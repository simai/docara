<?php

declare(strict_types=1);

namespace Simai\Docara\Framework;

use Simai\Docara\Portable\CanonicalJson;
use Simai\Docara\Portable\SchemaRepository;

final readonly class FrameworkComponentRuntime
{
    private function __construct(
        private FrameworkManifestRepository $manifests,
        private ComponentDirectiveParser $parser,
        private SchemaRepository $schemas,
        private FrameworkPropsValidator $validator,
        private FrameworkHostRenderer $renderer,
        private FrameworkAssetPlanner $assetPlanner,
        private FrameworkConsumerPolicy $consumerPolicy,
        private FrameworkManifestContract $manifestContract,
    ) {}

    /** @param array<string, mixed> $lock */
    public static function fromLock(array $lock, string $assetBase = '/_docara/framework'): self
    {
        return self::create(
            FrameworkManifestRepository::bundled(FrameworkLock::fromArray($lock)),
            $assetBase,
        );
    }

    public static function fromLockFile(string $path, string $assetBase = '/_docara/framework'): self
    {
        return self::create(
            FrameworkManifestRepository::bundled(FrameworkLock::fromJsonFile($path)),
            $assetBase,
        );
    }

    private static function create(FrameworkManifestRepository $repository, string $assetBase): self
    {
        $propsValidator = new FrameworkPropsValidator;
        $renderer = new FrameworkHostRenderer;
        $assetPlanner = new FrameworkAssetPlanner($repository, $assetBase);
        $consumerPolicy = new FrameworkConsumerPolicy;
        (new FrameworkAdmissionPreflight(
            $repository,
            $consumerPolicy,
            $propsValidator,
            $renderer,
            $assetPlanner,
        ))->assertReady();

        return new self(
            $repository,
            new ComponentDirectiveParser($repository->keys()),
            new SchemaRepository,
            $propsValidator,
            $renderer,
            $assetPlanner,
            $consumerPolicy,
            new FrameworkManifestContract,
        );
    }

    public function extract(string $markdown, string $pagePath): ComponentDirectiveDocument
    {
        $parsed = $this->parser->parse($markdown, $pagePath);
        $renderedHtml = [];
        $calls = [];
        $components = [];

        foreach ($parsed->directives as $directive) {
            $manifest = $this->manifests->get($directive->component);
            $props = $this->normalizeProps($manifest, $directive->props, $pagePath, $directive->ordinal);
            $this->validator->validate($manifest, $props);
            $html = $this->renderer->render($manifest, $props, $this->manifests->pairId());
            $renderedHtml[$directive->placeholder] = $html;
            $components[] = $directive->component;
            $portableCall = [
                'schema' => 'docara.component_call.v1',
                'id' => $directive->component,
                'props' => $props,
            ];
            $this->schemas->assertValid($portableCall, 'component-call.schema.json');
            $calls[] = $portableCall + [
                'ordinal' => $directive->ordinal,
                'line' => $directive->line,
                'placeholder' => $directive->placeholder,
                'html' => $html,
                'manifest_version' => (string) $manifest['version'],
                'provider' => (string) $manifest['owner_package'],
                'provider_revision' => $this->manifests->providerRevision($directive->component),
            ];
        }

        return new ComponentDirectiveDocument(
            $parsed->markdownWithPlaceholders,
            $renderedHtml,
            $calls,
            $this->assetPlanner->plan($components),
            [
                'schema' => 'docara.framework_component_runtime.v1',
                'mode' => 'bounded_consumer_verified',
                'runtime_pair' => $this->manifests->pairId(),
                'provider' => 'larena/ui',
                'provider_revision' => $this->manifests->providerRevision($this->manifests->keys()[0]),
                'supported_components' => $this->manifests->keys(),
                'consumer_policy_sha256' => $this->consumerPolicyHash(),
                'nonclaims' => $this->manifests->nonclaims(),
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $manifest
     * @param  array<string, mixed>  $authorProps
     * @return array<string, mixed>
     */
    private function normalizeProps(array $manifest, array $authorProps, string $pagePath, int $ordinal): array
    {
        $component = (string) $manifest['key'];
        $preset = $authorProps['preset'] ?? null;
        unset($authorProps['preset']);
        $this->consumerPolicy->assertNarrowing($component, $manifest);
        $this->consumerPolicy->assertAuthorProps($component, $authorProps);
        if ($preset !== null && ! is_string($preset)) {
            throw new FrameworkComponentException('FRAMEWORK_PRESET_INVALID', $component);
        }

        $exampleProps = $manifest['atlas']['example_props'] ?? null;
        if (! is_array($exampleProps)) {
            throw new FrameworkComponentException('FRAMEWORK_EXAMPLE_PROPS_MISSING', $component);
        }
        $props = $exampleProps;
        if ($preset !== null) {
            $presetProps = $manifest['presets'][$preset]['props'] ?? null;
            if (! is_array($presetProps)) {
                throw new FrameworkComponentException('FRAMEWORK_PRESET_UNKNOWN', $component . ':' . $preset);
            }
            $props = array_replace($props, $presetProps);
        }
        $props = array_replace($props, $authorProps);

        foreach ($this->manifestContract->mirrorMap($component, $manifest) as $source => $targets) {
            if (! array_key_exists($source, $authorProps)) {
                continue;
            }
            foreach ($targets as $target) {
                if (! array_key_exists($target, $authorProps)) {
                    $props[$target] = $authorProps[$source];
                }
            }
        }

        $props = $this->consumerPolicy->apply($component, $props, $pagePath, $ordinal);

        $ordered = [];
        foreach (array_keys($manifest['props']['properties']) as $key) {
            if (is_string($key) && array_key_exists($key, $props)) {
                $ordered[$key] = $props[$key];
            }
        }
        foreach ($props as $key => $value) {
            if (! array_key_exists((string) $key, $ordered)) {
                $ordered[(string) $key] = $value;
            }
        }

        return $ordered;
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
