<?php

namespace WebRegulate\LaravelAdministration\Classes\FormComponents;

use WebRegulate\LaravelAdministration\Enums\PageType;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;

/**
 * Class Input
 *
 * This class is responsible for generating input fields.
 */
class Image extends ManageableField
{
    /**
     * Render the input field.
     *
     * @param PageType $upsertType
     * @return mixed
     */
    public function render(PageType $upsertType): mixed
    {
        $HTML = '';
        
        $HTML .= view(WRLAHelper::getViewPath('components.forms.input-image'), [
            'name' => $this->attributes['name'],
            'label' => $this->getLabel(),
            'value' => '',
            'type' => 'file',
            'options' => $this->options,
            'attr' => collect($this->attributes)
                ->forget(['name', 'value', 'type'])
                ->toArray(),
        ])->render();

        return $HTML;
    }
}
