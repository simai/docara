# Docara DB-backed page persistence

Status: `implementation_written`

This bounded batch adds package-owned persistence for documentation pages. `DocumentationPage` now carries `title` and `body`; `EloquentDocumentationPageRepository` saves and reads the page through the application database; `DocaraServiceProvider` binds the repository contract and registers the package migration. Composer Laravel package discovery registers that provider for a consuming application.

The `docara_pages` table owns `id`, `page_ref`, `slug`, `title`, `body`, `locale`, `visibility`, `publication_status`, `version`, `published_at`, and timestamps. It enforces unique `page_ref` and unique `(locale, slug)`.

The implementation deliberately contains no routes, controller, admin UI, public renderer, authentication, authorization, audit integration, or changes to another package. It is one persistence prerequisite for the Developer Alpha vertical slice, not a CMS-readiness claim.
