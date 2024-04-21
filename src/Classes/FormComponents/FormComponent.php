<?php

namespace WebRegulate\LaravelAdministration\Classes\FormComponents;

use Illuminate\View\View;

class FormComponent
{
    /**
     * Attributes of the form component.
     *
     * @var array
     */
    public $attributes;

    /**
     * Return view component.
     *
     * @param mixed $inject
     * @return mixed
     */
    public function render(?string $inject = null): mixed
    {
        if($inject == null)
        {
            return <<<HTML
                <br />
                ---------------------<br />
                Override form component HTML in FormComponent render() method<br />
                ---------------------<br />
                <br />
            HTML;
        }
        else
        {
            return <<<HTML
                <div>
                    $inject
                </div>
            HTML;
        }
    }
}
