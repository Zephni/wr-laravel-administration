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
     * @param string|callable $bladeCode callable passes the field instance as the first argument
     * @param array $data
     * @return static
     */
    public static function make(?ManageableModel $manageableModel, ?string $column, string|callable $bladeCode, array $data = []): static
    {
        $manageableField = new static($column, $manageableModel?->getModelInstance()->{$column}, $manageableModel);
        $manageableField->options['bladeCode'] = $bladeCode;
        $manageableField->options['data'] = $data;
        return $manageableField;
    }

    public function render(): mixed
    {
        $bladeCode = is_string($this->options['bladeCode'])
            ? $this->options['bladeCode']
            : call_user_func($this->options['bladeCode'], $this);

        return Blade::render($bladeCode, $this->options['data']);
    }
}
