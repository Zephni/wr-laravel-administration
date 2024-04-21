<?php

namespace WebRegulate\LaravelAdministration\Classes\FormComponents;

use Illuminate\View\View;
use Illuminate\Support\Str;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;

/**
 * Class Input
 *
 * This class is responsible for generating input fields.
 */
class Input extends FormComponent
{
    /**
     * Input constructor.
     *
     * @param string $name
     * @param string $label
     * @param string $value
     */
    public function __construct(
        public string $name = '',
        public string $value = '',
    ) {
        $this->attributes = [
            'name' => $name,
            'value' => $value,
        ];
    }

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
