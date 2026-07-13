@extends('larena-admin::layouts.app')

@section('title', __('larena-docara::admin.framework_contract.title').' · Larena')
@section('eyebrow', __('larena-docara::admin.framework_contract.eyebrow'))
@section('heading', __('larena-docara::admin.framework_contract.heading'))
@section('description', __('larena-docara::admin.framework_contract.description'))

@section('content')
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
    >
        @include('larena-admin::components.dataview', ['dataview' => $dataview])

        <details data-larena-framework-contract-details>
            <summary>{{ __('larena-docara::admin.framework_contract.summary') }}</summary>
            <dl>
                <div>
                    <dt>{{ __('larena-docara::admin.framework_contract.compatibility') }}</dt>
                    <dd><code>{{ $frameworkPlan['compatibility_id'] }}</code></dd>
                </div>
                <div>
                    <dt>{{ __('larena-docara::admin.framework_contract.recipe') }}</dt>
                    <dd><code>{{ $frameworkPlan['adapter']['upstream_recipe'] }}</code></dd>
                </div>
            </dl>
            <p>{{ __('larena-docara::admin.framework_contract.selected') }}</p>
            <ul>
                @foreach ($frameworkPlan['entry_ids'] as $entryId)
                    <li><code>{{ $entryId }}</code></li>
                @endforeach
            </ul>
        </details>
    </section>
@endsection
