@extends('larena-admin::layouts.app')

@section('title', __('larena-docara::admin.pages.title').' · Larena')
@section('eyebrow', __('larena-docara::admin.pages.eyebrow'))
@section('heading', __('larena-docara::admin.pages.heading'))
@section('description', __('larena-docara::admin.pages.description'))
@section('actions')
    @if ($canWrite)
        <a class="larena-button larena-button-primary" href="{{ route('larena.docara.admin.pages.create') }}">{{ __('larena-docara::admin.actions.create') }}</a>
    @endif
@endsection

@section('content')
    @include('larena-admin::components.dataview', ['dataview' => $dataview])
@endsection
