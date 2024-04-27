<?php

namespace WebRegulate\LaravelAdministration\Classes\FormComponents;

use WebRegulate\LaravelAdministration\Enums\PageType;
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
     * @param PageType $upsertType
     * @return mixed
     */
    public function render(PageType $upsertType): mixed
    {
        return view(WRLAHelper::getViewPath('components.forms.input-text'), [
            'label' => $this->attributes['name'],
            'name' => $this->attributes['name'],
            'value' => $this->attributes['value'],
            'type' => 'text',
            'attr' => collect($this->attributes)
                ->forget(['name', 'value', 'type'])
                ->toArray(),
        ])->render();
    }
}
