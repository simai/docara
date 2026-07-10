<figure class="larena-page-block larena-page-block--image" data-block-instance="{{ $block['instance_id'] }}" data-smart-view="{{ $block['smart_view'] }}">
    <img src="{{ $block['image_url'] }}" alt="{{ $block['settings']['alt'] }}" loading="lazy">
    @if ($block['settings']['caption'] !== '')<figcaption>{{ $block['settings']['caption'] }}</figcaption>@endif
</figure>
