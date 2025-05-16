<?php

namespace WebRegulate\LaravelAdministration\Classes\ManageableFields;

use Illuminate\Http\Request;
use Illuminate\View\ComponentAttributeBag;
use WebRegulate\LaravelAdministration\Classes\ManageableModel;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;
use WebRegulate\LaravelAdministration\Traits\ManageableField;

class MonetaryValue
{
    use ManageableField;

    /**
     * Make method (can be used in any class that extends FormComponent).
     *
     * @param  ?mixed  $column
     * @param  int  $decimalPlaces
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
        $monetaryValueInstance->validation('numeric|min:0|decimal:0,'.$decimalPlaces);

        return $monetaryValueInstance;
    }

    /**
     * Upload the image from request and apply the value.
     */
    public function applySubmittedValue(Request $request, mixed $value): mixed
    {
        // Multiply by the currency decimal multiplier
        $value = $request->input($this->getAttribute('name')) * $this->options['currencyDecimalMultiplier'];

        // Make sure is integer
        $value = (int) $value;

        return $value;
    }

    /**
     * Render the input field.
     */
    public function render(): mixed
    {
        $actualValue = old($this->getAttribute('name'), $this->getValue());
        $value = number_format($actualValue / $this->options['currencyDecimalMultiplier'], $this->options['decimalPlaces']);

        return view(WRLAHelper::getViewPath('components.forms.input-monetary-value'), [
            'label' => $this->getLabel(),
            'options' => $this->options,
            'attributes' => new ComponentAttributeBag(array_merge($this->htmlAttributes, [
                'name' => $this->getAttribute('name'),
                'value' => $value,
                'type' => 'number',
            ])),

        ])->render();
    }
}
