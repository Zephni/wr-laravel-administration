<?php

namespace WebRegulate\LaravelAdministration\Classes\ManageableFields;

use Illuminate\View\ComponentAttributeBag;
use WebRegulate\LaravelAdministration\Enums\PageType;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;

class Text extends ManageableField
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
            'label' => $this->getLabel(),
            'options' => $this->options,
            'attributes' => new ComponentAttributeBag(array_merge($this->attributes, [
                'name' => $this->attributes['name'],
                'value' => $this->getValue(),
                'type' => $this->attributes['type'] ?? 'text',
            ])),

        ])->render();
    }
}
