<?php

namespace WebRegulate\LaravelAdministration\Classes\ManageableFields;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use WebRegulate\LaravelAdministration\Classes\ManageableModel;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;
use WebRegulate\LaravelAdministration\Traits\ManageableField;

class MultiImage
{
    use ManageableField;

    /**
     * Make method.
     *
     * @param ?ManageableModel $manageableModel
     * @param ?string $column
     * @param string $fileSystem Storage disk name
     * @param string $path  Storage path for uploaded images (required)
     * @param int $maxImages Maximum number of images allowed
     */
    public static function make(
        ?ManageableModel $manageableModel = null,
        ?string $column = null,
        string $fileSystem = 'public',
        string $path = 'images',
        int $maxImages = 5,
    ): static {
        if (empty($path)) {
            throw new Exception('Path is required for MultiImage ' . $column . ' field');
        }

        $value = $manageableModel?->getModelInstance()->{$column};
        if (is_array($value)) {
            $value = json_encode($value);
        }

        $instance = new static($column, $value, $manageableModel);

        $instance->setOptions([
            'path' => rtrim($path, '/'),
            'maxImages' => $maxImages,
            'fileSystem' => $fileSystem,
            'uploadValidation' => 'image|mimes:jpeg,png,jpg|max:10240',
        ]);

        return $instance;
    }

    /**
     * Set max images.
     */
    public function maxImages(int $maxImages): static
    {
        $this->setOption('maxImages', $maxImages);
        return $this;
    }

    /**
     * Set upload validation rules for individual images.
     */
    public function uploadValidation(string|array $validation): static
    {
        $this->setOption('uploadValidation', $validation);
        return $this;
    }

    /**
     * Render the field — embeds the MultiImageUploads Livewire component within a labelled wrapper.
     */
    public function render(): mixed
    {
        $validation = $this->getOption('uploadValidation');

        // Build existing-image data from the current stored JSON value.
        $existingImages = [];
        $currentValue = $this->getValue();
        if (!empty($currentValue)) {
            $filenames = json_decode($currentValue, true) ?? [];
            $disk = Storage::disk($this->getOption('fileSystem'));
            $path = rtrim($this->getOption('path'), '/');
            foreach ($filenames as $filename) {
                $existingImages[] = [
                    'url'  => $disk->url(ltrim("{$path}/{$filename}", '/')),
                    'name' => $filename,
                ];
            }
        }

        return view(WRLAHelper::getViewPath('components.forms.multi-image'), [
            'label'          => $this->getLabel(),
            'options'        => $this->options,
            'fieldName'      => $this->getName(),
            'maxImages'      => $this->getOption('maxImages'),
            'validation'     => $validation,
            'existingImages' => $existingImages,
        ])->render();
    }

    /**
     * Apply submitted value.
     *
     * Reads two hidden inputs written by the MultiImageUploads Livewire component:
     *   {fieldName}_existing        — JSON array of existing filenames to keep
     *   {fieldName}_new_serialized  — serialized TemporaryUploadedFile objects to store
     *
     * Returns null when the inputs are absent (field was not rendered).
     */
    public function applySubmittedValue(Request $request, mixed $value): mixed
    {
        $fieldName = $this->getName();

        // If the component was not rendered, leave the column untouched.
        if (!$request->has($fieldName . '_existing')) {
            return null;
        }

        // Surviving existing filenames.
        $existingFilenames = json_decode($request->input($fieldName . '_existing', '[]'), true) ?? [];

        // New temp-file uploads.
        $newFiles = [];
        $serialized = $request->input($fieldName . '_new_serialized', '');
        if (!empty($serialized)) {
            $allFiles = TemporaryUploadedFile::unserializeFromLivewireRequest($serialized);
            $newFiles = array_values(array_filter($allFiles, fn($f) => $f->exists()));
        }

        // Move new uploads to permanent storage.
        $newFilenames = [];
        if (!empty($newFiles)) {
            $disk = Storage::disk($this->getOption('fileSystem'));
            $path = WRLAHelper::forwardSlashPath($this->getOption('path'));

            if (!$disk->exists($path)) {
                $disk->makeDirectory($path);
            }

            foreach ($newFiles as $file) {
                $extension = $file->getClientOriginalExtension() ?: 'jpg';
                $filename  = (string) Str::uuid() . '.' . $extension;
                $disk->put("{$path}/{$filename}", $file->get());
                $newFilenames[] = $filename;
            }
        }

        return json_encode(array_merge($existingFilenames, $newFilenames));
    }
}
