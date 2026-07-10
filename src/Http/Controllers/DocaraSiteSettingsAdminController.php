<?php

declare(strict_types=1);

namespace Larena\Docara\Http\Controllers;

use Illuminate\Contracts\Translation\Translator;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Routing\Redirector;
use Illuminate\Validation\ValidationException;
use Larena\Access\Runtime\AccessOperationAuthorizer;
use Larena\Docara\Settings\DocaraSiteSettingsService;

final class DocaraSiteSettingsAdminController extends Controller
{
    public function __construct(
        private DocaraSiteSettingsService $settings,
        private ViewFactory $views,
        private Redirector $redirector,
        private Translator $translator,
        private AccessOperationAuthorizer $access,
    ) {
    }

    public function edit(Request $request): View
    {
        return $this->views->make('larena-docara::admin.site-settings', [
            'settings' => $this->settings->read(),
            'pages' => $this->settings->publishedPages(),
            'images' => $this->settings->publicImages(),
            'canWrite' => $this->access->authorize($request, 'setting.site.write')->isAllowed(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        /** @var array<string, string|null> $validated */
        $validated = $request->validate([
            'name_en' => ['required', 'string', 'max:120'],
            'name_ru' => ['required', 'string', 'max:120'],
            'description_en' => ['nullable', 'string', 'max:500'],
            'description_ru' => ['nullable', 'string', 'max:500'],
            'default_locale' => ['required', 'in:en,ru'],
            'logo_file_ref' => ['nullable', 'string'],
            'favicon_file_ref' => ['nullable', 'string'],
            'homepage_page_ref_en' => ['nullable', 'string'],
            'homepage_page_ref_ru' => ['nullable', 'string'],
        ]);

        $errors = [];
        foreach (['logo_file_ref', 'favicon_file_ref'] as $field) {
            $ref = trim((string) ($validated[$field] ?? ''));
            if ($ref !== '' && !$this->settings->eligibleImage($ref)) {
                $errors[$field] = $this->translator->get('larena-docara::admin.site_settings.validation.image');
            }
        }
        foreach (['en', 'ru'] as $locale) {
            $field = 'homepage_page_ref_' . $locale;
            $ref = trim((string) ($validated[$field] ?? ''));
            if ($ref !== '' && !$this->settings->eligiblePage($ref, $locale)) {
                $errors[$field] = $this->translator->get('larena-docara::admin.site_settings.validation.page');
            }
        }
        $defaultField = 'homepage_page_ref_' . $validated['default_locale'];
        if (trim((string) ($validated[$defaultField] ?? '')) === '') {
            $errors[$defaultField] = $this->translator->get('larena-docara::admin.site_settings.validation.default_homepage');
        }
        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        $this->settings->update([
            'name.en' => trim((string) $validated['name_en']),
            'name.ru' => trim((string) $validated['name_ru']),
            'description.en' => trim((string) ($validated['description_en'] ?? '')),
            'description.ru' => trim((string) ($validated['description_ru'] ?? '')),
            'default_locale' => $validated['default_locale'],
            'logo_file_ref' => $this->nullable($validated['logo_file_ref'] ?? null),
            'favicon_file_ref' => $this->nullable($validated['favicon_file_ref'] ?? null),
            'homepage_page_ref.en' => $this->nullable($validated['homepage_page_ref_en'] ?? null),
            'homepage_page_ref.ru' => $this->nullable($validated['homepage_page_ref_ru'] ?? null),
        ], (string) $request->attributes->get('larena_access_actor'));

        return $this->redirector->route('larena.docara.admin.site_settings.edit')
            ->with('status', $this->translator->get('larena-docara::admin.site_settings.saved'));
    }

    private function nullable(?string $value): ?string
    {
        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }
}
