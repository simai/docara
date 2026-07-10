<?php

declare(strict_types=1);

namespace Larena\Docara\Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;
use Larena\Docara\Contracts\DocumentationPage;
use Larena\Docara\Contracts\DocumentationPageRepository;
use Larena\Docara\Contracts\PublicationState;
use Larena\Docara\Enums\DocumentationVisibility;
use Larena\Docara\Enums\PublicationStatus;
use Larena\Docara\Persistence\EloquentDocumentationPageRepository;
use Larena\Docara\Tests\TestCase;
use LogicException;

final class DocumentationPagePersistenceTest extends TestCase
{
    public function testDraftPersistsAcrossIndependentApplicationBoots(): void
    {
        $repository = $this->repository();
        $repository->save($this->page(status: PublicationStatus::Draft));

        $this->refreshApplication();

        $restored = (new EloquentDocumentationPageRepository('docara_testing'))
            ->findByLocaleAndSlug('en', 'welcome');

        self::assertNotNull($restored);
        self::assertSame('Welcome', $restored->title);
        self::assertSame('Stored on disk.', $restored->body);
        self::assertSame(PublicationStatus::Draft, $restored->publication->status);
    }

    public function testPublishedLookupExcludesDraftAndReturnsPublishedPage(): void
    {
        $repository = $this->repository();
        $repository->save($this->page(status: PublicationStatus::Draft));
        self::assertNull($repository->findPublishedByLocaleAndSlug('en', 'welcome'));

        $repository->save($this->page(status: PublicationStatus::Published));
        $published = $this->repository()->findPublishedByLocaleAndSlug('en', 'welcome');

        self::assertNotNull($published);
        self::assertTrue($published->publication->publiclyVisible);
        self::assertSame('2026-07-09T12:00:00+00:00', $published->publication->publishedAt);
    }

    public function testDraftAndPublishedRowsSurviveRebootButOnlyPublishedRowIsPublic(): void
    {
        $repository = $this->repository();
        $repository->save($this->page(
            status: PublicationStatus::Draft,
            pageRef: 'docara:page:draft',
            slug: 'draft-page',
        ));
        $repository->save($this->page(
            status: PublicationStatus::Published,
            pageRef: 'docara:page:published',
            slug: 'published-page',
        ));

        $this->refreshApplication();
        $rebootedRepository = $this->repository();

        self::assertNotNull($rebootedRepository->findByLocaleAndSlug('en', 'draft-page'));
        self::assertNotNull($rebootedRepository->findByLocaleAndSlug('en', 'published-page'));
        self::assertNull($rebootedRepository->findPublishedByLocaleAndSlug('en', 'draft-page'));
        self::assertNotNull($rebootedRepository->findPublishedByLocaleAndSlug('en', 'published-page'));
    }

    public function testLocaleAndSlugCombinationIsUnique(): void
    {
        $repository = $this->repository();
        $repository->save($this->page(pageRef: 'docara:page:first'));

        $this->expectException(QueryException::class);
        $repository->save($this->page(pageRef: 'docara:page:second'));
    }

    public function testInMemorySqliteIsRejected(): void
    {
        config()->set('database.connections.docara_memory', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $this->expectException(LogicException::class);
        (new EloquentDocumentationPageRepository('docara_memory'))
            ->findByLocaleAndSlug('en', 'welcome');
    }

    public function testMigrationRollbackRemovesOwnedTable(): void
    {
        self::assertTrue(Schema::connection('docara_testing')->hasTable('docara_pages'));
        self::assertTrue(Schema::connection('docara_testing')->hasTable('docara_menus'));
        self::assertTrue(Schema::connection('docara_testing')->hasTable('docara_menu_items'));

        $this->artisan('migrate:rollback', [
            '--database' => 'docara_testing',
            '--force' => true,
        ])->assertSuccessful();

        self::assertFalse(Schema::connection('docara_testing')->hasTable('docara_pages'));
        self::assertFalse(Schema::connection('docara_testing')->hasTable('docara_menus'));
        self::assertFalse(Schema::connection('docara_testing')->hasTable('docara_menu_items'));
    }

    private function page(
        PublicationStatus $status = PublicationStatus::Draft,
        string $pageRef = 'docara:page:welcome',
        string $slug = 'welcome',
    ): DocumentationPage {
        $published = $status === PublicationStatus::Published;

        return new DocumentationPage(
            pageRef: $pageRef,
            slug: $slug,
            locale: 'en',
            visibility: DocumentationVisibility::Public,
            publication: new PublicationState(
                status: $status,
                version: $published ? '1.0.0' : '1.0.0-draft',
                publiclyVisible: $published,
                publishedAt: $published ? '2026-07-09T12:00:00+00:00' : null,
            ),
            title: 'Welcome',
            body: 'Stored on disk.',
        );
    }

    private function repository(): DocumentationPageRepository
    {
        return $this->app->make(DocumentationPageRepository::class);
    }
}
