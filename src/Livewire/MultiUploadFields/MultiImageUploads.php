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
     * Images
     */
    public array $images = [];

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
     * On updated images, validate and reindex
     */
    public function updatedImages(mixed $value)
    {
        // Flatten images array (as multiple can be injected into a slot)
        $images = array_reduce($this->images, function($carry, $item) {
            return array_merge($carry, is_array($item) ? $item : [$item]);
        }, []);

        // Validate images and get errors
        [$validatedImages, $validationErrors] = $this->validateFiles($images);

        foreach ($validationErrors as $validationError) {
            $this->addError('images.' . count($this->images), $validationError);
        }

        $this->images = $validatedImages;
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
