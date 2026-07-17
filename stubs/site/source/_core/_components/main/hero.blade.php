<section id="hero" class="m-bottom-4">
    <div class="dc-hero--wrap relative border-bottom-1 border-outline-variant">
        <div class="container p-x-1 m-inline-auto relative z-2">
            <div class="dc-hero--content p-x-2 md:p-x-4 max-w-md flex flex-col">
                <div class="dc-hero--title sf-h-1">Docara</div>
                <div class="dc-hero--description sf-text-1">Docara — платформа с открытым кодом, позволяющая выстраивать
                    работу с
                    документацией как с обычным кодом.
                    Объединяет написание, хранение и публикацию в единый управляемый процесс.
                </div>
                <div class="dc-hero--bottom flex gap-1">
                    <button
                            onclick="window.open('https://github.com/codespaces/new?repo=simai/docara-template&ref=main', '_blank')"
                            type="button"
                            class="sf-button sf-button--default sf-button--primary sf-button--size-1"
                    ><span class="sf-button-text-container">Начать</span></button>
                    <button
                            onclick="window.open(`{{$page->github}}`, '_blank')"
                            type="button"
                            class="sf-button sf-button--on-surface sf-button--outline sf-button--size-1"
                    >
                        <span class="sf-button-text-container">Github</span>
                    </button>
                </div>
            </div>
        </div>
        <div class="dc-hero-slider absolute inline-start-0 inline-end-0 bottom-0 top-0">
            <div
                    class="sf-slider w-full h-full"
                    data-loop="true"
                    data-autoplay="true"
                    data-space-between="12"
                    data-speed="450"
            >
                <div class="sf-slider-main swiper radius-default">
                    <div class="swiper-wrapper">
                        <div class="swiper-slide">
                            <div class="sf-slider-slide">
                                <img
                                        slot="slide"
                                        src="https://picsum.photos/1920/1080"
                                        alt="Primary 1"
                                />
                            </div>
                        </div>
                        <div class="swiper-slide">
                            <div class="sf-slider-slide">
                                <img
                                        slot="slide"
                                        src="https://picsum.photos/1920/1080?v_2"
                                        alt="Primary 2"
                                />
                            </div>
                        </div>
                        <div class="swiper-slide">
                            <div class="sf-slider-slide">
                                <img
                                        slot="slide"
                                        src="https://picsum.photos/1920/1080?v_3"
                                        alt="Primary 3"
                                />
                            </div>
                        </div>
                        <div class="swiper-slide">
                            <div class="sf-slider-slide">
                                <img
                                        slot="slide"
                                        src="https://picsum.photos/1920/1080?v_4"
                                        alt="Primary 4"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
{{--        <div class="dc-hero-image absolute inline-start-0 inline-end-0 bottom-0 top-0">--}}
{{--            <picture>--}}
{{--                <img class="w-full h-full object-cover"--}}
{{--                     src="{{ mix('img/hero.png', 'assets/build') }}">--}}
{{--            </picture>--}}
{{--        </div>--}}
        <div class="dc-hero--background gr-line-2 absolute inline-start-0 inline-end-0 bottom-0 top-0 z-1"></div>
    </div>
</section>
