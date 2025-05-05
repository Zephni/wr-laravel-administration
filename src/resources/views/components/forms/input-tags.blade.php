@props(['options' => [], 'label' => null])

@php
    // Set id from name if unset
    $id = empty($attributes->get('id')) ? 'wrinput-'.$attributes->get('name') : $attributes->get('id');
@endphp

<div class="{{ $options['containerClass'] ?? 'w-full flex-1 flex flex-col gap-1 md:flex-auto' }}">

@if(!empty($label))
    @themeComponent('forms.label', [
        'label' => $label,
        'attributes' => Arr::toAttributeBag([
            'for' => $id,
            'class' => $options['labelClass'] ?? ''
        ])
    ])
@endif

<div x-data="{
    tags: [],
    newTag: '',
    init() {
        // Set size to parent width on init and resize
        setTimeout(() => this.$el.style.width = this.$el.parentElement.offsetWidth + 'px', 0);
        window.addEventListener('resize', (e) => this.$el.style.width = this.$el.parentElement.offsetWidth + 'px');

        const initialVal = '{{ old($attributes->get('name'), $attributes->get('value') ?? '') }}';
        if (initialVal.trim()) {
            initialVal.split(',').forEach(tag => this.addTag(tag.trim()));
        }
    },
    addTag(tag) {
        if (tag && !this.tags.includes(tag)) {
            this.tags.push(tag);
            this.newTag = '';
        }
    },
    removeTag(index) {
        this.tags.splice(index, 1);
        this.$refs.newTagInput.focus();
    },
    handleInput(e) {
        // Comma or return
        if ((e.data === ',' || e.data === '\n') || e.inputType === 'insertFromPaste') {
            const parts = this.newTag.split(',').map(t => t.trim()).filter(Boolean);
            parts.forEach(t => this.addTag(t));
        }
    }
}"
x-init="init()"
class="whitespace-nowrap"
style="width: 100%; max-width: 100%;"
>
    <div class="w-full flex items-center overflow-x-auto gap-1 px-1.5 py-1 border border-slate-400 dark:border-slate-500 bg-slate-200 dark:bg-slate-900
        focus:outline-none focus:ring-1 focus:ring-primary-500 dark:focus:ring-primary-500 rounded-md shadow-sm">
        <template x-for="(tag, index) in tags" :key="index">
            <span class="inline-flex items-center px-2 bg-primary-500 bg-opacity-5 border-2 border-primary-500 rounded-md" style="line-height: 20px;">
                <span x-text="tag" class="relative font-medium pr-1.5" style="top: -1px;"></span>
                <button type="button" class="relative top-[-1px] text-primary-600 font-medium" @click="removeTag(index)">x</button>
            </span>
        </template>
        <input
            id="{{ $id }}"
            x-ref="newTagInput"
            x-model="newTag"
            @input="handleInput($event)"
            @keydown.enter.prevent="addTag(newTag)"
            @keydown.backspace="if (!newTag) removeTag(tags.length - 1)"
            class="border-none outline-none focus:ring-0 flex-1 bg-transparent
            placeholder-slate-400 dark:placeholder-slate-600"
        />
    </div>

    {{-- Actual input field --}}
    <input type="hidden" name="{{ $attributes['name'] }}" x-bind:value="tags.join(',')" />
</div>

{{-- Field notes (if options has notes key) --}}
@if(!empty($options['notes']))
    @themeComponent('forms.field-notes', ['notes' => $options['notes']])
@endif

@if($options['showError'] ?? true)
    @error($attributes->get('name'))
        @themeComponent('alert', ['type' => 'error', 'message' => $message, 'class' => 'mt-2'])
    @enderror
@endif

</div>