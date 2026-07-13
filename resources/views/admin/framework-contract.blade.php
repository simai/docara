@extends('larena-admin::layouts.app')

@section('title', __('larena-docara::admin.framework_contract.title').' · Larena')
@section('eyebrow', __('larena-docara::admin.framework_contract.eyebrow'))
@section('heading', __('larena-docara::admin.framework_contract.heading'))
@section('description', __('larena-docara::admin.framework_contract.description'))

@section('content')
    <link rel="stylesheet" href="{{ route('larena.docara.assets.show', ['assetKey' => \Larena\Docara\Assets\DocumentationPageAssetManifest::FRAMEWORK_CATALOG_CSS_KEY, 'v' => \Larena\Docara\Assets\DocumentationPageAssetManifest::ASSET_VERSION]) }}">
    <script src="{{ route('larena.docara.assets.show', ['assetKey' => \Larena\Docara\Assets\DocumentationPageAssetManifest::FRAMEWORK_CATALOG_JS_KEY, 'v' => \Larena\Docara\Assets\DocumentationPageAssetManifest::ASSET_VERSION]) }}" defer></script>
    <section
        data-larena-framework-contract="{{ \Larena\Docara\Ui\DocaraFrameworkAdapterContribution::ADAPTER_ID }}"
        data-framework-compatibility-id="{{ $frameworkPlan['compatibility_id'] }}"
        data-framework-registry-sha256="{{ $frameworkPlan['registry_sha256'] }}"
        data-framework-plan-sha256="{{ $frameworkPlan['plan_sha256'] }}"
        data-framework-data-source="{{ $frameworkPlan['adapter']['data']['source'] }}"
        data-framework-effects-allowed="false"
        data-framework-read-only="true"
        data-framework-upstream-gap="{{ $frameworkPlan['adapter']['support']['upstream_gap'] }}"
        data-framework-fallback="{{ $frameworkPlan['adapter']['support']['fallback'] }}"
        data-production-ready="false"
        data-larena-framework-explorer
        data-framework-registry-count="{{ $frameworkExplorer['counts']['total'] }}"
    >
        <section class="larena-framework-explorer" aria-labelledby="framework-explorer-heading">
            <header class="larena-framework-explorer__header">
                <p class="larena-framework-explorer__eyebrow">{{ __('larena-docara::admin.framework_contract.explorer_eyebrow') }}</p>
                <h2 id="framework-explorer-heading">{{ __('larena-docara::admin.framework_contract.explorer_heading') }}</h2>
                <p>{{ __('larena-docara::admin.framework_contract.explorer_description', ['count' => $frameworkExplorer['counts']['total']]) }}</p>
            </header>

            <div class="larena-framework-explorer__controls" data-framework-catalog-controls hidden>
                <label>
                    <span>{{ __('larena-docara::admin.framework_contract.search_label') }}</span>
                    <input type="search" data-framework-catalog-search placeholder="{{ __('larena-docara::admin.framework_contract.search_placeholder') }}" autocomplete="off">
                </label>
                <label>
                    <span>{{ __('larena-docara::admin.framework_contract.kind_label') }}</span>
                    <select data-framework-catalog-kind>
                        <option value="">{{ __('larena-docara::admin.framework_contract.kind_all') }}</option>
                        @foreach (['utility', 'component', 'smart-component', 'recipe'] as $kind)
                            <option value="{{ $kind }}">{{ __('larena-docara::admin.framework_contract.kinds.'.$kind) }} · {{ $frameworkExplorer['counts'][$kind] }}</option>
                        @endforeach
                    </select>
                </label>
                <button type="button" data-framework-catalog-reset>{{ __('larena-docara::admin.framework_contract.reset') }}</button>
            </div>
            <p class="larena-framework-explorer__results" data-framework-catalog-results data-framework-results-template="{{ __('larena-docara::admin.framework_contract.results', ['count' => ':count']) }}" aria-live="polite">{{ __('larena-docara::admin.framework_contract.results', ['count' => $frameworkExplorer['counts']['total']]) }}</p>

            <div class="larena-framework-explorer__table-wrap">
                <table class="larena-framework-explorer__table">
                    <thead>
                        <tr>
                            <th scope="col">{{ __('larena-docara::admin.framework_contract.columns.item') }}</th>
                            <th scope="col">{{ __('larena-docara::admin.framework_contract.columns.kind') }}</th>
                            <th scope="col" class="larena-framework-explorer__optional">{{ __('larena-docara::admin.framework_contract.columns.owner') }}</th>
                            <th scope="col">{{ __('larena-docara::admin.framework_contract.columns.details') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($frameworkExplorer['entries'] as $entry)
                            <tr data-framework-catalog-entry data-framework-kind="{{ $entry['kind'] }}" data-framework-search="{{ $entry['search_text'] }}">
                                <td>
                                    <strong>{{ $entry['title'] }}</strong>
                                    <code>{{ $entry['id'] }}</code>
                                </td>
                                <td>{{ __('larena-docara::admin.framework_contract.kinds.'.$entry['kind']) }}</td>
                                <td class="larena-framework-explorer__optional"><code>{{ $entry['owner'] }}</code></td>
                                <td>
                                    <details>
                                        <summary>{{ __('larena-docara::admin.framework_contract.details') }}</summary>
                                        <dl class="larena-framework-explorer__details">
                                            <div><dt>{{ __('larena-docara::admin.framework_contract.lifecycle') }}</dt><dd>{{ $entry['lifecycle'] }}</dd></div>
                                            <div><dt>{{ __('larena-docara::admin.framework_contract.readiness') }}</dt><dd>{{ $entry['readiness']['status'] }}</dd></div>
                                        </dl>
                                        @if ($entry['requires'] !== [])
                                            <p>{{ __('larena-docara::admin.framework_contract.dependencies') }}</p>
                                            <ul>@foreach ($entry['requires'] as $required)<li><code>{{ $required }}</code></li>@endforeach</ul>
                                        @endif
                                        @if ($entry['references'] !== [])
                                            <p>{{ __('larena-docara::admin.framework_contract.source_references') }}</p>
                                            <ul>@foreach ($entry['references'] as $reference)<li><code>{{ $reference }}</code></li>@endforeach</ul>
                                        @endif
                                    </details>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p data-framework-catalog-empty hidden>{{ __('larena-docara::admin.framework_contract.empty_filter') }}</p>
        </section>

        <section class="larena-framework-demos" aria-labelledby="framework-demos-heading">
            <header><h2 id="framework-demos-heading">{{ __('larena-docara::admin.framework_contract.demos_heading') }}</h2><p>{{ __('larena-docara::admin.framework_contract.demos_description') }}</p></header>
            <article>
                <h3>{{ __('larena-docara::admin.framework_contract.button_demo_title') }}</h3>
                <div class="larena-framework-demos__preview">{!! $buttonDemo !!}</div>
                <pre><code>Smart::render('sf-button', ['text' => '{{ __('larena-docara::admin.framework_contract.button_demo_action') }}', 'type' => 'primary']);</code></pre>
            </article>
            <article>
                <h3>{{ __('larena-docara::admin.framework_contract.layout_demo_title') }}</h3>
                <div class="larena-framework-demos__preview flex flex-col gap-1 overflow-x-auto w-full" data-framework-layout-demo>
                    <span>{{ __('larena-docara::admin.framework_contract.layout_demo_one') }}</span>
                    <span>{{ __('larena-docara::admin.framework_contract.layout_demo_two') }}</span>
                    <span>{{ __('larena-docara::admin.framework_contract.layout_demo_three') }}</span>
                </div>
                <pre><code>&lt;div class="flex flex-col gap-1 overflow-x-auto w-full"&gt;…&lt;/div&gt;</code></pre>
            </article>
            <article>
                <h3>{{ __('larena-docara::admin.framework_contract.table_demo_title') }}</h3>
                <p>{{ __('larena-docara::admin.framework_contract.table_demo_description') }}</p>
                @include('larena-admin::components.dataview', ['dataview' => $dataview])
                <pre><code>{{ $frameworkPlan['adapter']['upstream_recipe'] }} → {{ $frameworkPlan['adapter']['renderer']['smart_component'] }}</code></pre>
            </article>
        </section>
    </section>
@endsection
