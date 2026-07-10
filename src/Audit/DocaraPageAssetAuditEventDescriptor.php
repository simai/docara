<?php
declare(strict_types=1);
namespace Larena\Docara\Audit;
use Larena\Audit\Contracts\AuditEventDescriptor;
use Larena\Audit\Enums\AuditRetentionClass;
use Larena\Audit\Enums\AuditSeverity;
final readonly class DocaraPageAssetAuditEventDescriptor implements AuditEventDescriptor
{
    public function sourcePackage(): string { return 'larena/docara'; }
    public function category(): string { return 'media_library'; }
    public function type(): string { return 'file_used'; }
    public function severity(): AuditSeverity { return AuditSeverity::Notice; }
    public function retentionClass(): AuditRetentionClass { return AuditRetentionClass::Operational; }
    public function redactedPayloadFields(): array { return []; }
    public function forbiddenPayloadFields(): array { return ['bytes','content','storage_key','sha256','password','token','secret']; }
    public function isExperimental(): bool { return true; }
}
