<?php
namespace WebRegulate\LaravelAdministration\Livewire\MultiUploadFields;

use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;

class MultiImageUploads extends Component
{
    use WithFileUploads;

    /**
     * Field name used for wire:model bindings.
     * Must match the public property name on this component (default: 'images').
     */
    public string $fieldName = 'images';

    /**
     * Max images, set on mount
     */
    public int $maxImages = 5;

    /**
     * Validation rules
     */
    public string|array $validation;

    /**
     * New temp-upload images (0-based array index).
     */
    public array $images = [];

    /**
     * Replacement uploads keyed by existing-image index.
     * When the user uploads a file onto an existing-image slot, Livewire writes the
     * TemporaryUploadedFile here. updatedReplacements() then swaps out the DB entry
     * and moves the file into $images.
     */
    public array $replacements = [];

    /**
     * Existing DB images: array of ['url' => '...', 'name' => '...']
     */
    public array $existingImages = [];

    /**
     * Serialized new uploads — written into a hidden form input so applySubmittedValue
     * can retrieve the temp files from the HTTP POST without needing session storage.
     */
    public string $serializedImages = '';

    /**
     * Handle new/replacement uploads in the $images array.
     * Because $images uses true 0-based array indices (matching the new-upload slots),
     * uploading onto an already-occupied slot naturally overwrites that index.
     * The flatten+reindex step below normalises any sparse state that can arise when
     * multiple files are dropped into a single slot at once.
     */
    public function updatedImages(mixed $value): void
    {
        // Flatten images array (multiple files can be injected into a single slot via drag-and-drop)
        $images = array_reduce($this->images, function ($carry, $item) {
            return array_merge($carry, is_array($item) ? $item : [$item]);
        }, []);

        [$validatedImages, $validationErrors] = $this->validateFiles($images);

        foreach ($validationErrors as $validationError) {
            $this->addError('images.' . count($this->images), $validationError);
        }

        $this->images = $validatedImages;
        $this->syncSerializedImages();
    }

    /**
     * Handle replacement of an existing DB image.
     * wire:model on existing-image slots is bound to "replacements.{existingIndex}".
     */
    public function updatedReplacements(mixed $value, mixed $key): void
    {
        $index = (int) $key;
        $raw = $this->replacements[$index] ?? null;

        // Consume the replacement entry regardless of outcome.
        unset($this->replacements[$index]);
        $this->replacements = array_values($this->replacements);

        // Normalize: a "multiple" file input delivers an array even for a single file.
        $candidates = is_array($raw) ? array_values($raw) : ($raw !== null ? [$raw] : []);
        $candidates = array_values(array_filter($candidates, fn ($f) => $f instanceof TemporaryUploadedFile));

        if (empty($candidates)) {
            return;
        }

        [$validatedImages, $validationErrors] = $this->validateFiles($candidates);

        foreach ($validationErrors as $validationError) {
            $this->addError('images.' . count($this->images), $validationError);
        }

        if (empty($validatedImages)) {
            return;
        }

        // Remove the DB image that was replaced and append the new temp file(s).
        if (array_key_exists($index, $this->existingImages)) {
            unset($this->existingImages[$index]);
            $this->existingImages = array_values($this->existingImages);
        }

        $this->images = array_merge($this->images, $validatedImages);
        $this->syncSerializedImages();
    }

    /**
     * Serialize new uploads into $serializedImages for the hidden form input.
     */
    private function syncSerializedImages(): void
    {
        $this->serializedImages = empty($this->images)
            ? ''
            : TemporaryUploadedFile::serializeMultipleForLivewireResponse($this->images);
    }

    /**
     * Mount
     */
    public function mount(int $maxImages, string|array $validation = 'image|mimes:jpeg,png,jpg|max:10240', string $fieldName = 'images', array $existingImages = [])
    {
        $this->fieldName = $fieldName;
        $this->maxImages = $maxImages;
        $this->validation = $validation;
        $this->existingImages = $existingImages;
    }

    /**
     * Render
     */
    public function render()
    {
        return view(WRLAHelper::getViewPath('livewire.multi-upload-fields.multi-image-uploads'));
    }

    /**
     * Remove a newly-uploaded temp image by index.
     */
    public function removeImage(int $index): void
    {
        if(!array_key_exists($index, $this->images)) {
            return;
        }

        unset($this->images[$index]);

        // Reindex for consistent Livewire bindings (images.0, images.1, ...)
        $this->images = array_values($this->images);
        $this->syncSerializedImages();
    }

    /**
     * Remove an existing DB image by index.
     */
    public function removeExistingImage(int $index): void
    {
        if (!array_key_exists($index, $this->existingImages)) {
            return;
        }

        unset($this->existingImages[$index]);
        $this->existingImages = array_values($this->existingImages);
    }

    /**
     * Validate
     * 
     * @return array Validated files
     */
    private function validateFiles(array $images): array
    {
        // Validate each uploaded file individually
        $validatedImages = [];
        $validationErrors = [];
        $this->resetValidation('images');

        foreach ($images as $index => $image) {
            if (!$image instanceof TemporaryUploadedFile) {
                continue;
            }

            $validator = validator(['images' => [$index => $image]], [
                'images.' . $index => $this->validation
            ], [
                'images.' . $index . '.image' => 'Must be a valid image file.',
                'images.' . $index . '.max' => 'Must not exceed the maximum size of :max kilobytes.',
            ]);

            if ($validator->fails()) {
                $validationErrors[] = $validator->errors()->first('images.' . $index);
                continue;
            }

            $validatedImages[] = $image;
        }

        return [$validatedImages, $validationErrors];
    }
}
