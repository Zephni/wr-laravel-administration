@php
    $scalarItems = array_values(array_filter($items, fn ($c) => empty($c['isImage'])));

    $renderAttrs = function (array $attrs): string {
        $html = '';
        foreach ($attrs as $k => $v) {
            if ($v === null || $v === false) {
                continue;
            }
            $html .= ' ' . $k . '="' . e($v) . '"';
        }
        return $html;
    };

    $inputClasses = 'block w-full px-3 py-1 border border-slate-400 dark:border-slate-500 bg-slate-200 dark:bg-slate-900 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:focus:ring-primary-500 rounded-md shadow-sm placeholder-slate-400 dark:placeholder-slate-600';

    $existingImagesNameMap = collect($existingImages)->map(fn ($v) => $v['name'] ?? null)->filter()->toArray();

    // Layout mode: 'column' stacks each form group's inner fields vertically and flows form groups
    // in a uniform wrapping grid (like MultiImage); 'row' (default) lays inner fields out
    // horizontally, vertically centered, with form groups stacked on top of each other.
    $isColumnLayout = ($layout ?? 'row') === 'column';

    // Responsive grid column classes keyed by the configured number of columns. Written as literal
    // class strings so Tailwind's JIT scanner picks them up. Falls back to a 4-column grid.
    $columnLayoutGridClasses = [
        1 => 'grid-cols-1',
        2 => 'grid-cols-1 sm:grid-cols-2',
        3 => 'grid-cols-1 sm:grid-cols-2 md:grid-cols-3',
        4 => 'grid-cols-2 sm:grid-cols-2 md:grid-cols-4',
        5 => 'grid-cols-2 sm:grid-cols-3 md:grid-cols-5',
        6 => 'grid-cols-2 sm:grid-cols-3 md:grid-cols-6',
        7 => 'grid-cols-2 sm:grid-cols-4 md:grid-cols-7',
        8 => 'grid-cols-2 sm:grid-cols-4 md:grid-cols-8',
    ];
    $gridColsClass = $columnLayoutGridClasses[(int) ($columns ?? 4)] ?? 'grid-cols-2 sm:grid-cols-2 md:grid-cols-4';

    $groupsContainerClass = $isColumnLayout
        ? 'grid ' . $gridColsClass . ' items-start gap-4'
        : 'flex flex-col gap-y-3';

    $entryClass = $isColumnLayout
        ? 'relative flex flex-col gap-3 rounded-md border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/40 p-3'
        : 'relative flex flex-col md:flex-row md:items-center gap-4 rounded-md border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/40 p-3';

    // Derive a readable base label for each form group title from the add button label.
    // Example: "Add auto update" -> "Auto update".
    $groupTitleBase = preg_replace('/^\s*add\s+/i', '', (string) $addItemLabel);
    $groupTitleBase = trim((string) $groupTitleBase);
    if ($groupTitleBase === '') {
        $groupTitleBase = 'Row';
    }
    $groupTitleBase = ucfirst($groupTitleBase);
@endphp

<div wire:key="multi-field-{{ $fieldName }}" class="mt-2 flex flex-col gap-y-3">

    {{-- Hidden inputs so the parent field's applySubmittedValue can read state from the HTTP POST. --}}
    <input type="hidden" name="{{ $fieldName }}_existing_images" value="{{ json_encode($existingImagesNameMap, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}">

    @foreach($serializedImages as $compositeKey => $serialized)
        <input type="hidden" name="{{ $fieldName }}_newimg_{{ $compositeKey }}" value="{{ $serialized }}">
    @endforeach

    {{-- Item header (desktop only, row layout only) --}}
    @if(count($formGroups) > 0 && !$isColumnLayout)
        <div class="hidden md:flex items-end gap-4 px-3">
            @foreach($items as $item)
                <div class="{{ $item['itemClass'] ?? 'flex-1' }}">
                    @if(!empty($item['label']))
                        <span class="text-sm font-medium text-slate-600 dark:text-slate-300">{!! $item['label'] !!}
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    {{-- Form groups --}}
    <div class="{{ $groupsContainerClass }}">
    @forelse($formGroups as $groupIndex => $group)
        <div
            wire:key="multi-form-group-{{ $fieldName }}-{{ $groupIndex }}"
            class="{{ $entryClass }}"
        >
            <div class="relative top-[-1px] text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400 pr-8">
                {{ $groupTitleBase }} #{{ $groupIndex + 1 }}
            </div>

            {{-- Marker so every rendered form group index is present in the submitted _groups array. --}}
            <input type="hidden" name="{{ $fieldName }}_groups[{{ $groupIndex }}][__exists]" value="1">

            @foreach($items as $item)
                @php
                    $itemKey = $item['key'];
                    $compositeKey = $groupIndex . '__' . $itemKey;
                    // In column layout items fill the form group width; in row layout they use the
                    // item's configured itemClass (eg. fixed/flex widths sitting side by side).
                    $itemClass = $isColumnLayout ? 'w-full' : ($item['itemClass'] ?? 'flex-1');
                @endphp

                <div class="{{ $itemClass }} flex flex-col gap-1 min-w-0">

                    {{-- Field label. Row layout shows labels in the shared desktop header so the
                        per-item label is mobile-only; column layout always shows it above the item. --}}
                    @if(!empty($item['label']))
                        <span class="{{ $isColumnLayout ? 'block -mt-1 mb-1' : 'md:hidden' }} text-sm font-medium text-slate-600 dark:text-slate-300">{!! $item['label'] !!}</span>
                    @endif

                    @if(!empty($item['isImage']))
                        {{-- ============ Image item ============ --}}
                        @php
                            $newFile = $newImages[$compositeKey] ?? null;
                            $hasTemp = $newFile instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
                            $existing = $existingImages[$compositeKey] ?? null;
                            $previewSrc = $hasTemp ? $newFile->temporaryUrl() : ($existing['url'] ?? null);
                            $previewName = $hasTemp ? $newFile->getClientOriginalName() : ($existing['name'] ?? null);
                            $hasImage = !empty($previewSrc);
                        @endphp

                        <div
                            wire:key="multi-field-image-{{ $fieldName }}-{{ $compositeKey }}"
                            x-data="{
                                isDragging: false,
                                dragDepth: 0,
                                onDragEnter() { this.dragDepth++; this.isDragging = true; },
                                onDragLeave() { this.dragDepth = Math.max(0, this.dragDepth - 1); if (this.dragDepth === 0) this.isDragging = false; },
                                onDrop(event) {
                                    this.dragDepth = 0; this.isDragging = false;
                                    const droppedFiles = event.dataTransfer?.files;
                                    if (!droppedFiles || droppedFiles.length === 0) return;
                                    this.$refs.imageInput.files = droppedFiles;
                                    this.$refs.imageInput.dispatchEvent(new Event('input', { bubbles: true }));
                                    this.$refs.imageInput.dispatchEvent(new Event('change', { bubbles: true }));
                                }
                            }"
                            x-on:dragenter.prevent="onDragEnter()"
                            x-on:dragover.prevent
                            x-on:dragleave.prevent="onDragLeave()"
                            x-on:drop.prevent="onDrop($event)"
                            class="relative overflow-hidden bg-slate-100 hover:bg-slate-200 border border-slate-400 rounded-md shadow text-base transition-colors"
                            style="aspect-ratio: 1 / 0.8;"
                            title="{{ $hasImage ? $previewName : 'Add image' }}"
                        >
                            <label
                                for="multi-field-image-input-{{ $fieldName }}-{{ $compositeKey }}"
                                x-bind:class="isDragging ? 'opacity-0' : ''"
                                class="block w-full h-full cursor-pointer"
                            >
                                @if($hasImage)
                                    <div class="relative group w-full h-full flex flex-col text-slate-600">
                                        <img src="{{ $previewSrc }}" alt="{{ $previewName }}" class="absolute inset-0 w-full h-full object-cover filter blur-sm opacity-80 rounded-md pointer-events-none" />
                                        <div class="flex-1 min-h-0 overflow-hidden">
                                            <img src="{{ $previewSrc }}" alt="{{ $previewName }}" class="relative w-full h-full object-contain rounded-md" style="z-index: 1;" />
                                        </div>
                                        @if(!empty($previewName))
                                            <div class="bg-black/65 text-white text-xs px-2 py-1 truncate" title="{{ $previewName }}" style="z-index: 2;">{{ $previewName }}</div>
                                        @endif
                                        <div
                                            wire:loading.class="bg-black/30 opacity-100"
                                            wire:target="newImages.{{ $compositeKey }}"
                                            class="absolute inset-0 group-hover:bg-black/20 opacity-0 group-hover:opacity-100 flex flex-col items-center justify-center gap-3 transition-opacity rounded-md text-white"
                                            style="z-index: 3;"
                                        >
                                            <i wire:loading.remove wire:target="newImages.{{ $compositeKey }}" class="fa-solid fa-turn-down text-2xl"></i>
                                            <i wire:loading wire:target="newImages.{{ $compositeKey }}" class="fa-solid fa-spinner text-2xl animate-spin"></i>
                                            <div class="w-full text-center font-medium">Replace</div>
                                        </div>
                                    </div>
                                @else
                                    <div class="w-full h-full flex flex-col items-center justify-center gap-2 text-slate-600 p-2 text-center">
                                        <i wire:loading.remove wire:target="newImages.{{ $compositeKey }}" class="fa-solid fa-plus text-2xl"></i>
                                        <i wire:loading wire:target="newImages.{{ $compositeKey }}" class="fa-solid fa-spinner text-2xl animate-spin"></i>
                                        <div class="w-full text-sm">Drag &amp; drop<br />or browse</div>
                                    </div>
                                @endif

                                <input
                                    x-ref="imageInput"
                                    id="multi-field-image-input-{{ $fieldName }}-{{ $compositeKey }}"
                                    wire:model="newImages.{{ $compositeKey }}"
                                    type="file"
                                    class="hidden"
                                />
                            </label>

                            {{-- Drag overlay --}}
                            <div
                                x-cloak
                                x-show="isDragging"
                                class="absolute inset-0 z-40 rounded-md border-2 border-dashed border-primary-500 bg-primary-500/40 flex items-center justify-center p-3 text-center text-sm font-semibold text-white pointer-events-none"
                            >
                                <div class="flex flex-col justify-center items-center gap-y-1.5">
                                    <i class="fa-solid fa-cloud-arrow-up text-2xl"></i>
                                    <span>Upload image</span>
                                </div>
                            </div>

                            {{-- Delete image --}}
                            @if($hasImage)
                                <button
                                    type="button"
                                    wire:click.stop="removeImage('{{ $compositeKey }}')"
                                    class="absolute top-1 right-1 z-30 h-4 w-4 border border-white opacity-60 hover:opacity-100 flex justify-center items-center rounded-full bg-slate-600 hover:bg-rose-600 text-white shadow focus:outline-none transition-colors cursor-pointer"
                                    title="Remove image"
                                    aria-label="Remove image"
                                >
                                    <i class="fa-solid fa-xmark text-[10px] leading-none"></i>
                                </button>
                            @endif
                        </div>

                        @error('newImages.' . $compositeKey)
                            @themeComponent('alert', ['type' => 'error', 'message' => $message, 'class' => 'mt-1'])
                        @enderror

                    @elseif($item['type'] === 'textarea')
                        {{-- ============ Textarea item ============ --}}
                        <textarea
                            name="{{ $fieldName }}_groups[{{ $groupIndex }}][{{ $itemKey }}]"
                            wire:model="formGroups.{{ $groupIndex }}.{{ $itemKey }}"
                            rows="3"
                            class="{{ $inputClasses }}"
                            {!! $renderAttrs($item['attributes'] ?? []) !!}
                        >{{ $group[$itemKey] ?? '' }}</textarea>

                    @elseif($item['type'] === 'select')
                        {{-- ============ Select item ============ --}}
                        <select
                            name="{{ $fieldName }}_groups[{{ $groupIndex }}][{{ $itemKey }}]"
                            wire:model="formGroups.{{ $groupIndex }}.{{ $itemKey }}"
                            class="{{ $inputClasses }}"
                            {!! $renderAttrs($item['attributes'] ?? []) !!}
                        >
                            @foreach(($item['items'] ?? []) as $optionValue => $optionLabel)
                                <option value="{{ $optionValue }}" @selected(($group[$itemKey] ?? '') == $optionValue)>{{ $optionLabel }}</option>
                            @endforeach
                        </select>

                    @else
                        {{-- ============ Generic input item ============ --}}
                        <input
                            type="{{ $item['type'] ?? 'text' }}"
                            name="{{ $fieldName }}_groups[{{ $groupIndex }}][{{ $itemKey }}]"
                            wire:model="formGroups.{{ $groupIndex }}.{{ $itemKey }}"
                            value="{{ $group[$itemKey] ?? '' }}"
                            class="{{ $inputClasses }}"
                            {!! $renderAttrs($item['attributes'] ?? []) !!}
                        />
                    @endif
                </div>
            @endforeach

            {{-- Delete form group --}}
            <button
                type="button"
                wire:click="removeFormGroup({{ $groupIndex }})"
                class="absolute {{ $isColumnLayout ? 'top-2' : 'top-1' }} right-1 z-30 h-5 w-5 border border-white opacity-60 hover:opacity-100 flex justify-center items-center rounded-full bg-rose-600 hover:bg-rose-700 text-white shadow focus:outline-none transition-colors cursor-pointer"
                title="Delete row"
                aria-label="Delete row {{ $groupIndex + 1 }}"
            >
                <i class="fa-solid fa-xmark text-xs leading-none"></i>
            </button>
        </div>
    @empty
        <div class="text-sm text-slate-500 dark:text-slate-400 italic px-1">{{ $emptyText }}</div>
    @endforelse
    </div>

    {{-- Add form group --}}
    @if($this->canAddFormGroup)
        <div>
            <button
                type="button"
                wire:click="addFormGroup"
                class="inline-flex items-center gap-2 px-3 py-1.5 rounded-md border border-primary-500 text-primary-600 dark:text-primary-400 hover:bg-primary-500 hover:text-white transition-colors text-sm font-medium cursor-pointer"
            >
                <i class="fa-solid fa-plus"></i>
                {{ $addItemLabel }}
            </button>
        </div>
    @endif

</div>
