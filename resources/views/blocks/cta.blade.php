<section class="larena-page-block larena-page-block--cta larena-page-block--cta-{{ $block['settings']['style'] }}" data-block-instance="{{ $block['instance_id'] }}" data-smart-view="{{ $block['smart_view'] }}">
    <div><h2>{{ $block['settings']['title'] }}</h2>@if ($block['settings']['body'] !== '')<div class="larena-page-block__body">{{ $block['settings']['body'] }}</div>@endif</div>
    <a class="larena-page-block__button" href="{{ $block['settings']['url'] }}">{{ $block['settings']['label'] }}</a>
</section>
