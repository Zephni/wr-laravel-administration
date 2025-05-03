@props(['fileSystem' => null, 'publicUrl' => '', 'publicUrlWithoutDomain' => '', 'options' => [], 'label' => null])

@php
    // Set id from name if unset
    $id = empty($attributes->get('id')) ? 'wrinput-'.$attributes->get('name') : $attributes->get('id');

    // Get $name and $value from attribute as it's used alot here
    $name = $attributes->get('name');
    $value = $attributes->get('value');

    // Check if http image
    $isHttpImage = preg_match('/^http(s)?:\/\//', $value);

    // Check that $value exists as an image, if not then we use the $options['defaultImage']
    $imageExists = !empty($value) && $fileSystemImageExists && $value != $WRLAHelper::getCurrentThemeData('no_image_src');
    $src = $imageExists ? $publicUrl : $options['defaultImage'];
    $imageExistsHtml = $imageExists
        ? '<span class="float-right text-green-500">Image found</span>'
        : '<span class="float-right text-red-500">Image not found</span>';
@endphp

<div wire:ignore class="{{ $options['containerClass'] ?? 'w-full flex-1 md:flex-auto' }}">

@if(!empty($label))
    @themeComponent('forms.label', [
        'label' => $label,
        'attributes' => new \Illuminate\View\ComponentAttributeBag([
            'id' => $id,
            'class' => $options['labelClass'] ?? ''
        ])
    ])
@endif

<div class="flex justify-start items-center gap-6 mt-2">
    {{-- Preview image container --}}
    <div class="w-2/12">
        @themeComponent('forced-aspect-image', [
            'src' => $src,
            'originalSrc' => $publicUrlWithoutDomain,
            'class' => "wrla_image_preview {$options['class']} ".($imageExists ? '' : 'wrla_no_image'),
            'aspect' => $options["aspect"],
        ])
    </div>

    {{-- File input and notes container --}}
    <div class="flex flex-1 flex-col justify-center items-center pl-5 pr-10">
        <div class="flex w-full justify-between">
            {{-- File input --}}
            <input {{ $attributes->merge([
                'class' => 'wrla_image_input text-sm focus:outline-none focus:ring-1 focus:ring-primary-500 dark:focus:ring-primary-500 placeholder-slate-400 dark:placeholder-slate-600'
            ]) }}
                onchange="wrla_setPreviewImage(this)"
            />

            {{-- Remove button (if image does not yet exist anyway, or options has allowRemove true) --}}
            @if(!$imageExists || ($imageExists && $options['allowRemove'] == true))
                @themeComponent('forms.button', [
                    'size' => 'small',
                    'color' => 'danger',
                    'text' => 'Remove',
                    'icon' => 'fa fa-trash relative text-xs',
                    'attributes' => new \Illuminate\View\ComponentAttributeBag([
                        'type' => 'button',
                        'title' => 'Remove',
                        'class' => 'text-sm',
                        'onclick' => 'wrla_removeImage(this)',
                        'style' => $imageExists ? 'display: block;' : 'display: none;'
                    ])
                ])

                <input class="wrla_remove_input" type="hidden" name="wrla_remove_{!! $name !!}" value="false" />
            @endif
        </div>

        {{-- Field notes (if options has notes key) --}}
        @if(!empty($options['notes']))
            @themeComponent('forms.field-notes', ['notes' => $options['notes']])
        @endif

        @if($imageExists)
            @themeComponent('forms.field-notes', [
                'notes' => $imageExists || (!$isHttpImage && !$imageExists)
                    ? '<a href="'.$publicUrl.'" target="_blank" class="underline">'.$publicUrlWithoutDomain.'</a>'.$imageExistsHtml
                    : 'No image set',
                'attributes' => new \Illuminate\View\ComponentAttributeBag([
                    'class' => '!text-xs !px-2 !py-1',
                ])
            ])
        @endif
    </div>
</div>

@once
<script>
    function wrla_setPreviewImage(input) {
        if (input.files && input.files[0]) {
            var previewImageElement = input.parentElement.parentElement.parentElement.querySelector('.wrla_image_preview');

            var reader = new FileReader();
            
            reader.onload = function (e) {
                previewImageElement.src = e.target.result;
                previewImageElement.classList.remove('wrla_no_image');
            }
            
            reader.readAsDataURL(input.files[0]);

            // Show remove button (If exists)
            var removeButton = input.parentElement.querySelector('.wrla_remove_input');
            if (removeButton) {
                removeButton.value = 'false';
                removeButton.parentElement.querySelector('button').style.display = 'block';
            }
        }
    }

    function wrla_removeImage(button) {
        var input = button.parentElement.parentElement.querySelector('.wrla_image_input');
        var previewImageElement = input.parentElement.parentElement.parentElement.querySelector('.wrla_image_preview');
        var removeInput = button.parentElement.querySelector('.wrla_remove_input');
        
        input.value = '';
        button.style.display = 'none';
        previewImageElement.src = '{{ $WRLAHelper::getCurrentThemeData('no_image_src') }}';
        previewImageElement.classList.add('wrla_no_image');

        // Pass $imageExists to JS
        var imageExists = @json($imageExists);

        // We only need to set the removeInput value to true if a file already exists
        if(imageExists) {
            removeInput.value = 'true';
        }
    }
</script>
@endonce

@error($name)
    @themeComponent('alert', ['type' => 'error', 'message' => $message, 'class' => 'mt-2'])
@enderror

</div>