<?php

declare(strict_types=1);

namespace Larena\Docara\Settings;

use Illuminate\Database\ConnectionInterface;
use Larena\Docara\Contracts\DocumentationPage;
use Larena\Docara\Contracts\DocumentationPageRepository;
use Larena\Filesystem\Persistence\DatabaseLogicalFileRepository;
use Larena\Filesystem\Services\SafeFileService;
use Larena\Setting\Runtime\SiteSettingStore;
use stdClass;

final readonly class DocaraSiteSettingsService
{
    /** @return array<string, mixed> */
    public function defaults(): array
    {
        return [
            'name.en' => 'Larena', 'name.ru' => 'Larena',
            'description.en' => '', 'description.ru' => '',
            'default_locale' => 'en',
            'logo_file_ref' => null, 'favicon_file_ref' => null,
            'homepage_page_ref.en' => null, 'homepage_page_ref.ru' => null,
        ];
    }

    public function __construct(
        private SiteSettingStore $settings,
        private DocumentationPageRepository $pages,
        private DatabaseLogicalFileRepository $files,
        private SafeFileService $fileService,
        private ConnectionInterface $connection,
    ) {
    }

    /** @return array<string, mixed> */
    public function read(): array
    {
        return $this->settings->get($this->defaults());
    }

    /** @param array<string, mixed> $values @return list<string> */
    public function update(array $values, string $actor): array
    {
        return $this->settings->put($values, $actor);
    }

    /** @return list<array{page_ref:string,title:string,locale:string,slug:string}> */
    public function publishedPages(): array
    {
        return $this->connection->table('docara_pages')
            ->where('visibility', 'public')->where('publication_status', 'published')->whereNotNull('published_at')
            ->orderBy('locale')->orderBy('title')->get()
            ->map(static fn (stdClass $row): array => ['page_ref' => (string) $row->page_ref, 'title' => (string) $row->title, 'locale' => (string) $row->locale, 'slug' => (string) $row->slug])
            ->values()->all();
    }

    /** @return list<array{logical_ref:string,display_name:string,mime_type:string}> */
    public function publicImages(): array
    {
        return $this->files->all()
            ->filter(static fn ($file): bool => $file->getAttribute('visibility') === 'public' && str_starts_with((string) $file->getAttribute('mime_type'), 'image/'))
            ->map(static fn ($file): array => ['logical_ref' => (string) $file->getAttribute('logical_ref'), 'display_name' => (string) $file->getAttribute('display_name'), 'mime_type' => (string) $file->getAttribute('mime_type')])
            ->values()->all();
    }

    public function eligiblePage(string $pageRef, string $locale): bool
    {
        $page = $this->pages->findPublishedByPageRef($pageRef);
        return $page !== null && $page->locale === $locale;
    }

    public function eligibleImage(string $logicalRef): bool
    {
        $file = $this->files->find($logicalRef);
        return $file !== null && $file->getAttribute('visibility') === 'public' && str_starts_with((string) $file->getAttribute('mime_type'), 'image/');
    }

    /** @return array{page:DocumentationPage,locale:string,name:string,description:string,logo_url:?string,favicon_url:?string}|null */
    public function homepage(?string $requestedLocale): ?array
    {
        $values = $this->read();
        $default = in_array($values['default_locale'], ['en', 'ru'], true) ? (string) $values['default_locale'] : 'en';
        $locale = in_array($requestedLocale, ['en', 'ru'], true) ? (string) $requestedLocale : $default;
        $pageRef = trim((string) ($values['homepage_page_ref.' . $locale] ?? ''));
        if ($pageRef === '' && $locale !== $default) {
            $locale = $default;
            $pageRef = trim((string) ($values['homepage_page_ref.' . $locale] ?? ''));
        }
        if ($pageRef === '') {
            return null;
        }
        $page = $this->pages->findPublishedByPageRef($pageRef);
        if ($page === null || $page->locale !== $locale) {
            return null;
        }

        return ['page' => $page, 'locale' => $locale] + $this->identity($values, $locale);
    }

    /** @return array{name:string,description:string,logo_url:?string,favicon_url:?string} */
    public function identityFor(string $locale): array
    {
        return $this->identity($this->read(), in_array($locale, ['en', 'ru'], true) ? $locale : 'en');
    }

    /** @param array<string,mixed> $values @return array{name:string,description:string,logo_url:?string,favicon_url:?string} */
    private function identity(array $values, string $locale): array
    {
        return [
            'name' => trim((string) ($values['name.' . $locale] ?? '')) ?: 'Larena',
            'description' => trim((string) ($values['description.' . $locale] ?? '')),
            'logo_url' => $this->publicUrl((string) ($values['logo_file_ref'] ?? '')),
            'favicon_url' => $this->publicUrl((string) ($values['favicon_file_ref'] ?? '')),
        ];
    }

    private function publicUrl(string $logicalRef): ?string
    {
        if ($logicalRef === '') {
            return null;
        }
        $file = $this->files->find($logicalRef);
        if ($file === null || $file->getAttribute('visibility') !== 'public' || !str_starts_with((string) $file->getAttribute('mime_type'), 'image/')) {
            return null;
        }
        return $this->fileService->publicUrl($file);
    }
}
