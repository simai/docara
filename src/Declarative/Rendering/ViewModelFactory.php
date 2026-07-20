<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Rendering;

use Simai\Docara\Declarative\Plan\ResolvedSmartPlan;
use Simai\Docara\Declarative\Rendering\View\AlertViewModel;

final class ViewModelFactory
{
    public function alert(ResolvedSmartPlan $plan): AlertViewModel
    {
        if ($plan->smart !== 'ui.alert') {
            throw new \InvalidArgumentException('SMART_VIEW_MODEL_UNSUPPORTED');
        }
        $props = $plan->props;

        return new AlertViewModel(
            $this->escape((string) $props['id']),
            $this->escape((string) $plan->provenance['runtime_pair']),
            $this->escape((string) $props['type']),
            $this->escape((string) $props['variant']),
            $this->escape((string) $props['title']),
            $this->escape((string) $props['supporting-text']),
            $this->escape((string) $props['aria-label']),
            (bool) $props['closable'],
            isset($props['icon']) ? $this->escape((string) $props['icon']) : null,
        );
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
