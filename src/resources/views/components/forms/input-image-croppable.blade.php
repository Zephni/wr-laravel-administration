@props(['fileSystem' => null, 'publicUrl' => '', 'publicUrlWithoutDomain' => '', 'options' => [], 'label' => null])

<link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.js"></script>

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
        'attributes' => Arr::toAttributeBag([
            'for' => $id,
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
            'attributes' => Arr::toAttributeBag([
                'id' => 'croppedImagePreview',
            ]),
        ])
    </div>

    {{-- File input and notes container --}}
    <div class="flex flex-1 flex-col justify-center items-center pl-5 pr-10">
        <div class="flex w-full justify-between">
            {{-- File input --}}
            <input {{ $attributes->merge([
                'id' => 'imageInput',
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
                    'attributes' => Arr::toAttributeBag([
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
                'attributes' => Arr::toAttributeBag([
                    'class' => '!text-xs !px-2 !py-1',
                ])
            ])
        @endif
    </div>
</div>

{{-- Cropper JS preview / cropper area --}}
<div id="imageToCropContainer" class="flex justify-center items-center bg-slate-100 dark:bg-slate-900 p-3">
    <div style="width: 100%; max-width: 600px; margin-auto;">
        <img id="imageToCrop" src="" alt="Image to crop" style="max-width: 100%;">
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

    document.addEventListener('DOMContentLoaded', function () {
        const imageInput = document.getElementById('imageInput');
        const imageToCrop = document.getElementById('imageToCrop');
        const imageToCropContainer = document.getElementById('imageToCropContainer');
        const croppedImagePreview = document.getElementById('croppedImagePreview');

        // Hide preview om start
        imageToCropContainer.style.display = 'none';

        let cropper;

        let updatePreview = function() {
            // Get the cropped canvas
            const canvas = cropper.getCroppedCanvas({
                width: 500, // Set desired width
                height: 500, // Set desired height
            });

            // Convert canvas to a Blob
            canvas.toBlob(function (blob) {
                // Create a URL for the blob and set it as the src of the preview image
                const url = URL.createObjectURL(blob);
                croppedImagePreview.src = url;
                croppedImagePreview.style.display = 'flex'; // Show the preview
            }, 'image/png'); // Specify the output format
        }

        imageInput.addEventListener('change', function (e) {
            const files = e.target.files;
            if (files && files.length > 0) {
                const file = files[0];
                const reader = new FileReader();

                // Show image preview on crop
                imageToCropContainer.style.display = 'block';

                reader.onload = function (event) {
                    imageToCrop.src = event.target.result;

                    // Destroy previous cropper instance if it exists
                    if (cropper) {
                        cropper.destroy();
                    }

                    // Initialize Cropper.js
                    cropper = new Cropper(imageToCrop, {
                        aspectRatio: 16 / 12, // Or your desired aspect ratio
                        viewMode: 1, // Define the view mode
                        // Add other Cropper.js options here

                        // Update preview on ready and cropend
                        ready: () => {
                            updatePreview();
                        },
                        cropend: () => updatePreview(),
                    });
                };

                reader.readAsDataURL(file);
            }
        });

        // uploadButton.addEventListener('click', function () {
        //     if (cropper) {
        //         // Get the cropped canvas
        //         const canvas = cropper.getCroppedCanvas({
        //             width: 500, // Set desired width
        //             height: 500, // Set desired height
        //             // Add other getCroppedCanvas options
        //         });

        //         // Convert canvas to a Blob
        //         canvas.toBlob(function (blob) {
        //             // Use FormData to send the file and other data
        //             const formData = new FormData();
        //             formData.append('_token', '{{ csrf_token() }}'); // Add CSRF token
        //             formData.append('cropped_image', blob, 'cropped_image.png'); // Append the cropped image blob

        //             // Send the data to the backend using AJAX
        //             fetch('/upload-cropped-image', { // Replace with your backend route
        //                 method: 'POST',
        //                 body: formData,
        //             })
        //             .then(response => response.json())
        //             .then(data => {
        //                 console.log('Upload successful:', data);
        //                 // Display the uploaded image preview (optional)
        //                 if (data.url) {
        //                     croppedImagePreview.src = data.url;
        //                     croppedImagePreview.style.display = 'block';
        //                 }
        //                 // Handle success (e.g., show a success message, redirect)
        //             })
        //             .catch(error => {
        //                 console.error('Upload failed:', error);
        //                 // Handle errors (e.g., show an error message)
        //             });
        //         }, 'image/png'); // Specify the output format
        //     }
        // });
    });
</script>
@endonce

@error($name)
    @themeComponent('alert', ['type' => 'error', 'message' => $message, 'class' => 'mt-2'])
@enderror

</div>