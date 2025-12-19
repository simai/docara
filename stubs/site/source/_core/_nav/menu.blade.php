@php
    $level = $level ?? 0;
    $menuTree = $menuTree ?? $page->getMenu();
    $paths = $page->configurator->paths;
@endphp

@if($level > 0)
    <ul class="sf-nav-menu-wrap menu-level-{{ $level }}">
        @endif
        @foreach ($menuTree as $path => $node)
            @php
                if($node['isLink']) continue;
            @endphp

            @if($level > 0)
                @php
                    $hasPage = in_array($path, $paths);
                    $activeParent = $page->isActiveParent($node);
                    $activeItem = $page->getPath() === $path;
                @endphp
                <li class="sf-nav-menu-element {{$activeParent && !$activeItem ? 'active' : ''}}">
                    @if (!empty($node['children']))
                        @if($hasPage)
                            <div class="sf-nav-item flex {{ $activeItem   ? 'visited' : '' }} items-center justify-between">
                                <button class="sf-nav-toggle_button flex items-center" onclick="toggleNav(this)"
                                        type="button">
                                    <i class="sf-icon">keyboard_arrow_down</i>
                                </button>
                                <a href="{{$path}}" class="sf-nav-button flex" type="button">
                                    <span class="sf-nav-title">{{ $node['title'] }}</span>
                                </a>
                            </div>
                        @else
                            <button onclick="toggleNav(this)"
                                    class="sf-nav-button sf-nav-item flex items-center" type="button">
                              <span class="sf-nav-toggle_button flex items-center">
                                            <i class="sf-icon">keyboard_arrow_down</i>
                                        </span>
                                <span class="sf-nav-title">{{ $node['title'] }}</span>
                            </button>
                        @endif
                    @else
                        <a href="{{ $path }}"
                           class="sf-nav-menu-element--link sf-nav-item items-center flex sf-nav-menu--lvl{{ $level }} {{ $page->isActive($path) ? 'active' : '' }}">
                            <span class="sf-nav-title">{{ $node['title'] }}</span>
                        </a>
                    @endif
                    @elseif ($node['showInMenu'] && $node['path'])
                        <a href="{{ $path }}"
                           class="sf-nav-menu-element--link sf-nav-item items-center flex sf-nav-menu--lvl{{ $level }} {{ $page->isActive($path) ? 'active' : '' }}">
                            <span class="sf-nav-title">{{ $node['title'] }}</span>
                        </a>
                    @endif
                    @if (!empty($node['children']))
                        @include('_core._nav.menu', ['menuTree' => $node['children'], 'level' => $level + 1,])
                    @endif
                </li>
                @endforeach
                @if($level > 0)
    </ul>
@endif

