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
     * @param ?string $path  Storage path for uploaded images (required)
     * @param int $maxImages Maximum number of images allowed
     * @param string $fileSystem Storage disk name
     */
    public static function make(
        ?ManageableModel $manageableModel = null,
        ?string $column = null,
        ?string $path = null,
        int $maxImages = 5,
        string $fileSystem = 'public'
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

        return view(WRLAHelper::getViewPath('components.forms.multi-image'), [
            'label' => $this->getLabel(),
            'options' => $this->options,
            'fieldName' => $this->getName(),
            'maxImages' => $this->getOption('maxImages'),
            'validation' => $validation,
        ])->render();
    }

    /**
     * Apply submitted value.
     *
     * Reads the serialized temporary images stored in the session by the MultiImageUploads
     * Livewire component, moves each file to permanent storage, and returns a JSON-encoded
     * array of the stored relative paths.
     *
     * Returns null (no change) when no new images were uploaded.
     */
    public function applySubmittedValue(Request $request, mixed $value): mixed
    {
        $fieldName = $this->getName();
        $sessionKey = 'wrla_multi_image_' . $fieldName;
        $serialized = session()->get($sessionKey, '');

        if (empty($serialized)) {
            return null;
        }

        // Unserialize Livewire temporary files
        $files = TemporaryUploadedFile::unserializeFromLivewireRequest($serialized);
        $files = array_values(array_filter($files, fn($f) => $f->exists()));

        if (empty($files)) {
            return null;
        }

        $disk = Storage::disk($this->getOption('fileSystem'));
        $path = WRLAHelper::forwardSlashPath($this->getOption('path'));

        if (!$disk->exists($path)) {
            $disk->makeDirectory($path);
        }

        $storedPaths = [];

        foreach ($files as $file) {
            $extension = $file->getClientOriginalExtension() ?: 'jpg';
            $filename = (string) Str::uuid() . '.' . $extension;
            $disk->put("{$path}/{$filename}", $file->get());
            $storedPaths[] = ltrim("{$path}/{$filename}", '/');
        }

        session()->forget($sessionKey);

        return json_encode($storedPaths);
    }
}
