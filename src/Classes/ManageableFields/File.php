<?php

namespace WebRegulate\LaravelAdministration\Classes\ManageableFields;

use Exception;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\ComponentAttributeBag;
use WebRegulate\LaravelAdministration\Classes\ManageableModel;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;
use WebRegulate\LaravelAdministration\Traits\ManageableField;

class File
{
    use ManageableField;

    /**
     * Make method (can be used in any class that extends FormComponent).
     *
     * @param null|string|callable $filename  If string, can contain {id} and {time} placeholders. If callable, takes manageableModel, originalFilename params and must return the filename to store.
     */
    public static function make(?ManageableModel $manageableModel = null, ?string $column = null, ?string $path = null, null|string|callable $filename = null, string $fileSystem = 'public'): static
    {
        // If path is empty, we throw an exception
        if (empty($path)) {
            throw new \Exception('Path is required for File '.$column.' field');
        }

        $imageInstance = new static($column, $manageableModel?->getModelInstance()->{$column}, $manageableModel);

        $imageInstance->setOptions([
            'fileSystem' => $fileSystem,
            'path' => $path,
            'filename' => $filename,
            'unlinkOld' => true,
            'allowRemove' => true,
            'storeFilenameOnly' => true,
            'class' => '',
        ]);

        return $imageInstance;
    }

    /**
     * Get manageable model's absolute URL from manageable field
     */
    public static function getModelURL(ManageableModel $manageableModel, string $column): string
    {
        $value = $manageableModel->getModelInstance()->{$column};

        if (empty($value)) {
            return '';
        }

        $manageableField = $manageableModel->getManageableFieldByName($column);
        $urlPath = $manageableField->getFileSystem()->url(
            trim($manageableField->getPathOnly().'/'.$manageableModel->getModelInstance()->{$column}, '/')
        );

        return $urlPath;
    }

    /**
     * Upload the file from request and apply the value.
     */
    public function applySubmittedValue(Request $request, mixed $value): mixed
    {
        // Get current value of file
        $currentFile = $this->getAttribute('value');

        if ($value === WRLAHelper::WRLA_KEY_REMOVE) {
            $this->deleteFile($currentFile);
            return null;
        }

        // If file is uploaded, we upload the file and return the path to store in the database
        if ($request->hasFile($this->getAttribute('name'))) {
            $value = $this->uploadFile($request->file($this->getAttribute('name')));

            // Only delete old file if it is different from the newly stored file
            if ($this->getOption('unlinkOld') == true && !empty($currentFile)) {
                $newStoredPath = ltrim(WRLAHelper::forwardSlashPath($value), '/');
                $oldStoredPath = ltrim(WRLAHelper::forwardSlashPath(
                    $this->getOption('storeFilenameOnly') == true
                        ? $this->getPathOnly().'/'.$currentFile
                        : $currentFile
                ), '/');

                if ($newStoredPath !== $oldStoredPath) {
                    $this->deleteFile($currentFile);
                }
            }

            // If storeFilenameOnly is false, store the entire filepath/filename.ext
            if ($this->getOption('storeFilenameOnly') == false) {
                return $value;
            }

            // Otherwise, we store only the filename.ext
            $parts = explode('/', $value);
            return end($parts);
        }

        return null;
    }

    /**
     * Get file system
     */
    public function getFileSystem(): FileSystem
    {
        return Storage::disk($this->getOption('fileSystem'));
    }

    /**
     * Upload file
     *
     * @return string The path to the stored file relative to the file system
     */
    public function uploadFile(UploadedFile $file): string
    {
        $fileSystem = $this->getFileSystem();

        $path = WRLAHelper::forwardSlashPath($this->getPathOnly());
        $filename = $this->formatFileName($this->options['filename'], $file->getClientOriginalName());

        if (!$fileSystem->exists($path)) {
            $fileSystem->makeDirectory($path);
        }

        if (!str($filename)->contains('.')) {
            $filename .= '.'.$file->getClientOriginalExtension();
        }

        // Use same disk and validate write result
        $stored = $fileSystem->putFileAs($path, $file, $filename);
        if ($stored === false) {
            throw new \RuntimeException('Failed to store uploaded file on disk: '.$this->getOption('fileSystem'));
        }

        return ltrim(rtrim(ltrim($path, '/'), '/').'/'.$filename, '/');
    }

    /**
     * Get path
     */
    public function getPathOnly(): string
    {
        return ! empty($this->options['path']) ? $this->options['path'] : '';
    }

    /**
     * Delete file
     */
    public function deleteFile(string $filePathRelativeToFileSystem)
    {
        // Check is a filename
        $parts = explode('/', ltrim($filePathRelativeToFileSystem, '/'));
        $isAFileName = count($parts) > 0 && str_contains(end($parts), '.');

        if ($isAFileName) {
            $oldValue = WRLAHelper::forwardSlashPath($this->getPathOnly().'/'.$filePathRelativeToFileSystem);

            // Get file system
            $fileSystem = $this->getOption('fileSystem');

            // If file exists, delete it from file system
            Storage::disk($fileSystem)->delete($oldValue);
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
     * 
     * @param string|callable $name If string, can contain {id} and {time} placeholders. If callable, takes (manageableModel, originalFilename) and must return the filename to store.
     */
    public function formatFileName(null|string|callable $name, string $originalFileName): string
    {
        // If name is empty, use original filename
        if (empty($name)) {
            return $originalFileName;
        }

        // If callable, call the function to get the name
        if (is_callable($name)) {
            return $name($this->manageableModel->getModelInstance(), $originalFileName);
        }

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
     * Get disk storage path, returns the full path (including filename) relative to the filesystem
     */
    public function getDiskStoragePath(): string
    {
        $path = ($this->getOption('storeFilenameOnly') == true)
            ? '/'.$this->getPathOnly()
            : '';

        return str_replace('//', '/', $path.'/'.$this->getValue());
    }

    /**
     * Get absolute path, full path to the file (including filename) on the server
     */
    public function getAbsolutePath(): string
    {
        return $this->getFileSystem()->path($this->getDiskStoragePath());
    }

    /**
     * Get URL path, full URL to the file (including filename)
     */
    public function getURLPath(): string
    {
        if (str($this->getValue())->startsWith('http')) {
            return $this->getValue();
        }

        return $this->getFileSystem()->url($this->getDiskStoragePath());
    }

    /**
     * Get public URL path without domain
     */
    public function getURLPathWithoutDomain(): string
    {
        if (str($this->getValue())->startsWith('http')) {
            return $this->getValue();
        }

        $url = $this->getURLPath();

        return parse_url($url, PHP_URL_PATH) ?? '';
    }

    /**
     * Render the input field.
     */
    public function render(): mixed
    {
        $HTML = '';

        $fileSystemFileExists = true;
        try {
            if (! str($this->getValue())->startsWith('http')) {
                $file = $this->getFileSystem()->get($this->getDiskStoragePath());
                $fileSystemFileExists = !empty($file);
            }
        } catch (Exception) {
            $fileSystemFileExists = false;
        }

        $path = ($this->getOption('storeFilenameOnly') == true) ? '/'.ltrim(rtrim($this->getPathOnly(), '/'), '/').'/' : '';

        $HTML .= view(WRLAHelper::getViewPath('components.forms.input-file'), [
            'label' => $this->getLabel(),
            'options' => $this->options,
            'fileSystem' => $this->getFileSystem(),
            'publicUrl' => $this->getURLPath(),
            'publicUrlWithoutDomain' => $this->getURLPathWithoutDomain(),
            'fileSystemFileExists' => $fileSystemFileExists,
            'attributes' => new ComponentAttributeBag(array_merge($this->htmlAttributes, [
                'name' => $this->getAttribute('name'),
                'value' => $path.$this->getValue(),
                'type' => 'file',
            ])),
        ])->render();

        return $HTML;
    }
}
