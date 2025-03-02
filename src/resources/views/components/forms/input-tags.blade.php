@props(['options' => [], 'label' => null])

@php
    // Set id from name if unset
    $id = empty($attributes->get('id')) ? 'wrinput-'.$attributes->get('name') : $attributes->get('id');
@endphp

<div class="{{ $options['containerClass'] ?? 'w-full flex-1 flex flex-col gap-1 md:flex-auto' }}">

@if(!empty($label))
    {!! view($WRLAHelper::getViewPath('components.forms.label'), [
        'label' => $label,
        'attributes' => new \Illuminate\View\ComponentAttributeBag([
            'id' => $id,
            'class' => $options['labelClass'] ?? ''
        ])
    ])->render() !!}
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
    },
    handleInput(e) {
        if ((e.data === ',' || e.data === ' ') || e.inputType === 'insertFromPaste') {
            const parts = this.newTag.split(/[,\s]+/).map(t => t.trim()).filter(Boolean);
            parts.forEach(t => this.addTag(t));
        }
    }
}"
x-init="init()"
class="overflow-x-auto"
style="width: 100%; max-width: 100%;"
>
    <div class="w-full flex items-center gap-1 px-1.5 py-1 border border-slate-400 dark:border-slate-500 bg-slate-200 dark:bg-slate-900
        focus:outline-none focus:ring-1 focus:ring-primary-500 dark:focus:ring-primary-500 rounded-md shadow-sm">
        <template x-for="(tag, index) in tags" :key="index">
            <span class="inline-flex items-center px-2 border-2 border-primary-500 rounded-md">
                <span x-text="tag" class="font-medium pr-1.5"></span>
                <button type="button" class="relative top-[-1px] text-rose-600" @click="removeTag(index)">x</button>
            </span>
        </template>
        <input
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