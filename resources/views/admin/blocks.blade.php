@extends('larena-admin::layouts.app')

@section('title', __('larena-docara::admin.blocks.title', ['title' => $page->title]).' · Larena')
@section('eyebrow', __('larena-docara::admin.blocks.eyebrow'))
@section('heading', __('larena-docara::admin.blocks.heading', ['title' => $page->title]))
@section('description', __('larena-docara::admin.blocks.description'))
@section('actions')
    <a class="larena-button" href="{{ route('larena.docara.admin.pages.edit', ['slug' => $page->slug, 'locale' => $page->locale]) }}">{{ __('larena-docara::admin.actions.back_to_edit') }}</a>
    <a class="larena-button" href="{{ route('larena.docara.admin.pages.preview', ['slug' => $page->slug, 'locale' => $page->locale]) }}">{{ __('larena-docara::admin.actions.preview') }}</a>
@endsection

@section('content')
    <link rel="stylesheet" href="{{ route('larena.docara.assets.show', ['assetKey' => 'docara.admin.blocks.css', 'v' => \Larena\Docara\Assets\DocumentationPageAssetManifest::ASSET_VERSION]) }}">
    <script src="{{ route('larena.docara.assets.show', ['assetKey' => 'docara.admin.blocks.js', 'v' => \Larena\Docara\Assets\DocumentationPageAssetManifest::ASSET_VERSION]) }}" defer></script>

    @unless ($canWrite)
        <div class="larena-notice" role="status">{{ __('larena-docara::admin.blocks.read_only') }}</div>
    @endunless

    <form class="larena-block-editor" method="post" action="{{ route('larena.docara.admin.pages.blocks.update', ['slug' => $page->slug, 'locale' => $page->locale]) }}" data-page-block-editor>
        @csrf
        @method('PUT')
        <input type="hidden" name="locale" value="{{ $page->locale }}">
        <fieldset @disabled(!$canWrite)>
            <legend class="larena-sr-only">{{ __('larena-docara::admin.blocks.heading', ['title' => $page->title]) }}</legend>
            @if ($canWrite)
                <div class="larena-block-toolbar">
                    <label for="larena-block-type">{{ __('larena-docara::admin.blocks.add_label') }}</label>
                    <select id="larena-block-type" data-block-type>
                        @foreach ($definitions as $definition)
                            <option value="{{ $definition['key'] }}">{{ __('larena-docara::admin.'.$definition['label_key']) }}</option>
                        @endforeach
                    </select>
                    <button class="larena-button" type="button" data-add-block>{{ __('larena-docara::admin.blocks.add') }}</button>
                </div>
            @endif

            <p class="larena-block-empty" data-block-empty @if ($editorBlocks !== []) hidden @endif>{{ __('larena-docara::admin.blocks.empty') }}</p>
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
            <div class="larena-form-actions"><button class="larena-button larena-button-primary" type="submit">{{ __('larena-docara::admin.blocks.save_draft') }}</button></div>
        @endif
    </form>

    @if ($canPublish)
        <div class="larena-block-publish">
            <div><strong>{{ __('larena-docara::admin.blocks.publish_heading') }}</strong><p>{{ __('larena-docara::admin.blocks.publish_help') }}</p></div>
            <form method="post" action="{{ route('larena.docara.admin.pages.publish', ['slug' => $page->slug, 'locale' => $page->locale]) }}">
                @csrf
                <button class="larena-button larena-button-primary" type="submit">{{ __('larena-docara::admin.blocks.publish') }}</button>
            </form>
        </div>
    @endif
@endsection
