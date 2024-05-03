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
    <div class="w-2/12">
        @themeComponent('forced-aspect-image', [
            'src' => $src,
            'class' => 'border-2 border-primary-600',
            'imageClass' => 'wrla_image_preview',
            'aspect' => '1:1',
            'rounded' => 'full'
        ])
    </div>

    {{-- File input and notes container --}}
    <div class="flex flex-1 flex-col justify-center items-center px-10">
        <div class="flex w-full justify-between">
            {{-- File input --}}
            <input {{ $attributes->merge([
                'id' => $id,
                'type' => $type,
                'name' => $name,
                'class' => 'wrla_image_input text-sm focus:outline-none focus:ring-1 focus:ring-primary-500 dark:focus:ring-primary-500 placeholder-slate-400 dark:placeholder-slate-600'
            ])->merge($attr) }}
                onchange="wrla_setPreviewImage(this)"
            />

            {{-- Remove button (if image does not yet exist anyway, or options has allowRemove true) --}}
            @if(!$imageExists || ($imageExists && $options['allowRemove'] == true))
                @themeComponent('forms.button', [
                    'size' => 'small',
                    'type' => 'button',
                    'color' => 'danger',
                    'text' => 'Remove',
                    'icon' => 'fa fa-trash relative top-[-1px] text-xs',
                    'attr' => [
                        'title' => 'Remove',
                        'class' => 'text-sm',
                        'onclick' => 'wrla_removeImage(this)',
                        'style' => $imageExists ? 'display: block;' : 'display: none;'
                    ]
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
                'class' => '!text-xs !px-2 !py-1',
                'notes' => $imageExists || (!$isHttpImage && !$imageExists)
                    ? '<a href="'.$value.'" target="_blank">'.$value.'</a>'.$imageExistsHtml
                    : 'No image set'
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
        var previewImageElement = button.parentElement.parentElement.parentElement.querySelector('.wrla_image_preview');
        var removeInput = button.parentElement.querySelector('.wrla_remove_input');
        
        input.value = '';
        button.style.display = 'none';
        previewImageElement.src = 'https://ui-avatars.com/api/?name=X&background=FFD0CC&color=FFE2DD&size=200';

        // We only need to set the removeInput value to true if a file already exists
        @if($imageExists)
            removeInput.value = 'true';
        @endif
    }
</script>
@endonce

@error($name)
    @themeComponent('alert', ['type' => 'error', 'message' => $message, 'class' => 'mt-2'])
@enderror
