@props(['attr' => [], 'options' => [], 'ignoreOld' => false, 'label' => null, 'id' => '', 'name', 'value' => '', 'type' => 'text', 'required' => false, 'autofocus' => false, 'readonly' => false])

@php
    // Set id from name if unset
    $id = empty($id) ? 'wrinput-'.$name : $id;

    // Check if http image
    $isHttpImage = preg_match('/^http(s)?:\/\//', $value);

    // Check that $value exists as an image, if not then we use the $options['defaultImage']
    $imageExists = !empty($value) && file_exists(public_path($value));
    $src = $imageExists ? $value : $options['defaultImage'];
    $imageExistsHtml = $imageExists
        ? '<span class="float-right text-green-500">Image found</span>'
        : '<span class="float-right text-red-500">Image not found</span>';
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
            <img src="{{ $src }}" alt="Image" class="wrla-image-preview object-contain w-full h-full absolute top-0 left-0 rounded-full" />
        </div>
    </div>

    {{-- File input and notes container --}}
    <div class="flex flex-1 flex-col justify-center items-center px-10">
        <div class="flex w-full justify-between">
            {{-- File input --}}
            <input {{ $attributes->merge([
                'id' => $id,
                'type' => $type,
                'name' => $name,
                'class' => 'text-sm focus:outline-none focus:ring-1 focus:ring-primary-500 dark:focus:ring-primary-500 placeholder-slate-400 dark:placeholder-slate-600'
            ])->merge($attr) }}
                onchange="setPreviewImage(
                    this,
                    this.parentElement.parentElement.querySelector('.wrla-image-preview')
                )"
            />

            {{-- Remove button (if options has allowRemove true) --}}
            @if($imageExists && $options['allowRemove'] == true)
                @themeComponent('forms.button', [
                    'size' => 'small',
                    'type' => 'button',
                    'color' => 'danger',
                    'text' => 'Remove',
                    'icon' => 'fa fa-trash relative top-[-1px] text-xs',
                    'attr' => [
                        'title' => 'Remove',
                        'class' => 'text-sm',
                        'onclick' => 'removeImage(this)'
                    ]
                ])

                <input type="hidden" name="wrla_remove_{{ $name }}" value="false" />
            @endif
        </div>

        {{-- Field notes (if options has notes key) --}}
        @if(!empty($options['notes']))
            @themeComponent('forms.field-notes', ['notes' => $options['notes']])
        @endif

        @themeComponent('forms.field-notes', [
            'class' => '!text-xs !px-2 !py-1',
            'notes' => $imageExists || (!$isHttpImage && !$imageExists)
                ? '<a href="'.$value.'" target="_blank">'.$value.'</a>'.$imageExistsHtml
                : 'No image set'
        ])
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

    function removeImage(button) {
        var input = button.parentElement.parentElement.querySelector('input[type="file"]');
        var removeInput = document.querySelector('input[name="wrla_remove_'+input.name+'"]');
        var previewImageElement = button.parentElement.parentElement.parentElement.querySelector('.wrla-image-preview');
        
        input.value = '';
        removeInput.value = 'true';
        previewImageElement.src = ''; // TODO: Show delete image icon or something
    }
</script>
@endonce

@error($name)
    @themeComponent('alert', ['type' => 'error', 'message' => $message, 'class' => 'mt-2'])
@enderror
