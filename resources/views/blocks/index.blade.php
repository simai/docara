<div class="larena-page-blocks" data-larena-page-blocks="{{ $compositionMode ?? 'render' }}">
    @foreach ($blocks as $block)
        @includeIf('larena-docara::blocks.'.$block['type'], ['block' => $block])
    @endforeach
</div>
