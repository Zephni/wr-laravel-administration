<?php

namespace WebRegulate\LaravelAdministration\Classes\ManageableFields;

use Illuminate\View\ComponentAttributeBag;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;
use WebRegulate\LaravelAdministration\Traits\ManageableField;

class Wysiwyg
{
    use ManageableField;

    /**
     * Render the input field.
     */
    public function render(): mixed
    {
        return view(WRLAHelper::getViewPath('components.forms.wysiwyg'), [
            'label' => $this->getLabel(),
            'options' => $this->options,
            'attributes' => new ComponentAttributeBag(array_merge($this->htmlAttributes, [
                'name' => $this->getAttribute('name'),
                'value' => $this->getValue(),
                'class' => 'w-full',
            ])),
        ])->render();
    }
}
