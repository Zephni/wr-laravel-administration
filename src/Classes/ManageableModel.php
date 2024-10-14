<?php

namespace WebRegulate\LaravelAdministration\Classes;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\MessageBag;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\ComponentAttributeBag;
use WebRegulate\LaravelAdministration\Enums\PageType;
use WebRegulate\LaravelAdministration\Traits\ManageableField;
use WebRegulate\LaravelAdministration\Classes\ManageableFields\Text;
use WebRegulate\LaravelAdministration\Classes\ManageableFields\Select;
use WebRegulate\LaravelAdministration\Enums\ManageableModelPermissions;
use WebRegulate\LaravelAdministration\Classes\BrowseColumns\BrowseColumn;
use WebRegulate\LaravelAdministration\Classes\BrowseColumns\BrowseColumnBase;
use WebRegulate\LaravelAdministration\Classes\NavigationItems\NavigationItem;
use WebRegulate\LaravelAdministration\Classes\NavigationItems\NavigationItemManageableModel;

abstract class ManageableModel
{
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
     * Cache.
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

        // Set static options in global
        WRLAHelper::$globalManageableModelData[static::class] = [
            'baseModelClass' => null,
            'permissions' => [
                ManageableModelPermissions::CREATE->getString() => true,
                ManageableModelPermissions::BROWSE->getString() => true,
                ManageableModelPermissions::EDIT->getString() => true,
                ManageableModelPermissions::DELETE->getString() => true,
                ManageableModelPermissions::RESTORE->getString() => true,
            ],
            'urlAlias' => 'model',
            'displayName' => [
                'singular' => 'Model',
                'plural' => 'Models',
            ],
            'icon' => 'fa fa-cube',
            'hideFromNavigation' => false,
        ];
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
            $this->setModelInstance(new (static::getBaseModelClass()));
        // If model ID is passed, get the model instance by ID
        } elseif (is_numeric($modelInstanceOrId)) {
            $this->setModelInstance(static::getBaseModelClass()::find($modelInstanceOrId));
        // If model instance (extends base model) is passed, set it as the model instance
        } else if ($modelInstanceOrId instanceof (static::getBaseModelClass())) {
            $this->setModelInstance($modelInstanceOrId);
        }
    }

    /**
     * Make a static version of the constructor and load the instance setup.
     *
     * @param mixed|int|null $modelInstance
     * @return static
     */
    public static function make($modelInstanceOrId = null): static
    {
        return new static($modelInstanceOrId);
    }

    /**
     * Main setup.
     *
     * @return void
     */
    public abstract static function mainSetup(): void;

    /**
     * Static setup.
     *
     * @return void
     */
    public abstract static function globalSetup(): void;

    /**
     * Get static option.
     *
     * @param string $staticOptionKey The option key using dot notation.
     * @return mixed
     */
    public static function getStaticOption(string $class, string $staticOptionKey): mixed
    {
        return data_get(WRLAHelper::$globalManageableModelData[$class], $staticOptionKey);
    }

    /**
     * Get static options.
     *
     * @return array
     */
    public static function getStaticOptions(string $class): array
    {
        return WRLAHelper::$globalManageableModelData[$class];
    }

    /**
     * Set static option.
     *
     * @param string $staticOptionKey The option key using dot notation.
     * @param mixed $value The value to set.
     * @return void
     */
    public static function setStaticOption(string $staticOptionKey, mixed $value)
    {
        // data_set(static::$staticOptions, $staticOptionKey, $value);
        data_set(WRLAHelper::$globalManageableModelData, static::class.'.'.$staticOptionKey, $value);
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
     * Set permission.
     *
     * @param string $permission
     * @param bool|callable $value
     * @return void
     */
    public static function setPermission(string $permission, bool|callable $value): void
    {
        static::setStaticOption('permissions.'.$permission, $value);
    }

    /**
     * Get permission.
     *
     * @param string $permission
     * @return bool
     */
    public static function getPermission(string $permission): bool
    {
        $value = static::getStaticOption(static::class, 'permissions.'.$permission);

        if(is_callable($value)) {
            return $value();
        }

        return $value;
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
            return $manageableModel::getStaticOption($manageableModel::class, 'baseModelClass') === $modelClass;
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
     * Get manageable model class.
     *
     * @return string
     */
    public static function getManageableModelClass(): string
    {
        return static::class;
    }

    /**
     * Get the base model for the manageable model.
     *
     * @return string
     */
    public static function getBaseModelClass(): string
    {
        return static::getStaticOption(static::class, 'baseModelClass');
    }

    /**
     * Initialise a query builder for the base model
     *
     * @return Builder
     */
    public static function initialiseQueryBuilder(): Builder
    {
        return static::getBaseModelClass()::query();
    }

    /**
     * Get navigation item for this manageable model
     *
     * @return NavigationItemManageableModel
     */
    public static function getNavigationItem(): NavigationItemManageableModel
    {
        return new NavigationItemManageableModel(static::class);
    }

    /**
     * Set base model class.
     *
     * @return string
     */
    public static function setBaseModelClass(string $baseModelClass): string
    {
        static::setStaticOption('baseModelClass', $baseModelClass);
        return static::class;
    }

    /**
     * Set URL alias.
     *
     * @return string
     */
    public static function setUrlAlias(string $urlAlias): string
    {
        static::setStaticOption('urlAlias', $urlAlias);
        return static::class;
    }

    /**
     * Set display name. If either singular or plural version is left null, a human readable version of the class name will be generated.
     *
     * @return string
     */
    public static function setDisplayName(?string $displayNamesingular = null, ?string $displayNamePlural = null): string
    {
        if($displayNamesingular == null) {
            $displayNamesingular = str(class_basename(static::class))->kebab()->replace('-', ' ')->title()->singular()->toString();
        }

        if($displayNamePlural == null) {
            $displayNamePlural = str($displayNamesingular)->plural()->toString();
        }

        static::setStaticOption('displayName.singular', $displayNamesingular);
        static::setStaticOption('displayName.plural', $displayNamePlural);
        return static::class;
    }

    /**
     * Set URL alias.
     *
     * @return string
     */
    public static function setIcon(string $icon): string
    {
        static::setStaticOption('icon', $icon);
        return static::class;
    }

    /**
     * Set child navigation items.
     *
     * @param Collection|array $childNavigationItems
     */
    public static function setChildNavigationItems(Collection|array $childNavigationItems): void
    {
        // If collection turn into array
        if($childNavigationItems instanceof Collection) {
            $childNavigationItems = $childNavigationItems->toArray();
        }

        static::setStaticOption('navigation.children', $childNavigationItems);
    }

    /**
     * Set order by for browse page.
     *
     * @param string $column
     * @param string $direction
     */
    public static function setOrderBy(string $column = 'id', string $direction = 'desc'): void
    {
        static::setStaticOption('defaultOrderBy.column', $column);
        static::setStaticOption('defaultOrderBy.direction', $direction);
    }

    /**
     * Set browse filters.
     *
     * @param Collection|array $filters
     */
    public static function setBrowseFilters(Collection|array $filters)
    {
        // If collection turn into array
        if($filters instanceof Collection) {
            $filters = $filters->toArray();
        }

        static::setStaticOption('browse.filters', $filters);
    }

    /**
     * Set browse actions.
     *
     * @param Collection|array $actions
     */
    public static function setBrowseActions(Collection|array $actions)
    {
        // If collection turn into array
        if($actions instanceof Collection) {
            $actions = $actions->toArray();
        }

        static::setStaticOption('browse.actions', $actions);
    }

    /**
     * Get the display name for the manageable model.
     *
     * @param bool $plural
     * @return string
     */
    public static function getDisplayName(bool $plural = false): string
    {
        return static::getStaticOption(static::class, 'displayName.' . (!$plural ? 'singular' : 'plural'));
    }

    /**
     * Get the icon for the manageable model.
     *
     * @return string
     */
    public static function getIcon(): string
    {
        return static::getStaticOption(static::class, 'icon');
    }

    /**
     * Get URL alias.
     *
     * @return string
     */
    public static function getUrlAlias(): string
    {
        return static::getStaticOption(static::class, 'urlAlias');
    }

    /**
     * Get child navigation items.
     *
     * @return Collection
     */
    public static function getChildNavigationItems(): Collection
    {
        return collect(static::getStaticOption(static::class, 'navigation.children'));
    }

    /**
     * Get default child navigation items
     *
     * @var Collection
     */
    public static function getDefaultChildNavigationItems(): Collection {
        return collect([
            new NavigationItem(
                'wrla.manageable-models.browse',
                ['modelUrlAlias' => static::getStaticOption(static::class, 'urlAlias')],
                'Browse',
                'fa fa-list'
            ),
            new NavigationItem(
                'wrla.manageable-models.create',
                ['modelUrlAlias' => static::getStaticOption(static::class, 'urlAlias')],
                'Create',
                'fa fa-plus'
            )
        ]);
    }

    /**
     * Get browse actions
     *
     * @return Collection
     */
    public static function getDefaultBrowseActions(): Collection {
        $browseActions = collect();

        // $manageableModel = static::make(); // This makes everything crash with bytes exhausted error

        // Check has create permission
        if(static::getPermission(ManageableModelPermissions::CREATE->getString())) {
            $browseActions->put(-10, view(WRLAHelper::getViewPath('components.forms.button'), [
                'text' => 'Create ' . static::getDisplayName(),
                'icon' => 'fa fa-plus',
                'color' => 'teal',
                'size' => 'small',
                'href' => route('wrla.manageable-models.create', ['modelUrlAlias' => static::getStaticOption(static::class, 'urlAlias')])
            ]));
        }

        // At index 50 we put a forced gap to display any item after this on the right side
        $browseActions->put(50, <<<HTML
            <div class="ml-auto"></div>
        HTML);

        // Import Data
        $browseActions->put(51, view(WRLAHelper::getViewPath('components.forms.button'), [
            'text' => 'Import Data',
            'icon' => 'fa fa-file-import',
            'color' => 'teal',
            'size' => 'small',
            'attributes' => new ComponentAttributeBag([
                'onclick' => "window.loadLivewireModal(this, 'import-data-modal', {
                    manageableModelClass: '".str(static::class)->replace('\\', '\\\\')."'
                });"
            ])
        ]));

        // Export as CSV
        $browseActions->put(52, view(WRLAHelper::getViewPath('components.forms.button'), [
            'text' => 'Export CSV',
            'icon' => 'fa fa-file-csv',
            'color' => 'teal',
            'size' => 'small',
            'attributes' => new ComponentAttributeBag([
                'wire:click' => 'exportAsCSVAction'
            ])
        ]));

        return $browseActions;
    }

    /**
     * Get browse filters.
     *
     * @return Collection
     */
    public static function getBrowseFilters(): Collection {
        return collect(static::getStaticOption(static::class, 'browse.filters'));
    }

    /**
     * Get browse actions.
     *
     * @return Collection
     */
    public static function getBrowseActions(): Collection {
        return collect(static::getStaticOption(static::class, 'browse.actions'))->sortKeys();
    }

    /**
     * Get default order by.
     *
     * @return array
     */
    public static function getDefaultOrderBy(): Collection
    {
        return collect([
            'column' => static::getStaticOption(static::class, 'defaultOrderBy.column'),
            'direction' => static::getStaticOption(static::class, 'defaultOrderBy.direction'),
        ]);
    }

    /**
     * Get item actions, such as edit, delete.
     *
     * @param mixed $model
     * @return Collection
     */
    public function getDefaultInstanceActions(): Collection {
        // Get current page type and set browse actions to empty collection
        $currentPageType = WRLAHelper::getCurrentPageType();
        $browseActions = collect();

        // If create page, return empty collection
        if($currentPageType == PageType::CREATE) {
            return $browseActions;
        }

        // Get model instance
        $model = $this->getModelInstance();

        // If model doesn't have soft deletes and not trashed
        if(!static::isSoftDeletable() || $model->deleted_at == null) {
            // Check has edit permission
            if(static::getPermission(ManageableModelPermissions::EDIT->getString())) {
                $browseActions->put('edit', view(WRLAHelper::getViewPath('components.browse-actions.edit-button'), [
                    'manageableModel' => $this
                ]));
            }

            // Check has delete permission
            if(static::getPermission(ManageableModelPermissions::DELETE->getString())) {
                $browseActions->put('delete', view(WRLAHelper::getViewPath('components.browse-actions.delete-button'), [
                    'manageableModel' => $this
                ]));
            }
        // If trashed
        } else {
            // Check has restore permission
            if(static::getPermission(ManageableModelPermissions::RESTORE->getString())) {
                $browseActions->put('restore', view(WRLAHelper::getViewPath('components.browse-actions.restore-button'), [
                    'manageableModel' => $this
                ]));
            }

            // Check has delete permission
            if(static::getPermission(ManageableModelPermissions::DELETE->getString())) {
                $browseActions->put('delete', view(WRLAHelper::getViewPath('components.browse-actions.delete-button'), [
                    'manageableModel' => $this,
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
    public static function getDefaultBrowseFilters(): Collection {
        return collect([
            'searchFilter' => new BrowseFilter(
                // Field
                Text::makeBrowseFilter('searchFilter')
                    ->setLabel('Search', 'fas fa-search text-slate-400')
                    ->setOption('containerClass', 'w-1/3')
                    ->setAttributes([
                        'autofocus' => true,
                        'placeholder' => 'Search filter...'
                    ]),

                // Apply filter
                function(Builder $query, $table, $columns, $value) {
                    return $query->where(function($query) use($table, $columns, $value) {
                        foreach($columns as $column => $label) {
                            // If column is relationship, then modify the column to be the related column
                            if((WRLAHelper::isBrowseColumnRelationship($column))) {
                                $relationshipParts = WRLAHelper::parseBrowseColumnRelationship($column);

                                $baseModelClass = self::getBaseModelClass();
                                $relationshipTableName = (new $baseModelClass)->{$relationshipParts[0]}()->getRelated()->getTable();

                                $query->orWhereRelation($relationshipParts[0], "{$relationshipTableName}.{$relationshipParts[1]}", 'like', "%{$value}%");
                            // Otherwise just use the table and column
                            } else {
                                $column = "$table.$column";
                                $query->orWhere($column, 'like', "%{$value}%");
                            }
                        }
                    });
                }
            ),
            'softDeletedFilter' => new BrowseFilter(
                // Field
                Select::makeBrowseFilter('softDeletedFilter')
                    ->setLabel('Status', 'fas fa-heartbeat text-slate-400 !mr-1')
                    ->setItems([
                        'not_trashed' => 'Active only',
                        'trashed' => 'Soft deleted only',
                        'all' => 'All',
                    ])
                    ->setOption('containerClass', 'w-1/6')
                    ->validation('required|in:all,trashed,not_trashed'),

                // Apply filter
                function(Builder $query, $table, $columns, $value) {
                    if($value === 'not_trashed') {
                        return $query;
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
     * Get manageable fields method.
     *
     * @return array
     */
    public function getManageableFields(): array
    {
        throw new \Exception('getManageableFields() method needs to be overriden within: '.static::class);
    }

    /**
     * Get the browsable columns from options.
     *
     * @return array
     */
    public function getBrowseColumns(): array
    {
        throw new \Exception('getBrowseColumns() method needs to be overriden within: '.static::class);
    }

    public function getBrowseColumnsFinal(): Collection
    {
        // If any of the values are strings, we convert into BrowseColumn instances
        return collect($this->getBrowseColumns())->map(function($value, $key) {
            if($value == null) {
                return null;
            }

            // Determine whether $value is already a BrowseColumn instance
            $valueIsBrowseColumn = $value instanceof BrowseColumnBase;

            // If value is not a BrowseColumn instance, then we convert it into a basic string BrowseColumn
            return $valueIsBrowseColumn ? $value : BrowseColumn::make($value, 'string');
        });
    }

    /**
     * Get instance actions.
     * 
     * @return Collection
     */
    public function getInstanceActions(Collection $instanceActions): Collection
    {
        throw new \Exception('getInstanceActions($instanceActions) method needs to be overriden within: '.static::class);
    }

    public final function getInstanceActionsFinal(): Collection
    {
        return $this->getInstanceActions($this->getDefaultInstanceActions());
    }

    /**
     * Get a json value with -> notation, eg: column->key1->key2
     *
     * @param string $key The key in the json value.
     * @return mixed The value retrieved from the json.
     */
    public function getInstanceJsonValue(string $key, ?Model $overrideInstance = null): mixed
    {
        // Get the model instance
        $modelInstance = $overrideInstance ?? $this->getModelInstance();

        $parts = explode('->', $key); // Split the key into parts using '->' as the delimiter.
        $column = $parts[0]; // The first part is the column name.
        $dotNotation = implode('.', array_slice($parts, 1)); // The remaining parts are the dot notation.

        // Return the value from the json.
        return data_get(json_decode($modelInstance->{$column}, true), $dotNotation);
    }

    /**
     * Get value from model relationship, note this must also check whether uses -> notation for nested json
     *
     * @param string $key The key, eg. relationship.column or relationship.column->key
     * @return mixed The value retrieved from the relationship
     */
    public function getInstanceRelationValue(string $key): mixed
    {
        // Get the model instance
        $modelInstance = $this->getModelInstance();

        // Get relationship parts
        $relationshipParts = WRLAHelper::parseBrowseColumnRelationship($key);
        $relationshipName = $relationshipParts[0];
        $key = $relationshipParts[1];
        $relatedModel = $modelInstance->{$relationshipName};

        // If null, return
        if($relatedModel == null) return null;

        // If doesn't have -> notation then we can just return the relationship value
        if(strpos($key, '->') === false) return $relatedModel->{$key};

        // If has -> notation then we need to get the json value from the relationship
        return $this->getInstanceJsonValue($key, $relatedModel);
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

            // Get field name. If contains WRLA_REL_DOT then convert to . notation
            $fieldName = $manageableField->getAttribute('name');

            // Get input value
            $inputValue = $request->input($fieldName);

            // Run inline validation on the manageable field. If true then skip, if string message then fail
            $ifMessageThenFail = $manageableField->runInlineValidation($inputValue);

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
     * @param array $formComponents The array of form components.
     * @param array $formKeyValues The array of form key-value pairs.
     * @return bool|MessageBag Returns result of success, or a MessageBag of errors
     */
    public function updateModelInstanceProperties(Request $request, array $formComponents, array $formKeyValues): bool|MessageBag
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

        // Get the manageable fields for the model, and get form components as a collection
        $manageableFields = $this->getManageableFields();
        $formComponents = collect($formComponents);

        // First do a loop through to check for any field names that use -> notation for nested json, if
        // we find any we put these last in the loop so that their values can be applied after everything else
        // $manageableFields = $manageableFields->sortBy(function ($manageableField) {
        //     return strpos($manageableField->getAttribute('name'), '->') !== false;
        // })->values()->toArray();

        // Iterate over each manageable field
        foreach ($manageableFields as $manageableField) {
            $fieldName = $manageableField->getAttribute('name');
            $relationshipInstance = null;

            // If manageable field is relationship, we need to temporarily deal with the relationship instance instead
            if($manageableField->isRelationshipField()) {
                $relationshipInstance = $manageableField->getRelationshipInstance();
                // dump($fieldName, $manageableField->getRelationshipFieldName(), $relationshipInstance->{$manageableField->getRelationshipFieldName()});
            }

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

            $fieldValue = '';

            if (!$isUsingNestedJson) {
                if (array_key_exists($fieldName, $formKeyValues)) {
                    // Apply the value to the form component and get the field value
                    $fieldValue = $formComponent->applySubmittedValue($request, $formKeyValues[$fieldName]);
                }
            } else {
                // If is relation, get current field value from relationship instance
                if($relationshipInstance != null) {
                    $fieldValue = json_decode($relationshipInstance->{$manageableField->getRelationshipFieldName()});
                }

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
                // If relationship instance is set, we need to update and save now
                if($relationshipInstance != null) {
                    $relationshipInstance->{$manageableField->getRelationshipFieldName()} = $fieldValue;
                    // dump($fieldName, $manageableField->getRelationshipFieldName(), $fieldValue);
                    $relationshipInstance->save();
                } else {
                    // Update the field value of the model instance
                    $this->modelInstance->{$fieldName} = $fieldValue;
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
     * Post delete model instance hook
     *
     * @return void
     */
    public function postDeleteModelInstance(Request $request, int $oldId, bool $soft): void
    {
        // Override this method in your model to add custom logic after deleting the model instance
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
            static::$cache['isSoftDeletable'] = in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses(static::getBaseModelClass()));
        }

        return static::$cache['isSoftDeletable'] ?? false;
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

    /**
     * Get specific validation rule
     *
     * @return string
     */
    public function getValidationRule($column): string
    {
        return $this->getValidationRules()->get($column);
    }

    /**
     * Does one of the manageable fields have use a wysiwyg editor
     *
     * @return bool
     */
    public function usesWysiwyg(): bool
    {
        foreach($this->getManageableFields() as $manageableField) {
            if($manageableField->getType() == 'Wysiwyg') {
                return true;
            }
        }

        return false;
    }
}
