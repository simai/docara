<?php

declare(strict_types=1);

namespace Larena\Docara\Authoring;

use DateTimeImmutable;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Str;
use Larena\Audit\Contracts\AuditEvent;
use Larena\Audit\Runtime\AuditEventPipeline;
use Larena\Docara\Audit\DocaraPageAuditEventDescriptor;
use Larena\Docara\Contracts\DocumentationPage;
use Larena\Docara\Contracts\DocumentationAssetRef;
use Larena\Docara\Audit\DocaraPageAssetAuditEventDescriptor;
use Larena\Docara\Contracts\DocumentationPageRepository;
use Larena\Docara\Contracts\PublicationState;
use Larena\Docara\Enums\DocumentationVisibility;
use Larena\Docara\Enums\PublicationStatus;
use RuntimeException;
use stdClass;

final readonly class DocumentationPageAuthoringService
{
    public function __construct(
        private DocumentationPageRepository $pages,
        private AuditEventPipeline $audit,
        private ConnectionInterface $connection,
    ) {
    }

    /** @return list<array<string, string|null>> */
    public function list(): array
    {
        return $this->connection->table('docara_pages')
            ->orderBy('title')
            ->get()
            ->map(static fn (stdClass $record): array => [
                'page_ref' => (string) $record->page_ref,
                'slug' => (string) $record->slug,
                'title' => (string) $record->title,
                'locale' => (string) $record->locale,
                'status' => (string) $record->publication_status,
                'published_at' => $record->published_at === null ? null : (string) $record->published_at,
            ])
            ->values()
            ->all();
    }

    public function find(string $slug, string $locale = 'en'): ?DocumentationPage
    {
        return $this->pages->findByLocaleAndSlug($locale, $slug);
    }

    /** @param array{title:string, slug:string, body:string, status:string, locale?:string} $input */
    public function create(array $input, string $actor): DocumentationPage
    {
        return $this->persist(
            operation: 'created',
            actor: $actor,
            page: $this->pageFromInput('docara:page:' . Str::uuid()->toString(), $input, 1),
        );
    }

    /** @param array{title:string, slug:string, body:string, status:string, locale?:string} $input */
    public function update(string $currentSlug, array $input, string $actor): DocumentationPage
    {
        $current = $this->find($currentSlug, $input['locale'] ?? 'en');
        if ($current === null) {
            throw new RuntimeException('Documentation page not found.');
        }
        $page = $input['status'] === PublicationStatus::Published->value
            ? $this->publishedPageFromInput($current, $input)
            : $this->pageFromInput($current->pageRef, $input, ((int) $current->publication->version) + 1);

        return $this->persist(
            operation: 'updated',
            actor: $actor,
            page: $page,
        );
    }

    public function publish(string $slug, string $actor, string $locale = 'en'): DocumentationPage
    {
        $current = $this->find($slug, $locale);
        if ($current === null) {
            throw new RuntimeException('Documentation page not found.');
        }
        if ($current->publication->status === PublicationStatus::Published) {
            return $current;
        }

        $page = new DocumentationPage(
            pageRef: $current->pageRef,
            slug: $current->slug,
            locale: $current->locale,
            visibility: DocumentationVisibility::Public,
            publication: new PublicationState(
                status: PublicationStatus::Published,
                version: (string) (((int) $current->publication->version) + 1),
                publiclyVisible: true,
                publishedAt: (new DateTimeImmutable())->format(DATE_ATOM),
            ),
            title: $current->title,
            body: $current->body,
            assets: $current->assets,
        );

        return $this->persist('published', $actor, $page);
    }

    public function unpublish(string $slug, string $actor, string $locale = 'en'): DocumentationPage
    {
        $current = $this->find($slug, $locale);
        if ($current === null) {
            throw new RuntimeException('Documentation page not found.');
        }
        if ($current->publication->status !== PublicationStatus::Published) {
            return $current;
        }

        $page = new DocumentationPage(
            pageRef: $current->pageRef,
            slug: $current->slug,
            locale: $current->locale,
            visibility: DocumentationVisibility::Public,
            publication: new PublicationState(
                status: PublicationStatus::Draft,
                version: (string) (((int) $current->publication->version) + 1),
                publiclyVisible: false,
            ),
            title: $current->title,
            body: $current->body,
            assets: $current->assets,
        );

        return $this->persist('unpublished', $actor, $page);
    }

    /** @param array{title:string, slug:string, body:string, status:string, locale?:string} $input */
    private function pageFromInput(string $pageRef, array $input, int $version): DocumentationPage
    {
        $status = PublicationStatus::from($input['status']);
        if ($status === PublicationStatus::Published) {
            throw new RuntimeException('Use the explicit publish operation.');
        }

        return new DocumentationPage(
            pageRef: $pageRef,
            slug: $input['slug'],
            locale: $input['locale'] ?? 'en',
            visibility: DocumentationVisibility::Public,
            publication: new PublicationState(
                status: $status,
                version: (string) $version,
                publiclyVisible: false,
            ),
            title: $input['title'],
            body: $input['body'],
            assets: $this->assetsFromInput($input),
        );
    }

    /** @param array{title:string, slug:string, body:string, status:string, locale?:string} $input */
    private function publishedPageFromInput(DocumentationPage $current, array $input): DocumentationPage
    {
        if ($current->publication->status !== PublicationStatus::Published) {
            throw new RuntimeException('Use the explicit publish operation.');
        }

        return new DocumentationPage(
            pageRef: $current->pageRef,
            slug: $input['slug'],
            locale: $current->locale,
            visibility: DocumentationVisibility::Public,
            publication: new PublicationState(
                status: PublicationStatus::Published,
                version: (string) (((int) $current->publication->version) + 1),
                publiclyVisible: true,
                publishedAt: $current->publication->publishedAt,
            ),
            title: $input['title'],
            body: $input['body'],
            assets: $this->assetsFromInput($input),
        );
    }

    private function persist(string $operation, string $actor, DocumentationPage $page): DocumentationPage
    {
        return $this->connection->transaction(function () use ($operation, $actor, $page): DocumentationPage {
            $saved = $this->pages->save($page);
            $descriptor = new DocaraPageAuditEventDescriptor($operation);
            $this->audit->route($descriptor, AuditEvent::create(
                sourcePackage: $descriptor->sourcePackage(),
                category: $descriptor->category(),
                type: $descriptor->type(),
                actor: $actor,
                subject: $saved->pageRef,
                severity: $descriptor->severity(),
                retentionClass: $descriptor->retentionClass(),
                correlationId: Str::uuid()->toString(),
                payload: [
                    'operation' => $operation,
                    'slug' => $saved->slug,
                    'status' => $saved->publication->status->value,
                    'version' => $saved->publication->version,
                ],
            ));

            if (in_array($operation, ['created', 'updated'], true)) {
                foreach ($saved->assets as $asset) {
                    $assetDescriptor = new DocaraPageAssetAuditEventDescriptor();
                    $this->audit->route($assetDescriptor, AuditEvent::create(
                        sourcePackage: $assetDescriptor->sourcePackage(), category: $assetDescriptor->category(), type: $assetDescriptor->type(),
                        actor: $actor, subject: $saved->pageRef, severity: $assetDescriptor->severity(), retentionClass: $assetDescriptor->retentionClass(),
                        correlationId: Str::uuid()->toString(), payload: ['page_ref'=>$saved->pageRef,'slug'=>$saved->slug,'logical_file_ref'=>$asset->logicalFileRef,'purpose'=>$asset->purpose],
                    ));
                }
            }

            return $saved;
        });
    }

    /** @param array<string,mixed> $input @return list<DocumentationAssetRef> */
    private function assetsFromInput(array $input): array
    {
        $ref = trim((string) ($input['hero_file_ref'] ?? ''));
        return $ref === '' ? [] : [new DocumentationAssetRef($ref, 'hero', null)];
    }
}
