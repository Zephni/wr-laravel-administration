<?php

namespace WebRegulate\LaravelAdministration\Classes\FormComponents;

use Illuminate\View\View;
use Illuminate\Support\Str;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;
use WebRegulate\LaravelAdministration\Classes\ManageableModel;

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
     * @param mixed $inject
     * @return mixed
     */
    public function render($inject = null): mixed
    {
        return parent::render(view(WRLAHelper::getViewPath('components.forms.input-text'), [
            'name' => $this->attributes['name'],
            'label' => Str::title(str_replace('_', ' ', $this->attributes['name'])),
            'value' => $this->attributes['value'],
        ])->render());
    }
}
