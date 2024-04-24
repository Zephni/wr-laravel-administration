<?php

namespace WebRegulate\LaravelAdministration\Classes\FormComponents;

use Illuminate\View\View;
use WebRegulate\LaravelAdministration\Classes\ManageableModel;

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
     * @param ManageableModel $manageableModel
     * @param mixed $column
     * @return static
     */
    public static function make(ManageableModel $manageableModel, string $column): static
    {
        return new static($column, $manageableModel->modelInstance->{$column});
    }

    /**
     * Set / Get attribute.
     *
     * @param string $key
     * @param ?string $value
     * @return $this | string $value
     */
    public function attribute(string $key, ?string $value): static
    {
        if($value == null) {
            return $this->attributes[$key];
        }

        $this->attributes[$key] = $value;

        return $this;
    }

    /**
     * Merge or get attributes.
     *
     * @param ?array $attributes
     * @return $this
     */
    public function attributes(?array $attributes): static
    {
        if($attributes == null) {
            return $this->attributes;
        }

        $this->attributes = array_merge($this->attributes, $attributes);

        return $this;
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
