@extends('larena-admin::layouts.app')

@section('title', $menu->name.' · Larena')
@section('eyebrow', __('larena-docara::admin.menus.eyebrow'))
@section('heading', $menu->name)
@section('description', __('larena-docara::admin.menus.edit_description', ['code' => $menu->code, 'locale' => strtoupper($menu->locale)]))

@section('content')
<script src="{{ route('larena.docara.assets.show', ['assetKey' => \Larena\Docara\Assets\DocumentationPageAssetManifest::MENU_JS_KEY, 'v' => \Larena\Docara\Assets\DocumentationPageAssetManifest::ASSET_VERSION]) }}" defer></script>
{!! $validation !!}

<section class="larena-panel larena-menu-editor" aria-labelledby="menu-settings-heading">
    <h2 id="menu-settings-heading">{{ __('larena-docara::admin.menus.settings') }}</h2>
    @if ($canWrite)
        <form method="post" action="{{ route('larena.docara.admin.menus.update', ['menu' => $menu->id]) }}" class="larena-form larena-form-inline">@csrf @method('PUT')
            {!! $settingsComponents['name'] !!}{!! $settingsComponents['active'] !!}{!! $settingsComponents['submit'] !!}
        </form>
    @else
        <dl class="larena-definition"><div><dt>{{ __('larena-docara::admin.menus.fields.name') }}</dt><dd>{{ $menu->name }}</dd></div><div><dt>{{ __('larena-docara::admin.menus.fields.active') }}</dt><dd>{{ $menu->is_active ? __('larena-docara::admin.menus.active') : __('larena-docara::admin.menus.inactive') }}</dd></div></dl>
    @endif
</section>

<section class="larena-panel larena-menu-editor" aria-labelledby="menu-items-heading">
    <h2 id="menu-items-heading">{{ __('larena-docara::admin.menus.items_heading') }}</h2>
    <p>{{ __('larena-docara::admin.menus.items_help') }}</p>
    @if ($items->isEmpty()){!! $noItemsAlert !!}@endif
    <div class="larena-menu-items">
    @foreach ($items as $item)
        <article class="larena-menu-item larena-form-card" style="--menu-depth: {{ $item->parent_id ? 1 : 0 }}">
            @if ($canWrite)
                <form method="post" action="{{ route('larena.docara.admin.menus.items.update', ['menu' => $menu->id, 'item' => $item->id]) }}" class="larena-menu-item__form">@csrf @method('PUT')
                    {!! $itemComponents[$item->id]['label'] !!}{!! $itemComponents[$item->id]['parent'] !!}{!! $itemComponents[$item->id]['order'] !!}{!! $itemComponents[$item->id]['active'] !!}{!! $itemComponents[$item->id]['save'] !!}
                </form>
                <form method="post" action="{{ route('larena.docara.admin.menus.items.destroy', ['menu' => $menu->id, 'item' => $item->id]) }}" data-larena-confirm="{{ __('larena-docara::admin.menus.confirm_item_remove') }}">@csrf @method('DELETE'){!! $itemComponents[$item->id]['remove'] !!}</form>
            @else
                <strong>{{ $item->label }}</strong><span>{{ __('larena-docara::admin.menus.fields.order') }}: {{ $item->sort_order }}</span>
            @endif
        </article>
    @endforeach
    </div>

    @if ($canWrite)
        <form method="post" action="{{ route('larena.docara.admin.menus.items.store', ['menu' => $menu->id]) }}" class="larena-form larena-form-card larena-menu-add">@csrf
            <h3>{{ __('larena-docara::admin.menus.add_item') }}</h3>
            @if ($pages === []){!! $noPagesAlert !!}@else
                {!! $addComponents['page'] !!}{!! $addComponents['label'] !!}{!! $addComponents['parent'] !!}{!! $addComponents['order'] !!}{!! $addComponents['active'] !!}{!! $addComponents['submit'] !!}
            @endif
        </form>
    @endif
</section>

@if ($canDelete)
<section class="larena-panel larena-danger-zone" aria-labelledby="menu-delete-heading"><h2 id="menu-delete-heading">{{ __('larena-docara::admin.menus.delete_heading') }}</h2><p>{{ __('larena-docara::admin.menus.delete_help') }}</p><form method="post" action="{{ route('larena.docara.admin.menus.destroy', ['menu' => $menu->id]) }}" data-larena-confirm="{{ __('larena-docara::admin.menus.confirm_delete') }}">@csrf @method('DELETE'){!! $settingsComponents['delete'] !!}</form></section>
@endif
@endsection
