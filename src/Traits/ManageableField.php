<?php

namespace WebRegulate\LaravelAdministration\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use WebRegulate\LaravelAdministration\Classes\BrowseFilter;
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
    public static array $livewireFields = [];

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
        'active' => true, // If false, this field will not be displayed, submitted / validated
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
     * Filter key values
     *
     * @var array
     */
    public static array $browseFilterValues = [];

    /**
     * Static field index
     *
     * @var int
     */
    public static $fieldIndex = 0;

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
     * Callable to apply submitted value. May be overriden in special cases, such as when applying a hash to a password.
     *
     * @var mixed
     */
    private mixed $applySubmittedValueCallable = null;

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
        if(WRLAHelper::isBrowseColumnRelationship($name ?? '')) {
            $value = $manageableModel->getInstanceRelationValue($name);
        }
        elseif(str_contains($name ?? '', '->')) {
            $value = $manageableModel->getInstanceJsonValue($name);
        }

        // Get parent class of this trait
        $parentClass = get_class($this);

        // Get default validation rules if exists
        $defaultValidationRules = config("wr-laravel-administration.default_validation_rules.$parentClass");
        if(!empty($defaultValidationRules)) {
            $this->validation($defaultValidationRules);
        }

        // Increment field index
        self::$fieldIndex++;

        // Get name
        $name = $this->buildNameAttribute($name ?? '');

        // Set base attributes
        $this->htmlAttributes = [
            'name' => $name,
            'value' => $value ?? '',
        ];

        // Set manageable model
        $this->manageableModel = $manageableModel;

        // Handle livewire setup (if applicable)
        $this->handleLivewireSetup();

        // Set manageable models attributes if applicable
        $this->setManageableModelValue();

        // Run post constructed method
        $this->postConstructed();
    }

    /**
     * Set active, must be the last method called in the chain as returns null if not active.
     */
    public function setActive(bool|callable $active): ?static
    {
        // If callable, call it with $this as parameter
        if(is_callable($active)) {
            $active = $active($this);
        }

        // Set active option
        $this->options['active'] = $active;

        return $this->options['active'] ? $this : null;
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
        // If empty, return unique name
        if(empty($name)) {
            return 'wrla_field_'.self::$fieldIndex;
        }

        return str_replace('.', WRLAHelper::WRLA_REL_DOT, $name);
    }

    /**
     * If manageable model is not null and manageable model has an attribute with this
     * field's name, set the manageable models attribute to the value
     *
     * @return void
     */
    public function setManageableModelValue(): void
    {
        // If manageable model is null, return
        if($this->manageableModel === null) return;

        // Get name to test with against manageable model
        $name = $this->getName();

        // If attribute does not exist, get all column names from table and fill out empty values
        if(!property_exists($this->manageableModel->getModelInstance(), $name)) {
            $this->manageableModel->fillEmptyInstanceAttributesWithDefaults();
        }

        // If attribute still does not exist, return
        if(!$this->manageableModel->getModelInstance()->hasAttribute($name)) return;

        // Set manageable model property value
        $this->manageableModel->getModelInstance()->setAttribute($name, $this->getValue());
    }

    /**
     * Make method (can be used in any class that extends FormComponent).
     *
     * @param ?ManageableModel $manageableModel
     * @param ?mixed $column
     * @param ?array $options
     * @return static
     */
    public static function make(?ManageableModel $manageableModel = null, ?string $column = null, ?array $options = null): static
    {
        $manageableField = new static($column, $manageableModel?->getModelInstance()->{$column}, $manageableModel);

        if(!is_null($options)) {
            $manageableField->setOptions($options);
        }

        return $manageableField;
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
            if(str_contains((string) $key, 'wire:model')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Make browse.filter version of the form component.
     *
     * @param string $filterAlias Must be the same as the BrowseFilter key
     * @param string $filterLabel
     * @param string $filterIcon
     * @param string $containerClass
     * @return static
     */
    public static function makeBrowseFilter(?string $filterAlias = null, ?string $filterLabel = null, ?string $filterIcon = null, string $containerClass = 'flex-1'): static
    {
        return static::make(null, $filterAlias)
            ->setOptions([
                'newRow' => false,
                'containerClass' => $containerClass,
                'labelClass' => 'font-thin mb-1',
            ])
            ->setLabel($filterLabel, !empty($filterIcon) ? "$filterIcon text-slate-400 mr-1" : null)
            ->setAttributes([
                'wire:model.live.debounce.300ms' => 'filters.'.$filterAlias,
            ]);
    }

    /**
     * Make browse.filter version of the form component.
     *
     * @param callable $callback Takes $query, $table, $columns, $value
     * @return BrowseFilter
     */
    public function browseFilterApply(callable $callback): BrowseFilter
    {
        return new BrowseFilter(
            $this,

            // Apply browse filter
            $callback
        );
    }

    /**
     * Get filter value.
     */
    public function getBrowseFilterValue(string $filterAlias): mixed
    {
        return ManageableField::$browseFilterValues[$filterAlias] ?? null;
    }

    /**
     * Set static filter value.
     */
    public static function setStaticBrowseFilterValue(string $filterAlias, mixed $value): void
    {
        // Set static filter value
        ManageableField::$browseFilterValues[$filterAlias] = $value;
    }

    /**
     * Set value.
     *
     * @param mixed $value
     */
    public function setValue(mixed $value): void
    {
        // Set value attribute
        $this->setAttribute('value', $value);

        // If livewire field, set livewire field value
        if($this->isModeledWithLivewire()) {
            ManageableModel::setLivewireField($this->getName(), $value);
        }
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
     * Get name attribute.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->htmlAttributes['name'];
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
     * Should submit method, if false then the form will not submit it's value by setting the form="none" attribute.
     *
     * @param bool $shouldSubmit
     * @return $this
     */
    public function shouldSubmit(bool $shouldSubmit): static
    {
        if(!$shouldSubmit) {
            $this->setAttribute('form', 'none');
        } else {
            $this->removeAttribute('form');
        }

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
     * Begin group
     *
     * @return $this
     */
    public function beginGroup(): static
    {
        $this->options['beginGroup'] = true;
        return $this;
    }

    /**
     * End group
     *
     * @return $this
     */
    public function endGroup(): static
    {
        $this->options['endGroup'] = true;
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
     * Remove attribute
     *
     * @param string $key
     * @return $this
     */
    public function removeAttribute(string $key): static
    {
        unset($this->htmlAttributes[$key]);
        return $this;
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
     * Livewire setup. Return a key => value array of livewire fields to register their default values.
     * Note that if returns array, this manageable field will automatically add the wire:model.live attribute to the input field.
     * If not using livewire fields for this manageable model, return null.
     *
     * @return ?array Key => value array of livewire fields to register their default values.
     */
    public function livewireSetup(): ?array
    {
        return null;
    }

    /**
     * Set livewire wire:model.live attribute with the livewireData. prefix for use in
     * the livewire component and other manageable fields.
     *
     * @param string $name
     * @return $this
     */
    public function setLivewireModel(string $type = 'live'): static
    {
        // If $type is not empty, prepend .
        $injectType = !empty($type) ? ".$type" : '';

        // Set wire:model attribute
        $this->setAttribute("wire:model{$injectType}", "livewireData.{$this->getAttribute('name')}");

        // If first render, set livewire field
        if(ManageableModel::$numberOfRenders === 0) {
            ManageableModel::setLivewireField($this->getName(), $this->getValue());
        }

        return $this;
    }

    /**
     * Handle livewire setup
     *
     * @return void
     */
    private function handleLivewireSetup(): void
    {
        // Get livewire setup result
        $livewireSetupResult = $this->livewireSetup();

        // If null then return
        if($livewireSetupResult === null) {
            return;
        }

        // Set livewire model attribute
        $this->setLivewireModel();

        // Set each static field default if not already set
        foreach($livewireSetupResult as $key => $value) {
            if(!ManageableModel::hasLivewireField($key)) {
                ManageableModel::setLivewireField($key, $value);
            }
        }
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
            // We setValue here to also handle the livewire field value
            $this->setValue($this->manageableModel->getModelInstance()->{$this->getName()} ?? $value);

            return $this;
        }

        // Set value
        $this->setValue($value);

        // Set manageable model value
        $this->setManageableModelValue();

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
            return static::getLabelFromFieldName($this->htmlAttributes['name']);
        }
        // If null then return null
        elseif($label === null) {
            return null;
        }

        return $label;
    }

    /**
     * Get label from field name
     *
     * @return string
     */
    public static function getLabelFromFieldName(string $fieldName): string
    {
        // If if ends with _id, remove it
        if(str($fieldName)->endsWith('_id')) {
            $fieldName = str($fieldName)->beforeLast('_id');
        }

        // If name is based on a relation on json column (eg has a . or -> in it) we get the last part of the name
        $label = str_replace(WRLAHelper::WRLA_REL_DOT, '.', $fieldName);
        $label = str($label)->afterLast('.')->afterLast('->');
        $label = $label->replace('_', ' ')->trim(' ')->ucfirst();
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
     * The callable must take Request $request, and mixed $value, and return the value to be set.
     * If callable is set, it will be called instead of the default applySubmittedValue method.
     *
     * @param callable
     * @return mixed
     */
    public function overrideApplySubmittedValue(callable $callable): mixed
    {
        $this->applySubmittedValueCallable = $callable;
        return $this;
    }

    /**
     * Apply submitted value final.
     *
     * @param Request $request
     * @param mixed $value
     * @return mixed
     */
    public function applySubmittedValueFinal(Request $request, mixed $value): mixed
    {
        // If callable is set then run it
        if($this->applySubmittedValueCallable !== null) {
            return call_user_func($this->applySubmittedValueCallable, $request, $value);
        }

        // Otherwise run the default applySubmittedValue method
        return $this->applySubmittedValue($request, $value);
    }

    /**
     * Hide from pages.
     *
     * @param PageType ...$pageTypes
     */
    public function hideFrom(...$pageTypes): static
    {
        $this->showOnPages = array_filter($this->showOnPages, fn($pageType) => !in_array($pageType, $pageTypes));

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
        if(str($this->htmlAttributes['name'])->contains('->')) {
            return false;
        }

        if(str($this->htmlAttributes['name'])->contains('.') || str($this->htmlAttributes['name'])->contains(WRLAHelper::WRLA_REL_DOT)) {
            return true;
        }

        return false;
    }


    /**
     * Is using nested json.
     * 
     * @return bool
     */
    public function isUsingNestedJson(): bool
    {
        if(str($this->htmlAttributes['name'])->contains('->')) {
            return true;
        }

        return false;
    }

    /**
     * Get relationship name.
     *
     * @return string
     */
    public function getRelationshipName(): string
    {
        $relationshipName = str($this->htmlAttributes['name'])->before(WRLAHelper::WRLA_REL_DOT);
        return $relationshipName;
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
        return once(function() {
            // Get model instance, field name and relationship parts.
            $modelInstance = $this->manageableModel->getModelInstance();
            $fieldName = str($this->htmlAttributes['name'])->replace(WRLAHelper::WRLA_REL_DOT, '.');
            $relationshipParts = WRLAHelper::parseBrowseColumnRelationship($fieldName);


            if($modelInstance == null) {
                dd('This model instance no longer exists', $modelInstance, $fieldName, $relationshipParts);
            }

            // Check relation exists on model instance
            if(method_exists($modelInstance, $relationshipParts[0])) {
                // Reload and get relationship instance
                $modelInstance->load($relationshipParts[0]);
                $relationshipInstance = $modelInstance->{$relationshipParts[0]};
            }

            // Check whether dynamic relationship exists
            if(!isset($relationshipInstance) || $relationshipInstance === null) {
                // If relationshipParts has more than 1 part, we need to get the nested relationship
                if(count($relationshipParts) > 1) {
                    $potentialRelationship = $modelInstance->{$relationshipParts[0]};

                    // First check if relationship is already resolved (this can also happen if relationship is set using dynamic resolveRelationUsing)
                    if($potentialRelationship instanceof Model) {
                        $relationshipInstance = $potentialRelationship;
                    } else {
                        $relationshipInstance = $potentialRelationship()->first();
                    }
                }
            }

            // If relationship instance is not null, return it
            if($relationshipInstance != null) return $relationshipInstance;

            // Get model class from relationship and return new instance
            $relationship = $modelInstance->{$relationshipParts[0]}();
            $relationshipClass = $relationship->getRelated()::class;
            return new $relationshipClass;
        });
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
        ManageableModel::setLivewireFields($fields);

        $HTML = $this->getOption('beginGroup') == true ? '<div class="w-full flex flex-col md:flex-row items-center gap-6">' : '';
        $HTML .= $this->render();
        $HTML .= $this->getOption('endGroup') == true ? '</div>' : '';

        if(empty($HTML))
        {
            return <<<HTML_WRAP
                <br />
                ---------------------<br />
                Override form component HTML in FormComponent render() method<br />
                ---------------------<br />
                <br />
            HTML_WRAP;
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
