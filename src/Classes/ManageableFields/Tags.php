<?php

namespace WebRegulate\LaravelAdministration\Classes\ManageableFields;

use Illuminate\View\ComponentAttributeBag;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;
use WebRegulate\LaravelAdministration\Traits\ManageableField;

class Tags
{
    use ManageableField;

    /**
     * Render the input field.
     */
    public function render(): mixed
    {
        return view(WRLAHelper::getViewPath('components.forms.input-tags'), [
            'label' => $this->getLabel(),
            'options' => $this->options,
            'attributes' => new ComponentAttributeBag(array_merge($this->htmlAttributes, [
                'name' => $this->getAttribute('name'),
                'value' => $this->getValue(),
                'type' => $this->getAttribute('type') ?? 'text',
            ])),

        ])->render();
    }

    /**
     * Define a list of common tags that can be clicked to inject into the input field.
     */
    public function commonTags(array $tags): static
    {
        $this->options['commonTags'] = $tags;
        return $this;
    }
}
