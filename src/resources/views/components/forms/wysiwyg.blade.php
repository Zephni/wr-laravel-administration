@props(['options' => [], 'ignoreOld' => false, 'label' => null])

@php
    // Set id from name if unset
    $id = empty($attributes->get('id')) ? 'wrinput-'.$attributes->get('name') : $attributes->get('id');

    // Get value
    $value = $ignoreOld ? $attributes->get('value') : old($attributes->get('name'), $attributes->get('value'));
@endphp

@if(!empty($label))
    {!! view($WRLAHelper::getViewPath('components.forms.label'), [
        'label' => $label,
        'attributes' => new \Illuminate\View\ComponentAttributeBag([
            'id' => $id.'-label',
            'class' => $options['labelClass'] ?? ''
        ])
    ])->render() !!}
@endif

<textarea {{ $attributes->merge([
    'id' => 'editor',
    'class' => 'wysiwyg',
    'style' => 'white-space: pre-wrap; word-wrap: break-word;'
// Remove value from attributes
])->except('value')  }}>{{
    $value
}}</textarea>

{{-- Field notes (if options has notes key) --}}
@if(!empty($options['notes']))
    @themeComponent('forms.field-notes', ['notes' => $options['notes']])
@endif

@error($attributes->get('name'))
    @themeComponent('alert', ['type' => 'error', 'message' => $message, 'class' => 'mt-2'])
@enderror

@once
<style>
    .ck-source-editing-area,
    .ck-editor__editable {
        min-height: 300px;
    }

    .ck-editor__main {
        height: 300px;
        min-height: 300px;
        overflow-y: scroll;
        border: 1px solid #bbbbbb;
    }

    .ck-content { background: rgb(226 232 240 / var(--tw-bg-opacity)) !important; }

    .dark .ck-editor { border: 1px solid rgb(100 116 139 / var(--tw-border-opacity)) !important; }
    .dark .ck-editor * { border: 1px solid rgb(71 85 105 / var(--tw-border-opacity)) !important; }
    .dark .ck-content { background: rgb(15 23 42 / var(--tw-bg-opacity)) !important; }
    .dark .ck.ck-toolbar__items, .dark .ck.ck-toolbar__items * { background: rgb(51 65 85 / var(--tw-bg-opacity)) !important; color: #DDDDDD !important; }
    .dark .ck.ck-toolbar.ck-toolbar_grouping { background: rgb(51 65 85 / var(--tw-bg-opacity)) !important; }
    .dark .ck.ck-button:hover, .dark .ck.ck-button:hover * { background: rgb(31 35 65 / var(--tw-bg-opacity)) !important; }
</style>
@endonce
