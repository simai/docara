<section class="larena-page-block larena-page-block--text larena-page-block--align-{{ $block['settings']['alignment'] }}" data-block-instance="{{ $block['instance_id'] }}" data-smart-view="{{ $block['smart_view'] }}">
    @if ($block['settings']['heading'] !== '')<h2>{{ $block['settings']['heading'] }}</h2>@endif
    <div class="larena-page-block__body">{{ $block['settings']['body'] }}</div>
</section>
