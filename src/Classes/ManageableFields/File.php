<?php

namespace WebRegulate\LaravelAdministration\Classes\ManageableFields;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\View\ComponentAttributeBag;
use WebRegulate\LaravelAdministration\Classes\ManageableModel;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;
use WebRegulate\LaravelAdministration\Traits\ManageableField;

class File
{
    use ManageableField;

    /**
     * Manipulate image function, set with manipulateImage method on creation
     *
     * @var callable
     */
    public $manipulateImageFunction = null;

    /**
     * Make method (can be used in any class that extends FormComponent).
     *
     * @param  ?mixed  $column
     */
    public static function make(?ManageableModel $manageableModel = null, ?string $column = null, ?string $path = null, ?string $filename = null): static
    {
        // If path is empty, we throw an exception
        if (empty($path)) {
            throw new \Exception('Path is required for File '.$column.' field');
        }

        $imageInstance = new static($column, $manageableModel?->getModelInstance()->{$column}, $manageableModel);
        $imageInstance->setOptions([
            'path' => $path,
            'filename' => $filename,
            'unlinkOld' => true,
            'allowRemove' => true,
            'aspect' => null,
            'storeFilenameOnly' => true,
            'class' => '',
        ]);

        return $imageInstance;
    }

    /**
     * Upload the image from request and apply the value.
     */
    public function applySubmittedValue(Request $request, mixed $value): mixed
    {
        // Get current value of file
        $currentImage = $this->getAttribute('value');

        // If value is equal to the special constant WRLA_KEY_REMOVE, we delete the image
        if ($value === WRLAHelper::WRLA_KEY_REMOVE) {
            $this->deleteFileByPath($currentImage);

            return null;
        }

        if ($request->hasFile($this->getAttribute('name'))) {
            $value = $this->uploadFile($request->file($this->getAttribute('name')));

            // If unlinkOld option set, and an image already exists with the old value, we delete it
            if ($this->getOption('unlinkOld') == true && ! empty($currentImage)) {
                $this->deleteFileByPath($currentImage);
            }

            // If storeFilenameOnly is false, store the entire filepath/filename.ext
            if ($this->getOption('storeFilenameOnly') == false) {
                return $value;
            }

            // Otherwise, we store only the filename.ext
            $parts = explode('/', $value);
            $value = end($parts);

            return $value;
        }

        return null;
    }

    /**
     * Upload file
     *
     * @param  UploadedFile  $request
     */
    public function uploadFile(UploadedFile $file): string
    {
        // Get path and filename
        $path = WRLAHelper::forwardSlashPath($this->getPath());
        $filename = $this->formatFileName($this->options['filename'] ?? $file->getClientOriginalName());

        // If directory doesn't exist, create it
        if (! is_dir(public_path($path))) {
            mkdir(public_path($path), 0777, true);
        }

        // New, save file
        $file = $file->move(public_path($path), $filename);

        return '/'.rtrim(ltrim($path, '/'), '/').'/'.$filename;
    }

    /**
     * Get path
     */
    public function getPath(): string
    {
        return $this->options['path'] ?? 'uploads';
    }

    /**
     * Delete file by path
     */
    public function deleteFileByPath(string $pathRelativeToPublic)
    {
        // Check is a filename
        $parts = explode('/', ltrim($pathRelativeToPublic, '/'));
        $isAFileName = count($parts) > 0 && str_contains(end($parts), '.');

        if ($isAFileName) {
            $oldValue = WRLAHelper::forwardSlashPath(public_path().'/'.$this->getPath().'/'.$pathRelativeToPublic);

            // If is file and exists, delete it
            // dd($oldValue);
            if (file_exists($oldValue)) {
                unlink($oldValue);
            }
        }
    }

    /**
     * Get value
     */
    public function getValue(): string
    {
        // If starts with http, return as is
        if (str_starts_with((string) $this->getAttribute('value'), 'http')) {
            return $this->getAttribute('value');
            // Else, we apply a forward slash to the beginning of the path
        } else {
            // If no value is set, return empty string
            if (empty($this->getAttribute('value'))) {
                return '';
            }

            return '/'.ltrim(WRLAHelper::forwardSlashPath($this->getAttribute('value')), '/');
        }
    }

    /**
     * Format file name
     */
    public function formatFileName(string $name): string
    {
        // If find {id} in the name
        if (str_contains($name, '{id}')) {
            // Get the id of the model instance
            $id = $this->manageableModel->getModelInstance()->id;

            // If id is null, get the next id from the model
            if (empty($id)) {
                $id = $this->manageableModel->getModelInstance()->max('id') + 1;
            }

            // Replace {id} with the id of the model instance
            $name = str_replace('{id}', $id, $name);
        }

        // If find {time} in the name
        if (str_contains($name, '{time}')) {
            // Get the current time
            $time = time();

            // Replace {time} with the current time
            $name = str_replace('{time}', $time, $name);
        }

        return $name;
    }

    /**
     * Set unlink old image option if new image is set
     *
     * @return $this
     */
    public function unlinkOld(bool $unlink = true): static
    {
        $this->setOption('unlinkOld', $unlink);

        return $this;
    }

    /**
     * Set allow remove option
     *
     * @return $this
     */
    public function allowRemove(bool $allow = true): static
    {
        $this->setOption('allowRemove', $allow);

        return $this;
    }

    /**
     * Store filepath only
     */
    public function storeFilenameOnly(bool $storeFilenameOnly = true): static
    {
        $this->setOption('storeFilenameOnly', $storeFilenameOnly);

        return $this;
    }

    /**
     * Render the input field.
     */
    public function render(): mixed
    {
        $HTML = '';

        $path = ($this->getOption('storeFilenameOnly') == true) ? '/'.ltrim(rtrim($this->getPath(), '/'), '/').'/' : '';

        $HTML .= view(WRLAHelper::getViewPath('components.forms.input-file'), [
            'label' => $this->getLabel(),
            'options' => $this->options,
            'attributes' => new ComponentAttributeBag(array_merge($this->htmlAttributes, [
                'name' => $this->getAttribute('name'),
                'value' => $path.$this->getValue(),
                'type' => 'file',
            ])),
        ])->render();

        return $HTML;
    }
}
