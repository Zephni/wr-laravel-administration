@foreach($WRLAHelper::getNavigationItems() as $navigationItem)
    {{-- If $navigationItem is null or fails checkCondition then continue --}}
    @continue($navigationItem == null || !$navigationItem->checkShowCondition())
    @php $enabled = $navigationItem->checkEnabledCondition(); @endphp

    {{-- If navigation item does not have children --}}
    @if(!$navigationItem->hasChildren())
        @if($navigationItem->route == 'wrla::html')
            {!! $navigationItem->render() !!}
        @else
            <div class="relative w-full overflow-hidden">
                <a
                    @if($enabled === true) href="{{ $navigationItem->getUrl() }}" @else title="{{ $enabled }}" @endif
                    @if($navigationItem->openInNewTab === true) target="_blank" @endif class="@if($WRLAHelper::isNavItemCurrentRoute($navigationItem)) !text-primary-500 bg-slate-800 !border-t-2 !border-b-2 border-slate-600 @endif grid grid-cols-[36px,1fr] @if($enabled === true) hover:bg-slate-800 hover:!text-primary-500 dark:hover:!text-primary-500 text-slate-200 @else text-slate-400 @endif justify-start items-center whitespace-nowrap w-full select-none pl-2 pt-2 pb-1 font-bold">
                    <div class="text-center w-8 h-8 overflow-hidden">
                        <i class="{{ $navigationItem->icon }} text-lg mr-1 @if($navigationItem->isActive()) text-primary-500 @endif"></i>
                    </div>
                    <span class="relative text-sm" style="top: -1px;">{{ $navigationItem->render() }}</span>
                </a>
            </div>
        @endif
    {{-- If navigation item has children --}}
    @else
        <div x-data="{
            thisActive: {{ $navigationItem->isActive() ? 'true' : 'false' }},
            dropdownOpen: $persist(false).using(sessionStorage).as('nav_' + {{ $navigationItem->index }} + '_open'),
            childIsActive: {{ $navigationItem->isChildActive() ? 'true' : 'false' }}
        }"
            @navigation_item_clicked.window="if($event.detail.except !== {{ $navigationItem->index }}) dropdownOpen = false"
            @click="$dispatch('navigation_item_clicked', { except: {{ $navigationItem->index }} })"
            class="relative w-full overflow-hidden">
            <div class="relative flex items-stretch justify-between h-fit w-full whitespace-nowrap select-none font-bold bg-slate-700">

                {{-- If navigation item has a route --}}
                @if(!empty($navigationItem->route))
                    <a href="{{ $navigationItem->getUrl() }}"
                        @if($navigationItem->openInNewTab === true) target="_blank" @endif
                        :class="{ '!text-primary-500 !bg-slate-800': !dropdownOpen && thisActive, '!text-primary-500 bg-slate-750': childIsActive, '!border-t-2 !border-b-2 border-slate-600': !dropdownOpen && (thisActive || childIsActive), '!border-t-2 !border-b border-slate-600': dropdownOpen && (thisActive || childIsActive) }"
                        class="grid grid-cols-[36px,1fr] justify-start items-center whitespace-nowrap w-full select-none pl-2 pt-2 pb-1 font-bold text-slate-200 hover:text-primary-500 bg-slate-700 hover:bg-slate-800">
                        <div class="text-center w-8 h-8 overflow-hidden">
                            <i class="{{ $navigationItem->icon }} text-lg mr-1" :class="{ 'text-primary-500': thisActive || childIsActive }"></i>
                        </div>
                        <span class="relative text-sm" style="top: -1px;">{{ $navigationItem->render() }}</span>
                    </a>
                {{-- If navigation item does not have a route --}}
                @else
                    <span
                        @click="dropdownOpen = !dropdownOpen;"
                        :class="{ '!text-primary-500 !bg-slate-800': !dropdownOpen && thisActive, '!text-primary-500 bg-slate-750': childIsActive, '!border-t-2 !border-b-2 border-slate-600': !dropdownOpen && (thisActive || childIsActive), '!border-t-2 !border-b border-slate-600': dropdownOpen && (thisActive || childIsActive) }"
                        class="grid grid-cols-[36px,1fr] justify-start items-center whitespace-nowrap w-full select-none pl-2 pt-2 pb-1 font-bold text-slate-200 hover:text-primary-500 bg-slate-700 hover:bg-slate-800 cursor-pointer">
                        <div class="text-center w-8 h-8 overflow-hidden">
                            <i class="{{ $navigationItem->icon }} text-lg mr-1" :class="{ 'text-primary-500': thisActive || childIsActive }"></i>
                        </div>
                        <span class="relative text-sm" style="top: -1px;">{{ $navigationItem->render() }}</span>
                    </span>
                @endif

                {{-- Dropdown arrow --}}
                <div
                    style="z-index: 6;"
                    @click="dropdownOpen = !dropdownOpen;"
                    :class="{ '!text-primary-500': dropdownOpen, '!border-t-2 !border-b-2 border-slate-600': !dropdownOpen && (thisActive || childIsActive), '!border-t-2 !border-b border-slate-600': dropdownOpen && (thisActive || childIsActive) }"
                    class="border-l border-slate-550 bg-slate-725 absolute right-0 bg-slate-700 flex justify-center items-center w-10 min-w-10 min-h-full hover:bg-slate-800 text-slate-300 dark:text-slate-300 cursor-pointer hover:text-primary-500">
                    <i :class="{'fas fa-chevron-right': !dropdownOpen, 'fas fa-chevron-down': dropdownOpen}" class="text-xs mt-1"></i>
                </div>
            </div>

            {{-- Dropdown child list --}}
            <div x-show="dropdownOpen"
                x-transition
                x-transition:enter.duration.100ms
                x-transition:leave.duration.40ms
                class="w-full bg-slate-725 border-t border-b border-slate-800" style="border-bottom-color: {{ config('wr-laravel-administration.colors.slate.600') }};">
                @foreach($navigationItem->children as $child)
                    {{-- If $child is null or fails checkCondition then continue --}}
                    @continue($child == null || !$child->checkShowCondition())
                    @php $enabled = $child->checkEnabledCondition(); @endphp
                    <a
                        @if($enabled === true) href="{{ $child->getUrl() }}" @else title="{{ $enabled }}" @endif
                        class="grid grid-cols-[36px,1fr] @if($child->isActive()) !text-primary-500 !bg-slate-800 @endif @if($enabled === true) hover:bg-slate-800 hover:!text-primary-500 dark:hover:!text-primary-500 text-slate-200 @else text-slate-400 @endif flex items-center justify-start w-full pl-7 pr-6 pt-1 pb-0 font-bold">
                        <div class="text-center w-8 h-8 overflow-hidden">
                            <i class="{{ $child->icon }} text-lg mr-1 @if($child->isActive()) text-primary-500 @endif"></i>
                        </div>
                        <span class="relative text-sm" style="top: -1px;">{{ $child->render() }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

@endforeach
