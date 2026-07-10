@extends('larena-admin::layouts.app')

@section('title', $menu->name.' · Larena')
@section('eyebrow', __('larena-docara::admin.menus.eyebrow'))
@section('heading', $menu->name)
@section('description', __('larena-docara::admin.menus.edit_description', ['code' => $menu->code, 'locale' => strtoupper($menu->locale)]))

@section('content')
@if ($errors->any())<div class="larena-alert larena-alert-error" role="alert"><strong>{{ __('larena-docara::admin.menus.validation_heading') }}</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

<section class="larena-panel larena-menu-editor" aria-labelledby="menu-settings-heading">
    <h2 id="menu-settings-heading">{{ __('larena-docara::admin.menus.settings') }}</h2>
    @if ($canWrite)
        <form method="post" action="{{ route('larena.docara.admin.menus.update', ['menu' => $menu->id]) }}" class="larena-form larena-form-inline">@csrf @method('PUT')
            <div class="larena-field"><label for="menu-name">{{ __('larena-docara::admin.menus.fields.name') }}</label><input id="menu-name" name="name" value="{{ old('name', $menu->name) }}" maxlength="255" required></div>
            <label class="larena-check"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $menu->is_active))> {{ __('larena-docara::admin.menus.fields.active') }}</label>
            <button class="larena-button larena-button-primary" type="submit">{{ __('larena-docara::admin.menus.actions.save') }}</button>
        </form>
    @else
        <dl class="larena-definition"><div><dt>{{ __('larena-docara::admin.menus.fields.name') }}</dt><dd>{{ $menu->name }}</dd></div><div><dt>{{ __('larena-docara::admin.menus.fields.active') }}</dt><dd>{{ $menu->is_active ? __('larena-docara::admin.menus.active') : __('larena-docara::admin.menus.inactive') }}</dd></div></dl>
    @endif
</section>

<section class="larena-panel larena-menu-editor" aria-labelledby="menu-items-heading">
    <h2 id="menu-items-heading">{{ __('larena-docara::admin.menus.items_heading') }}</h2>
    <p>{{ __('larena-docara::admin.menus.items_help') }}</p>
    @if ($items->isEmpty())<div class="larena-empty larena-empty-compact"><p>{{ __('larena-docara::admin.menus.no_items') }}</p></div>@endif
    <div class="larena-menu-items">
    @foreach ($items as $item)
        <article class="larena-menu-item" style="--menu-depth: {{ $item->parent_id ? 1 : 0 }}">
            @if ($canWrite)
                <form method="post" action="{{ route('larena.docara.admin.menus.items.update', ['menu' => $menu->id, 'item' => $item->id]) }}" class="larena-menu-item__form">@csrf @method('PUT')
                    <div class="larena-field"><label for="item-{{ $item->id }}-label">{{ __('larena-docara::admin.menus.fields.label') }}</label><input id="item-{{ $item->id }}-label" name="label" value="{{ old('label', $item->label) }}" required maxlength="255"></div>
                    <div class="larena-field"><label for="item-{{ $item->id }}-parent">{{ __('larena-docara::admin.menus.fields.parent') }}</label><select id="item-{{ $item->id }}-parent" name="parent_id"><option value="">{{ __('larena-docara::admin.menus.root') }}</option>@foreach($items as $parent)@if($parent->id !== $item->id)<option value="{{ $parent->id }}" @selected((int)$item->parent_id === (int)$parent->id)>{{ $parent->label }}</option>@endif @endforeach</select></div>
                    <div class="larena-field"><label for="item-{{ $item->id }}-order">{{ __('larena-docara::admin.menus.fields.order') }}</label><input id="item-{{ $item->id }}-order" type="number" name="sort_order" min="0" max="100000" value="{{ $item->sort_order }}" required></div>
                    <label class="larena-check"><input type="checkbox" name="is_active" value="1" @checked($item->is_active)> {{ __('larena-docara::admin.menus.fields.active') }}</label>
                    <button class="larena-button" type="submit">{{ __('larena-docara::admin.menus.actions.save_item') }}</button>
                </form>
                <form method="post" action="{{ route('larena.docara.admin.menus.items.destroy', ['menu' => $menu->id, 'item' => $item->id]) }}" onsubmit="return confirm('{{ __('larena-docara::admin.menus.confirm_item_remove') }}')">@csrf @method('DELETE')<button class="larena-link-danger" type="submit">{{ __('larena-docara::admin.menus.actions.remove_item') }}</button></form>
            @else
                <strong>{{ $item->label }}</strong><span>{{ __('larena-docara::admin.menus.fields.order') }}: {{ $item->sort_order }}</span>
            @endif
        </article>
    @endforeach
    </div>

    @if ($canWrite)
        <form method="post" action="{{ route('larena.docara.admin.menus.items.store', ['menu' => $menu->id]) }}" class="larena-form larena-menu-add">@csrf
            <h3>{{ __('larena-docara::admin.menus.add_item') }}</h3>
            @if ($pages === [])<p class="larena-alert larena-alert-info">{{ __('larena-docara::admin.menus.no_published_pages') }}</p>@else
                <div class="larena-field"><label for="new-page">{{ __('larena-docara::admin.menus.fields.page') }}</label><select id="new-page" name="page_ref" required>@foreach($pages as $page)<option value="{{ $page->page_ref }}">{{ $page->title }} · /{{ $page->slug }}</option>@endforeach</select></div>
                <div class="larena-field"><label for="new-label">{{ __('larena-docara::admin.menus.fields.label') }}</label><input id="new-label" name="label" value="{{ old('label') }}" maxlength="255" required></div>
                <div class="larena-field"><label for="new-parent">{{ __('larena-docara::admin.menus.fields.parent') }}</label><select id="new-parent" name="parent_id"><option value="">{{ __('larena-docara::admin.menus.root') }}</option>@foreach($items as $parent)<option value="{{ $parent->id }}">{{ $parent->label }}</option>@endforeach</select></div>
                <div class="larena-field"><label for="new-order">{{ __('larena-docara::admin.menus.fields.order') }}</label><input id="new-order" type="number" name="sort_order" value="100" min="0" max="100000" required></div>
                <label class="larena-check"><input type="checkbox" name="is_active" value="1" checked> {{ __('larena-docara::admin.menus.fields.active') }}</label>
                <button class="larena-button larena-button-primary" type="submit">{{ __('larena-docara::admin.menus.actions.add_item') }}</button>
            @endif
        </form>
    @endif
</section>

@if ($canDelete)
<section class="larena-panel larena-danger-zone" aria-labelledby="menu-delete-heading"><h2 id="menu-delete-heading">{{ __('larena-docara::admin.menus.delete_heading') }}</h2><p>{{ __('larena-docara::admin.menus.delete_help') }}</p><form method="post" action="{{ route('larena.docara.admin.menus.destroy', ['menu' => $menu->id]) }}" onsubmit="return confirm('{{ __('larena-docara::admin.menus.confirm_delete') }}')">@csrf @method('DELETE')<button class="larena-button larena-button-danger" type="submit">{{ __('larena-docara::admin.menus.actions.delete') }}</button></form></section>
@endif
@endsection
