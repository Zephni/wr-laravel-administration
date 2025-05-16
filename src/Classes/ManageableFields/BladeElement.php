<?php

namespace WebRegulate\LaravelAdministration\Classes\ManageableFields;

use Exception;
use Illuminate\Support\Facades\Blade;
use WebRegulate\LaravelAdministration\Classes\ManageableModel;
use WebRegulate\LaravelAdministration\Traits\ManageableField;

class BladeElement
{
    use ManageableField;

    /**
     * Make method (can be used in any class that extends FormComponent).
     *
     * @param  ?mixed  $column
     * @param  string|callable  $bladeCode  callable passes the field instance as the first argument. NOTE! You must use {{ \$variable }} backslashes to escape the variable in the blade code.
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

        try {
            return Blade::render($bladeCode, $this->options['data']);
        } catch (Exception) {
            // If blade code not valid, try and remove {{ }} from the code
            $sanitizedBladeCode = preg_replace('/{{.*?}}/', '', (string) $bladeCode);

            return Blade::render($sanitizedBladeCode, $this->options['data']);
        }
    }

    /**
     * Set data
     */
    public function setData(array $data): static
    {
        $this->options['data'] = $data;

        return $this;
    }
}
