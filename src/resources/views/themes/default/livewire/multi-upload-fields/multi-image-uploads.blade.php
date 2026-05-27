{{-- Images Container --}}
<div wire:key="images-container" class="flex flex-col gap-y-2">
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-5">
        {{-- Existing images --}}
        @foreach($images as $index => $image)
            <div class="flex flex-col gap-y-2">
                <x-wrla-image-uploader
                    :existing-image="$image"
                    :image-index="$index"
                    :max-images="$maxImages"
                    :field-name="$fieldName"
                    :delete-action="'removeImage('.$index.')'" 
                />
            </div>
        @endforeach
    
        {{-- Add image --}}
        @if(count($images) < $maxImages)
            <div class="flex flex-col gap-y-2">
                <x-wrla-image-uploader
                    :image-index="count($images)"
                    :max-images="$maxImages"
                    :field-name="$fieldName"
                />
            </div>
        @endif
    </div>

    {{-- If there are any errors related to these images, display them here --}}
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
