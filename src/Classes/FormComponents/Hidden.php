<?php

namespace WebRegulate\LaravelAdministration\Classes\FormComponents;

use WebRegulate\LaravelAdministration\Classes\WRLAHelper;

/**
 * Class Input
 *
 * This class is responsible for generating input fields.
 */
class Hidden extends FormComponent
{
    /**
     * Render the input field.
     *
     * @param mixed $inject
     * @return mixed
     */
    public function render($inject = null): mixed
    {
        return parent::render(view(WRLAHelper::getViewPath('components.forms.input-text'), [
            'label' => $this->attributes['name'],
            'name' => $this->attributes['name'],
            'value' => $this->attributes['value'],
            'type' => 'text',
            'attr' => collect($this->attributes)
                ->put('wire:model', 'formFields.' . $this->attributes['name'])
                ->forget(['name', 'value', 'type'])
                ->toArray(),
        ])->render());
    }
}
