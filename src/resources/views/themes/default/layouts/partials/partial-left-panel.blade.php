{{-- Left panel, uses alpine to allow reiszing width, resize bar fixed to right side absolutely --}}
<div
    x-data="{
        dragging: false,
        startX: 0,
        minW: 0,
        maxW: 0
    }"
    x-bind:style="'width: ' + leftPanelAttemptedWidth + 'px;'"
    :class="(leftPanelOpen ? 'min-w-44 max-w-[33%] ' : 'min-w-0 max-w-0 border-none ') + (!dragging ? 'transition-all' : '')"
    id="left-panel"
    style="z-index: 6;"
    class="relative sticky hidden whitespace-nowrap md:flex top-0 flex flex-col justify-start items-start h-full border-r-2 border-slate-300 dark:border-slate-950 bg-slate-700 dark:bg-slate-700 shadow-lg shadow-slate-500 dark:shadow-slate-950 z-10">

    {{-- Collapse button (Use collapse icon from fontawesome) --}}
    <button
        @click="leftPanelOpen = !leftPanelOpen; dragging = false;"
        x-bind:style="leftPanelOpen ? 'right: 0;' : 'right: -20px;'"
        x-bind:class="leftPanelOpen ? 'rounded-bl border-l' : 'rounded-br border-r';"
        class="absolute z-10 top-0 pt-1 w-5 opacity-60 text-sm flex justify-center items-center border-b bg-slate-800 text-slate-400 shadow-md dark:shadow-slate-900 dark:border-slate-400 cursor-pointer">
        <div class="relative">
            <i x-bind:class="{'fas fa-chevron-left': leftPanelOpen, 'fas fa-chevron-right': !leftPanelOpen}" class="relative -top-0.5"></i>
        </div>
    </button>

    {{-- Resize bar --}}
    <div
        :class="leftPanelOpen ? 'cursor-ew-resize' : 'hidden cursor-auto';"
        class="absolute top-0 -right-1 h-full w-[4px] bg-slate-400 dark:bg-slate-800 border-r border-slate-400 dark:border-slate-500"
        @mousedown="$event.preventDefault(); if(leftPanelOpen && !dragging){ startX = $event.clientX; dragging = true; }"
        @mousemove.window="if (dragging) {
            leftPanelAttemptedWidth = leftPanelAttemptedWidth + $event.clientX - startX;
            startX = $event.clientX;

            // Get the min and max width of the left panel
            minW = parseInt(window.getComputedStyle(document.getElementById('left-panel')).getPropertyValue('min-width'));
            maxW = Math.floor(window.innerWidth * 0.33);

            // If leftPanelWidth is lower than min width or higher than max width, set it to min or max width
            if (leftPanelAttemptedWidth < minW) leftPanelAttemptedWidth = minW;
            if (leftPanelAttemptedWidth > maxW) leftPanelAttemptedWidth = maxW;
        }"
        @mouseup.window="dragging = false;"
    ></div>

    {{-- Logo --}}
    <div class="w-3/4 max-w-48 mx-auto pt-4 pb-4">
        {{-- <img src="{{ asset(config('wr-laravel-administration.logo.light')) }}" title="Light Logo" alt="Light Logo" class="dark:hidden w-full" /> --}}
        <img src="{{ asset(config('wr-laravel-administration.logo.dark')) }}" title="Dark Logo" alt="Dark Logo" class="w-full" />
    </div>

    {{-- Divider --}}
    <div class="w-full border-t border-slate-600"></div>

    {{-- Impersonating user bar --}}
    @if($WRLAHelper::isImpersonatingUser())
        <div class="w-full px-5 py-2 bg-slate-850 text-slate-200 overflow-hidden text-sm border-b border-slate-600">
            <a href="{{ route('wrla.impersonate.switch-back') }}" class="text-primary-500">
                <i class="fa fa-key mr-1"></i>
                Switch back
            </a>
            to {{ $WRLAHelper::getImpersonatingOriginalUser()->name }}
        </div>
    @endif

    {{-- Profile avatar and logged in info --}}
    <div
        :class="leftPanelOpen ? 'flex' : 'hidden';"
        class="flex w-full justify-start items-center gap-4 px-5 py-4 bg-slate-800 text-slate-200 overflow-hidden">
        <div class="w-18 min-w-14">
            @themeComponent('forced-aspect-image', [
                'src' => $WRLAUser->getProfileAvatar(),
                'class' => 'border border-slate-600',
                'imageClass' => 'object-cover',
                'aspect' => '1:1',
                'rounded' => 'full'
            ])
        </div>
        <div class="flex flex-col text-sm">
            <div class="flex flex-col pb-1">
                <span class="text-sm">{{ $WRLAUser->name }}</span>
                <span class="text-xs font-semibold text-slate-400">{{ $WRLAUser->getRole() }}</span>
            </div>
            <span class="flex justify-start items-center gap-2 text-xs">
                <i class="fa fa-circle text-primary-500" style="font-size: 8px;"></i>
                <div class="flex gap-2">
                    <span class="text-slate-300">Online</span>
                    <span class="text-slate-400">|</span>
                    <a href="{{ route('wrla.logout') }}" class="text-primary-500">Logout</a>
                </div>
            </span>
        </div>
    </div>

    {{-- Overflow Y scroll area --}}
    <div class="flex flex-col justify-start items-start w-full h-full overflow-y-auto">

        {{-- Navigation --}}
        <div class="flex flex-col gap-1 w-full border-t border-slate-600 pt-1">
            @include('wr-laravel-administration::themes.default.layouts.partials.partial-navigation')
        </div>

    </div>
</div>
