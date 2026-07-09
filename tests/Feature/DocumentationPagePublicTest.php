<?php

declare(strict_types=1);

namespace Larena\Docara\Tests\Feature;

use Illuminate\Support\Facades\DB;
use Larena\Docara\Contracts\DocumentationPage;
use Larena\Docara\Contracts\DocumentationPageRepository;
use Larena\Docara\Contracts\PublicationState;
use Larena\Docara\Enums\DocumentationVisibility;
use Larena\Docara\Enums\PublicationStatus;
use Larena\Docara\Tests\TestCase;

final class DocumentationPagePublicTest extends TestCase
{
    public function testAnonymousPublishedPageIsEscapedReadOnlyAndSurvivesReconnect(): void
    {
        $this->repository()->save($this->page(PublicationStatus::Published, '<Welcome>', '<script>alert(1)</script>'));
        $pageCount = DB::table('docara_pages')->count();
        $auditCount = DB::table('larena_audit_events')->count();

        $this->get('/docs/welcome')
            ->assertOk()
            ->assertSee('&lt;Welcome&gt;', false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false);

        self::assertSame($pageCount, DB::table('docara_pages')->count());
        self::assertSame($auditCount, DB::table('larena_audit_events')->count());

        $this->refreshApplication();
        $this->get('/docs/welcome')->assertOk()->assertSee('&lt;Welcome&gt;', false);
    }

    public function testAnonymousDraftAndMissingPagesReturn404(): void
    {
        $this->repository()->save($this->page(PublicationStatus::Draft, 'Draft', 'Hidden'));

        $this->get('/docs/welcome')->assertNotFound();
        $this->get('/docs/missing')->assertNotFound();
    }

    private function page(PublicationStatus $status, string $title, string $body): DocumentationPage
    {
        $published = $status === PublicationStatus::Published;

        return new DocumentationPage(
            pageRef: 'docara:page:welcome',
            slug: 'welcome',
            locale: 'en',
            visibility: DocumentationVisibility::Public,
            publication: new PublicationState(
                status: $status,
                version: '1',
                publiclyVisible: $published,
                publishedAt: $published ? '2026-07-09T12:00:00+00:00' : null,
            ),
            title: $title,
            body: $body,
        );
    }

    private function repository(): DocumentationPageRepository
    {
        return $this->app->make(DocumentationPageRepository::class);
    }
}
