<?php

namespace WebRegulate\LaravelAdministration\Classes\ManageableFields;

use WebRegulate\LaravelAdministration\Traits\ManageableField;
use Illuminate\View\ComponentAttributeBag;
use WebRegulate\LaravelAdministration\Enums\PageType;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;

class Text
{
    use ManageableField;

    /**
     * Render the input field.
     *
     * @return mixed
     */
    public function render(): mixed
    {
        return view(WRLAHelper::getViewPath('components.forms.input-text'), [
            'label' => $this->getLabel(),
            'options' => $this->options,
            'attributes' => new ComponentAttributeBag(array_merge($this->htmlAttributes, [
                'name' => $this->getAttribute('name'),
                'value' => $this->getValue(),
                'type' => $this->getAttribute('type') ?? 'text',
            ])),

        ])->render();
    }
}
