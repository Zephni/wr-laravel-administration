@props(['options' => [], 'label' => null])

@php
    // Set id from name if unset
    $id = empty($attributes->get('id')) ? 'wrinput-'.$attributes->get('name') : $attributes->get('id');
@endphp

<div wire:ignore class="{{ $options['containerClass'] ?? 'w-full flex-1 md:flex-auto' }}">

    @if(!empty($label))
        {!! view($WRLAHelper::getViewPath('components.forms.label'), [
            'label' => $label,
            'attributes' => new \Illuminate\View\ComponentAttributeBag([
                'id' => $id.'-label',
                'class' => ($options['labelClass'] ?? ''),
                'style' => 'margin-bottom: 5px;'
            ])
        ])->render() !!}
    @endif

    <textarea {{ $attributes->merge([
        'id' => $id,
        'class' => 'wrla_wysiwyg',
        'style' => 'white-space: pre-wrap; word-wrap: break-word;'
    // Remove value from attributes
    ])->except('value')  }}>{{
        $attributes->get('value')
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
        .ck.ck-reset.ck-editor {
            margin-top: 7px;
        }

        .ck-source-editing-area,
        .ck-editor__editable {
            min-height: 260px;
        }

        .ck-editor__main {
            height: 260px;
            min-height: 260px;
            overflow-y: scroll;
            border: 1px solid #bbbbbb;
        }

        .ck-content { background: rgb(226 232 240 / var(--tw-bg-opacity)) !important; }

        .dark .ck-editor { border: 1px solid rgb(100 116 139 / var(--tw-border-opacity)) !important; }
        .dark .ck-editor * { border: 1px solid rgb(71 85 105 / var(--tw-border-opacity)) !important; }
        .dark .ck-content { background: rgb(15 23 42 / var(--tw-bg-opacity)) !important; }
        .dark .ck.ck-toolbar.ck-toolbar_grouping { background: rgb(51 65 85 / var(--tw-bg-opacity)) !important; }
        .dark .ck.ck-button:hover, .dark .ck.ck-button:hover * { background: rgb(31 35 65 / var(--tw-bg-opacity)) !important; }
        .dark .ck-on { background: rgb(31 35 65 / var(--tw-bg-opacity)) !important; }
        .dark .ck.ck-toolbar__items, .dark .ck.ck-toolbar__items * { background: rgb(51 65 85 / var(--tw-bg-opacity)); color: #DDDDDD !important; }
    </style>
    @endonce

</div>