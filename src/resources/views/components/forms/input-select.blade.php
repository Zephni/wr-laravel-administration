@props(['attr' => [], 'options' => [], 'ignoreOld' => false, 'label' => null, 'id' => '', 'name', 'value' => '', 'items' => [], 'required' => false, 'autofocus' => false, 'readonly' => false])

@php
    // Set id from name if unset
    $id = empty($id) ? 'wrinput-'.$name : $id;
@endphp

@if(isset($options['containerClass']) && $options['containerClass'] !== null)
    <div class="{{ $options['containerClass'] }}">
@endif

@if(!empty($label))
    {!! view($WRLAHelper::getViewPath('components.forms.label'), [
        'id' => $id.'-label',
        'label' => $label,
        'class' => $options['labelClass'] ?? ''
    ])->render() !!}
@endif

{{-- @foreach($items as $itemKey => $itemValue)
    @if($loop->index == 2)
        @dd($value, $itemKey, $itemValue)
    @endif
@endforeach --}}

<select {{ $attributes->merge([
    'id' => $id,
    'name' => $name,
    'class' => 'block w-full mt-2 px-3 py-1 border border-slate-400 dark:border-slate-600 bg-slate-200 dark:bg-slate-900
        focus:outline-none focus:ring-1 focus:ring-primary-500 dark:focus:ring-primary-500 rounded-md shadow-sm placeholder-slate-400 dark:placeholder-slate-600 pr-3'
])->merge($attr) }}>
    @foreach($items as $itemKey => $itemValue)
        <option value="{{ $itemKey }}" @if($ignoreOld ? $value == $itemKey : old($name, $value) == $itemKey) selected @endif>{{ $itemValue }}</option>
    @endforeach
</select>

{{-- Field notes (if options has notes key) --}}
@if(!empty($options['notes']))
    @themeComponent('forms.field-notes', ['notes' => $options['notes']])
@endif

@error($name)
    @themeComponent('alert', ['type' => 'error', 'message' => $message, 'class' => 'mt-2'])
@enderror

@if(isset($options['containerClass']) && $options['containerClass'] !== null)
    </div>
@endif
