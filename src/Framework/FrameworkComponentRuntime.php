<?php

declare(strict_types=1);

namespace Simai\Docara\Framework;

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
    ) {}

    /** @param array<string, mixed> $lock */
    public static function fromLock(array $lock, string $assetBase = '/_docara/framework'): self
    {
        $repository = FrameworkManifestRepository::bundled(FrameworkLock::fromArray($lock));

        return new self(
            $repository,
            new ComponentDirectiveParser,
            new SchemaRepository,
            new FrameworkPropsValidator,
            new FrameworkHostRenderer,
            new FrameworkAssetPlanner($repository, $assetBase),
        );
    }

    public static function fromLockFile(string $path, string $assetBase = '/_docara/framework'): self
    {
        $repository = FrameworkManifestRepository::bundled(FrameworkLock::fromJsonFile($path));

        return new self(
            $repository,
            new ComponentDirectiveParser,
            new SchemaRepository,
            new FrameworkPropsValidator,
            new FrameworkHostRenderer,
            new FrameworkAssetPlanner($repository, $assetBase),
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
                'provider_revision' => FrameworkManifestRepository::PROVIDER_REVISION,
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
                'provider_revision' => FrameworkManifestRepository::PROVIDER_REVISION,
                'supported_components' => ['ui.alert', 'ui.button'],
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
        if ($component === 'ui.alert' && array_key_exists('id', $authorProps)) {
            throw new FrameworkComponentException(
                'FRAMEWORK_PROP_MANAGED',
                'ui.alert:id is generated deterministically by Docara',
            );
        }
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

        foreach (is_array($manifest['atlas']['controls'] ?? null) ? $manifest['atlas']['controls'] : [] as $control) {
            if (! is_array($control) || ! is_string($control['key'] ?? null) || ! array_key_exists($control['key'], $authorProps)) {
                continue;
            }
            foreach (is_array($control['mirror_props'] ?? null) ? $control['mirror_props'] : [] as $mirror) {
                if (is_string($mirror) && ! array_key_exists($mirror, $authorProps)) {
                    $props[$mirror] = $authorProps[$control['key']];
                }
            }
        }

        if ($component === 'ui.alert') {
            $props['id'] = 'docara-alert-' . substr(hash('sha256', $pagePath . "\0" . $ordinal), 0, 16);
            if (($props['closable'] ?? false) === true) {
                throw new FrameworkComponentException(
                    'FRAMEWORK_PROP_UNSUPPORTED_IN_BOUNDED_RUNTIME',
                    'ui.alert:closable requires sf-icon-button, which is absent from the pinned runtime pair',
                );
            }
        }

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
}
