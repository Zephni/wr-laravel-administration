<?php

namespace WebRegulate\LaravelAdministration\Classes\FormComponents;

use WebRegulate\LaravelAdministration\Enums\PageType;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;

/**
 * Class Input
 *
 * This class is responsible for generating input fields.
 */
class Textarea extends FormComponent
{
    /**
     * Render the input field.
     *
     * @param PageType $upsertType
     * @return mixed
     */
    public function render(PageType $upsertType): mixed
    {
        return view(WRLAHelper::getViewPath('components.forms.textarea'), [
            'name' => $this->attributes['name'],
            'label' => $this->getLabel(),
            'value' => $this->attributes['value'],
            'attr' => collect($this->attributes)
                ->forget(['name', 'value'])
                ->toArray(),
        ])->render();
    }
}
