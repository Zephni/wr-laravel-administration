<?php

namespace WebRegulate\LaravelAdministration\Livewire\MultiUploadFields;

use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;

/**
 * Backs the {@see \WebRegulate\LaravelAdministration\Classes\ManageableFields\MultiField} field.
 *
 * Manages a dynamic list of form groups where each form group contains arbitrary inner fields
 * (defined by the parent field as items). Scalar inner inputs post their values natively via the
 * form (they carry both a `name` and a `wire:model`), while image items round-trip through
 * Livewire's temporary upload mechanism and are serialised into hidden inputs so the parent
 * field's applySubmittedValue() can read them from the HTTP POST.
 */
class MultiFormGroups extends Component
{
    use WithFileUploads;

    /**
     * Parent field name — used as the prefix for the submitted hidden inputs.
     */
    public string $fieldName = 'multi_field';

    /**
     * Item schema (from MultiFieldItem::toViewArray()).
     */
    public array $items = [];

    /**
     * Maximum number of form groups (0 = unlimited).
     */
    public int $maxFormGroups = 0;

    /**
     * Label for the add-form-group button.
     */
    public string $addItemLabel = 'Add row';

    /**
     * Text shown when there are no form groups.
     */
    public string $emptyText = 'No rows yet.';

    /**
     * Layout mode for each form group's inner fields ('row' or 'column').
     */
    public string $layout = 'row';

    /**
     * Number of columns used by the column layout grid.
     */
    public int $columns = 4;

    /**
     * Scalar form group values: array of [itemKey => value], indexed by form group position.
     */
    public array $formGroups = [];

    /**
     * New temp-upload images, keyed by composite "{groupIndex}__{itemKey}".
     */
    public array $newImages = [];

    /**
     * Existing DB images, keyed by composite "{groupIndex}__{itemKey}" => ['url' => ..., 'name' => ...].
     */
    public array $existingImages = [];

    /**
     * Serialized new uploads, keyed by composite "{groupIndex}__{itemKey}" — written into hidden inputs.
     */
    public array $serializedImages = [];

    /**
     * Mount.
     */
    public function mount(
        string $fieldName,
        array $items,
        int $maxFormGroups = 0,
        string $addItemLabel = 'Add row',
        string $emptyText = 'No rows yet.',
        string $layout = 'row',
        int $columns = 4,
        array $formGroups = [],
        array $existingImages = [],
    ): void {
        $this->fieldName = $fieldName;
        $this->items = $items;
        $this->maxFormGroups = $maxFormGroups;
        $this->addItemLabel = $addItemLabel;
        $this->emptyText = $emptyText;
        $this->layout = $layout;
        $this->columns = max(1, $columns);
        $this->formGroups = array_values($formGroups);
        $this->existingImages = $existingImages;
    }

    /**
     * Scalar (non-image) items from the schema.
     */
    protected function scalarItems(): array
    {
        return array_values(array_filter($this->items, fn ($c) => empty($c['isImage'])));
    }

    /**
     * Append a new empty form group.
     */
    public function addFormGroup(): void
    {
        if ($this->maxFormGroups > 0 && count($this->formGroups) >= $this->maxFormGroups) {
            return;
        }

        $newGroup = [];
        foreach ($this->scalarItems() as $item) {
            $newGroup[$item['key']] = $item['default'] ?? '';
        }

        $this->formGroups[] = $newGroup;
    }

    /**
     * Remove a form group by index, shifting all image maps to match the reindexed form groups.
     */
    public function removeFormGroup(int $index): void
    {
        if (array_key_exists($index, $this->formGroups)) {
            unset($this->formGroups[$index]);
            $this->formGroups = array_values($this->formGroups);
        }

        $this->newImages = $this->shiftImageMap($this->newImages, $index);
        $this->serializedImages = $this->shiftImageMap($this->serializedImages, $index);
        $this->existingImages = $this->shiftImageMap($this->existingImages, $index);
    }

    /**
     * Drop the removed form group's images and shift higher group indices down by one.
     */
    protected function shiftImageMap(array $map, int $removedIndex): array
    {
        $result = [];

        foreach ($map as $compositeKey => $value) {
            [$groupIndex, $itemKey] = explode('__', $compositeKey, 2);
            $groupIndex = (int) $groupIndex;

            if ($groupIndex === $removedIndex) {
                continue;
            }

            if ($groupIndex > $removedIndex) {
                $groupIndex--;
            }

            $result[$groupIndex . '__' . $itemKey] = $value;
        }

        return $result;
    }

    /**
     * Handle a new/replacement image upload bound to "newImages.{groupIndex}__{itemKey}".
     */
    public function updatedNewImages(mixed $value, ?string $key = null): void
    {
        if ($key === null) {
            return;
        }

        $raw = $this->newImages[$key] ?? null;

        // A "multiple" file input delivers an array even for a single file.
        $candidates = is_array($raw) ? array_values($raw) : ($raw !== null ? [$raw] : []);
        $candidates = array_values(array_filter($candidates, fn ($f) => $f instanceof TemporaryUploadedFile));

        $this->resetErrorBag('newImages.' . $key);

        if (empty($candidates)) {
            unset($this->newImages[$key], $this->serializedImages[$key]);
            return;
        }

        $file = $candidates[0];
        $validation = $this->itemUploadValidation($key);

        $validator = validator(['file' => $file], ['file' => $validation], [
            'file.image' => 'Must be a valid image file.',
            'file.mimes' => 'Must be a valid image type.',
            'file.max' => 'Must not exceed the maximum size of :max kilobytes.',
        ]);

        if ($validator->fails()) {
            unset($this->newImages[$key], $this->serializedImages[$key]);
            $this->addError('newImages.' . $key, $validator->errors()->first('file'));
            return;
        }

        // Keep a single file per item and serialize it for the hidden input.
        $this->newImages[$key] = $file;
        $this->serializedImages[$key] = TemporaryUploadedFile::serializeMultipleForLivewireResponse([$file]);

        // Uploading onto an item that held an existing image replaces it.
        unset($this->existingImages[$key]);
    }

    /**
     * Remove an image (new or existing) from a single item.
     */
    public function removeImage(string $key): void
    {
        unset($this->newImages[$key], $this->serializedImages[$key], $this->existingImages[$key]);
    }

    /**
     * Resolve the upload validation rules for the item referenced by a composite key.
     */
    protected function itemUploadValidation(string $compositeKey): string|array
    {
        $itemKey = explode('__', $compositeKey, 2)[1] ?? '';

        foreach ($this->items as $item) {
            if (($item['key'] ?? null) === $itemKey) {
                return $item['uploadValidation'] ?? 'image|mimes:jpeg,png,jpg,gif|max:10240';
            }
        }

        return 'image|mimes:jpeg,png,jpg,gif|max:10240';
    }

    /**
     * Whether another form group can be added.
     */
    public function getCanAddFormGroupProperty(): bool
    {
        return $this->maxFormGroups === 0 || count($this->formGroups) < $this->maxFormGroups;
    }

    /**
     * Render.
     */
    public function render()
    {
        return view(WRLAHelper::getViewPath('livewire.multi-upload-fields.multi-form-groups'));
    }
}
