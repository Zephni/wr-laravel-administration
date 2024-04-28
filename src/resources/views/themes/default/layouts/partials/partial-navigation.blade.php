@foreach($WRLAHelper::getNavigationItems() as $navigationItem)
    {{-- If $navigationItem is null then continue --}}
    @continue($navigationItem == null)

    {{-- If navigation item does not have children --}}
    @if(!$navigationItem->hasChildren())
        <div class="relative w-full overflow-hidden">
            <a href="{{ $navigationItem->getUrl() }}" class="@if($navigationItem->getUrl() == url()->current()) !text-primary-500 @endif flex justify-start items-center whitespace-nowrap w-full select-none px-6 pt-2 pb-1 font-bold text-slate-200 hover:text-primary-500 bg-slate-700 hover:bg-slate-800 border-b border-slate-500">
                <i class="{{ $navigationItem->icon }} w-6 h-6 text-primary-500 mt-1 mr-1"></i>
                <span class="text-sm">{{ $navigationItem->render() }}</span>
            </a>
        </div>
    {{-- If navigation item has children --}}
    @else
        <div x-data="{
            dropdownOpen: $persist(false).using(sessionStorage).as('nav_' + {{ $navigationItem->index }} + '_open'),
            childIsActive: false
        }" class="relative w-full overflow-hidden">
            <div class="relative flex items-stretch justify-between h-fit w-full whitespace-nowrap select-none font-bold bg-slate-700">

                {{-- If navigation item has a route --}}
                @if(!empty($navigationItem->route))
                    <a href="{{ $navigationItem->getUrl() }}"
                        :class="{ '!text-primary-500 font-bold': !dropdownOpen && childIsActive }"
                        class="flex-1 h-full px-6 text-slate-200-6 pt-2 pb-1 text-start text-slate-200 hover:text-primary-500 hover:bg-slate-800 whitespace-nowrap border-b border-slate-500">
                        <i class="{{ $navigationItem->icon }} w-6 h-6 text-primary-500 mr-1"></i>
                        <span class="text-sm">{{ $navigationItem->render() }}</span>
                    </a>
                {{-- If navigation item does not have a route --}}
                @else
                    <div class="flex-1 h-full px-6 pt-2 pb-1 text-start whitespace-nowrap hover:!text-primary-500">
                        <i class="{{ $navigationItem->icon }} w-6 h-6 text-primary-500 mr-1"></i>
                        <span class="text-sm">{{ $navigationItem->render() }}</span>
                    </div>
                @endif

                {{-- Dropdown arrow --}}
                <div @click="dropdownOpen = !dropdownOpen" x-bind:class="{'': !dropdownOpen, '': dropdownOpen}" class="flex justify-center items-center w-8 min-w-8 min-h-full hover:bg-slate-800 text-slate-300 dark:text-slate-300 cursor-pointer hover:text-primary-500 border-l border-b border-gray-500 dark:border-gray-500">
                    <i x-bind:class="{'fas fa-chevron-right': !dropdownOpen, 'fas fa-chevron-down': dropdownOpen}" class="text-xs mt-1"></i>
                </div>

            </div>

            {{-- Dropdown child list --}}
            <div x-show="dropdownOpen" class="w-full" x-transition x-transition:enter.duration.300ms x-transition:leave.duration.300ms>
                @foreach($navigationItem->children as $child)
                    <a
                        x-init="() => { if ('{{ $child->getUrl() }}' == '{{ url()->current() }}') { childIsActive = true; } }"
                        href="{{ $child->getUrl() }}" class="@if($child->getUrl() == url()->current()) !text-primary-500 @endif flex items-center justify-start w-full pl-9 pr-6 pt-1 text-slate-200 pb-0 font-bold bg-slate-700 hover:bg-slate-800 hover:!text-primary-500 dark:hover:!text-primary-500 border-b border-slate-500">
                        <i class="{{ $child->icon }} w-6 h-6 text-primary-500 mt-1 mr-1"></i>
                        <span class="text-sm">{{ $child->render() }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

@endforeach
