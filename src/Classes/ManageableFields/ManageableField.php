<?php

namespace WebRegulate\LaravelAdministration\Classes\ManageableFields;

use Illuminate\Http\Request;
use WebRegulate\LaravelAdministration\Enums\PageType;
use WebRegulate\LaravelAdministration\Classes\ManageableModel;

class ManageableField
{
    /**
     * Key remove constant
     *
     * @var string
     */
    const WRLA_KEY_REMOVE = '__WRLA::KEY::REMOVE__';

    /**
     * Attributes of the form component.
     *
     * @var array
     */
    public $attributes;

    /**
     * Options
     *
     * @var array
     */
    public array $options = [];

    /**
     * Validation rule
     *
     * @var string
     */
    public string $validationRules = '';

    /**
     * Inline validation rules
     *
     * @var array
     */
    public array $inlineValidationRules = [];

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
     * Manageable model instance
     *
     * @var ?ManageableModel
     */
    public ?ManageableModel $manageableModel = null;

    /**
     * FormComponent constructor.
     *
     * @param ?string $name
     * @param ?string $value
     * @param ?ManageableModel $manageableModel
     */
    public function __construct(?string $name, ?string $value, ?ManageableModel $manageableModel = null)
    {
        // Check if name has any -> in it, if so we need to get the value useing wrla json notation
        if(strpos($name, '->') !== false) {
            $value = $manageableModel->getInstanceJsonValue($name);
        }

        // Set base attributes
        $this->attributes = [
            'name' => $name ?? '',
            'value' => $value ?? '',
        ];

        $this->manageableModel = $manageableModel;

        $this->postConstructed();
    }

    /**
     * Make method (can be used in any class that extends FormComponent).
     *
     * @param ?ManageableModel $manageableModel
     * @param ?mixed $column
     * @return self
     */
    public static function make(?ManageableModel $manageableModel = null, ?string $column = null): self
    {
        return new static($column, $manageableModel?->getModelInstance()->{$column}, $manageableModel);
    }

    /**
     * Get value
     *
     * @return string
     */
    public function getValue(): string
    {
        return $this->attributes['value'];
    }

    /**
     * Post constructed method, called after name and value attributes are set.
     *
     * @return $this
     */
    public function postConstructed(): mixed
    {
        return $this;
    }

    /**
     * Pre validation method, called before validation rules are set.
     *
     * @param ?string $value
     * @return bool Return true if we have changed the value and want to force merge into request input
     */
    public function preValidation(?string $value): bool
    {
        return false;
    }

    /**
     * Add inline validation rule, note each $callable must take 1 parameter of request input value
     * object, and must return true on success, and a string message on failure.
     * Inline validation is run within the manageable model in it's runInlineValidation method.
     * Note that inline validation is run after the standard validation rule set.
     *
     * @param callable ...$callback
     * @return $this
     */
    public function inlineValidation(callable ...$callbacks): self
    {
        $this->inlineValidationRules = array_merge($this->inlineValidationRules, $callbacks);

        return $this;
    }

    /**
     * Run inline validation on the form component.
     *
     * @param true|string $result
     */
    public function runInlineValidation($requestValue): true|string
    {
        foreach($this->inlineValidationRules as $callback) {
            $result = $callback($requestValue);

            if($result !== true) {
                return $result;
            }
        }

        return true;
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
        if($attributes === null) {
            return $this->attributes;
        }

        $this->attributes = array_merge($this->attributes, $attributes);

        return $this;
    }

    /**
     * Add required attribute and validation
     *
     * @return $this
     */
    public function required(): static
    {
        $this->attribute('required', 'required');
        $this->validation('required');

        return $this;
    }

    /**
     * Set / Get option.
     *
     * @param string $key
     * @param mixed $value
     * @return $this | string
     */
    public function option(string $key, mixed $value = null): static|string
    {
        if($value === null) {
            if(isset($this->options[$key])){
                return $this->options[$key];
            } else {
                return false;
            }
        }

        $this->options[$key] = $value;

        return $this;
    }

    /**
     * Merge or get options.
     *
     * @param ?array $options
     * @return $this|array
     */
    public function options(?array $options = null): static|array
    {
        if($options == null) {
            return $this->options;
        }

        $this->options = array_merge($this->options, $options);

        return $this;
    }

    /**
     * Set default value.
     * 
     * @param mixed $value
     * @return $this
     */
    public function default(mixed $value): static
    {
        // If model is being modified rather than created, then skip setting default value
        if($this->manageableModel?->isBeingCreated() === false) {
            return $this;
        }
        
        $this->attributes['value'] = $value;

        return $this;
    }

    /**
     * Set label
     *
     * @param ?string $label
     * @return $this
     */
    public function setLabel(?string $label): static
    {
        $this->options['label'] = $label;

        return $this;
    }

    /**
     * Notes
     *
     * @param string $notes
     * @return $this
     */
    public function notes(string $notes): static
    {
        $this->options['notes'] = $notes;

        return $this;
    }

    /**
     * Append or overwrite validation rules
     * TODO: Add allowance of array validations
     *
     * @param string $validationRules Validation rules
     * @param bool $overwrite Overwrite existing validation rules
     * @return $this
     */
    public function validation(string $validationRules, $overwrite = false): static
    {
        if($overwrite || empty($this->validationRules)) {
            $this->validationRules = $validationRules;
        } else {
            $this->validationRules .= '|' . $validationRules;
        }

        return $this;
    }

    /**
     * Apply submitted value. May be overriden in special cases, such as when applying a hash to a password.
     *
     * @param Request $request
     * @param mixed $value
     * @return mixed
     */
    public function applySubmittedValue(Request $request, mixed $value): mixed
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
     * Get label.
     *
     * @return string
     */
    public function getLabel(): string
    {        
        // If label set in options and not null then use that
        if(isset($this->options['label'])) {
            if($this->options['label'] === null) {
                return '';
            }

            return $this->options['label'];
        }

        // If name is based on a json column (eg has a -> in it) then we need to get the string after the ->
        // and then explode the . dots and get the last element.
        if(strpos($this->attributes['name'], '->') !== false) {
            $label = explode('->', $this->attributes['name']);
            $label = end($label);
        } else {
            $label = $this->attributes['name'];
        }

        return ucfirst(str_replace('_', ' ', $label));
    }

    /**
     * If condition is true, run callback.
     * 
     * @param bool $isTrue
     * @param callable $callback (Must take $this as a parameter and return $this)
     * @return $this
     */
    public function onCondition(bool $isTrue, callable $callback): static
    {
        if(!$isTrue) {
            return $this;
        }

        return $callback($this);
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
