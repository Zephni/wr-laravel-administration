<?php

namespace WebRegulate\LaravelAdministration\Traits;

use Illuminate\Http\Request;
use WebRegulate\LaravelAdministration\Enums\PageType;
use WebRegulate\LaravelAdministration\Classes\ManageableModel;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;

trait ManageableField
{
    /**
     * Attributes of the form component.
     *
     * @var array
     */
    public $htmlAttributes;

    /**
     * Static livewire $fields array.
     * 
     * @var array
     */
    public static array $fields = [];

    /**
     * Options
     *
     * @var array
     */
    public array $options = [
        'containerClass' => null,
        'label' => 'wrla::from_field_name',
        'ifNullThenString' => false,
        'beginGroup' => false,
        'endGroup' => false,
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
        // Check if name has . (relationship) or -> (json seperator) in it we need to get the appropriate value
        if(strpos($name, '.') !== false) {
            $value = $manageableModel->getInstanceRelationValue($name);
        }
        elseif(strpos($name, '->') !== false) {
            $value = $manageableModel->getInstanceJsonValue($name);
        }

        // Get name
        $name = $this->buildNameAttribute($name ?? '');

        // Set base attributes
        $this->htmlAttributes = [
            'name' => $name,
            'value' => $value ?? '',
        ];

        $this->manageableModel = $manageableModel;

        $this->postConstructed();
    }

    /**
     * Build name attribute. We need this because PHP converts dots to underscores in request input, so we need to
     * convert the . to a special reversable key.
     * 
     * @param string $name
     * @return string
     */
    private static function buildNameAttribute(string $name): string
    {
        return str_replace('.', WRLAHelper::WRLA_REL_DOT, $name);
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
     * Moddeled with livewire
     * 
     * @return bool
     */
    public function isModeledWithLivewire(): bool
    {
        foreach($this->htmlAttributes as $key => $value) {
            if(strpos($key, 'wire:model') !== false) {
                return true;
            }
        }

        return false;
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
            return $this->htmlAttributes['value'];
        }

        return old($this->htmlAttributes['name'], $this->htmlAttributes['value']) ?? '';
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
            return gettype($ifNullThenString) === 'string' && $ifNullThenString;
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
     * Merge or set options.
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
        if($key == 'name') {
            $value = $this->buildNameAttribute($value);
        }

        $this->htmlAttributes[$key] = $value;
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
        return $this->htmlAttributes[$key] ?? null;
    }

    /**
     * Set attributes.
     *
     * @param ?array $attributes
     * @return $this|array
     */
    public function setAttributes(array $attributes = []): static
    {
        // If name is set then convert to special key
        if(isset($attributes['name'])) {
            $attributes['name'] = $this->buildNameAttribute($attributes['name']);
        }
        
        $this->htmlAttributes = array_merge($this->htmlAttributes, $attributes);
        return $this;
    }

    /**
     * Get attributes.
     *
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->htmlAttributes;
    }

    /**
     * Set static field
     * 
     * @param string $key
     * @param mixed $value
     */
    public static function setField(string $key, mixed $value): void
    {
        self::$fields[$key] = $value;
    }

    /**
     * Set static fields. For fields with attribute: wire:model.live="fields.field_name", passed to each field blade view (if applicable).
     * 
     * @param array $fields
     */
    public static function setFields(array $fields): void
    {
        self::$fields = $fields;
    }

    /**
     * Has field
     * 
     * @param string $key
     * @return bool
     */
    public static function hasField(string $key): bool
    {
        return isset(self::$fields[$key]);
    }

    /**
     * Get field
     * 
     * @param string $key
     * @return mixed
     */
    public static function getField(string $key): mixed
    {
        return self::$fields[$key] ?? null;
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

        $this->htmlAttributes['value'] = $value;

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
            // If name is based on a relation on json column (eg has a . or -> in it) we get the last part of the name
            $label = str_replace(WRLAHelper::WRLA_REL_DOT, '.', $this->htmlAttributes['name']);
            $label = str($label)->afterLast('.')->afterLast('->');
            $label = $label->replace('_', ' ')->ucfirst();
            return $label;
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
            return typeOf($ifNullThenString) === 'string' && $ifNullThenString;
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
     * Is relationship field.
     * 
     * @return bool
     */
    public function isRelationshipField(): bool
    {
        return str($this->htmlAttributes['name'])->contains('.') || str($this->htmlAttributes['name'])->contains(WRLAHelper::WRLA_REL_DOT);
    }

    /**
     * Get relationship field name.
     * 
     * @return string
     */
    public function getRelationshipFieldName(): string
    {
        $fieldName = str($this->htmlAttributes['name'])->after(WRLAHelper::WRLA_REL_DOT);

        // If contains ->, get the first part
        if($fieldName->contains('->')) {
            $fieldName = $fieldName->before('->');
        }

        return $fieldName;
    }

    /**
     * Get relationship instance.
     * 
     * @return mixed
     */
    public function getRelationshipInstance(): mixed
    {
        // Get model instance, field name and relationship parts
        $modelInstance = $this->manageableModel->getModelInstance();
        $fieldName = str($this->htmlAttributes['name'])->replace(WRLAHelper::WRLA_REL_DOT, '.');
        $relationshipParts = WRLAHelper::parseBrowseColumnRelationship($fieldName);

        // Reload and get relationship instance
        $modelInstance->load($relationshipParts[0]);
        $relationshipInstance = $modelInstance->{$relationshipParts[0]};
        
        // If relationship instance is not null, return it
        if($relationshipInstance != null) return $relationshipInstance;

        // Get model class from relationship and return new instance
        $relationship = $modelInstance->{$relationshipParts[0]}();
        $relationshipClass = get_class($relationship->getRelated());
        return new $relationshipClass;
    }

    /**
     * Return view component.
     *
     * @param PageType $upsertType
     * @param array $fields
     * @return mixed
     */
    public function renderParent(PageType $upsertType, array $fields): mixed
    {
        if(!in_array($upsertType, $this->showOnPages))
        {
            return '';
        }

        // Set static fields
        self::setFields($fields);

        $HTML = $this->getOption('beginGroup') == true ? '<div class="w-full flex flex-col md:flex-row items-center gap-6">' : '';
        $HTML .= $this->render();
        $HTML .= $this->getOption('endGroup') == true ? '</div>' : '';

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
                $HTML
            HTML;
        }
    }

    /**
     * Render the input field.
     *
     * @return mixed
     */
    public function render(): mixed
    {
        return null;
    }
}
