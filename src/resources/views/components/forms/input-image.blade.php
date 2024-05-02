@props(['attr' => [], 'options' => [], 'ignoreOld' => false, 'label' => null, 'id' => '', 'name', 'value' => '', 'type' => 'text', 'required' => false, 'autofocus' => false, 'readonly' => false])

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

<div class="flex justify-start items-center gap-6 mt-2">
    {{-- Preview image container --}}
    <div class="flex flex-col items-center gap-2 w-2/12">
        <div class="w-full h-0 pb-[100%] relative">
            <img src="{{ !empty($value) ? $value : $user->getProfileImage() }}" alt="Image" class="wrla-image-preview object-contain w-full h-full absolute top-0 left-0 rounded-full" />
        </div>
    </div>

    {{-- File input and notes container --}}
    <div class="flex flex-1 flex-col justify-center items-center px-10">
        {{-- File input --}}
        <input {{ $attributes->merge([
            'id' => $id,
            'type' => $type,
            'name' => $name,
            'value' => $ignoreOld ? $value : old($name, $value),
            'class' => 'w-full text-sm focus:outline-none focus:ring-1 focus:ring-primary-500 dark:focus:ring-primary-500 placeholder-slate-400 dark:placeholder-slate-600'
        ])->merge($attr) }}
            onchange="setPreviewImage(
                this,
                this.parentElement.parentElement.querySelector('.wrla-image-preview')
            )"
        />

        {{-- Field notes (if options has notes key) --}}
        @if(!empty($options['notes']))
            @themeComponent('forms.field-notes', ['notes' => $options['notes']])
        @endif
    </div>
</div>

@once
<script>
    function setPreviewImage(input, previewImageElement) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            
            reader.onload = function (e) {
                previewImageElement.src = e.target.result;
            }
            
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>
@endonce

@error($name)
    @themeComponent('alert', ['type' => 'error', 'message' => $message, 'class' => 'mt-2'])
@enderror
