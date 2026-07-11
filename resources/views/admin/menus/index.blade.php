@extends('larena-admin::layouts.app')

@section('title', __('larena-docara::admin.menus.title').' · Larena')
@section('eyebrow', __('larena-docara::admin.menus.eyebrow'))
@section('heading', __('larena-docara::admin.menus.heading'))
@section('description', __('larena-docara::admin.menus.description'))
@section('actions')
    @if ($canWrite){!! \Larena\Ui\SfActionLink::render(route('larena.docara.admin.menus.create'), __('larena-docara::admin.menus.actions.create'), 'primary', 'default')->html !!}@endif
@endsection

@section('content')
@foreach ($dataview['asset_tags'] as $assetTag){!! $assetTag !!}@endforeach
{!! $dataview['html'] !!}
@endsection
