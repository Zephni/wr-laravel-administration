<?php

namespace WebRegulate\LaravelAdministration\Classes\ManageableFields;

use Illuminate\View\ComponentAttributeBag;
use WebRegulate\LaravelAdministration\Enums\PageType;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;

class Hidden extends ManageableField
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
            'attributes' => new ComponentAttributeBag(array_merge($this->attributes, [
                'name' => $this->attributes['name'],
                'value' => $this->getValue(),
                'type' => 'hidden',
            ])),
        ])->render();
    }
}
