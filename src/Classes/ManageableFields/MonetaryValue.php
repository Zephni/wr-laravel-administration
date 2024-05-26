<?php

namespace WebRegulate\LaravelAdministration\Classes\ManageableFields;

use Illuminate\Http\Request;
use Illuminate\View\ComponentAttributeBag;
use WebRegulate\LaravelAdministration\Enums\PageType;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;
use WebRegulate\LaravelAdministration\Classes\ManageableModel;

class MonetaryValue extends ManageableField
{
    /**
     * Make method (can be used in any class that extends FormComponent).
     *
     * @param ?ManageableModel $manageableModel
     * @param ?mixed $column
     * @param string $currencySymbol
     * @param int $currencyDecimalMultiplier
     * @param int $decimalPlaces
     * @return static
     */
    public static function make(?ManageableModel $manageableModel = null, ?string $column = null, string $currencySymbol = '£', int $currencyDecimalMultiplier = 100, $decimalPlaces = 2): static
    {
        $monetaryValueInstance = new static($column, $manageableModel?->getModelInstance()->{$column}, $manageableModel);
        $monetaryValueInstance->setOptions([
            'currencySymbol' => $currencySymbol,
            'currencyDecimalMultiplier' => $currencyDecimalMultiplier,
            'decimalPlaces' => $decimalPlaces,
        ]);

        // Add default validation rules
        $monetaryValueInstance->validation('numeric|min:0|decimal:0,' . $decimalPlaces);

        return $monetaryValueInstance;
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
        // Multiply by the currency decimal multiplier
        $value = $request->input($this->attributes['name']) * $this->options['currencyDecimalMultiplier'];

        // Make sure is integer
        $value = (int) $value;

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
        $actualValue = old($this->attributes['name'], $this->getValue());
        $value = number_format($actualValue / $this->options['currencyDecimalMultiplier'], $this->options['decimalPlaces']);

        return view(WRLAHelper::getViewPath('components.forms.input-monetary-value'), [
            'label' => $this->getLabel(),
            'options' => $this->options,
            'attributes' => new ComponentAttributeBag(array_merge($this->attributes, [
                'name' => $this->attributes['name'],
                'value' => $value,
                'type' => 'number',
            ])),

        ])->render();
    }
}
