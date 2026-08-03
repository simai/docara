<?php

declare(strict_types=1);

namespace Simai\Docara\Application;

use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\Preview\PreviewKernel;
use Simai\Docara\Preview\PreviewTarget;

final readonly class ArtifactTestService
{
    public function __construct(
        private PreviewKernel $preview,
        private ValidationService $validation = new ValidationService,
    ) {}

    public function test(string $root, string $kind, string $id, string $page): OperationResult
    {
        $validationKind = $kind === 'smart' ? 'smart' : ($kind === 'layout' ? 'layout' : throw new \InvalidArgumentException('SDK_TEST_KIND_UNKNOWN:' . $kind));
        $validated = $this->validation->validate($root, $validationKind, $id);
        $target = $kind === 'smart' ? PreviewTarget::Smart : PreviewTarget::Layout;
        $artifact = $this->preview->render($root, $page, $target, $kind === 'smart' ? $id : null);
        if ($kind === 'layout' && ($artifact->provenance['layout_id'] ?? null) !== $id) {
            throw new PortableConfigurationException(
                'SDK_TEST_LAYOUT_CONTEXT_MISMATCH',
                "Test page [$page] uses layout [" . ($artifact->provenance['layout_id'] ?? 'unknown') . "] instead of [$id].",
            );
        }
        $runtime = ProjectRuntime::load($root);

        return OperationResult::success('test', $kind . ':' . $id, [
            'validation' => $validated->status,
            'fixture' => ['page' => $artifact->page, 'target' => $artifact->target->value, 'selector' => $artifact->selector],
            'html_sha256' => $artifact->sha256(),
            'page_html_sha256' => hash('sha256', $artifact->pageHtml),
            'assets' => $artifact->assets,
            'dependencies' => $artifact->dependencies,
            'provenance' => $artifact->provenance,
            'accepted_build_receipt' => false,
        ], $runtime->provenance());
    }
}
