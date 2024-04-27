<?php

namespace WebRegulate\LaravelAdministration\Classes\FormComponents;

use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Contracts\Validation\ValidationRule;
use WebRegulate\LaravelAdministration\Enums\PageType;
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
     * Show on pages
     *
     * @var array
     */
    public array $showOnPages = [
        PageType::CREATE,
        PageType::EDIT,
        // UpsertType::BROWSE,
    ];

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
     * Hide from pages.
     *
     * @param PageType ...$pageTypes
     */
    public function hideFrom(...$pageTypes): static
    {
        $this->showOnPages = array_filter($this->showOnPages, function($pageType) use ($pageTypes) {
            return !in_array($pageType, $pageTypes);
        });

        return $this;
    }

    /**
     * Show on pages.
     *
     * @param PageType ...$pageTypes
     */
    public function showOn(...$pageTypes): static
    {
        $this->showOnPages = array_merge($this->showOnPages, $pageTypes);

        return $this;
    }

    /**
     * Show only on pages.
     *
     * @param PageType ...$pageTypes
     */
    public function showOnlyOn(...$pageTypes): static
    {
        $this->showOnPages = $pageTypes;

        return $this;
    }

    /**
     * Return view component.
     *
     * @param PageType $upsertType
     * @param mixed $inject
     * @return mixed
     */
    public function renderParent(PageType $upsertType): mixed
    {
        if(!in_array($upsertType, $this->showOnPages))
        {
            return '';
        }

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
     * @param PageType $upsertType
     * @return mixed
     */
    public function render(PageType $upsertType): mixed
    {
        return null;
    }
}
