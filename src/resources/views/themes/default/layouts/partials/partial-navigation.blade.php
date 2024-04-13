@foreach($WRLAHelper::getNavigationItems() as $navigationItem)
    {{-- Using tailwind, this is a pretty yet flexible naviigation menu. It allows children, and uses alpine for toggling dropdowns.  --}}

    {{-- If navigation item does not have children --}}
    @if(!$navigationItem->hasChildren())
        <div class="relative w-full overflow-hidden">
            <a href="{{ $navigationItem->getUrl() }}" class="flex justify-start items-center whitespace-nowrap w-full select-none px-6 pt-2 pb-1 font-semibold text-slate-900 dark:text-slate-100 bg-slate-50 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-900 hover:text-slate-900 dark:hover:text-slate-100 border-b border-slate-400 dark:border-slate-700 first-of-type:border-t">
                <i class="{{ $navigationItem->icon }} w-6 h-6 text-primary-500 mt-1 mr-1"></i>
                <span class="text-sm">{{ $navigationItem->render() }}</span>
            </a>
        </div>
    {{-- If navigation item has children --}}
    @else
        <div x-data="{ ['nav_' + {{ $navigationItem->index }} + '_open']: $persist(false) }" class="relative w-full overflow-hidden">
            <div class="relative flex items-stretch justify-between h-fit w-full whitespace-nowrap select-none font-semibold text-slate-900 dark:text-slate-100 bg-slate-50 dark:bg-slate-800 border-b border-slate-400 dark:border-slate-700">
                @if(!empty($navigationItem->route))
                    <a href="{{ $navigationItem->getUrl() }}" class="flex-1 h-full px-6 pt-2 pb-1 text-start hover:bg-slate-200 dark:hover:bg-slate-900 hover:text-slate-900 dark:hover:text-slate-100 whitespace-nowrap">
                        <i class="{{ $navigationItem->icon }} w-6 h-6 text-primary-500 mt-1 mr-1"></i>
                        <span class="text-sm">{{ $navigationItem->render() }}</span>
                    </a>
                @else
                    <div class="flex-1 h-full px-6 pt-2 pb-1 text-start whitespace-nowrap">
                        <i class="{{ $navigationItem->icon }} w-6 h-6 text-primary-500 mt-1 mr-1"></i>
                        <span class="text-sm">{{ $navigationItem->render() }}</span>
                    </div>
                @endif
                <div @click="$data['nav_' + {{ $navigationItem->index }} + '_open'] = !$data['nav_' + {{ $navigationItem->index }} + '_open']" x-bind:class="{'': !$data['nav_' + {{ $navigationItem->index }} + '_open'], '': $data['nav_' + {{ $navigationItem->index }} + '_open']}" class="absolute right-0 flex justify-center items-center w-8 min-w-8 min-h-full bg-slate-50 dark:bg-slate-800 text-slate-500 cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-900 hover:text-primary-500 border-l border-gray-400 dark:border-gray-700">
                    <i x-bind:class="{'fas fa-chevron-right': !$data['nav_' + {{ $navigationItem->index }} + '_open'], 'fas fa-chevron-down': $data['nav_' + {{ $navigationItem->index }} + '_open']}" class="text-xs mt-1"></i>
                </div>
            </div>

            <div x-show="$data['nav_' + {{ $navigationItem->index }} + '_open']" class="w-full" x-transition x-transition:enter.duration.300ms x-transition:leave.duration.300ms>
                @foreach($navigationItem->children as $child)
                    <a href="{{ $child->getUrl() }}" class="flex items-center justify-between w-full pl-9 pr-6 pt-1 pb-0 font-semibold text-slate-900 dark:text-slate-100 hover:bg-slate-200 dark:hover:bg-slate-900 hover:text-slate-900 dark:hover:text-slate-100 border-b border-slate-400 dark:border-slate-700">
                        <div>
                            <i class="{{ $child->icon }} w-6 h-6 text-primary-500 mr-1"></i>
                            <span class="text-sm">{{ $child->render() }}</span>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

@endforeach
