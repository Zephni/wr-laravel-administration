@props([
    'title',
    'icon',
])

<div {{ $attributes }}>
    <h1 class="text-2xl font-light">
        <i class="{{ $icon }} text-slate-700 dark:text-white mr-1"></i>
        {{ $title }}
    </h1>
    <hr class="border-b border-slate-400 w-80 mt-1 mb-5">
</div>