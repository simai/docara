<section class="larena-page-block larena-page-block--columns" data-block-instance="{{ $block['instance_id'] }}" data-smart-view="{{ $block['smart_view'] }}">
    <div>@if ($block['settings']['left_title'] !== '')<h2>{{ $block['settings']['left_title'] }}</h2>@endif<div class="larena-page-block__body">{{ $block['settings']['left_body'] }}</div></div>
    <div>@if ($block['settings']['right_title'] !== '')<h2>{{ $block['settings']['right_title'] }}</h2>@endif<div class="larena-page-block__body">{{ $block['settings']['right_body'] }}</div></div>
</section>
