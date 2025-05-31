<?php

namespace WebRegulate\LaravelAdministration\Classes\ManageableFields;

use Exception;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\ComponentAttributeBag;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use WebRegulate\LaravelAdministration\Classes\ManageableModel;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;
use WebRegulate\LaravelAdministration\Traits\ManageableField;

/**
 * Class ImageCroppable
 *
 * NOTE: For now you can only use 1 croppable image per manageable model form elements.
 */
class ImageCroppable
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
    public static function make(?ManageableModel $manageableModel = null, ?string $column = null, ?string $path = null, ?string $filename = null, string $fileSystem = 'public'): static
    {
        // If path is empty, we throw an exception
        if (empty($path)) {
            throw new \Exception('Path is required for Image '.$column.' field');
        }

        $imageInstance = new static($column, $manageableModel?->getModelInstance()->{$column}, $manageableModel);
        $imageInstance->setOptions([
            'fileSystem' => $fileSystem,
            'path' => $path,
            'filename' => $filename,
            'defaultImage' => WRLAHelper::getCurrentThemeData('no_image_src'),
            'unlinkOld' => true,
            'allowRemove' => true,
            'aspect' => null,
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
            return WRLAHelper::getCurrentThemeData('no_image_src') ?? '';
        }

        $manageableField = $manageableModel->getManageableFieldByName($column);
        $urlPath = $manageableField->getFileSystem()->url(
            trim($manageableField->getPathOnly().'/'.$manageableModel->getModelInstance()->{$column}, '/')
        );

        return $urlPath;
    }

    /**
     * Upload the image from request and apply the value.
     */
    public function applySubmittedValue(Request $request, mixed $value): mixed
    {
        // Get current value of image
        $currentImage = $this->getAttribute('value');

        // If value is equal to the special constant WRLA_KEY_REMOVE, we delete the image
        if ($value === WRLAHelper::WRLA_KEY_REMOVE) {
            $this->deleteImage($currentImage);

            return null;
        }

        if ($request->hasFile($this->getAttribute('name'))) {
            $value = $this->uploadImage($request->file($this->getAttribute('name')));

            // If unlinkOld option set, and an image already exists with the old value, we delete it
            if ($this->getOption('unlinkOld') == true && ! empty($currentImage)) {
                $this->deleteImage($currentImage);
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
     * Get file system
     */
    public function getFileSystem(): FileSystem
    {
        return Storage::disk($this->getOption('fileSystem'));
    }

    /**
     * Upload image
     *
     * @param  UploadedFile  $request
     */
    public function uploadImage(UploadedFile $file): string
    {
        // Get file system
        $fileSystem = $this->getFileSystem();

        // Get path and filename
        $path = WRLAHelper::forwardSlashPath($this->getPathOnly());
        $filename = $this->formatImageName($this->options['filename'] ?? $file->getClientOriginalName());

        // If directory doesn't exist, create it
        if (! $fileSystem->exists($path)) {
            $fileSystem->makeDirectory($path);
        }

        // New, we now use Intervention
        $imageManager = new ImageManager(new Driver);
        $image = $imageManager->read($file);

        if ($this->manipulateImageFunction !== null) {
            $manipulateImageFunction = $this->manipulateImageFunction;
            $image = $manipulateImageFunction($image);
        }

        // If no extension is provided in the filename, use the original file extension
        if (! str($filename)->contains('.')) {
            $filename .= '.'.$file->getClientOriginalExtension();
        }

        // Get file system
        $fileSystem = $this->getOption('fileSystem');

        // Store image
        Storage::disk($fileSystem)->put("$path/$filename", $image->encode());

        return ltrim(rtrim(ltrim($path, '/'), '/').'/'.$filename, '/');
    }

    /**
     * Manipulate image
     *
     * @return $this
     */
    public function manipulateImage(callable $callback): static
    {
        $this->manipulateImageFunction = $callback;

        return $this;
    }

    /**
     * Cover fit aspect ratio
     *
     * @param  string  $aspect  (4/3, 16/9, etc)
     * @param  string  $position  (center, top, bottom, left, right, top-left, top-right, bottom-left, bottom-right)
     * @return $this
     */
    public function coverFitAspect(int $width, string $aspect = '4/3', string $position = 'center', int $quality = 100): static
    {
        // Set aspect display for image
        $this->aspect($aspect);

        // Get aspect parts
        $aspectParts = explode('/', $aspect);

        return $this->manipulateImage(function ($image) use ($width, $aspectParts, $position, $quality) {
            $height = $width / $aspectParts[0] * $aspectParts[1];
            $image->cover($width, $height, $position);
            $image->toJpeg($quality);

            return $image;
        });
    }

    /**
     * Get path
     */
    public function getPathOnly(): string
    {
        return ! empty($this->options['path']) ? $this->options['path'] : '';
    }

    /**
     * Delete image file
     */
    public function deleteImage(string $filePathRelativeToFileSystem)
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
        // Get value attriubute
        $value = $this->getAttribute('value');

        // If starts with http, return as is
        if (str($value)->startsWith('http')) {
            return $value;
            // Else, we apply a forward slash to the beginning of the path
        } else {
            // If no value is set, return default image
            if (empty($value)) {
                return $this->getOption('defaultImage') ?? '';
            }

            return '/'.ltrim(WRLAHelper::forwardSlashPath($value), '/');
        }
    }

    /**
     * Format image name
     */
    public function formatImageName(string $name): string
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
     * Default if no image is set
     *
     * @return $this
     */
    public function defaultImage(?string $path): static
    {
        $this->setOption('defaultImage', $path);

        return $this;
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
     * Set aspect ratio using 1/1 format
     */
    public function aspect(?string $aspect = null): static
    {
        $this->setOption('aspect', $aspect);

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
     * Set rounded image, with false, null, or 'none' for none, true for 'full', or any tailwind string rounded available value
     */
    public function rounded(null|bool|string $rounded = true): static
    {
        if ($rounded == false) {
            $this->options['class'] = str_replace('rounded-full', '', $this->options['class']);

            return $this;
        }

        if ($rounded === true || $rounded === null) {
            $rounded = 'full';
        }

        $this->options['class'] .= " rounded-$rounded";

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

        return $path.$this->getValue();
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
        $fileSystemImageExists = true;
        try {
            if (! str($this->getValue())->startsWith('http')) {
                // $fileSystemImageExists = $this->getFileSystem()->exists($this->getDiskStoragePath());
            }
        } catch (Exception) {
            // $fileSystemImageExists = false;
        }

        $HTML = view(WRLAHelper::getViewPath('components.forms.input-image-croppable'), [
            'label' => $this->getLabel(),
            'options' => $this->options,
            'fileSystem' => $this->getFileSystem(),
            'publicUrl' => $this->getURLPath(),
            'publicUrlWithoutDomain' => $this->getURLPathWithoutDomain(),
            'fileSystemImageExists' => $fileSystemImageExists,
            'attributes' => new ComponentAttributeBag(array_merge($this->htmlAttributes, [
                'name' => $this->getAttribute('name'),
                'value' => $this->getDiskStoragePath(),
                'type' => 'file',
            ])),
        ])->render();

        return $HTML;
    }
}
