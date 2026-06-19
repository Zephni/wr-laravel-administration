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

/**
 * Base manageable field for file uploads stored on a Laravel filesystem disk.
 *
 * This class is the canonical home for the upload lifecycle (resolve path,
 * format the stored filename, write the file, optionally unlink the old one,
 * and render the input). Subclasses (eg. {@see Image}, {@see ImageCroppable})
 * specialise behaviour via the following hooks rather than re-implementing the
 * full pipeline:
 *
 *   - {@see defaultOptions()}      Declare the default options array.
 *   - {@see emptyModelUrl()}       URL returned by {@see getModelURL()} when the
 *                                  model column is empty.
 *   - {@see processUploadedFile()} Performs the actual disk write inside
 *                                  {@see uploadFile()}. Override to apply
 *                                  transformations (eg. image manipulation).
 *   - {@see viewPath()}            Blade view used by {@see render()}.
 *   - {@see renderInputValue()}    String written to the file input's value
 *                                  attribute.
 *   - {@see additionalViewData()}  Extra keys merged into the render view data.
 */
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
            throw new Exception('Path is required for '.static::class.' '.$column.' field');
        }

        $instance = new static($column, $manageableModel?->model()->{$column}, $manageableModel);

        // Merge subclass-provided defaults with the caller-provided values.
        $instance->setOptions(array_merge(static::defaultOptions(), [
            'fileSystem' => $fileSystem,
            'path' => $path,
            'filename' => $filename,
        ]));

        return $instance;
    }

    /**
     * Default options for this field. Subclasses should override and merge with
     * {@code parent::defaultOptions()} to add their own keys.
     */
    protected static function defaultOptions(): array
    {
        return [
            'fileSystem' => 'public',
            'path' => '',
            'filename' => null,
            'unlinkOld' => true,
            'allowRemove' => true,
            'storeFilenameOnly' => true,
            'class' => '',
        ];
    }

    /**
     * This field handles file uploads, so its raw value cannot be transferred
     * to a new record (eg. when duplicating).
     */
    public function isFileUploadField(): bool
    {
        return true;
    }

    /**
     * Get manageable model's absolute URL from manageable field
     */
    public static function getModelURL(ManageableModel $manageableModel, string $column): string
    {
        $value = $manageableModel->model()->{$column};

        if (empty($value)) {
            return static::emptyModelUrl();
        }

        $manageableField = $manageableModel->getManageableFieldByName($column);
        $urlPath = $manageableField->getFileSystem()->url(
            trim($manageableField->getPathOnly().'/'.$manageableModel->model()->{$column}, '/')
        );

        return $urlPath;
    }

    /**
     * URL returned by {@see getModelURL()} when the underlying column is empty.
     * Override in subclasses to provide a placeholder (eg. a no-image asset).
     */
    protected static function emptyModelUrl(): string
    {
        return '';
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
        if ($request->hasFile($this->getName())) {
            $value = $this->uploadFile($request->file($this->getName()));

            // Only delete old file if it is different from the newly stored file
            // (avoids removing a file that was just overwritten with the same name).
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
            return ltrim(end($parts), '/');
        }

        return null;
    }

    /**
     * Get file system
     */
    public function getFileSystem(): Filesystem
    {
        return Storage::disk($this->getOption('fileSystem'));
    }

    /**
     * Upload file. Resolves the destination directory and filename, ensures the
     * extension is present, then delegates the actual write to
     * {@see processUploadedFile()} so subclasses can apply processing.
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

        $this->processUploadedFile($file, $path, $filename);

        return ltrim(rtrim(ltrim($path, '/'), '/').'/'.$filename, '/');
    }

    /**
     * Hook: perform the actual disk write of the uploaded file.
     *
     * The default implementation streams the file straight to disk via
     * {@code putFileAs}. Override in subclasses (eg. {@see Image}) to apply a
     * processing pipeline (image manipulation, transcoding, etc.) before
     * writing to the disk.
     *
     * @param  string  $path  The destination directory relative to the disk root.
     * @param  string  $filename  The final filename (with extension).
     */
    protected function processUploadedFile(UploadedFile $file, string $path, string $filename): void
    {
        $stored = $this->getFileSystem()->putFileAs($path, $file, $filename);
        if ($stored === false) {
            throw new \RuntimeException('Failed to store uploaded file on disk: '.$this->getOption('fileSystem'));
        }
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
        }

        // If no value is set, return empty string
        if (empty($this->getAttribute('value'))) {
            return '';
        }

        // Else, apply a forward slash to the beginning of the path
        return '/'.ltrim(WRLAHelper::forwardSlashPath($this->getAttribute('value')), '/');
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
            return $name($this->manageableModel->model(), $originalFileName);
        }

        // If find {id} in the name
        if (str_contains($name, '{id}')) {
            // Get the id of the model instance
            $id = $this->manageableModel->model()->id;

            // If id is null, get the next id from the model
            if (empty($id)) {
                $id = $this->manageableModel->model()->max('id') + 1;
            }

            // Replace {id} with the id of the model instance
            $name = str_replace('{id}', $id, $name);
        }

        // If find {time} in the name
        if (str_contains($name, '{time}')) {
            // Replace {time} with the current time
            $name = str_replace('{time}', time(), $name);
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
     * Hook: blade view used by {@see render()}.
     */
    protected function viewPath(): string
    {
        return WRLAHelper::getViewPath('components.forms.input-file');
    }

    /**
     * Hook: value rendered into the [type=file] input's `value` attribute.
     */
    protected function renderInputValue(): string
    {
        $path = ($this->getOption('storeFilenameOnly') == true)
            ? '/'.ltrim(rtrim($this->getPathOnly(), '/'), '/').'/'
            : '';

        return $path.$this->getValue();
    }

    /**
     * Hook: extra data keys merged into the render view payload. Subclasses
     * should override to rename / add view variables consumed by their blade
     * template (eg. Image uses `fileSystemImageExists`).
     */
    protected function additionalViewData(bool $fileExists): array
    {
        return ['fileSystemFileExists' => $fileExists];
    }

    /**
     * Probe the disk to determine whether the current value resolves to an
     * existing file (or is an external http(s) URL we treat as existing).
     */
    protected function checkFileExists(): bool
    {
        try {
            if (str($this->getValue())->startsWith('http')) {
                return true;
            }

            $file = $this->getFileSystem()->get($this->getDiskStoragePath());

            return !empty($file);
        } catch (Exception) {
            return false;
        }
    }

    /**
     * Render the input field.
     */
    public function render(): mixed
    {
        $fileExists = $this->checkFileExists();

        $data = array_merge([
            'label' => $this->getLabel(),
            'options' => $this->options,
            'fileSystem' => $this->getFileSystem(),
            'publicUrl' => $this->getURLPath(),
            'publicUrlWithoutDomain' => $this->getURLPathWithoutDomain(),
            'attributes' => new ComponentAttributeBag(array_merge($this->htmlAttributes, [
                'name' => $this->getName(),
                'value' => $this->renderInputValue(),
                'type' => 'file',
            ])),
        ], $this->additionalViewData($fileExists));

        return view($this->viewPath(), $data)->render();
    }
}
