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
     * FormComponent constructor.
     *
     * @param string $name
     * @param mixed $value
     */
    public function __construct(string $name, mixed $value)
    {
        $this->attributes = [
            'name' => $name,
            'value' => $value,
        ];
    }

    /**
     * Make method (can be used in any class that extends FormComponent).
     *
     * @param string $name
     * @param mixed $value
     * @return static
     */
    public static function make(string $name, mixed $value): static
    {
        return new static($name, $value);
    }

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
