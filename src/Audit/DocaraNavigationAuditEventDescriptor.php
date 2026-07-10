<?php

declare(strict_types=1);

namespace Larena\Docara\Audit;

use Larena\Audit\Contracts\AuditEventDescriptor;
use Larena\Audit\Enums\AuditRetentionClass;
use Larena\Audit\Enums\AuditSeverity;

final readonly class DocaraNavigationAuditEventDescriptor implements AuditEventDescriptor
{
    public function __construct(private string $operation)
    {
    }

    public function sourcePackage(): string { return 'larena/docara'; }
    public function category(): string { return 'navigation'; }
    public function type(): string { return 'docara_navigation_' . $this->operation; }
    public function severity(): AuditSeverity { return AuditSeverity::Notice; }
    public function retentionClass(): AuditRetentionClass { return AuditRetentionClass::Operational; }
    public function redactedPayloadFields(): array { return []; }
    public function forbiddenPayloadFields(): array { return ['body', 'password', 'password_hash', 'token', 'secret', 'session', 'cookie', 'request']; }
    public function isExperimental(): bool { return true; }
}
