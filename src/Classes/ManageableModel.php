<?php

namespace WebRegulate\LaravelAdministration\Classes;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Stringable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Database\Eloquent\Builder;
use WebRegulate\LaravelAdministration\Classes\ManageableFields\Select;
use WebRegulate\LaravelAdministration\Classes\ManageableFields\ManageableField;
use WebRegulate\LaravelAdministration\Classes\ManageableFields\Text;

class ManageableModel
{
    /**
     * Model that this manageable model is based on, eg. \App\Models\User::class.
     *
     * @var ?string
     */
    private static ?string $baseModelClass = null;

    /**
     * Base model instance
     *
     * @var mixed
     */
    private mixed $modelInstance = null;

    /**
     * Collection of manageable models, pushed to in the register method which is called within the serice provider.
     *
     * @var ?Collection
     */
    public static ?Collection $manageableModels = null;

    /**
     * WRLAPermissions instance
     *
     * @var WRLAPermissions
     */
    private static ?WRLAPermissions $permissions = null;

    /**
     * Cache
     *
     * @var array
     */
    public static array $cache = [
        'isSoftDeletable' => null
    ];

    /**
     * Register the manageable model.
     *
     * @return void
     */
    public static function register(): void
    {
        // If manageable models is null, set it to a collection
        if (is_null(static::$manageableModels)) {
            static::$manageableModels = collect();
        }

        // Register the model
        static::$manageableModels->push(static::class);
    }

    /**
     * If model instance is passed (must be an instance of the base model), set it as the model instance.
     * If model ID is passed, get the model instance by ID.
     * Otherwise, set the model instance to a new instance of the base model.
     *
     * @param mixed|int|null $modelInstance
     */
    public function __construct($modelInstanceOrId = null)
    {
        // If model instance or id is null, set the model instance to a new instance of the base model
        if($modelInstanceOrId == null) {
            $this->setModelInstance(new static::$baseModelClass);
        // If model ID is passed, get the model instance by ID
        } elseif (is_numeric($modelInstanceOrId)) {
            $this->setModelInstance(static::$baseModelClass::find($modelInstanceOrId));
        // If model instance (extends base model) is passed, set it as the model instance
        } else if ($modelInstanceOrId instanceof static::$baseModelClass) {
            $this->setModelInstance($modelInstanceOrId);
        }

        // Apply permissions
        self::$permissions = new WRLAPermissions($this);
    }

    /**
     * Make, a static version of the constructor
     *
     * @param mixed|int|null $modelInstance
     * @return static
     */
    public static function make($modelInstanceOrId = null): static
    {
        return new static($modelInstanceOrId);
    }

    /**
     * Set the base model instance.
     *
     * @param mixed $modelInstance
     * @return ManageableModel
     */
    public function setModelInstance($modelInstance): ManageableModel
    {
        $this->modelInstance = $modelInstance;
        return $this;
    }

    /**
     * Get the base model instance.
     *
     * @return mixed
     */
    public function getModelInstance(): mixed
    {
        return $this->modelInstance;
    }

    /**
     * Get manageable model by model class.
     *
     * @param string $modelClass
     * @return mixed
     */
    public static function getByModelClass(string $modelClass): mixed
    {
        return static::$manageableModels->first(function ($manageableModel) use ($modelClass) {
            return $manageableModel::$baseModelClass === $modelClass;
        });
    }

    /**
     * Get manageable model by URL alias.
     *
     * @param string $urlAlias
     * @return mixed
     */
    public static function getByUrlAlias(string $urlAlias): mixed
    {
        $manageableModel = static::$manageableModels->first(function ($manageableModel) use ($urlAlias) {
            return $manageableModel::getUrlAlias() === $urlAlias;
        });

        return $manageableModel;
    }

    /**
     * Get the base model for the manageable model.
     *
     * @return string
     */
    public static function getBaseModelClass(): string
    {
        return static::$baseModelClass;
    }

    /**
     * Permissions
     *
     * @return WRLAPermissions
     */
    public static function permissions(): WRLAPermissions
    {
        // If permissions not set, create a new instance
        if(!isset(self::$permissions) || self::$permissions == null) {
            self::$permissions = new WRLAPermissions(new self());
        }

        return self::$permissions;
    }

    /**
     * Get the URL alias for the manageable model.
     *
     * @return string
     */
    public static function getUrlAlias(): string
    {
        return 'manageable-model';
    }

    /**
     * Get the display name for the manageable model.
     *
     * @param bool $plural
     * @return string
     */
    public static function getDisplayName(bool $plural = false): string
    {
        return !$plural ? 'Manageable Model' : 'Manageable Models';
    }

    /**
     * Get the icon for the manageable model.
     *
     * @return string
     */
    public static function getIcon(): string
    {
        return 'fa fa-question';
    }

    /**
     * Get browsable columns.
     * To add a relationship column, use the format 'local_column::other_table.display_column'.
     * To add a json value column, use the format 'json_column->key1->key2'.
     *
     * @return Collection
     */
    public static function getBrowsableColumns(): Collection
    {
        return collect([
            'id' => 'ID',
            // 'column_name' => 'Column Name',
            // ...
        ]);
    }

    /**
     * Get browse actions
     *
     * @return Collection
     */
    public static function getBrowseActions(): Collection {
        $browseActions = collect();

        $manageableModel = static::make();

        if($manageableModel::permissions()->hasPermission(WRLAPermissions::CREATE)) {
            $browseActions->put('create', view(WRLAHelper::getViewPath('components.forms.button'), [
                'text' => 'Create ' . static::getDisplayName(),
                'icon' => 'fa fa-plus text-sm',
                'href' => route('wrla.manageable-model.create', ['modelUrlAlias' => static::getUrlAlias()])
            ]));
        }

        return $browseActions;
    }

    /**
     * Get browse item actions
     *
     * @param mixed $model
     * @return Collection
     */
    public static function getBrowseItemActions(mixed $model): Collection {
        $manageableModel = static::make($model);

        $browseActions = collect();
        
        // If model doesn't have soft delets and not trashed
        if(!static::isSoftDeletable() || $model->deleted_at == null) {
            if($manageableModel::permissions()->hasPermission(WRLAPermissions::EDIT)) {
                $browseActions->put('edit', view(WRLAHelper::getViewPath('components.browse-actions.edit-button'), [
                    'manageableModel' => $manageableModel
                ]));
            }

            if($manageableModel::permissions()->hasPermission(WRLAPermissions::DELETE)) {
                $browseActions->put('delete', view(WRLAHelper::getViewPath('components.browse-actions.delete-button'), [
                    'manageableModel' => $manageableModel
                ]));
            }
        // If trashed
        } else {
            if($manageableModel::permissions()->hasPermission(WRLAPermissions::RESTORE)) {
                $browseActions->put('restore', view(WRLAHelper::getViewPath('components.browse-actions.restore-button'), [
                    'manageableModel' => $manageableModel
                ]));
            }

            if($manageableModel::permissions()->hasPermission(WRLAPermissions::DELETE)) {
                $browseActions->put('delete', view(WRLAHelper::getViewPath('components.browse-actions.delete-button'), [
                    'manageableModel' => $manageableModel,
                    'text' => 'Permanent Delete',
                    'permanent' => true
                ]));
            }
        }

        return $browseActions;
    }

    /**
     * Get browse filters (Applies to browse page)
     *
     * @return Collection
     */
    public static function getBrowseFilters(): Collection {
        return collect([
            'search' => new Filterable(
                // Field
                Text::make(null, 'search')
                    ->setLabel(null)
                    ->setAttributes([
                        'wire:model.live.debounce.400ms'=> 'filters.search',
                        'autofocus' => true,
                        'placeholder' => 'Search filter...',
                        'style' => 'margin-top: 0px;'
                    ]),

                // Applicable filter
                function(Builder $query, $columns, $value) {
                    return $query->where(function($query) use($columns, $value) {
                        foreach($columns as $column => $label) {
                            // If column is relationship, then modify the column to be the related column
                            if(strpos($column, '::') !== false) {
                                $parts = explode('::', $column);
                                $relationship = explode('.', $parts[1]);
                                $column = $relationship[0] . '.' . $relationship[1];
                            }
        
                            $query->orWhere($column, 'like', '%'.$value.'%');
                        }
                    });
                }
            ),
            'softDeleted' => new Filterable(
                // Field
                Select::make(null, 'softDeleted')
                    ->setLabel(null)
                    ->setItems([
                        'not_trashed' => 'Not Trashed',
                        'trashed' => 'Trashed Only',
                        'all' => 'All',
                    ])
                    ->default('not_trashed')
                    ->setAttributes([
                        'wire:model.live' => 'filters.softDeleted',
                        'style' => 'margin-top: 0px;'
                    ])
                    ->validation('required|in:all,trashed,not_trashed'),

                // Applicable filter
                function(Builder $query, $columns, $value) {
                    if($value === 'not_trashed') {
                        return $query->whereNull('deleted_at');
                    } else if($value === 'trashed') {
                        return $query->onlyTrashed();
                    } else if($value == 'all') {
                        return $query->withTrashed();
                    }
                    return $query;
                }
            )
        ]);
    }

    /**
     * Virtual get manageable fields method.
     *
     * @return Collection
     */
    public function getManageableFields(): Collection
    {
        return collect();
    }

    /**
     * Get a json value with -> notation, eg: column->key1->key2
     *
     * @param string $key The key in the json value.
     * @return mixed The value retrieved from the json.
     */
    public function getInstanceJsonValue(string $key): mixed
    {
        // Get the model instance
        $modelInstance = $this->getModelInstance();

        $parts = explode('->', $key); // Split the key into parts using '->' as the delimiter.
        $column = $parts[0]; // The first part is the column name.
        $dotNotation = implode('.', array_slice($parts, 1)); // The remaining parts are the dot notation.

        // Return the value from the json.
        return data_get(json_decode($modelInstance->{$column}, true), $dotNotation);
    }

    /**
     * Get validation rules
     *
     * @return Collection
     */
    public function getValidationRules(): Collection
    {
        $manageableFields = $this->getManageableFields();

        $validationRules = collect();

        foreach ($manageableFields as $manageableField) {
            $validationRules->put($manageableField->getAttribute('name'), $manageableField->validationRules);
        }

        return $validationRules;
    }

    /**
     * Get form fields values array
     *
     * @return array
     */
    public function getFormFieldsKeyValues(): array
    {
        $manageableFields = $this->getManageableFields();

        $formFieldsValues = [];

        foreach ($manageableFields as $manageableField) {
            $formFieldsValues[$manageableField->getAttribute('name')] = $manageableField->getAttribute('value');
        }

        return $formFieldsValues;
    }

    /**
     * Run inline validation on manageable fields
     *
     * @param Request $request
     * @return true|array If true then validation passed, if array (passed as [attribute.name => error]) then validation failed
     */
    public function runInlineValidation(Request $request): true|array
    {
        $manageableFields = $this->getManageableFields();

        $messageBag = [];

        foreach ($manageableFields as $manageableField) {
            // If doesn't have inline validation then skip
            if (empty($manageableField->inlineValidationRules)) {
                continue;
            }

            // Run inline validation on the manageable field. If true then skip, if string message then fail
            $ifMessageThenFail = $manageableField->runInlineValidation($request->input($manageableField->getAttribute('name')));

            // Now we pass back the attribute.name and message
            if($ifMessageThenFail !== true) {
                $messageBag[$manageableField->getAttribute('name')] = $manageableField->getLabel() . ': ' . $ifMessageThenFail;
            }
        }

        return empty($messageBag) ? true : $messageBag;
    }

    /**
     * Update the properties of the model instance based on the request and form data.
     *
     * @param Request $request The HTTP request object.
     * @param Collection $formComponents The collection of form components.
     * @param array $formKeyValues The array of form key-value pairs.
     * @return bool|MessageBag Returns result of success, or a MessageBag of errors
     */
    public function updateModelInstanceProperties(Request $request, Collection $formComponents, array $formKeyValues): bool|MessageBag
    {
        // Perform any necessary actions before updating the model instance
        $this->postUpdateModelInstance($request);

        // Check id request has any values that start with wrla_remove_ and apply to formKeyValues if so
        if (count($request->all()) > 0) {
            // Collect all keys that start with wrla_remove_ and have a value of true
            $removeKeys = collect($request->all())->filter(function ($value, $key) {
                return strpos($key, 'wrla_remove_') === 0 && $value === 'true';
            });

            // If there are any keys to remove, set the special WRLA_KEY_REMOVE constant value and unset the original key
            if ($removeKeys->count() > 0) {
                $removeKeys->each(function ($value, $key) use(&$formKeyValues) {
                    $keyWithoutRemovePrefix = ltrim($key, 'wrla_remove_');
                    $formKeyValues[$keyWithoutRemovePrefix] = ManageableField::WRLA_KEY_REMOVE;
                    unset($formKeyValues[$key]);
                });
            }
        }

        // Get the manageable fields for the model
        $manageableFields = $this->getManageableFields();

        // First do a loop through to check for any field names that use -> notation for nested json, if
        // we find any we put these last in the loop so that their values can be applied after everything else
        $manageableFields = $manageableFields->sortBy(function ($manageableField) {
            return strpos($manageableField->getAttribute('name'), '->') !== false;
        })->values()->toArray();

        // Iterate over each manageable field
        foreach ($manageableFields as $manageableField) {
            $fieldName = $manageableField->getAttribute('name');

            // Get the form component by name
            $formComponent = $formComponents->first(function ($_formComponent) use ($fieldName) {
                return $_formComponent->getAttribute('name') === $fieldName;
            });

            // Check if the field name is based on a JSON column
            $isUsingNestedJson = false;
            if (strpos($fieldName, '->') !== false) {
                // If form key does not exist, then skip
                if (!array_key_exists($formComponent->getAttribute('name'), $formKeyValues)) {
                    continue;
                }

                $isUsingNestedJson = true;
                [$fieldName, $dotNotation] = WRLAHelper::parseJsonNotation($fieldName);
                $newValue = $formKeyValues[$formComponent->getAttribute('name')];
            }

            // Check if the field name exists in the form key-value pairs
            if (array_key_exists($fieldName, $formKeyValues)) {
                if (!$isUsingNestedJson) {
                    // Apply the value to the form component and get the field value
                    $fieldValue = $formComponent->applySubmittedValue($request, $formKeyValues[$fieldName]);
                } else {
                    // Apply the value to the form component and get the new value
                    $newValue = $formComponent->applySubmittedValue($request, $newValue);

                    // Set the new value using dot notation on the field value
                    data_set($fieldValue, $dotNotation, $newValue);

                    // Convert the field value to JSON
                    if($newValue instanceof MessageBag) {
                        return $newValue;
                    } else {
                        $fieldValue = json_encode($fieldValue, JSON_UNESCAPED_SLASHES);
                    }
                }

                // If field value an error bag something has failed, so return it instead of updating the model instance
                if ($fieldValue instanceof MessageBag) {
                    return $fieldValue;
                } else {
                    // Update the field value of the model instance
                    $this->modelInstance->$fieldName = $fieldValue;
                }
            }
        }

        return true;
    }

    /**
     * Post update model instance hook
     *
     * @return void
     */
    public function postUpdateModelInstance(Request $request): void
    {
        // Override this method in your model to add custom logic after updating the model instance
    }

    /**
     * Is model soft deletable
     *
     * @return bool
     */
    public static function isSoftDeletable(): bool
    {
        if(static::$cache['isSoftDeletable'] == null) {
            // Get whether base model has SoftDeletes trait
            static::$cache['isSoftDeletable'] = in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses(static::$baseModelClass));
        }

        return static::$cache['isSoftDeletable'];
    }

    /**
     * Is being created.
     *
     * @return bool
     */
    public function isBeingCreated(): bool
    {
        return $this->getModelInstance()->id == null;
    }
}
