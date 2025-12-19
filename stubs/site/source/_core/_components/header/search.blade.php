<div id="search_doc" class="search--wrap flex content-main-end ml-auto w-0 sm:w-full text-right ">
    <script>
        window.sfSearchNotFound = '{{$page->translate('notFound')}}'
    </script>
    <div class="sf-input-container sf-input-container--1 sf-input-search-container grow-none">
        <div id="input_search" class="sf-input sf-input--size-1 sf-input--decoration-bordered">
            <div class="sf-input-field">
                <i class="sf-icon">search</i>
                <div class="sf-input-text-container">
                    <div class="sf-input-group">
                        <input name="search" type="text" class="sf-input-main sf-input-text"
                               placeholder="{{$page->translate('search')}}">
                    </div>
                </div>
                <button
                        id="search_close"
                        type="button"
                        class="sf-close sf-close--size-1 hidden">
                    <i class="sf-icon"></i>
                </button>
            </div>
        </div>

        <div id="search_results" class="docsearch-input__holder hidden">
            <div class="docsearch-input__main flex flex-col"></div>
        </div>
    </div>
</div>



