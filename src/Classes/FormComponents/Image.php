<?php

namespace WebRegulate\LaravelAdministration\Classes\FormComponents;

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
        ]);
        return $imageInstance;
    }

    /**
     * Setup the options for the image field.
     *
     * @param string $path
     * @param ?string $filename
     * @return $this
     */
    public function setup(string $path = null, ?string $filename = null): self
    {
        $this->options['path'] = $path;
        $this->options['filename'] = $filename;
        return $this;
    }

    /**
     * Upload the image from request and apply the value.
     *
     * @param Request $request
     * @param mixed $value
     * @return mixed
     */
    public function applyValue(Request $request, mixed $value): mixed
    {

        dd($request->hasFile($this->attributes['name'], $request->file($this->attributes['name'])));
        if ($request->hasFile($this->attributes['name'])) {
            $file = $request->file($this->attributes['name']);
            $path = $this->options['path'] ?? 'uploads';
            $filename = $this->options['filename'] ?? $file->getClientOriginalName();
            $file->move(public_path($path), $filename);
            $value = $path . '/' . $filename;
        }

        return $value;
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
            'value' => '',
            'type' => 'file',
            'options' => $this->options,
            'attr' => collect($this->attributes)
                ->forget(['name', 'value', 'type'])
                ->toArray(),
        ])->render();

        return $HTML;
    }
}
