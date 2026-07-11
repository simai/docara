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
use Illuminate\Support\ViewErrorBag;
use Larena\Docara\Admin\DocaraSiteSettingsFormPresenter;

final class DocaraSiteSettingsAdminController extends Controller
{
    public function __construct(
        private DocaraSiteSettingsService $settings,
        private ViewFactory $views,
        private Redirector $redirector,
        private Translator $translator,
        private AccessOperationAuthorizer $access,
        private DocaraSiteSettingsFormPresenter $formPresenter,
    ) {
    }

    public function edit(Request $request): View
    {
        $settings = $this->settings->read();
        $pages = $this->settings->publishedPages();
        $images = $this->settings->publicImages();
        $canWrite = $this->access->authorize($request, 'setting.site.write')->isAllowed();

        return $this->views->make('larena-docara::admin.site-settings', [
            'canWrite' => $canWrite,
            'formComponents' => $this->formComponents($request, $settings, $pages, $images, $canWrite),
        ]);
    }

    /**
     * @param array<string,mixed> $settings
     * @param list<array{page_ref:string,title:string,locale:string,slug:string}> $pages
     * @param list<array{logical_ref:string,display_name:string,mime_type:string}> $images
     * @return array<string,string>
     */
    private function formComponents(Request $request, array $settings, array $pages, array $images, bool $canWrite): array
    {
        $values = [
            'name_en' => (string) $request->old('name_en', $settings['name.en']),
            'name_ru' => (string) $request->old('name_ru', $settings['name.ru']),
            'description_en' => (string) $request->old('description_en', $settings['description.en']),
            'description_ru' => (string) $request->old('description_ru', $settings['description.ru']),
            'default_locale' => (string) $request->old('default_locale', $settings['default_locale']),
            'logo_file_ref' => (string) $request->old('logo_file_ref', $settings['logo_file_ref']),
            'favicon_file_ref' => (string) $request->old('favicon_file_ref', $settings['favicon_file_ref']),
            'homepage_page_ref_en' => (string) $request->old('homepage_page_ref_en', $settings['homepage_page_ref.en']),
            'homepage_page_ref_ru' => (string) $request->old('homepage_page_ref_ru', $settings['homepage_page_ref.ru']),
        ];
        $bag = $request->session()->get('errors');
        $errors = [];
        if ($bag instanceof ViewErrorBag) {
            foreach ($bag->getBag('default')->getMessages() as $field => $messages) {
                $errors[$field] = implode(' ', $messages);
            }
        }

        return $this->formPresenter->present($values, $pages, $images, $errors, $canWrite);
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
