<?php

declare(strict_types=1);

namespace Larena\Docara\Composition;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Larena\Audit\Contracts\AuditEvent;
use Larena\Audit\Runtime\AuditEventPipeline;
use Larena\Docara\Audit\DocaraPageCompositionAuditEventDescriptor;
use Larena\Filesystem\Persistence\DatabaseLogicalFileRepository;
use Larena\Layout\Contracts\PageBlockDefinition;
use Larena\Layout\Contracts\PageBlockInstance;
use Larena\Layout\Contracts\PageComposition;
use Larena\Layout\Runtime\PageBlockCatalog;
use Larena\Layout\Runtime\PageCompositionNormalizer;
use RuntimeException;
use stdClass;

final readonly class DocaraPageCompositionService
{
    public function __construct(
        private ConnectionInterface $connection,
        private PageBlockCatalog $catalog,
        private PageCompositionNormalizer $normalizer,
        private DatabaseLogicalFileRepository $files,
        private AuditEventPipeline $audit,
    ) {
    }

    /** @return list<array<string,mixed>> */
    public function editorSchema(): array
    {
        return $this->catalog->editorSchema();
    }

    public function draft(string $pageRef): PageComposition
    {
        return $this->read($pageRef, 'draft_blocks');
    }

    public function published(string $pageRef): PageComposition
    {
        return $this->read($pageRef, 'published_blocks');
    }

    /** @param array<int,mixed> $blocks */
    public function saveDraft(string $pageRef, array $blocks, string $actor): PageComposition
    {
        $this->assertSchema();
        $composition = $this->normalizer->normalize($blocks);
        $this->assertEligibleFiles($composition);

        return $this->connection->transaction(function () use ($pageRef, $actor, $composition): PageComposition {
            $current = $this->connection->table('docara_page_compositions')->where('page_ref', $pageRef)->lockForUpdate()->first();
            $version = $current === null ? 1 : ((int) $current->draft_version) + 1;
            $payload = [
                'draft_blocks' => $this->encode($composition),
                'draft_version' => $version,
                'draft_actor' => $actor,
                'updated_at' => now(),
            ];
            if ($current === null) {
                $this->connection->table('docara_page_compositions')->insert($payload + [
                    'page_ref' => $pageRef, 'published_blocks' => null, 'published_version' => null,
                    'published_actor' => null, 'published_at' => null, 'created_at' => now(),
                ]);
            } else {
                $this->connection->table('docara_page_compositions')->where('page_ref', $pageRef)->update($payload);
            }
            $this->record('updated', $pageRef, $actor, $composition, $version);
            return $composition;
        });
    }

    public function publish(string $pageRef, string $actor): PageComposition
    {
        $this->assertSchema();

        return $this->connection->transaction(function () use ($pageRef, $actor): PageComposition {
            $record = $this->connection->table('docara_page_compositions')->where('page_ref', $pageRef)->lockForUpdate()->first();
            if (!$record instanceof stdClass) {
                return new PageComposition([]);
            }
            $composition = $this->decode((string) $record->draft_blocks);
            $this->assertEligibleFiles($composition);
            $version = (int) $record->draft_version;
            $now = now();
            $this->connection->table('docara_page_compositions')->where('page_ref', $pageRef)->update([
                'published_blocks' => $this->encode($composition), 'published_version' => $version,
                'published_actor' => $actor, 'published_at' => $now, 'updated_at' => $now,
            ]);
            $this->connection->table('docara_page_composition_versions')->updateOrInsert(
                ['page_ref' => $pageRef, 'version' => $version],
                ['blocks' => $this->encode($composition), 'actor' => $actor, 'published_at' => $now, 'created_at' => $now, 'updated_at' => $now],
            );
            $this->record('published', $pageRef, $actor, $composition, $version);
            return $composition;
        });
    }

    private function read(string $pageRef, string $column): PageComposition
    {
        if (!Schema::hasTable('docara_page_compositions')) {
            return new PageComposition([]);
        }
        $value = $this->connection->table('docara_page_compositions')->where('page_ref', $pageRef)->value($column);
        return is_string($value) && $value !== '' ? $this->decode($value) : new PageComposition([]);
    }

    private function decode(string $json): PageComposition
    {
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($decoded) || ($decoded['schema'] ?? null) !== 'larena.layout.page_composition.v1' || !is_array($decoded['blocks'] ?? null)) {
            throw new RuntimeException('docara_page_composition_invalid');
        }
        return $this->normalizer->normalize($decoded['blocks']);
    }

    private function encode(PageComposition $composition): string
    {
        return json_encode($composition->toArray(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    private function assertSchema(): void
    {
        if (!Schema::hasTable('docara_page_compositions')) {
            throw new RuntimeException('docara_page_composition_schema_missing');
        }
    }

    private function assertEligibleFiles(PageComposition $composition): void
    {
        $definitions = [];
        foreach ($this->catalog->all() as $definition) {
            $definitions[$definition->key] = $definition;
        }
        foreach ($composition->blocks as $block) {
            $definition = $definitions[$block->type];
            foreach ($this->fileFields($definition) as $field) {
                $ref = $block->settings[$field] ?? '';
                if ($ref === '') {
                    continue;
                }
                $file = $this->files->find($ref);
                if ($file === null || $file->getAttribute('visibility') !== 'public' || !str_starts_with((string) $file->getAttribute('mime_type'), 'image/')) {
                    throw new RuntimeException('docara_page_block_image_invalid:' . $block->instanceId . ':' . $field);
                }
            }
        }
    }

    /** @return list<string> */
    private function fileFields(PageBlockDefinition $definition): array
    {
        $fields = [];
        foreach ($definition->fields as $field) {
            if ($field->type === 'file') {
                $fields[] = $field->key;
            }
        }
        return $fields;
    }

    private function record(string $operation, string $pageRef, string $actor, PageComposition $composition, int $version): void
    {
        $descriptor = new DocaraPageCompositionAuditEventDescriptor($operation);
        $types = array_values(array_unique(array_map(static fn (PageBlockInstance $block): string => $block->type, $composition->blocks)));
        sort($types);
        $this->audit->route($descriptor, AuditEvent::create(
            sourcePackage: $descriptor->sourcePackage(), category: $descriptor->category(), type: $descriptor->type(),
            actor: $actor, subject: $pageRef, severity: $descriptor->severity(), retentionClass: $descriptor->retentionClass(),
            correlationId: Str::uuid()->toString(), payload: ['operation' => $operation, 'block_count' => count($composition->blocks), 'block_types' => $types, 'version' => $version],
        ));
    }
}
