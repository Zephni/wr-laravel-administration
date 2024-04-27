<?php

namespace WebRegulate\LaravelAdministration\Classes\FormComponents;

use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Contracts\Validation\ValidationRule;
use WebRegulate\LaravelAdministration\Enums\UpsertType;
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
     * Validation rule
     *
     * @var string|ValidationRule|null
     */
    public string|ValidationRule|null $validationRule;

    /**
     * FormComponent constructor.
     *
     * @param ?string $name
     * @param ?string $value
     */
    public function __construct(?string $name, ?string $value)
    {
        $this->attributes = [
            'name' => $name ?? '',
            'value' => $value ?? '',
        ];
    }

    /**
     * Make method (can be used in any class that extends FormComponent).
     *
     * @param ?ManageableModel $manageableModel
     * @param ?mixed $column
     * @return static
     */
    public static function make(?ManageableModel $manageableModel = null, ?string $column = null): static
    {
        return new static($column, $manageableModel?->getModelInstance()->{$column});
    }

    /**
     * Set / Get attribute.
     *
     * @param string $key
     * @param ?string $value
     * @return $this | string
     */
    public function attribute(string $key, ?string $value = null): static|string
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
     * @return $this|array
     */
    public function attributes(?array $attributes = null): static|array
    {
        if($attributes == null) {
            return $this->attributes;
        }

        $this->attributes = array_merge($this->attributes, $attributes);

        return $this;
    }

    /**
     * Add validation rules
     *
     * @param string|ValidationRule|null
     * @return $this
     */
    public function validation(string|ValidationRule|null $validationRule): static
    {
        $this->validationRule = $validationRule;

        return $this;
    }

    /**
     * Apply value. May be overriden in special cases, such as when applying a hash to a password.
     *
     * @param mixed $value
     * @return mixed
     */
    public function applyValue(mixed $value): mixed
    {
        return $value;
    }

    /**
     * Return view component.
     *
     * @param UpsertType $upsertType
     * @param mixed $inject
     * @return mixed
     */
    public function renderParent(UpsertType $upsertType): mixed
    {
        $HTML = $this->render($upsertType);

        if(empty($HTML))
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
                    $HTML
                </div>
            HTML;
        }
    }

    /**
     * Render the input field.
     *
     * @param UpsertType $upsertType
     * @return mixed
     */
    public function render(UpsertType $upsertType): mixed
    {
        return null;
    }
}
