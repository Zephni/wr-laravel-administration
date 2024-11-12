@props(['options' => [], 'label' => null, 'items' => []])

@php
    // Set id from name if unset
    $id = empty($attributes->get('id')) ? 'wrinput-'.$attributes->get('name') : $attributes->get('id');
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

<select {{ $attributes->merge([
    'class' => 'block w-full mt-2 px-3 py-1 border border-slate-400 dark:border-slate-500 bg-slate-200 dark:bg-slate-900
        focus:outline-none focus:ring-1 focus:ring-primary-500 dark:focus:ring-primary-500 rounded-md shadow-sm placeholder-slate-400 dark:placeholder-slate-600 pr-3'
]) }}>
    @foreach($items as $itemKey => $itemValue)
        <option value="{{ $itemKey }}" @if($attributes->get('value') == $itemKey) selected @endif>{{ $itemValue }}</option>
    @endforeach
</select>

{{-- Field notes (if options has notes key) --}}
@if(!empty($options['notes']))
    @themeComponent('forms.field-notes', ['notes' => $options['notes']])
@endif

@error($attributes->get('name'))
    @themeComponent('alert', ['type' => 'error', 'message' => $message, 'class' => 'mt-2'])
@enderror

</div>