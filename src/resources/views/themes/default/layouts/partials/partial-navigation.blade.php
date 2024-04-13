@foreach($WRLAHelper::getNavigationItems() as $navigationItem)
    {{-- Using tailwind, this is a pretty yet flexible naviigation menu. It allows children, and uses alpine for toggling dropdowns.  --}}

    {{-- If navigation item does not have children --}}
    @if(!$navigationItem->hasChildren())
        <a href="{{ $navigationItem->getUrl() }}" class="flex items-center justify-between w-full select-none px-6 pt-3 pb-2 font-semibold text-slate-900 dark:text-slate-100 bg-slate-50 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-900 hover:text-slate-900 dark:hover:text-slate-100 border-b border-slate-400 dark:border-slate-700 first-of-type:border-t">
            <div>
                <i class="{{ $navigationItem->icon }} w-6 h-6 text-primary-500 mr-2"></i>
                <span>{{ $navigationItem->name }}</span>
            </div>
        </a>
    {{-- If navigation item has children --}}
    @else
        <div x-data="{ ['nav_' + {{ $navigationItem->index }} + '_open']: $persist(false) }" class="relative w-full">
            <div class="flex items-stretch justify-between h-fit w-full select-none font-semibold text-slate-900 dark:text-slate-100 bg-slate-50 dark:bg-slate-800 border-b border-slate-400 dark:border-slate-700">
                @if(!empty($navigationItem->route))
                    <a href="{{ $navigationItem->getUrl() }}" class="flex-1 h-full px-6 pt-3 pb-2 text-start hover:bg-slate-200 dark:hover:bg-slate-900 hover:text-slate-900 dark:hover:text-slate-100">
                        <i class="{{ $navigationItem->icon }} w-6 h-6 text-primary-500 mr-2"></i>
                        <span>{{ $navigationItem->name }}</span>
                    </a>
                @else
                    <div class="flex-1 h-full px-6 pt-3 pb-2 text-start">
                        <i class="{{ $navigationItem->icon }} w-6 h-6 text-primary-500 mr-2"></i>
                        <span>{{ $navigationItem->name }}</span>
                    </div>
                @endif
                <div @click="$data['nav_' + {{ $navigationItem->index }} + '_open'] = !$data['nav_' + {{ $navigationItem->index }} + '_open']" x-bind:class="{'': !$data['nav_' + {{ $navigationItem->index }} + '_open'], 'bg-slate-300 dark:bg-slate-850': $data['nav_' + {{ $navigationItem->index }} + '_open']}" class="flex justify-center items-center w-12 min-h-full text-slate-500 cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-900 hover:text-slate-900 dark:hover:text-slate-100">
                    <i x-bind:class="{'fas fa-chevron-right': !$data['nav_' + {{ $navigationItem->index }} + '_open'], 'fas fa-chevron-down group:bg-slate-700': $data['nav_' + {{ $navigationItem->index }} + '_open']}"></i>
                </div>
            </div>

            <div x-show="$data['nav_' + {{ $navigationItem->index }} + '_open']" @click.away="$data['nav_' + {{ $navigationItem->index }} + '_open'] = false" class="w-full" x-transition x-transition:enter.duration.300ms x-transition:leave.duration.300ms>
                @foreach($navigationItem->children as $child)
                    <a href="{{ $child->getUrl() }}" class="flex items-center justify-between w-full pl-9 pr-6 pt-2 pb-1 font-semibold text-slate-900 dark:text-slate-100 hover:bg-slate-200 dark:hover:bg-slate-900 hover:text-slate-900 dark:hover:text-slate-100 border-b border-slate-400 dark:border-slate-700">
                        <div>
                            <i class="{{ $child->icon }} w-6 h-6 text-primary-500 mr-3"></i>
                            <span>{{ $child->name }}</span>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

@endforeach
