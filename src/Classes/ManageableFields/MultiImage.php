<?php

namespace WebRegulate\LaravelAdministration\Classes\ManageableFields;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
     * @param null|string|callable $filename  If string, can contain {index}, {id}, {time} placeholders. If callable, takes (manageableModel, originalFilename, index) and must return the filename. If null, uses the original filename from the uploaded file.
     * @param int $maxImages Maximum number of images allowed
     */
    public static function make(
        ?ManageableModel $manageableModel = null,
        ?string $column = null,
        string $fileSystem = 'public',
        string $path = 'images',
        null|string|callable $filename = null,
        int $maxImages = 5,
    ): static {
        if (empty($path)) {
            throw new Exception('Path is required for MultiImage ' . $column . ' field');
        }

        $value = $manageableModel?->model()->{$column};
        if (is_array($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $instance = new static($column, $value, $manageableModel);

        $instance->setOptions([
            'path' => rtrim($path, '/'),
            'maxImages' => $maxImages,
            'fileSystem' => $fileSystem,
            'filename' => $filename,
            'uploadValidation' => 'image|mimes:jpeg,png,jpg|max:10240',
        ]);

        return $instance;
    }

    /**
     * Set filename option.
     *
     * @param null|string|callable $filename  If string, can contain {index}, {id}, {time} placeholders. If callable, takes (manageableModel, originalFilename, index) and must return the filename. If null, uses the original filename from the uploaded file.
     */
    public function filename(null|string|callable $filename): static
    {
        $this->setOption('filename', $filename);
        return $this;
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
     * Format the filename for a new uploaded image.
     *
     * @param null|string|callable $name  If null, returns the original filename. If string, replaces {index}, {id}, {time} placeholders. If callable, calls it with (manageableModel, originalFilename, index).
     */
    public function formatImageName(null|string|callable $name, string $originalFileName, int $index = 0): string
    {
        // If null, use the original filename as-is.
        if ($name === null) {
            return $originalFileName;
        }

        // If callable, delegate to it.
        if (is_callable($name)) {
            return $name($this->manageableModel?->model(), $originalFileName, $index);
        }

        // Replace {index} with the current image index.
        if (str_contains($name, '{index}')) {
            $name = str_replace('{index}', $index, $name);
        }

        // Replace {id} with the model's id.
        if (str_contains($name, '{id}')) {
            $id = $this->manageableModel?->model()->id;
            if (empty($id)) {
                $id = $this->manageableModel?->model()->max('id') + 1;
            }
            $name = str_replace('{id}', $id, $name);
        }

        // Replace {time} with the current Unix timestamp.
        if (str_contains($name, '{time}')) {
            $name = str_replace('{time}', time(), $name);
        }

        return $name;
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
     * Pre-validation hook.
     *
     * The Livewire component writes state into {fieldName}_existing and {fieldName}_new_serialized
     * rather than {fieldName} itself, so the field's key is absent from $request->all().
     * Returning true here forces the current column value to be merged into the request under
     * the field's own key, which prevents ManageableModel::updateModelInstanceProperties from
     * skipping this field due to the key-existence guard.
     */
    public function preValidation(?string $value): bool
    {
        return true;
    }

    /**
     * Render the field — embeds the MultiImageUploads Livewire component within a labelled wrapper.
     */
    public function render(): mixed
    {
        $validation = $this->getOption('uploadValidation');

        // Build existing-image data from the current stored JSON value.
        $existingImages = [];
        $parseError = null;
        $currentValue = $this->getValue();
        if (!empty($currentValue)) {
            try {
                $filenames = json_decode(is_array($currentValue) ? json_encode($currentValue) : $currentValue, true);
                if (!is_array($filenames)) {
                    throw new \RuntimeException('Stored value did not decode to an array.');
                }
                $fileSystemName = $this->getOption('fileSystem');
                $disk = Storage::disk($fileSystemName);
                $path = rtrim($this->getOption('path'), '/');
                $isPublicDisk = $fileSystemName === 'public';
                foreach ($filenames as $filename) {
                    if (!is_string($filename)) {
                        throw new \RuntimeException('Unexpected non-string entry in image list.');
                    }
                    $filePath = ltrim("{$path}/{$filename}", '/');
                    if ($isPublicDisk) {
                        $url = $disk->url($filePath);
                    } else {
                        $url = route('wrla.serve-file', [
                            'disk'        => $fileSystemName,
                            'encodedPath' => base64_encode($filePath),
                        ]);
                    }
                    $existingImages[] = [
                        'url'  => $url,
                        'name' => $filename,
                    ];
                }
            } catch (\Throwable $e) {
                $parseError = 'Could not parse stored image data (the JSON may be malformed or corrupted). Saving this form will overwrite the existing value.';
                $existingImages = [];
            }
        }

        return view(WRLAHelper::getViewPath('components.forms.multi-image'), [
            'label'          => $this->getLabel(),
            'options'        => $this->options,
            'fieldName'      => $this->getName(),
            'maxImages'      => $this->getOption('maxImages'),
            'validation'     => $validation,
            'existingImages' => $existingImages,
            'parseError'     => $parseError,
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

        // If the component was not rendered, leave the column untouched by returning the
        // current value unchanged (returning null would wipe the column).
        if (!$request->has($fieldName . '_existing')) {
            return $value;
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

            $filenameOption = $this->getOption('filename');

            foreach ($newFiles as $index => $file) {
                $originalName = $file->getClientOriginalName();
                $filename = $this->formatImageName($filenameOption, $originalName, $index);

                // If the resolved filename has no extension, append the uploaded file's extension.
                if (!str_contains($filename, '.')) {
                    $extension = $file->getClientOriginalExtension() ?: 'jpg';
                    $filename .= '.' . $extension;
                }

                $disk->put("{$path}/{$filename}", $file->get());
                $newFilenames[] = $filename;
            }
        }

        return array_merge($existingFilenames, $newFilenames);
    }
}
