@extends('larena-admin::layouts.app')

@section('title', __('larena-docara::admin.framework_demo.title').' · Larena')
@section('eyebrow', __('larena-docara::admin.framework_demo.eyebrow'))
@section('heading', __('larena-docara::admin.framework_demo.heading'))
@section('description', __('larena-docara::admin.framework_demo.description'))

@section('content')
    <link rel="stylesheet" href="{{ $frameworkCatalogAssets['css'] }}">
    <script src="{{ $frameworkCatalogAssets['js'] }}" defer></script>

    <section class="larena-framework-demonstration" data-larena-framework-demonstration data-framework-entry-id="{{ $utility['id'] }}" data-framework-read-only="true" data-production-ready="false">
        <p><a href="{{ route('larena.docara.admin.pages.framework.utilities') }}">{{ __('larena-docara::admin.framework_demo.back_to_utilities') }}</a></p>
        <header>
            <p class="larena-framework-explorer__eyebrow">{{ __('larena-docara::admin.framework_utilities.live_example') }}</p>
            <h2>{{ __('larena-docara::admin.framework_utilities.' . $demonstration['title_key']) }}</h2>
            <p>{{ __('larena-docara::admin.framework_utilities.' . $demonstration['description_key']) }}</p>
            <p><strong>{{ __('larena-docara::admin.framework_demo.entry') }}</strong> <code>{{ $utility['id'] }}</code></p>
        </header>

        <section class="larena-framework-utility-demo" data-framework-utility-demo data-framework-base-classes="{{ $demonstration['base_classes'] }}" aria-labelledby="{{ $demonstration['id'] }}-heading">
            <h3 id="{{ $demonstration['id'] }}-heading">{{ __('larena-docara::admin.framework_demo.preview') }}</h3>
            <label>
                <span>{{ __('larena-docara::admin.framework_utilities.example_value') }}</span>
                <select data-framework-utility-demo-select>
                    @foreach ($demonstration['variants'] as $variant)
                        <option value="{{ $variant['classes'] }}">{{ $variant['id'] }}</option>
                    @endforeach
                </select>
            </label>
            <div class="larena-framework-utility-demo__preview {{ $demonstration['base_classes'] }} {{ $demonstration['variants'][0]['classes'] }}" data-framework-utility-demo-preview>
                <span>{{ __('larena-docara::admin.framework_utilities.demo_one') }}</span>
                <span>{{ __('larena-docara::admin.framework_utilities.demo_two') }}</span>
                <span>{{ __('larena-docara::admin.framework_utilities.demo_three') }}</span>
            </div>
            <pre><code data-framework-utility-demo-code>&lt;div class=&quot;{{ $demonstration['base_classes'] }} {{ $demonstration['variants'][0]['classes'] }}&quot;&gt;…&lt;/div&gt;</code></pre>
        </section>

        <section class="larena-framework-demonstration__metadata" aria-labelledby="{{ $demonstration['id'] }}-metadata">
            <h3 id="{{ $demonstration['id'] }}-metadata">{{ __('larena-docara::admin.framework_demo.contract') }}</h3>
            <p>{{ __('larena-docara::admin.framework_utilities.example_source_note') }}</p>
            <p><strong>{{ __('larena-docara::admin.framework_utilities.used_utilities') }}</strong> @foreach ($demonstration['utility_ids'] as $utilityId)<code>{{ $utilityId }}</code>@if (! $loop->last), @endif @endforeach</p>
            <details><summary>{{ __('larena-docara::admin.framework_utilities.source_references') }}</summary><ul>@foreach ($demonstration['source_refs'] as $reference)<li><code>{{ $reference }}</code></li>@endforeach</ul></details>
        </section>
    </section>
@endsection
