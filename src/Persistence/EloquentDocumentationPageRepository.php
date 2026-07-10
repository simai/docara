<?php

declare(strict_types=1);

namespace Larena\Docara\Persistence;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Carbon\CarbonImmutable;
use InvalidArgumentException;
use Larena\Docara\Contracts\DocumentationPage;
use Larena\Docara\Contracts\DocumentationAssetRef;
use Larena\Docara\Contracts\DocumentationPageRepository;
use Larena\Docara\Contracts\PublicationState;
use Larena\Docara\Enums\DocumentationVisibility;
use Larena\Docara\Enums\PublicationStatus;
use Larena\Docara\Models\DocumentationPageRecord;
use LogicException;
use stdClass;

final class EloquentDocumentationPageRepository implements DocumentationPageRepository
{
    public function __construct(private readonly ?string $connection = null)
    {
    }

    public function save(DocumentationPage $page): DocumentationPage
    {
        $this->assertDurableConnection();
        $this->assertSupportedPage($page);

        $record = $this->query()->updateOrCreate(
            ['page_ref' => $page->pageRef],
            [
                'slug' => $page->slug,
                'title' => $page->title,
                'body' => $page->body,
                'assets' => array_map(static fn (DocumentationAssetRef $asset): array => ['logical_file_ref'=>$asset->logicalFileRef,'purpose'=>$asset->purpose,'alt_text'=>$asset->altText], $page->assets),
                'locale' => $page->locale,
                'visibility' => $page->visibility,
                'publication_status' => $page->publication->status,
                'version' => $page->publication->version,
                'published_at' => $page->publication->publishedAt,
            ],
        );

        return $this->toDomain($record);
    }

    public function findByLocaleAndSlug(string $locale, string $slug): ?DocumentationPage
    {
        $this->assertDurableConnection();

        $record = $this->table()
            ->where('locale', $locale)
            ->where('slug', $slug)
            ->first();

        return $record instanceof stdClass ? $this->rowToDomain($record) : null;
    }

    public function findPublishedByLocaleAndSlug(string $locale, string $slug): ?DocumentationPage
    {
        $this->assertDurableConnection();

        $record = $this->table()
            ->where('locale', $locale)
            ->where('slug', $slug)
            ->where('visibility', DocumentationVisibility::Public->value)
            ->where('publication_status', PublicationStatus::Published->value)
            ->whereNotNull('published_at')
            ->first();

        return $record instanceof stdClass ? $this->rowToDomain($record) : null;
    }

    public function findPublishedByPageRef(string $pageRef): ?DocumentationPage
    {
        $this->assertDurableConnection();

        $record = $this->table()
            ->where('page_ref', $pageRef)
            ->where('visibility', DocumentationVisibility::Public->value)
            ->where('publication_status', PublicationStatus::Published->value)
            ->whereNotNull('published_at')
            ->first();

        return $record instanceof stdClass ? $this->rowToDomain($record) : null;
    }

    /** @return Builder<DocumentationPageRecord> */
    private function query(): Builder
    {
        $model = new DocumentationPageRecord();
        if ($this->connection !== null) {
            $model->setConnection($this->connection);
        }

        return $model->newQuery();
    }

    private function table(): QueryBuilder
    {
        $model = new DocumentationPageRecord();
        if ($this->connection !== null) {
            $model->setConnection($this->connection);
        }

        return $model->getConnection()->table($model->getTable());
    }

    private function assertDurableConnection(): void
    {
        $model = new DocumentationPageRecord();
        if ($this->connection !== null) {
            $model->setConnection($this->connection);
        }

        $database = (string) $model->getConnection()->getConfig('database');
        if ($database === '' || $database === ':memory:') {
            throw new LogicException('Docara persistence requires a durable database; sqlite::memory: is not accepted.');
        }
    }

    private function assertSupportedPage(DocumentationPage $page): void
    {
        foreach ([
            'pageRef' => $page->pageRef,
            'slug' => $page->slug,
            'locale' => $page->locale,
            'title' => $page->title,
        ] as $field => $value) {
            if (trim($value) === '') {
                throw new InvalidArgumentException("DocumentationPage {$field} cannot be empty.");
            }
        }

        if ($page->sectionRefs !== []) {
            throw new InvalidArgumentException('Section persistence is outside the current DB-backed Page batch.');
        }

        $shouldBePublic = $page->visibility === DocumentationVisibility::Public
            && $page->publication->status === PublicationStatus::Published;
        if ($page->publication->publiclyVisible !== $shouldBePublic) {
            throw new InvalidArgumentException('Publication visibility must match page visibility and publication status.');
        }
        if ($page->publication->status === PublicationStatus::Published
            && $page->publication->publishedAt === null) {
            throw new InvalidArgumentException('Published pages require publishedAt.');
        }
    }

    private function toDomain(DocumentationPageRecord $record): DocumentationPage
    {
        $publishedAt = $record->getAttribute('published_at');

        return new DocumentationPage(
            pageRef: (string) $record->getAttribute('page_ref'),
            slug: (string) $record->getAttribute('slug'),
            locale: (string) $record->getAttribute('locale'),
            visibility: $record->getAttribute('visibility'),
            publication: new PublicationState(
                status: $record->getAttribute('publication_status'),
                version: (string) $record->getAttribute('version'),
                publiclyVisible: $record->getAttribute('visibility') === DocumentationVisibility::Public
                    && $record->getAttribute('publication_status') === PublicationStatus::Published,
                publishedAt: $publishedAt instanceof DateTimeInterface ? $publishedAt->format(DATE_ATOM) : null,
            ),
            title: (string) $record->getAttribute('title'),
            body: (string) $record->getAttribute('body'),
            assets: $this->assets((array) ($record->getAttribute('assets') ?? [])),
        );
    }

    private function rowToDomain(stdClass $record): DocumentationPage
    {
        $visibility = DocumentationVisibility::from((string) $record->visibility);
        $status = PublicationStatus::from((string) $record->publication_status);
        $publishedAt = $record->published_at === null
            ? null
            : CarbonImmutable::parse((string) $record->published_at, 'UTC')->toAtomString();

        return new DocumentationPage(
            pageRef: (string) $record->page_ref,
            slug: (string) $record->slug,
            locale: (string) $record->locale,
            visibility: $visibility,
            publication: new PublicationState(
                status: $status,
                version: (string) $record->version,
                publiclyVisible: $visibility === DocumentationVisibility::Public
                    && $status === PublicationStatus::Published,
                publishedAt: $publishedAt,
            ),
            title: (string) $record->title,
            body: (string) $record->body,
            assets: $this->assets(json_decode((string) ($record->assets ?? '[]'), true) ?: []),
        );
    }

    /** @param array<int,array<string,mixed>> $items @return list<DocumentationAssetRef> */
    private function assets(array $items): array
    {
        return array_values(array_map(static fn(array $item): DocumentationAssetRef => new DocumentationAssetRef((string)($item['logical_file_ref']??''),(string)($item['purpose']??'hero'),isset($item['alt_text'])?(string)$item['alt_text']:null),$items));
    }
}
