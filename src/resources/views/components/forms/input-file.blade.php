@props(['options' => [], 'label' => null])

@php
    // Set id from name if unset
    $id = empty($attributes->get('id')) ? 'wrinput-'.$attributes->get('name') : $attributes->get('id');
    $chooseFileText = $options['chooseFileText'] ?? 'Choose a file...';
@endphp

<div class="{{ $options['containerClass'] ?? 'w-full flex-1 md:flex-auto' }}">

@if(!empty($label))
    {!! view($WRLAHelper::getViewPath('components.forms.label'), [
        'label' => $label,
        'attributes' => new \Illuminate\View\ComponentAttributeBag([
            'id' => $id,
            'class' => $options['labelClass'] ?? ''
        ])
    ])->render() !!}
@endif

<div
    x-data="{ displayText: '{{ $chooseFileText }}' }"
    class="relative">
    <input
        wire:key="{{ rand() }}"
        wire:loading.attr="disabled"
        type="file"
        id="{{ $id }}"
        class="absolute z-0 inset-0 opacity-0 w-0 h-0 !cursor-pointer"
        {{ $attributes->merge(['class' => '']) }}
        x-on:change="
            displayText = ($el.files.length) ? Array.from($el.files).map(file => file.name).join(', ') : '{{ $chooseFileText }}';
        "/>
    <label for="{{ $id }}" class="group z-40 flex justify-start items-center p-1.5 gap-2 border border-slate-300 dark:border-slate-600 bg-slate-100 dark:bg-slate-800 rounded-lg cursor-pointer">
        <div type="button" class="px-3 py-1.5 text-white bg-primary-600 hover:bg-primary-500 font-medium rounded-lg group-hover:bg-primary-500">
            <i class="fas fa-upload text-sm mr-1"></i>
            Browse
        </div>
        <div wire:loading class="ml-2 flex gap-3">
            <i class="fas fa-spinner fa-spin"></i>
            <span>Uploading file, please wait this can take some time...</span>
        </div>
        <span wire:loading.remove id="{{ $id }}-filename" class="ml-2" x-text="displayText"></span>
    </label>
</div>

{{-- Field notes (if options has notes key) --}}
@if(!empty($options['notes']))
    @themeComponent('forms.field-notes', ['notes' => $options['notes']])
@endif

@error($attributes->get('name'))
    @themeComponent('alert', ['type' => 'error', 'message' => $message, 'class' => 'mt-2'])
@enderror

</div>
