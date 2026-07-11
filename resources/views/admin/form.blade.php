@extends('larena-admin::layouts.app')

@section('title', ($editing ? __('larena-docara::admin.form.edit_title') : __('larena-docara::admin.form.create_title')).' · Larena')
@section('eyebrow', __('larena-docara::admin.form.eyebrow'))
@section('heading', $editing ? __('larena-docara::admin.form.edit_heading') : __('larena-docara::admin.form.create_heading'))
@section('description', $editing ? __('larena-docara::admin.form.edit_description') : __('larena-docara::admin.form.create_description'))
@section('actions')
    {!! \Larena\Ui\SfActionLink::render(route('larena.docara.admin.pages.index'), __('larena-docara::admin.actions.back'))->html !!}
    @if ($editing)
        {!! \Larena\Ui\SfActionLink::render(route('larena.docara.admin.pages.preview', ['slug' => $page->slug, 'locale' => $page->locale]), __('larena-docara::admin.actions.preview'))->html !!}
        {!! \Larena\Ui\SfActionLink::render(route('larena.docara.admin.pages.blocks.edit', ['slug' => $page->slug, 'locale' => $page->locale]), __('larena-docara::admin.actions.compose_blocks'))->html !!}
    @endif
@endsection

@section('content')
    @if ($editing)
        <div class="larena-form-actions" aria-label="{{ __('larena-docara::admin.form.publication_status') }}">
            {{ __('larena-docara::admin.form.current_status') }}
            {!! $formComponents['status'](__('larena-docara::admin.statuses.'.$page->publication->status->value), $page->publication->status->value) !!}
        </div>
    @endif

    <section class="larena-panel">
        <form class="larena-form" method="post" action="{{ $editing ? route('larena.docara.admin.pages.update', ['slug' => $page->slug, 'locale' => $page->locale]) : route('larena.docara.admin.pages.store') }}">
            @csrf
            @if ($editing) @method('PUT') @endif
            <div class="larena-form-grid">
                {!! $formComponents['title'] !!}
                {!! $formComponents['slug'] !!}
                {!! $formComponents['locale'] !!}
                @if($editing)<input type="hidden" name="locale" value="{{ $page->locale }}">@endif
            </div>
            {!! $formComponents['body'] !!}
            {!! $formComponents['hero'] !!}
            {!! $formComponents['publication_status'] !!}
            @if ($editing && $page->publication->status->value === 'published')<input type="hidden" name="status" value="published">@endif
            <div class="larena-form-actions">
                {!! $formComponents['save'] !!}
            </div>
        </form>
    </section>

    @if ($editing)
        <div class="larena-form-actions larena-secondary-action" aria-label="{{ __('larena-docara::admin.form.publication_actions') }}">
            @if ($page->publication->status->value === 'published')
                {!! \Larena\Ui\SfActionLink::render(route('larena.docara.public.show', ['slug' => $page->slug, 'locale' => $page->locale]), __('larena-docara::admin.actions.view_live'))->html !!}
                @if ($canPublish)
                    <form method="post" action="{{ route('larena.docara.admin.pages.unpublish', ['slug' => $page->slug, 'locale' => $page->locale]) }}">
                        @csrf
                        {!! $formComponents['unpublish'] !!}
                    </form>
                @endif
            @elseif ($canPublish)
                <form method="post" action="{{ route('larena.docara.admin.pages.publish', ['slug' => $page->slug, 'locale' => $page->locale]) }}">
                    @csrf
                    {!! $formComponents['publish'] !!}
                </form>
            @endif
        </div>
    @endif
@endsection
