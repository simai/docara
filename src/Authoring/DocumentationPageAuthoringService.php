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
                'status' => (string) $record->publication_status,
                'published_at' => $record->published_at === null ? null : (string) $record->published_at,
            ])
            ->values()
            ->all();
    }

    public function find(string $slug): ?DocumentationPage
    {
        return $this->pages->findByLocaleAndSlug('en', $slug);
    }

    /** @param array{title:string, slug:string, body:string, status:string} $input */
    public function create(array $input, string $actor): DocumentationPage
    {
        return $this->persist(
            operation: 'created',
            actor: $actor,
            page: $this->pageFromInput('docara:page:' . Str::uuid()->toString(), $input, 1),
        );
    }

    /** @param array{title:string, slug:string, body:string, status:string} $input */
    public function update(string $currentSlug, array $input, string $actor): DocumentationPage
    {
        $current = $this->find($currentSlug);
        if ($current === null) {
            throw new RuntimeException('Documentation page not found.');
        }

        return $this->persist(
            operation: 'updated',
            actor: $actor,
            page: $this->pageFromInput($current->pageRef, $input, ((int) $current->publication->version) + 1),
        );
    }

    public function publish(string $slug, string $actor): DocumentationPage
    {
        $current = $this->find($slug);
        if ($current === null) {
            throw new RuntimeException('Documentation page not found.');
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
        );

        return $this->persist('published', $actor, $page);
    }

    /** @param array{title:string, slug:string, body:string, status:string} $input */
    private function pageFromInput(string $pageRef, array $input, int $version): DocumentationPage
    {
        $status = PublicationStatus::from($input['status']);
        if ($status === PublicationStatus::Published) {
            throw new RuntimeException('Use the explicit publish operation.');
        }

        return new DocumentationPage(
            pageRef: $pageRef,
            slug: $input['slug'],
            locale: 'en',
            visibility: DocumentationVisibility::Public,
            publication: new PublicationState(
                status: $status,
                version: (string) $version,
                publiclyVisible: false,
            ),
            title: $input['title'],
            body: $input['body'],
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

            return $saved;
        });
    }
}
