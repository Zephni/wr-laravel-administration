<?php

namespace WebRegulate\LaravelAdministration\Classes\ManageableFields;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use WebRegulate\LaravelAdministration\Enums\PageType;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;
use WebRegulate\LaravelAdministration\Classes\ManageableModel;

class Image extends ManageableField
{
    /**
     * Manipulate image function, set with manipulateImage method on creation
     *
     * @var callable
     */
    public $manipulateImageFunction = null;

    /**
     * Make method (can be used in any class that extends FormComponent).
     *
     * @param ?ManageableModel $manageableModel
     * @param ?mixed $column
     * @param ?string $path
     * @param ?string $filename
     * @return self
     */
    public static function make(?ManageableModel $manageableModel = null, ?string $column = null, ?string $path = null, ?string $filename = null): self
    {
        // If path is empty, we throw an exception
        if (empty($path)) {
            throw new \Exception('Path is required for Image '.$column.' field');
        }

        $imageInstance = new static($column, $manageableModel?->getModelInstance()->{$column}, $manageableModel);
        $imageInstance->options([
            'path' => $path,
            'filename' => $filename,
            'defaultImage' => 'https://via.placeholder.com/120x120.jpg?text=No+Image+Available',
            'unlinkOld' => true,
            'allowRemove' => true,
            'aspect' => null,
            'rounded' => false,
        ]);
        return $imageInstance;
    }

    /**
     * Upload the image from request and apply the value.
     *
     * @param Request $request
     * @param mixed $value
     * @return mixed
     */
    public function applySubmittedValue(Request $request, mixed $value): mixed
    {
        // Get current value of image
        $currentImage = $this->attributes['value'];

        // If value is equal to the special constant WRLA_KEY_REMOVE, we delete the image
        if ($value === ManageableField::WRLA_KEY_REMOVE) {
            $this->deleteImageByPath($currentImage);
            return null;
        }

        if ($request->hasFile($this->attributes['name'])) {
            $value = $this->uploadImage($request->file($this->attributes['name']));

            // If unlinkOld option set, and an image already exists with the old value, we delete it
            if ($this->getOption('unlinkOld') == true && !empty($currentImage)) {
                $this->deleteImageByPath($currentImage);
            }
            
            return $value;
        }

        return null;
    }

    /**
     * Upload image
     * 
     * @param UploadedFile $request
     * @return string
     */
    public function uploadImage(UploadedFile $file): string
    {
        // Get path and filename
        $path = WRLAHelper::forwardSlashPath($this->getPath());
        $filename = $this->formatImageName($this->options['filename'] ?? $file->getClientOriginalName());

        // If directory doesn't exist, create it
        if (!is_dir(public_path($path))) {
            mkdir(public_path($path), 0777, true);
        }

        // New, we now use Intervention
        $imageManager = new ImageManager(new Driver());
        $image = $imageManager->read($file);
        $manipulateImageFunction = $this->manipulateImageFunction;
        $image = $manipulateImageFunction($image);
        $image->save(public_path($path) . '/' . $filename);

        return rtrim(ltrim($path, '/'), '/') . '/' . $filename;
    }

    /**
     * Manipulate image
     * 
     * @param callable $callback
     * @return $this
     */
    public function manipulateImage(callable $callback): self
    {
        $this->manipulateImageFunction = $callback;
        return $this;
    }


    /**
     * Get path
     * 
     * @return string
     */
    public function getPath(): string
    {
        return $this->options['path'] ?? 'uploads';
    }

    /**
     * Delete image file
     * 
     * @param string $pathRelativeToPublic
     */
    public function deleteImageByPath(string $pathRelativeToPublic)
    {
        // First we check that there is atleast one path item in the value, and that the last item is a filename
        $parts = explode('/', ltrim($pathRelativeToPublic, '/'));
        $hasAteastOnePath = count($parts) > 1;
        $isAFileName = count($parts) > 1 && strpos(end($parts), '.') !== false;

        if($hasAteastOnePath && $isAFileName) {
            $oldValue = WRLAHelper::forwardSlashPath(public_path($pathRelativeToPublic));
            $testPath = WRLAHelper::forwardSlashPath($this->getPath());

            // Check whether the value includes the option: path, if it doesn't then we do not delete the file for safety
            $includesPath = strpos($oldValue, $testPath) !== false;
            
            // If the new value includes the path and the old file exists, we delete it
            if ($includesPath && file_exists($oldValue)) {
                unlink($oldValue);
            }
        }
    }

    /**
     * Get value
     * 
     * @return string
     */
    public function getValue(): string
    {
        // If starts with http, return as is
        if (strpos($this->attributes['value'], 'http') === 0) {
            return $this->attributes['value'];
        // Else, we apply a forward slash to the beginning of the path
        } else {
            // If no value is set, return default image
            if (empty($this->attributes['value'])) {
                return $this->getOption('defaultImage');
            }
            
            return '/'.ltrim(WRLAHelper::forwardSlashPath($this->attributes['value']), '/');
        }
    }

    /**
     * Format image name
     * 
     * @param string $name
     * @return string
     */
    public function formatImageName(string $name): string
    {
        // If find {id} in the name
        if (strpos($name, '{id}') !== false) {
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
        if (strpos($name, '{time}') !== false) {
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
     * @param string $path
     * @return $this
     */
    public function defaultImage(string $path): self
    {
        $this->setOption('defaultImage', $path);
        return $this;
    }

    /**
     * Set unlink old image option if new image is set
     * 
     * @param bool $unlink
     * @return $this
     */
    public function unlinkOld(bool $unlink = true): self
    {
        $this->setOption('unlinkOld', $unlink);
        return $this;
    }

    /**
     * Set allow remove option
     * 
     * @param bool $allow
     * @return $this
     */
    public function allowRemove(bool $allow = true): self
    {
        $this->setOption('allowRemove', $allow);
        return $this;
    }

    /**
     * Set aspect ratio using 1:1 format
     * 
     * @param null|string $aspect
     */
    public function aspect(?string $aspect = null): self
    {
        $this->setOption('aspect', $aspect);
        return $this;
    }

    /**
     * Set rounded image, with false, null, or 'none' for none, true for 'full', or any tailwind string rounded available value
     * 
     * @param null|bool|string $rounded
     */
    public function rounded(null|bool|string $rounded = true): self
    {
        $this->setOption('rounded', $rounded);
        return $this;
    }

    /**
     * Render the input field.
     *
     * @param PageType $upsertType
     * @return mixed
     */
    public function render(PageType $upsertType): mixed
    {
        $HTML = '';

        $HTML .= view(WRLAHelper::getViewPath('components.forms.input-image'), [
            'name' => $this->attributes['name'],
            'label' => $this->getLabel(),
            'value' => $this->getValue(),
            'type' => 'file',
            'options' => $this->options,
            'attr' => collect($this->attributes)
                ->forget(['name', 'value', 'type'])
                ->toArray(),
        ])->render();

        return $HTML;
    }
}
