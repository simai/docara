<?php

declare(strict_types=1);

namespace Larena\Docara\Audit;

use Larena\Audit\Contracts\AuditEventDescriptor;
use Larena\Audit\Enums\AuditRetentionClass;
use Larena\Audit\Enums\AuditSeverity;

final readonly class DocaraPageCompositionAuditEventDescriptor implements AuditEventDescriptor
{
    public function __construct(private string $operation)
    {
    }

    public function sourcePackage(): string { return 'larena/docara'; }
    public function category(): string { return 'docara_page_composition'; }
    public function type(): string { return 'docara_page_blocks_' . $this->operation; }
    public function severity(): AuditSeverity { return in_array($this->operation, ['published', 'update_denied', 'publish_denied'], true) ? AuditSeverity::Warning : AuditSeverity::Notice; }
    public function retentionClass(): AuditRetentionClass { return AuditRetentionClass::Operational; }
    public function redactedPayloadFields(): array { return []; }
    public function forbiddenPayloadFields(): array { return ['blocks', 'settings', 'body', 'text', 'title', 'url', 'file_content', 'storage_key', 'path', 'request', 'password', 'token', 'secret']; }
    public function isExperimental(): bool { return true; }
}
