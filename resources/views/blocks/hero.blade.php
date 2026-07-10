<section class="larena-page-block larena-page-block--hero larena-page-block--hero-{{ $block['settings']['style'] }}" data-block-instance="{{ $block['instance_id'] }}" data-smart-view="{{ $block['smart_view'] }}">
    <div class="larena-page-block__hero-copy">
        @if ($block['settings']['eyebrow'] !== '')<p class="larena-page-block__eyebrow">{{ $block['settings']['eyebrow'] }}</p>@endif
        <h2>{{ $block['settings']['title'] }}</h2>
        @if ($block['settings']['body'] !== '')<div class="larena-page-block__body">{{ $block['settings']['body'] }}</div>@endif
        @if ($block['settings']['cta_url'] !== '')<a class="larena-page-block__button" href="{{ $block['settings']['cta_url'] }}">{{ $block['settings']['cta_label'] }}</a>@endif
    </div>
    @if ($block['image_url'])<img src="{{ $block['image_url'] }}" alt="" loading="eager">@endif
</section>
