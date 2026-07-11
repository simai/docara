@extends('larena-admin::layouts.app')

@section('title', __('larena-docara::admin.blocks.title', ['title' => $page->title]).' · Larena')
@section('eyebrow', __('larena-docara::admin.blocks.eyebrow'))
@section('heading', __('larena-docara::admin.blocks.heading', ['title' => $page->title]))
@section('description', __('larena-docara::admin.blocks.description'))
@section('actions')
    {!! \Larena\Ui\SfActionLink::render(route('larena.docara.admin.pages.edit', ['slug' => $page->slug, 'locale' => $page->locale]), __('larena-docara::admin.actions.back_to_edit'))->html !!}
    {!! \Larena\Ui\SfActionLink::render(route('larena.docara.admin.pages.preview', ['slug' => $page->slug, 'locale' => $page->locale]), __('larena-docara::admin.actions.preview'))->html !!}
@endsection

@section('content')
    <link rel="stylesheet" href="{{ route('larena.docara.assets.show', ['assetKey' => 'docara.admin.blocks.css', 'v' => \Larena\Docara\Assets\DocumentationPageAssetManifest::ASSET_VERSION]) }}">
    <script src="{{ route('larena.docara.assets.show', ['assetKey' => 'docara.admin.blocks.js', 'v' => \Larena\Docara\Assets\DocumentationPageAssetManifest::ASSET_VERSION]) }}" defer></script>

    @unless ($canWrite)
        {!! $blockUi->alert('read_only') !!}
    @endunless

    <form class="larena-block-editor" method="post" action="{{ route('larena.docara.admin.pages.blocks.update', ['slug' => $page->slug, 'locale' => $page->locale]) }}" data-page-block-editor>
        @csrf
        @method('PUT')
        <input type="hidden" name="locale" value="{{ $page->locale }}">
        <fieldset @disabled(!$canWrite)>
            <legend class="larena-sr-only">{{ __('larena-docara::admin.blocks.heading', ['title' => $page->title]) }}</legend>
            @if ($canWrite)
                <div class="larena-block-toolbar">
                    <span data-block-type>{!! $blockUi->typeSelector($definitions) !!}</span>
                    <span data-add-block>{!! $blockUi->button('add') !!}</span>
                </div>
            @endif

            <div class="larena-block-empty" data-block-empty @if ($editorBlocks !== []) hidden @endif>{!! $blockUi->alert('empty') !!}</div>
            <div class="larena-block-list" data-block-list>
                @foreach ($editorBlocks as $block)
                    @include('larena-docara::admin.partials.block-card', ['block' => $block, 'readOnly' => !$canWrite])
                @endforeach
            </div>

            @foreach ($definitions as $definition)
                <template data-block-template="{{ $definition['key'] }}">
                    @include('larena-docara::admin.partials.block-card', ['block' => ['index' => '__INDEX__', 'position' => '__POSITION__', 'definition' => $definition, 'value' => ['instance_id' => '__INSTANCE__', 'type' => $definition['key'], 'enabled' => true, 'sort' => 100, 'settings' => []]], 'readOnly' => false])
                </template>
            @endforeach
        </fieldset>

        @if ($canWrite)
            <div class="larena-form-actions">{!! $blockUi->button('save_draft', 'primary', 'submit') !!}</div>
        @endif
    </form>

    @if ($canPublish)
        <div class="larena-block-publish">
            <div><strong>{{ __('larena-docara::admin.blocks.publish_heading') }}</strong><p>{{ __('larena-docara::admin.blocks.publish_help') }}</p></div>
            <form method="post" action="{{ route('larena.docara.admin.pages.publish', ['slug' => $page->slug, 'locale' => $page->locale]) }}">
                @csrf
                {!! $blockUi->button('publish', 'primary', 'submit') !!}
            </form>
        </div>
    @endif
@endsection
