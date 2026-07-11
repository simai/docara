@extends('larena-admin::layouts.app')

@section('title', __('larena-docara::admin.pages.title').' · Larena')
@section('eyebrow', __('larena-docara::admin.pages.eyebrow'))
@section('heading', __('larena-docara::admin.pages.heading'))
@section('description', __('larena-docara::admin.pages.description'))
@section('actions')
    @if ($canWrite)
        {!! \Larena\Ui\SfActionLink::render(route('larena.docara.admin.pages.create'), __('larena-docara::admin.actions.create'), 'primary', 'default')->html !!}
    @endif
@endsection

@section('content')
    @include('larena-admin::components.dataview', ['dataview' => $dataview])
@endsection
