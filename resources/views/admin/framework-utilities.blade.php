@extends('larena-admin::layouts.app')

@section('title', __('larena-docara::admin.framework_utilities.title').' · Larena')
@section('eyebrow', __('larena-docara::admin.framework_utilities.eyebrow'))
@section('heading', __('larena-docara::admin.framework_utilities.heading'))
@section('description', __('larena-docara::admin.framework_utilities.description', ['count' => $utilityExplorer['counts']['utilities']]))

@section('content')
    <link rel="stylesheet" href="{{ route('larena.docara.assets.show', ['assetKey' => \Larena\Docara\Assets\DocumentationPageAssetManifest::FRAMEWORK_CATALOG_CSS_KEY, 'v' => \Larena\Docara\Assets\DocumentationPageAssetManifest::ASSET_VERSION]) }}">
    <script src="{{ route('larena.docara.assets.show', ['assetKey' => \Larena\Docara\Assets\DocumentationPageAssetManifest::FRAMEWORK_CATALOG_JS_KEY, 'v' => \Larena\Docara\Assets\DocumentationPageAssetManifest::ASSET_VERSION]) }}" defer></script>

    <section data-larena-utility-explorer data-framework-registry-count="{{ $utilityExplorer['counts']['utilities'] }}" data-framework-registry-sha256="{{ $utilityExplorer['registry_sha256'] }}" data-framework-read-only="true" data-production-ready="false">
        <section class="larena-framework-explorer" aria-labelledby="utility-explorer-heading">
            <header class="larena-framework-explorer__header">
                <p class="larena-framework-explorer__eyebrow">{{ __('larena-docara::admin.framework_utilities.pinned_contract') }}</p>
                <h2 id="utility-explorer-heading">{{ __('larena-docara::admin.framework_utilities.utility_heading') }}</h2>
                <p>{{ __('larena-docara::admin.framework_utilities.utility_description') }}</p>
            </header>

            <p><a href="{{ route('larena.docara.admin.pages.framework.contract') }}">{{ __('larena-docara::admin.framework_utilities.back_to_framework') }}</a></p>

            <div class="larena-framework-explorer__controls" data-framework-catalog-controls hidden>
                <label>
                    <span>{{ __('larena-docara::admin.framework_utilities.search_label') }}</span>
                    <input type="search" data-framework-catalog-search placeholder="{{ __('larena-docara::admin.framework_utilities.search_placeholder') }}" autocomplete="off">
                </label>
                <label>
                    <span>{{ __('larena-docara::admin.framework_utilities.readiness_label') }}</span>
                    <select data-framework-catalog-kind>
                        <option value="">{{ __('larena-docara::admin.framework_utilities.readiness_all') }}</option>
                        <option value="ready">{{ __('larena-docara::admin.framework_utilities.ready') }}</option>
                        <option value="discoverable">{{ __('larena-docara::admin.framework_utilities.discoverable') }}</option>
                    </select>
                </label>
                <button type="button" data-framework-catalog-reset>{{ __('larena-docara::admin.framework_utilities.reset') }}</button>
            </div>
            <p class="larena-framework-explorer__results" data-framework-catalog-results data-framework-results-template="{{ __('larena-docara::admin.framework_utilities.results', ['count' => ':count']) }}" aria-live="polite">{{ __('larena-docara::admin.framework_utilities.results', ['count' => $utilityExplorer['counts']['utilities']]) }}</p>

            <div class="larena-framework-explorer__table-wrap">
                <table class="larena-framework-explorer__table">
                    <thead><tr><th scope="col">{{ __('larena-docara::admin.framework_utilities.columns.utility') }}</th><th scope="col">{{ __('larena-docara::admin.framework_utilities.columns.readiness') }}</th><th scope="col">{{ __('larena-docara::admin.framework_utilities.columns.details') }}</th></tr></thead>
                    <tbody>
                        @foreach ($utilityExplorer['utilities'] as $utility)
                            <tr data-framework-catalog-entry data-framework-kind="{{ $utility['readiness']['status'] }}" data-framework-search="{{ $utility['search_text'] }}">
                                <td><strong>{{ $utility['title'] }}</strong><code>{{ $utility['id'] }}</code><small>{{ $utility['purpose'] }}</small></td>
                                <td>{{ $utility['readiness']['status'] }}</td>
                                <td>
                                    <details>
                                        <summary>{{ __('larena-docara::admin.framework_utilities.details') }}</summary>
                                        <dl class="larena-framework-explorer__details">
                                            <div><dt>{{ __('larena-docara::admin.framework_utilities.owner') }}</dt><dd><code>{{ $utility['owner'] }}</code></dd></div>
                                            <div><dt>{{ __('larena-docara::admin.framework_utilities.lifecycle') }}</dt><dd>{{ $utility['lifecycle'] }}</dd></div>
                                            <div><dt>{{ __('larena-docara::admin.framework_utilities.rule_families') }}</dt><dd>{{ implode(', ', $utility['parameters']['rule_names']) ?: __('larena-docara::admin.framework_utilities.not_declared') }}</dd></div>
                                            <div><dt>{{ __('larena-docara::admin.framework_utilities.asset_root') }}</dt><dd><code>{{ $utility['parameters']['asset_root'] }}</code></dd></div>
                                        </dl>
                                        <p>{{ __('larena-docara::admin.framework_utilities.value_grammar_note') }}</p>
                                        @if ($utility['constraints']['requires'] !== [])
                                            <p>{{ __('larena-docara::admin.framework_utilities.requires') }}</p><ul>@foreach ($utility['constraints']['requires'] as $required)<li><code>{{ $required }}</code></li>@endforeach</ul>
                                        @endif
                                        <p>{{ __('larena-docara::admin.framework_utilities.source_references') }}</p><ul>@foreach ($utility['references'] as $reference)<li><code>{{ $reference }}</code></li>@endforeach</ul>
                                    </details>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p data-framework-catalog-empty hidden>{{ __('larena-docara::admin.framework_utilities.empty_filter') }}</p>
        </section>

        <section class="larena-framework-demos" aria-labelledby="utility-recipes-heading">
            <header><h2 id="utility-recipes-heading">{{ __('larena-docara::admin.framework_utilities.recipes_heading', ['count' => $utilityExplorer['counts']['recipes']]) }}</h2><p>{{ __('larena-docara::admin.framework_utilities.recipes_description') }}</p></header>
            @foreach ($utilityExplorer['recipes'] as $recipe)
                <article data-framework-utility-recipe="{{ $recipe['id'] }}">
                    <h3>{{ $recipe['title'] }}</h3>
                    <p>{{ $recipe['description'] }}</p>
                    <div class="larena-framework-demos__preview">
                        @if ($recipe['id'] === 'layout.vertical-stack')
                            <div class="{{ $recipe['classes'] }}"><span>{{ __('larena-docara::admin.framework_utilities.demo_one') }}</span><span>{{ __('larena-docara::admin.framework_utilities.demo_two') }}</span><span>{{ __('larena-docara::admin.framework_utilities.demo_three') }}</span></div>
                        @elseif ($recipe['id'] === 'layout.balanced-toolbar')
                            <div class="{{ $recipe['classes'] }}"><strong>{{ __('larena-docara::admin.framework_utilities.demo_title') }}</strong><span>{{ __('larena-docara::admin.framework_utilities.demo_action') }}</span></div>
                        @elseif ($recipe['id'] === 'layout.scroll-safe-region')
                            <div class="{{ $recipe['classes'] }}"><span class="larena-framework-utility-wide">{{ __('larena-docara::admin.framework_utilities.demo_wide') }}</span></div>
                        @else
                            <div class="{{ $recipe['classes'] }}"><span>{{ __('larena-docara::admin.framework_utilities.demo_one') }}</span><span>{{ __('larena-docara::admin.framework_utilities.demo_two') }}</span><span>{{ __('larena-docara::admin.framework_utilities.demo_three') }}</span></div>
                        @endif
                    </div>
                    <pre><code>&lt;div class="{{ $recipe['classes'] }}"&gt;…&lt;/div&gt;</code></pre>
                    <p><strong>{{ __('larena-docara::admin.framework_utilities.used_utilities') }}</strong> @foreach ($recipe['utility_ids'] as $utilityId)<code>{{ $utilityId }}</code>@if (! $loop->last), @endif @endforeach</p>
                    <details><summary>{{ __('larena-docara::admin.framework_utilities.source_references') }}</summary><ul>@foreach ($recipe['source_refs'] as $reference)<li><code>{{ $reference }}</code></li>@endforeach</ul></details>
                </article>
            @endforeach
        </section>
    </section>
@endsection
