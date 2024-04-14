@foreach($WRLAHelper::getNavigationItems() as $navigationItem)
    {{-- If $navigationItem is null then continue --}}
    @continue($navigationItem == null)

    {{-- If navigation item does not have children --}}
    @if(!$navigationItem->hasChildren())
        <div class="relative w-full overflow-hidden">
            <a href="{{ $navigationItem->getUrl() }}" class="@if($navigationItem->getUrl() == url()->current()) !text-primary-500 @endif flex justify-start items-center whitespace-nowrap w-full select-none px-6 pt-2 pb-1 font-bold hover:text-primary-500 bg-slate-50 dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-900 border-b border-slate-400 dark:border-slate-700 first-of-type:border-t">
                <i class="{{ $navigationItem->icon }} w-6 h-6 text-primary-500 mt-1 mr-1"></i>
                <span class="text-sm">{{ $navigationItem->render() }}</span>
            </a>
        </div>
    {{-- If navigation item has children --}}
    @else
        <div x-data="{
            uniqueIndexKey: 'nav_' + {{ $navigationItem->index }} + '_open',
            ['nav_' + {{ $navigationItem->index }} + '_open']: $persist(false),
            childIsActive: false
        }" class="relative w-full overflow-hidden">
            <div class="relative flex items-stretch justify-between h-fit w-full whitespace-nowrap select-none font-bold bg-slate-50 dark:bg-slate-800 border-b border-slate-400 dark:border-slate-700">

                {{-- If navigation item has a route --}}
                @if(!empty($navigationItem->route))
                    <a href="{{ $navigationItem->getUrl() }}"
                        :class="{ 'text-primary-500': !$data[uniqueIndexKey] && childIsActive }"
                        class="flex-1 h-full px-6 pt-2 pb-1 text-start hover:text-primary-500 hover:bg-slate-100 dark:hover:bg-slate-900 whitespace-nowrap">
                        <i class="{{ $navigationItem->icon }} w-6 h-6 text-primary-500 mt-1 mr-1"></i>
                        <span class="text-sm">{{ $navigationItem->render() }}</span>
                    </a>
                {{-- If navigation item does not have a route --}}
                @else
                    <div class="flex-1 h-full px-6 pt-2 pb-1 text-start whitespace-nowrap hover:!text-primary-500">
                        <i class="{{ $navigationItem->icon }} w-6 h-6 text-primary-500 mt-1 mr-1"></i>
                        <span class="text-sm">{{ $navigationItem->render() }}</span>
                    </div>
                @endif

                {{-- Dropdown arrow --}}
                <div @click="$data[uniqueIndexKey] = !$data[uniqueIndexKey]" x-bind:class="{'': !$data[uniqueIndexKey], '': $data[uniqueIndexKey]}" class="absolute right-0 flex justify-center items-center w-8 min-w-8 min-h-full bg-slate-50 dark:bg-slate-800 text-slate-500 cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-900 hover:text-primary-500 border-l border-gray-400 dark:border-gray-700">
                    <i x-bind:class="{'fas fa-chevron-right': !$data[uniqueIndexKey], 'fas fa-chevron-down': $data[uniqueIndexKey]}" class="text-xs mt-1"></i>
                </div>

            </div>

            {{-- Dropdown child list --}}
            <div x-show="$data[uniqueIndexKey]" class="w-full" x-transition x-transition:enter.duration.300ms x-transition:leave.duration.300ms>
                @foreach($navigationItem->children as $child)
                    <a
                        x-init="() => { if ('{{ $child->getUrl() }}' == '{{ url()->current() }}') { childIsActive = true; } }"
                        href="{{ $child->getUrl() }}" class="@if($child->getUrl() == url()->current()) !text-primary-500 @endif flex items-center justify-start w-full pl-9 pr-6 pt-1 pb-0 font-bold bg-slate-50 dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-900 hover:!text-primary-500 dark:hover:!text-primary-500 border-b border-slate-400 dark:border-slate-700">
                        <i class="{{ $child->icon }} w-6 h-6 text-primary-500 mt-1 mr-1"></i>
                        <span class="text-sm">{{ $child->render() }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

@endforeach
