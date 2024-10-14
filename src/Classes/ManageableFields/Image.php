<?php

namespace WebRegulate\LaravelAdministration\Classes\ManageableFields;

use WebRegulate\LaravelAdministration\Traits\ManageableField;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\View\ComponentAttributeBag;
use WebRegulate\LaravelAdministration\Enums\PageType;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;
use WebRegulate\LaravelAdministration\Classes\ManageableModel;

class Image
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
     * @param ?ManageableModel $manageableModel
     * @param ?mixed $column
     * @param ?string $path
     * @param ?string $filename
     * @return static
     */
    public static function make(?ManageableModel $manageableModel = null, ?string $column = null, ?string $path = null, ?string $filename = null): static
    {
        // If path is empty, we throw an exception
        if (empty($path)) {
            throw new \Exception('Path is required for Image '.$column.' field');
        }

        $imageInstance = new static($column, $manageableModel?->getModelInstance()->{$column}, $manageableModel);
        $imageInstance->setOptions([
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
     * Upload the image from request and apply the value.
     *
     * @param Request $request
     * @param mixed $value
     * @return mixed
     */
    public function applySubmittedValue(Request $request, mixed $value): mixed
    {
        // Get current value of image
        $currentImage = $this->getAttribute('value');

        // If value is equal to the special constant WRLA_KEY_REMOVE, we delete the image
        if ($value === ManageableField::WRLA_KEY_REMOVE) {
            $this->deleteImageByPath($currentImage);
            return null;
        }

        if ($request->hasFile($this->getAttribute('name'))) {
            $value = $this->uploadImage($request->file($this->getAttribute('name')));

            // If unlinkOld option set, and an image already exists with the old value, we delete it
            if ($this->getOption('unlinkOld') == true && !empty($currentImage)) {
                $this->deleteImageByPath($currentImage);
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

        return '/'.rtrim(ltrim($path, '/'), '/') . '/' . $filename;
    }

    /**
     * Manipulate image
     *
     * @param callable $callback
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
     * @param int $width
     * @param string $aspect (4/3, 16/9, etc)
     * @param string $position (center, top, bottom, left, right, top-left, top-right, bottom-left, bottom-right)
     * @param int $quality
     * @return $this
     */
    public function coverFitAspect(int $width, string $aspect = '4/3', string $position = 'center', int $quality = 100): static
    {
        // Set aspect display for image
        $this->aspect($aspect);

        // Get aspect parts
        $aspectParts = explode(':', $aspect);

        return $this->manipulateImage(function($image) use ($width, $aspectParts, $position, $quality) {
            $height = $width / $aspectParts[0] * $aspectParts[1];
            $image->cover($width, $height, $position);
            $image->toJpeg($quality);
            return $image;
        });
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
        // Check is a filename
        $parts = explode('/', ltrim($pathRelativeToPublic, '/'));
        $isAFileName = count($parts) > 0 && strpos(end($parts), '.') !== false;

        if($isAFileName) {
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
     *
     * @return string
     */
    public function getValue(): string
    {
        // If starts with http, return as is
        if (strpos($this->getAttribute('value'), 'http') === 0) {
            return $this->getAttribute('value');
        // Else, we apply a forward slash to the beginning of the path
        } else {
            // If no value is set, return default image
            if (empty($this->getAttribute('value'))) {
                return $this->getOption('defaultImage');
            }

            return '/'.ltrim(WRLAHelper::forwardSlashPath($this->getAttribute('value')), '/');
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
    public function defaultImage(string $path): static
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
    public function unlinkOld(bool $unlink = true): static
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
    public function allowRemove(bool $allow = true): static
    {
        $this->setOption('allowRemove', $allow);
        return $this;
    }

    /**
     * Set aspect ratio using 1:1 format
     *
     * @param null|string $aspect
     */
    public function aspect(?string $aspect = null): static
    {
        $this->setOption('aspect', $aspect);
        return $this;
    }

    /**
     * Store filepath only
     *
     * @param bool $storeFilenameOnly
     */
    public function storeFilenameOnly(bool $storeFilenameOnly = true): static
    {
        $this->setOption('storeFilenameOnly', $storeFilenameOnly);
        return $this;
    }

    /**
     * Set rounded image, with false, null, or 'none' for none, true for 'full', or any tailwind string rounded available value
     *
     * @param null|bool|string $rounded
     */
    public function rounded(null|bool|string $rounded = true): static
    {
        if($rounded == false) {
            $this->options['class'] = str_replace('rounded-full', '', $this->options['class']);
            return $this;
        }

        if($rounded === true || $rounded === null) $rounded = 'full';

        $this->options['class'] .= " rounded-$rounded";

        return $this;
    }



    /**
     * Render the input field.
     *
     * @return mixed
     */
    public function render(): mixed
    {
        $HTML = '';

        $path = ($this->getOption('storeFilenameOnly') == true) ? '/'.ltrim(rtrim($this->getPath(), '/'), '/').'/' : '';

        $HTML .= view(WRLAHelper::getViewPath('components.forms.input-image'), [
            'label' => $this->getLabel(),
            'options' => $this->options,
            'attributes' => new ComponentAttributeBag(array_merge($this->htmlAttributes, [
                'name' => $this->getAttribute('name'),
                'value' => $path.$this->getValue(),
                'type' => 'file'
            ])),
        ])->render();

        return $HTML;
    }
}
