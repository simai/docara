<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Smart;

use Simai\Docara\Declarative\Document\SmartCallNode;
use Simai\Docara\Declarative\Plan\ResolvedSmartPlan;
use Simai\Docara\Declarative\Rendering\RenderArtifact;
use Simai\Docara\Document\ComponentBlockNode;
use Simai\Docara\Document\ComponentContractNode;
use Simai\Docara\Document\ComponentNode;
use Simai\Docara\Document\ContentComponentRenderer;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\Smart\SmartRegistry;

final readonly class SmartComponentGateway
{
    public function __construct(
        private SmartRegistry $smarts = new SmartRegistry([]),
        private ProviderPlanResolverRegistry $resolvers = new ProviderPlanResolverRegistry([]),
        private ContentComponentRenderer $content = new ContentComponentRenderer,
    ) {}

    /** @param array<string, mixed> $frameworkLock */
    public static function bundled(array $frameworkLock): self
    {
        $smarts = SmartRegistry::bundled();

        return new self($smarts, new ProviderPlanResolverRegistry([
            new DocaraProviderPlanResolver(new CompositeSmartPlanResolver(smarts: $smarts)),
            new FrameworkProviderPlanResolver(SmartPlanResolver::fromLock($frameworkLock), $smarts),
        ]));
    }

    public static function content(): self
    {
        $smarts = SmartRegistry::bundled();

        return new self($smarts, new ProviderPlanResolverRegistry([
            new DocaraProviderPlanResolver(new CompositeSmartPlanResolver(smarts: $smarts)),
        ]));
    }

    /** @param array<string,mixed> $frameworkLock */
    public static function withProject(SmartRegistry $smarts, string $projectProviderId, array $frameworkLock): self
    {
        return new self($smarts, new ProviderPlanResolverRegistry([
            new PortableProviderPlanResolver($projectProviderId, $smarts),
            new DocaraProviderPlanResolver(new CompositeSmartPlanResolver(smarts: $smarts)),
            new FrameworkProviderPlanResolver(SmartPlanResolver::fromLock($frameworkLock), $smarts),
        ]));
    }

    public function resolve(SmartCallNode $call): ResolvedSmartPlan
    {
        return $this->resolveAt($call, null, 0, []);
    }

    /** @param array<string, mixed> $declaredChildren */
    private function resolveAt(
        SmartCallNode $call,
        ?string $parent,
        int $depth,
        array $declaredChildren,
    ): ResolvedSmartPlan {
        if ($depth > 8) {
            throw new PortableConfigurationException('PORTABLE_SMART_COMPOSITION_DEPTH_EXCEEDED', $call->smart);
        }
        $definition = $this->smarts->definition($call->smart);
        $constraints = is_array($definition->provenance['consumer_constraints'] ?? null)
            ? $definition->provenance['consumer_constraints']
            : [];
        $parents = $constraints['admitted_parents'] ?? [];
        if (is_array($parents) && $parents !== [] && (! is_string($parent) || ! in_array($parent, $parents, true))) {
            throw new PortableConfigurationException('PORTABLE_SMART_PARENT_FORBIDDEN', $call->smart);
        }
        foreach ($constraints['prop_values'] ?? [] as $prop => $allowed) {
            $value = $call->props[$prop] ?? ($definition->portableManifest['props'][$prop]['default'] ?? null);
            if (! is_array($allowed) || ! in_array($value, $allowed, true)) {
                throw new PortableConfigurationException('PORTABLE_SMART_PROP_POLICY_FORBIDDEN', $call->smart . ':' . $prop);
            }
        }

        $plan = $this->resolvers->get($definition->providerId)->resolve($call);
        $viewChildren = $plan->provenance['portable_view']['children'] ?? [];
        if ($declaredChildren !== [] && $viewChildren !== []) {
            throw new PortableConfigurationException('PORTABLE_SMART_CHILDREN_COLLISION', $call->smart);
        }
        $children = $declaredChildren !== [] ? $declaredChildren : $viewChildren;
        if ($children === []) {
            return $plan;
        }
        if (! is_array($children) || array_is_list($children) || count($children) > 64) {
            throw new PortableConfigurationException('PORTABLE_SMART_CHILDREN_INVALID', $call->smart);
        }

        $slots = $definition->portableManifest['slots'] ?? [];
        if (! is_array($slots) || array_is_list($slots)) {
            throw new PortableConfigurationException('PORTABLE_SMART_SLOTS_INVALID', $call->smart);
        }
        $resolved = [];
        $slotCounts = [];
        foreach ($children as $childId => $record) {
            if (! is_string($childId) || preg_match('/^[a-z0-9][a-z0-9.-]*$/D', $childId) !== 1
                || ! is_array($record) || array_is_list($record)
            ) {
                throw new PortableConfigurationException('PORTABLE_SMART_CHILD_INVALID', $call->smart);
            }
            $smart = $record['smart'] ?? null;
            $slot = $record['slot'] ?? null;
            $view = $record['view'] ?? 'default';
            $props = $record['props'] ?? [];
            $nested = $record['children'] ?? [];
            if (! is_string($smart) || ! is_string($slot) || ! is_string($view)
                || ! is_array($props) || array_is_list($props)
                || ! is_array($nested) || ($nested !== [] && array_is_list($nested))
            ) {
                throw new PortableConfigurationException('PORTABLE_SMART_CHILD_INVALID', $call->smart . ':' . $childId);
            }
            if (is_string($record['preset'] ?? null)) {
                $props['preset'] = $record['preset'];
            }
            $slotContract = $slots[$slot] ?? null;
            $canonical = $this->smarts->canonicalKey($smart);
            if (! is_array($slotContract)
                || ! in_array($canonical, $slotContract['accepts'] ?? [], true)
            ) {
                throw new PortableConfigurationException('PORTABLE_SMART_CHILD_SLOT_FORBIDDEN', $call->smart . ':' . $childId);
            }
            $slotCounts[$slot] = ($slotCounts[$slot] ?? 0) + 1;
            if (($slotContract['multiple'] ?? false) !== true && $slotCounts[$slot] > 1) {
                throw new PortableConfigurationException('PORTABLE_SMART_CHILD_COUNT_INVALID', $call->smart . ':' . $slot);
            }
            $resolved[] = $this->resolveAt(
                new SmartCallNode(
                    $call->id() . '.child.' . $childId,
                    $smart,
                    $view,
                    $props,
                    $call->ordinal,
                    $call->span(),
                    $slot,
                ),
                $definition->key,
                $depth + 1,
                $nested,
            );
        }
        foreach ($slots as $slot => $contract) {
            if (is_array($contract) && ($contract['required'] ?? false) === true && ($slotCounts[$slot] ?? 0) === 0) {
                throw new PortableConfigurationException('PORTABLE_SMART_REQUIRED_SLOT_MISSING', $call->smart . ':' . $slot);
            }
        }

        return new ResolvedSmartPlan(
            $plan->nodeId,
            $plan->smart,
            $plan->view,
            $plan->template,
            $plan->props,
            $plan->assets,
            array_replace($plan->provenance, [
                'dependency_trace' => array_map(static fn (ResolvedSmartPlan $child): array => [
                    'smart' => $child->smart,
                    'provider' => $child->provenance['provider'] ?? null,
                    'provider_revision' => $child->provenance['provider_revision'] ?? null,
                ], $resolved),
            ]),
            $resolved,
        );
    }

    public function renderComponentContract(ComponentContractNode $component, ?string $bodyHtml = null): RenderArtifact
    {
        if ($component instanceof ComponentNode) {
            if ($bodyHtml !== null) {
                throw new PortableConfigurationException(
                    'CONTENT_COMPONENT_INLINE_BODY_FORBIDDEN',
                    "Inline component [{$component->component()}] cannot receive a document body at [{$component->location()->label()}].",
                );
            }

            return $this->content->render($component);
        }
        if ($component instanceof ComponentBlockNode && $bodyHtml !== null) {
            return $this->content->renderBlock($component, $bodyHtml);
        }

        throw new PortableConfigurationException(
            'CONTENT_COMPONENT_DOCUMENT_BODY_REQUIRED',
            "Block component [{$component->component()}] requires a rendered document body at [{$component->location()->label()}].",
        );
    }
}
