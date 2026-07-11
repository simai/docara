<section class="larena-block-card" data-block-card data-block-type-value="{{ $block['definition']['key'] }}">
    <input type="hidden" name="blocks[{{ $block['index'] }}][instance_id]" value="{{ $block['value']['instance_id'] }}">
    <input type="hidden" name="blocks[{{ $block['index'] }}][type]" value="{{ $block['definition']['key'] }}">
    <input type="hidden" name="blocks[{{ $block['index'] }}][sort]" value="{{ $block['value']['sort'] ?? 100 }}" data-block-sort>
    <header class="larena-block-card__header">
        <div><span class="larena-block-position" data-block-position data-label="{{ __('larena-docara::admin.blocks.position', ['number' => ':number']) }}">{{ __('larena-docara::admin.blocks.position', ['number' => $block['position']]) }}</span><h2>{{ __('larena-docara::admin.'.$block['definition']['label_key']) }}</h2><code>{{ $block['definition']['smart_view'] }}</code></div>
        @unless ($readOnly)
            <div class="larena-block-card__actions">
                <span data-move-up>{!! $blockUi->button('move_up', 'secondary', 'button', '↑') !!}</span>
                <span data-move-down>{!! $blockUi->button('move_down', 'secondary', 'button', '↓') !!}</span>
                <span data-remove-block>{!! $blockUi->button('remove', 'danger') !!}</span>
            </div>
        @endunless
    </header>
    <input type="hidden" name="blocks[{{ $block['index'] }}][enabled]" value="0">{!! $blockUi->checkbox("blocks[{$block['index']}][enabled]", (bool) ($block['value']['enabled'] ?? false), $readOnly) !!}
    <div class="larena-form-grid">
        @foreach ($block['definition']['fields'] as $field)
            @include('larena-docara::admin.partials.block-field', ['field' => $field, 'index' => $block['index'], 'value' => $block['value']['settings'][$field['key']] ?? $field['default']])
        @endforeach
    </div>
</section>
