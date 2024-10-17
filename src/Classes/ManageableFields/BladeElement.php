<?php
namespace WebRegulate\LaravelAdministration\Classes\ManageableFields;

use Illuminate\Support\Facades\Blade;
use WebRegulate\LaravelAdministration\Traits\ManageableField;
use WebRegulate\LaravelAdministration\Classes\ManageableModel;

class BladeElement
{
    use ManageableField;

    /**
     * Make method (can be used in any class that extends FormComponent).
     *
     * @param ?ManageableModel $manageableModel
     * @param ?mixed $column
     * @param string $bladeCode
     * @param array $data
     * @return static
     */
    public static function make(?ManageableModel $manageableModel = null, ?string $column = null, string $bladeCode, array $data = []): static
    {
        $manageableField = new static($column, $manageableModel?->getModelInstance()->{$column}, $manageableModel);
        $manageableField->options['bladeCode'] = $bladeCode;
        $manageableField->options['data'] = $data;
        return $manageableField;
    }
    
    public function render(): mixed
    {
        return Blade::render($this->options['bladeCode'], $this->options['data']);
    }
}