{{-- Hidden inputs so applySubmittedValue can read state from the HTTP POST --}}
<input type="hidden" name="{{ $fieldName }}_existing" value="{{ json_encode(array_column($existingImages, 'name')) }}">
<input type="hidden" name="{{ $fieldName }}_new_serialized" value="{{ $serializedImages }}">

{{-- Images Container --}}
<div wire:key="images-container" class="flex flex-col gap-y-2">
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-5">
        {{-- Existing DB images --}}
        @foreach($existingImages as $index => $existingImage)
            <div class="flex flex-col gap-y-2">
                <x-wrla-image-uploader
                    :existing-image-url="$existingImage['url']"
                    :existing-image-original-name="$existingImage['name']"
                    :image-index="$index"
                    :max-images="$maxImages"
                    :field-name="$fieldName"
                    :delete-action="'removeExistingImage('.$index.')'"
                />
            </div>
        @endforeach

        {{-- New temp uploads --}}
        @foreach($images as $index => $image)
            <div class="flex flex-col gap-y-2">
                <x-wrla-image-uploader
                    :existing-image="$image"
                    :image-index="count($existingImages) + $index"
                    :max-images="$maxImages"
                    :field-name="$fieldName"
                    :delete-action="'removeImage('.$index.')'"
                />
            </div>
        @endforeach

        {{-- Add image slot --}}
        @if((count($existingImages) + count($images)) < $maxImages)
            <div class="flex flex-col gap-y-2">
                <x-wrla-image-uploader
                    :image-index="count($existingImages) + count($images)"
                    :max-images="$maxImages"
                    :field-name="$fieldName"
                />
            </div>
        @endif
    </div>

    {{-- Validation errors --}}
    @if($errors->has('images.*'))
        <div class="w-full">
            @foreach($errors->get('images.*') as $imageErrors)
                @foreach($imageErrors as $error)
                    @themeComponent('alert', ['type' => 'error', 'message' => $error, 'class' => 'mb-2'])
                @endforeach
            @endforeach
        </div>
    @endif
</div>
