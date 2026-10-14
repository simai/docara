<?php

declare(strict_types=1);

namespace Simai\Docara\Smart\Runtime\Context;

use Simai\Docara\Declarative\Rendering\View\HeaderViewModel;
use Simai\Docara\Smart\Runtime\SmartInvocation;

final class BrandingContextAdapter implements SmartContextAdapter
{
    public function id(): string
    {
        return 'docara.branding';
    }

    public function prepare(SmartInvocation $invocation): object
    {
        $branding = $invocation->props['branding'] ?? null;
        if (! is_array($branding)) {
            throw new \InvalidArgumentException('SMART_CONTEXT_BRANDING_INVALID');
        }

        return new HeaderViewModel(
            $this->escape((string) $branding['title']),
            $branding['label'] === null ? null : $this->escape((string) $branding['label']),
            $this->escape((string) $branding['size']),
            $this->escape((string) $branding['home_url']),
            $branding['logo'] === null ? null : $this->escape((string) $branding['logo']),
            $branding['logo_dark'] === null ? null : $this->escape((string) $branding['logo_dark']),
        );
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
