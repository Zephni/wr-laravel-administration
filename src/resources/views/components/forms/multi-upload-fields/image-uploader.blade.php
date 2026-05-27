@props([
    'existingImage' => null,
    'existingImageOriginalName' => null,
    'imageIndex',
    'maxImages',
    'fieldName' => 'images',
    'deleteAction' => null,
])

@php
    $hasPreviewableImage = $existingImage instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
    $imageName = !empty($existingImageOriginalName)
        ? $existingImageOriginalName
        : ($hasPreviewableImage ? $existingImage->getClientOriginalName() : null);
@endphp

<div
    wire:key="image-uploader-{{ $fieldName }}-{{ $imageIndex }}"
    x-data="{
        isDragging: false,
        isDeleteHovered: false,
        dragDepth: 0,
        onDragEnter() {
            this.dragDepth++;
            this.isDragging = true;
        },
        onDragLeave() {
            this.dragDepth = Math.max(0, this.dragDepth - 1);
            if (this.dragDepth === 0) {
                this.isDragging = false;
            }
        },
        onDrop(event) {
            this.dragDepth = 0;
            this.isDragging = false;

            const droppedFiles = event.dataTransfer?.files;
            if (!droppedFiles || droppedFiles.length === 0) {
                return;
            }

            this.$refs.imageInput.files = droppedFiles;

            // Trigger native events so Livewire picks up the new FileList.
            this.$refs.imageInput.dispatchEvent(new Event('input', { bubbles: true }));
            this.$refs.imageInput.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }"
    x-on:dragenter.prevent="onDragEnter()"
    x-on:dragover.prevent
    x-on:dragleave.prevent="onDragLeave()"
    x-on:drop.prevent="onDrop($event)"
    {{ $attributes->merge([
        'class' => 'relative overflow-hidden bg-slate-100 hover:bg-slate-200 border border-slate-400 rounded-md shadow-lg text-base transition-colors',
        'style' => 'aspect-ratio: 1 / 0.8;',
        'title' => $hasPreviewableImage ? $imageName : 'Add image',
    ]) }}
>
    <div class="w-full h-full transition-opacity has-[+.delete-image-btn:hover]:opacity-20">
        {{-- Counter --}}
        <div class="absolute top-1 left-1 bg-slate-600 text-white border border-slate-50 text-xs font-semibold px-2 py-0.5 rounded-full z-20 cursor-default">
            {{ $imageIndex + 1 }} / <span class="pl-px">{{ $maxImages }}</span>
        </div>

        <label
            for="image-input-{{ $fieldName }}-{{ $imageIndex }}"
            x-bind:class="isDragging ? 'opacity-0' : ''"
            class="block w-full h-full cursor-pointer"
        >

            {{-- If existing image (and is previewable) --}}
            @if($hasPreviewableImage)

                <div class="has-[+.delete-image-btn:hover]:opacity-20 relative group w-full h-full flex flex-col text-slate-600">
                    {{-- Blurred image that covers entire background --}}
                    <img
                        src="{{ $existingImage->temporaryUrl() }}"
                        alt="Existing Image {{ $imageIndex + 1 }}"
                        class="absolute inset-0 w-full h-full object-cover filter blur-sm opacity-80 rounded-md pointer-events-none"
                    />
                    {{-- Image --}}
                    <div class="flex-1 min-h-0 overflow-hidden">
                        <img
                            src="{{ $existingImage->temporaryUrl() }}"
                            alt="Existing Image {{ $imageIndex + 1 }}"
                            x-bind:class="isDeleteHovered ? 'opacity-50' : 'opacity-100'"
                            class="relative w-full h-full object-contain rounded-md transition-opacity"
                            style="z-index: 1;"
                        />
                    </div>
                    {{-- Image name --}}
                    @if(!empty($imageName))
                        <div
                            class="bg-black/65 text-white text-xs px-2 py-1 truncate"
                            title="{{ $imageName }}"
                            style="z-index: 2;"
                        >
                            {{ $imageName }}
                        </div>
                    @endif
                    <div
                        wire:loading.class="bg-black/30 opacity-100"
                        wire:target="{{ $fieldName }}.{{ $imageIndex }}"
                        class="absolute inset-0 group-hover:bg-black/20 opacity-0 group-hover:opacity-100 flex flex-col items-center justify-center gap-3 transition-opacity rounded-md text-white"
                        style="z-index: 3;"
                    >
                        <i wire:loading.remove wire:target="{{ $fieldName }}.{{ $imageIndex }}" class="fa-solid fa-turn-down text-3xl"></i>
                        <i wire:loading wire:target="{{ $fieldName }}.{{ $imageIndex }}" class="fa-solid fa-spinner text-3xl animate-spin"></i>
                        <div class="w-full text-center text-lg font-medium">
                            Replace image
                        </div>
                    </div>
                </div>

            {{-- If add image --}}
            @else

                <div class="w-full h-full flex flex-col items-center justify-center gap-3 text-slate-600">
                    <i wire:loading.remove wire:target="{{ $fieldName }}.{{ $imageIndex }}" class="fa-solid fa-plus text-3xl"></i>
                    <i wire:loading wire:target="{{ $fieldName }}.{{ $imageIndex }}" class="fa-solid fa-spinner text-3xl animate-spin"></i>
                    <div class="w-full text-center">
                        Drag and drop<br />
                        or browse image/s
                    </div>
                </div>

            @endif

            {{-- Image input --}}
            <input
                x-ref="imageInput"
                id="image-input-{{ $fieldName }}-{{ $imageIndex }}"
                wire:model="{{ $fieldName }}.{{ $imageIndex }}"
                type="file"
                class="hidden"
                multiple
            />
        </label>
    </div>

    {{-- On hover while dragging --}}
    <div
        x-cloak
        x-show="isDragging"
        class="absolute inset-0 z-40 rounded-md border-2 border-dashed border-primary-500 bg-primary-500/40 flex items-center justify-center p-3 text-center text-sm font-semibold text-white pointer-events-none"
    >
        <div class="w-full h-full flex flex-col justify-center items-center gap-y-1.5">
            <i class="fa-solid fa-cloud-arrow-up text-3xl mb-2"></i>
            <span class="text-lg font-medium">Upload image/s</span>
        </div>
    </div>

    {{-- Delete action --}}
    @if($hasPreviewableImage && $deleteAction)
        <button
            type="button"
            wire:click.stop="{{ $deleteAction }}"
            x-on:mouseenter="isDeleteHovered = true"
            x-on:mouseleave="isDeleteHovered = false"
            class="delete-image-btn absolute top-1 right-1 z-30 h-5 w-5 border border-white opacity-50 hover:opacity-100 flex justify-center items-center rounded-full bg-rose-600 text-white shadow-md focus:outline-none focus:ring-2 focus:ring-rose-400 transition-colors cursor-pointer"
            title="Delete image"
            aria-label="Delete image {{ $imageIndex + 1 }}"
        >
            <i class="fa-solid fa-xmark text-xs"></i>
        </button>
    @endif

</div>
