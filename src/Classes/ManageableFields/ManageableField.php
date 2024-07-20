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
    public array $options = [
        'containerClass' => null,
        'label' => 'wrla::from_field_name',
        'ifNullThenString' => false,
    ];

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
     * @return static
     */
    public static function make(?ManageableModel $manageableModel = null, ?string $column = null): static
    {
        return new static($column, $manageableModel?->getModelInstance()->{$column}, $manageableModel);
    }

    /**
     * Get manageable field type (eg. the class name: Text, Select, etc.)
     *
     * @return string
     */
    public function getType(): string
    {
        return (new \ReflectionClass($this))->getShortName();
    }

    /**
     * Make browse.filter version of the form component.
     *
     * @param string $filterAlias Must be the same as the BrowseFilter key
     * @return static
     */
    public static function makeBrowseFilter(?string $filterAlias = null): static
    {
        return static::make(null, $filterAlias)
            ->setOptions([
                'containerClass' => '',
                'labelClass' => 'font-thin',
            ])
            ->setAttributes([
                'wire:model.live' => 'filters.'.$filterAlias,
                'class' => '!mt-1'
            ]);
    }

    /**
     * Get value, if option ignoreOld is set then return the value attribute, otherwise return
     * the old value if it exists in the request.
     *
     * @return string
     */
    public function getValue(): string
    {
        if($this->options['ignoreOld'] ?? false) {
            return $this->attributes['value'];
        }

        return old($this->attributes['name'], $this->attributes['value']) ?? '';
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
        // If null then string check
        $ifNullThenString = $this->getOption('ifNullThenString');
        if($ifNullThenString !== false && $value === null) {
            return gettype($ifNullThenString) === 'string' ? $ifNullThenString : '';
        }
        
        // Return false by default so no adjustments are made to the request input
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
    public function inlineValidation(callable ...$callbacks): static
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
     * Add required attribute and validation
     *
     * @return $this
     */
    public function required(): static
    {
        $this->setAttribute('required', 'required');
        $this->validation('required');

        return $this;
    }

    /**
     * Set option.
     *
     * @param string $key
     * @param ?string $value
     * @return $this
     */
    public function setOption(string $key, ?string $value): static
    {
        $this->options[$key] = $value;
        return $this;
    }

    /*
     * Get option.
     *
     * @param string $key
     * @return mixed
     */
    public function getOption(string $key): mixed
    {
        return $this->options[$key] ?? null;
    }

    /**
     * Merge or get options.
     *
     * @param ?array $options
     * @return $this
     */
    public function setOptions(array $options): static
    {
        $this->options = array_merge($this->options, $options);
        return $this;
    }

    /**
     * Get options.
     *
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Set attribute.
     *
     * @param string $key
     * @param ?string $value
     * @return $this
     */
    public function setAttribute(string $key, ?string $value): static
    {
        $this->attributes[$key] = $value;
        return $this;
    }

    /**
     * Get attribute.
     *
     * @param string $key
     * @return mixed
     */
    public function getAttribute(string $key): mixed
    {
        return $this->attributes[$key] ?? null;
    }

    /**
     * Set attributes.
     *
     * @param ?array $attributes
     * @return $this|array
     */
    public function setAttributes(array $attributes = []): static
    {
        $this->attributes = array_merge($this->attributes, $attributes);
        return $this;
    }

    /**
     * Get attributes.
     *
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->attributes;
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
    public function setLabel(?string $label, ?string $icon = null): static
    {
        // If icon is set then prepend it to the label
        if($icon !== null) {
            $label = "<i class='$icon mr-0.5'></i> $label";
        }

        $this->options['label'] = $label;

        return $this;
    }

    /**
     * Get label.
     *
     * @return ?string
     */
    public function getLabel(): ?string
    {
        // Get label option
        $label = $this->getOption('label');

        // If wrla::from_field_name then get the name and convert to label
        if($label === 'wrla::from_field_name') {
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
        // If null then return null
        elseif($label === null) {
            return null;
        }

        return $label;
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
        $ifNullThenString = $this->getOption('ifNullThenString');

        if($ifNullThenString !== false && $value === null) {
            return typeOf($ifNullThenString) === 'string' ? $ifNullThenString : '';
        }

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
