@props(['attr' => [], 'ignoreOld' => false, 'label' => null, 'id' => '', 'name', 'value' => '', 'type' => 'text', 'required' => false, 'autofocus' => false, 'readonly' => false])

@php
    // Set id from name if unset
    $id = empty($id) ? 'wrinput-'.$name : $id;
@endphp

@if(!empty($label))
    {!! view($WRLAHelper::getViewPath('components.forms.label'), [
        'id' => $id,
        'label' => $label
    ])->render() !!}
@endif

<input {{ $attributes->merge([
    'id' => $id,
    'type' => $type,
    'name' => $name,
    'value' => $ignoreOld ? $value : old($name, $value),
    'class' => 'block w-full mt-2 px-3 py-1 border border-slate-400 dark:border-slate-600 bg-slate-200 dark:bg-slate-900
        focus:outline-none focus:ring-1 focus:ring-primary-500 dark:focus:ring-primary-500 rounded-md shadow-sm placeholder-slate-400 dark:placeholder-slate-600'
])->merge($attr) }} />

@error($name)
    @themeComponent('alert', ['type' => 'error', 'message' => $message, 'class' => 'mt-2'])
@enderror
