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
     * Field name used for wire:model bindings and session key.
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
     * Serialized images (Use TemporaryUploadedFile::unserializeFromLivewireRequest to retrieve)
     */
    public string $serializedImages = '';

    /**
     * On updated images, serialize so we can repopulate on re-mount
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

        // Set images and sync serialized version for session storage
        $this->images = $validatedImages;
        $this->syncSerializedImages();
    }

    /**
     * Serialize images for session storage
     */
    private function syncSerializedImages(): void
    {
        $this->serializedImages = empty($this->images)
            ? ''
            : TemporaryUploadedFile::serializeMultipleForLivewireResponse($this->images);

        session()->put('wrla_multi_image_' . $this->fieldName, $this->serializedImages);
    }

    /**
     * Mount
     */
    public function mount(int $maxImages, string|array $validation = 'image|mimes:jpeg,png,jpg|max:10240', string $fieldName = 'images')
    {
        // Set field name, max images and validation
        $this->fieldName = $fieldName;
        $this->maxImages = $maxImages;
        $this->validation = $validation;

        // Restore from session if serializedImages is not already populated by Livewire
        if (empty($this->serializedImages)) {
            $this->serializedImages = session()->get('wrla_multi_image_' . $this->fieldName, '');
        }

        // Unserialize images from session, filtering out any stale temp files that
        // no longer exist on disk (prevents temporaryUrl() from throwing on render).
        if(!empty($this->serializedImages)) {
            $files = TemporaryUploadedFile::unserializeFromLivewireRequest($this->serializedImages);
            $this->images = array_values(array_filter($files, fn($f) => $f->exists() && $f->isPreviewable()));
            $this->syncSerializedImages();
        }
    }

    /**
     * Render
     */
    public function render()
    {
        return view(WRLAHelper::getViewPath('livewire.multi-upload-fields.multi-image-uploads'));
    }

    /**
     * Remove an existing image by index
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
