<?php

namespace WebRegulate\LaravelAdministration\Classes\ManageableFields;

use Illuminate\Http\Request;
use WebRegulate\LaravelAdministration\Enums\PageType;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;
use WebRegulate\LaravelAdministration\Classes\ManageableModel;

/**
 * Class Input
 *
 * This class is responsible for generating input fields.
 */
class Image extends ManageableField
{
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
        $imageInstance = new static($column, $manageableModel?->getModelInstance()->{$column}, $manageableModel);
        $imageInstance->options([
            'path' => $path,
            'filename' => $filename,
            'defaultImage' => 'https://via.placeholder.com/150x150.jpg?text=No+Image+Available',
            'unlinkOld' => true,
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
        if ($request->hasFile($this->attributes['name'])) {
            // Get the file, path and filename
            $file = $request->file($this->attributes['name']);
            $path = $this->options['path'] ?? 'uploads';
            $path = WRLAHelper::forwardSlashPath($path);
            $filename = $this->options['filename'] ?? $file->getClientOriginalName();
            $filename = $this->formatImageName($filename);

            // If unlinkOld option set, and an image already exists with the old value, we delete it
            if ($this->option('unlinkOld') == true && !empty($this->attributes['value'])) {
                // First we make sure that the value looks like a path with a file name, so we don't accidentally delete something else
                $parts = explode('/', $this->attributes['value']);
                $isAFileName = count($parts) > 1 && strpos(end($parts), '.') !== false;

                if($isAFileName) {
                    $oldValue = WRLAHelper::forwardSlashPath(public_path($this->attributes['value']));

                    // Check that the mime type is one of the validation values
                    // Get mimes: part of validation
                    $validationRules = explode('|', $this->validationRules);
                    $mimes = array_filter($validationRules, function($rule) {
                        return strpos($rule, 'mimes:') === 0;
                    });
                    
                    // Get the mime types from the mimes: part of validation
                    $mimes = explode(',', explode(':', end($mimes))[1]);
                    
                    // Get the mime type of the file (remove the image/ part)
                    $fileMimeType = str_replace('image/', '', $file->getMimeType());
                    
                    // If the old value exists and the file mime type is in the validation rules, we delete the old value
                    if (file_exists($oldValue) && in_array($fileMimeType, $mimes)) {
                        unlink($oldValue);
                    }
                }
            }

            // Move the file to the path and fix the value that we apply to the column
            $file->move(public_path($path), $filename);
            $value = rtrim(ltrim($path, '/'), '/') . '/' . $filename;
            
            return $value;
        }

        return null;
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
                return $this->option('defaultImage');
            }
            
            return '/'.WRLAHelper::forwardSlashPath($this->attributes['value']);
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
    public function defaultImage(string $path = true): self
    {
        $this->option('defaultImage', $path);
        return $this;
    }

    /**
     * Set unlink old image option if new image is set
     * 
     * @param bool $unlink
     * @return $this
     */
    public function unlinkOld(bool $unlink): self
    {
        $this->option('unlinkOld', $unlink);
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
