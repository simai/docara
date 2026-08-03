<?php

declare(strict_types=1);

namespace Simai\Docara\Application;

use Simai\Docara\Portable\CanonicalJson;

final class QaIdentity
{
    /** @param array<string, mixed> $plan */
    public static function planId(array $plan): string
    {
        unset($plan['plan_id']);

        return hash('sha256', CanonicalJson::encode($plan));
    }

    /** @param array<string, mixed> $reference */
    public static function referenceId(array $reference): string
    {
        unset($reference['reference_id']);

        return hash('sha256', CanonicalJson::encode($reference));
    }

    /** @param array<string, mixed> $reference */
    public static function referenceManifestSha256(array $reference): string
    {
        return hash('sha256', CanonicalJson::encode($reference));
    }
}
