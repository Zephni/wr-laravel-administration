<?php

namespace WebRegulate\LaravelAdministration\Classes\FormComponents;

use WebRegulate\LaravelAdministration\Enums\UpsertType;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;

/**
 * Class Input
 *
 * This class is responsible for generating input fields.
 */
class Text extends FormComponent
{
    /**
     * Render the input field.
     *
     * @param UpsertType $upsertType
     * @return mixed
     */
    public function render(UpsertType $upsertType): mixed
    {
        return view(WRLAHelper::getViewPath('components.forms.input-text'), [
            'name' => $this->attributes['name'],
            'label' => str(str_replace('_', ' ', $this->attributes['name']))->title(),
            'value' => $this->attributes['value'],
            'type' => $this->attributes['type'] ?? 'text',
            'attr' => collect($this->attributes)
                ->put('wire:model', 'formFields.' . $this->attributes['name'])
                ->forget(['name', 'value', 'type'])
                ->toArray(),
        ])->render();
    }
}
