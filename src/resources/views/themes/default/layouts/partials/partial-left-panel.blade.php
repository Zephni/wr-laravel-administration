{{-- Left panel, uses alpine to allow reiszing width, resize bar fixed to right side absolutely --}}
<div
    x-data="{
        dragging: false,
        startX: 0,
        minW: 0,
        maxW: 0
    }"
    x-bind:style="'width: ' + leftPanelWidth + 'px;'"
    :class="(leftPanelOpen ? 'min-w-44 max-w-[33%] ' : 'min-w-0 max-w-0 border-none ') + (!dragging ? 'transition-all' : '')"
    id="left-panel"
    class="fixed flex flex-col justify-start items-start h-full border-r-2 border-slate-300 dark:border-slate-950 bg-slate-700 dark:bg-slate-850 shadow-lg shadow-slate-500 dark:shadow-slate-950 z-10">

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
        class="absolute top-0 -right-1 h-full w-[4px] bg-slate-300 dark:bg-slate-800 border-r border-slate-400 dark:border-slate-500"
        @mousedown="$event.preventDefault(); if(leftPanelOpen && !dragging) startX = $event.clientX; dragging = true;"
        @mousemove.window="if (dragging) {
            leftPanelWidth = leftPanelWidth + $event.clientX - startX;
            startX = $event.clientX;

            // Get the min and max width of the left panel
            minW = parseInt(window.getComputedStyle(document.getElementById('left-panel')).getPropertyValue('min-width'));
            maxW = Math.floor(window.innerWidth * 0.33);

            // If leftPanelWidth is lower than min width or higher than max width, set it to min or max width
            if (leftPanelWidth < minW) leftPanelWidth = minW;
            if (leftPanelWidth > maxW) leftPanelWidth = maxW;
        }"
        @mouseup.window="dragging = false;"
    ></div>

    <div class="w-3/4 max-w-48 mx-auto py-8">
        {{-- <img src="{{ asset(config('wr-laravel-administration.logo.light')) }}" title="Light Logo" alt="Light Logo" class="dark:hidden w-full" /> --}}
        <img src="{{ asset(config('wr-laravel-administration.logo.dark')) }}" title="Dark Logo" alt="Dark Logo" class="dark:block w-full" />
    </div>

    @include('wr-laravel-administration::themes.default.layouts.partials.partial-navigation')
</div>
