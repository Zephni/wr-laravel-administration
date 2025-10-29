<?php

namespace WebRegulate\LaravelAdministration\Classes;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\MessageBag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Blade;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\ComponentAttributeBag;
use WebRegulate\LaravelAdministration\Enums\PageType;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;
use WebRegulate\LaravelAdministration\Classes\ManageableFields\Text;
use WebRegulate\LaravelAdministration\Classes\ManageableFields\Select;
use WebRegulate\LaravelAdministration\Enums\ManageableModelPermissions;
use WebRegulate\LaravelAdministration\Classes\BrowseColumns\BrowseColumn;
use WebRegulate\LaravelAdministration\Enums\AdditionalRenderPosition;
use WebRegulate\LaravelAdministration\Classes\BrowseColumns\BrowseColumnBase;
use WebRegulate\LaravelAdministration\Classes\NavigationItems\NavigationItem;
use WebRegulate\LaravelAdministration\Classes\NavigationItems\NavigationItemManageableModel;

abstract class ManageableModel
{
    /**
     * Base model instance
     */
    private mixed $modelInstance = null;

    /**
     * Collection of manageable models, pushed to in the register method which is called within the serice provider.
     */
    public static ?Collection $manageableModels = null;

    /**
     * Number of renders, used in browse and upsert livewire components
     */
    public static int $numberOfRenders = 0;

    /**
     * Livewire fields
     */
    public static array $livewireFields = [];

    /**
     * Browse filter fields
     */
    public static array $browseFilterFields = [];

    /**
     * Registered instance actions
     */
    public array $registeredInstanceActions = [];

    /**
     * Uses wysiwyg editor
     */
    public bool $usesWysiwygEditor = false;

    /**
     * Model instance cache using {manageable model class} => [{model id} => {model instance}]
     */
    public static array $modelInstanceCache = [];

    /**
     * Register the manageable model.
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
                ManageableModelPermissions::ENABLED->value => self::getDefaultPermission(ManageableModelPermissions::ENABLED),
                ManageableModelPermissions::CREATE->value => self::getDefaultPermission(ManageableModelPermissions::CREATE),
                ManageableModelPermissions::BROWSE->value => self::getDefaultPermission(ManageableModelPermissions::BROWSE),
                ManageableModelPermissions::EDIT->value => self::getDefaultPermission(ManageableModelPermissions::EDIT),
                ManageableModelPermissions::DELETE->value => self::getDefaultPermission(ManageableModelPermissions::DELETE),
                ManageableModelPermissions::RESTORE->value => self::getDefaultPermission(ManageableModelPermissions::RESTORE),
                ManageableModelPermissions::SHOW_IN_NAV->value => self::getDefaultPermission(ManageableModelPermissions::SHOW_IN_NAV),
            ],
            'urlAlias' => 'model',
            'displayName' => [
                'singular' => 'Model',
                'plural' => 'Models',
            ],
            'rememberFilters' => [
                'enabled' => true,
                'mode' => 'session', // At the moment only 'session' is supported
            ],
            'icon' => 'fa fa-cube',
            'hideFromNavigation' => false,
        ];
    }

    public static function getDefaultPermission(ManageableModelPermissions $permission): bool|callable
    {
        return config('wr-laravel-administration.default_manageable_model_permissions.'.$permission->value, true);
    }

    /**
     * If model instance is passed (must be an instance of the base model), set it as the model instance.
     * If model ID is passed, get the model instance by ID.
     * Otherwise, set the model instance to a new instance of the base model.
     *
     * @param  null|int|mixed  $modelInstance
     */
    public function __construct($modelInstanceOrId = null, bool $withTrashed = false)
    {
        // If model instance or id is null, set the model instance to a new instance of the base model
        if ($modelInstanceOrId == null) {
            $this->setModelInstance(new (static::getBaseModelClass()));
        }
        // If model ID is passed, get the model instance by ID
        elseif (is_numeric($modelInstanceOrId)) {
            // If static class not found, add array
            if (!array_key_exists(static::class, self::$modelInstanceCache)) {
                self::$modelInstanceCache[static::class] = [];
            }

            // If array key does not exist for this model id, find it and cache it
            if(!array_key_exists($modelInstanceOrId, self::$modelInstanceCache[static::class]) ) {
                // If not attempting to find with trashed OR model does not have soft deletes
                if (!$withTrashed || !WRLAHelper::isSoftDeletable(static::getBaseModelClass())) {
                    $modelInstance = static::getBaseModelClass()::find($modelInstanceOrId);
                } else {
                    $modelInstance = static::getBaseModelClass()::withTrashed()->find($modelInstanceOrId);
                }
                
                // If model instance is not found, throw an exception
                if ($modelInstance == null) {
                    throw new \Exception("Model instance with ID {$modelInstanceOrId} not found for manageable model ".static::class);
                }

                // Cache the model instance
                self::$modelInstanceCache[static::class][$modelInstanceOrId] = $modelInstance;
            }

            // Set model instance
            $this->setModelInstance(self::$modelInstanceCache[static::class][$modelInstanceOrId]);
        }
        // If model instance (extends base model) is passed, set it as the model instance
        elseif ($modelInstanceOrId instanceof (static::getBaseModelClass())) {
            $this->setModelInstance($modelInstanceOrId);
        }
    }

    /**
     * Make a static version of the constructor and load the instance setup.
     *
     * @param  mixed|int|null  $modelInstance
     */
    public static function make($modelInstanceOrId = null): static
    {
        return new static($modelInstanceOrId);
    }

    /**
     * Abstract: Main setup.
     */
    abstract public static function mainSetup(): void;

    /**
     * Abstract: Global setup.
     */
    abstract public static function globalSetup(): void;

    /**
     * Abstract: Browse setup.
     */
    abstract public static function browseSetup(): void;

    /**
     * URL edit
     */
    public static function urlBrowse(array $filters = []): string
    {
        return route('wrla.manageable-models.browse', [
            'modelUrlAlias' => static::getUrlAlias(),
            'preFilters' => $filters,
        ]);
    }

    /**
     * URL edit
     */
    public static function urlCreate(): string
    {
        return route('wrla.manageable-models.create', [
            'modelUrlAlias' => static::getUrlAlias(),
        ]);
    }

    /**
     * URL edit
     */
    public static function urlEdit(int $id): string
    {
        return route('wrla.manageable-models.edit', [
            'modelUrlAlias' => static::getUrlAlias(),
            'id' => $id
        ]);
    }

    /**
     * Browse setup.
     *
     * @return void
     */
    public static function browseSetupFinal($browseFilterFields)
    {
        once(function () use ($browseFilterFields): void {
            self::$browseFilterFields = $browseFilterFields;
            static::browseSetup();
        });
    }

    /**
     * Get browse filter value.
     */
    public static function getBrowseFilterValue(string $key): mixed
    {
        return self::$browseFilterFields[$key] ?? null;
    }

    /**
     * Abstract: Get manageable fields method.
     */
    abstract public function getManageableFields(): array;

    /**
     * Abstract: Get the browsable columns from options.
     */
    abstract public function getBrowseColumns(): array;

    /**
     * Abstract: Get instance actions.
     */
    abstract public function getInstanceActions(Collection $instanceActions): Collection;

    /**
     * Set static field
     */
    public static function setLivewireField(string $key, mixed $value): void
    {
        self::$livewireFields[$key] = $value;
    }

    /**
     * Set static fields. For fields with attribute: wire:model.live="fields.field_name", passed to each field blade view (if applicable).
     */
    public static function setLivewireFields(array $fields): void
    {
        self::$livewireFields = $fields;
    }

    /**
     * Has field
     */
    public static function hasLivewireField(string $key): bool
    {
        return isset(ManageableModel::$livewireFields[$key]);
    }

    /**
     * Get field
     */
    public static function getLivewireField(string $key): mixed
    {
        return ManageableModel::$livewireFields[$key] ?? null;
    }

    /**
     * Get static option.
     *
     * @param  string  $staticOptionKey  The option key using dot notation.
     */
    public static function getStaticOption(string $class, string $staticOptionKey): mixed
    {
        return data_get(WRLAHelper::$globalManageableModelData[$class], $staticOptionKey);
    }

    /**
     * Get static options.
     */
    public static function getStaticOptions(string $class): array
    {
        return WRLAHelper::$globalManageableModelData[$class];
    }

    /**
     * Set static option.
     *
     * @param  string  $staticOptionKey  The option key using dot notation.
     * @param  mixed  $value  The value to set.
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
     * @param  mixed  $modelInstance
     */
    public function setModelInstance($modelInstance): ManageableModel
    {
        $this->modelInstance = $modelInstance;

        return $this;
    }

    /**
     * Get the base model instance.
     */
    public function getModelInstance(): mixed
    {
        return $this->modelInstance;
    }

    /**
     * Set permission.
     * @param $permission Permission key
     * @param bool|callable $value Value to set, can be a boolean or a callable that takes $wrlaUserData and returns a boolean.
     */
    public static function setPermission(mixed $permission, bool|callable $value): void
    {
        if (is_string($permission)) {
            $permissionKey = $permission;
            // If not string we assume ENUM here, so get string value
        } else {
            $permissionKey = $permission->value;
        }

        static::setStaticOption('permissions.'.$permissionKey, $value);
    }

    /**
     * Get permission.
     */
    public static function getPermission(mixed $permission): bool
    {
        if (is_string($permission)) {
            $permissionKey = $permission;
            // If not string we assume ENUM here, so get string value
        } else {
            $permissionKey = $permission->value;
        }

        $value = static::getStaticOption(static::class, 'permissions.'.$permissionKey);

        if (is_callable($value)) {
            return $value(WRLAHelper::getCurrentUserData()) ?? false;
        }

        return $value;
    }

    /**
     * Get manageable model by model class.
     */
    public static function getByModelClass(string $modelClass): mixed
    {
        return static::$manageableModels->first(fn ($manageableModel) => $manageableModel::getStaticOption($manageableModel::class, 'baseModelClass') === $modelClass);
    }

    /**
     * Get manageable model by URL alias.
     */
    public static function getByUrlAlias(string $urlAlias): mixed
    {
        $manageableModel = static::$manageableModels->first(fn ($manageableModel) => $manageableModel::getUrlAlias() === $urlAlias);

        return $manageableModel;
    }

    /**
     * Get manageable model class.
     */
    public static function getManageableModelClass(): string
    {
        return static::class;
    }

    /**
     * Get the base model for the manageable model.
     */
    public static function getBaseModelClass(): string
    {
        return static::getStaticOption(static::class, 'baseModelClass');
    }

    /**
     * Initialise a query builder for the base model
     */
    public static function initialiseQueryBuilder(): Builder
    {
        return static::getBaseModelClass()::query();
    }

    /**
     * Get navigation item for this manageable model
     */
    public static function getNavigationItem(): NavigationItemManageableModel
    {
        return new NavigationItemManageableModel(static::class);
    }

    /**
     * Set base model class.
     */
    public static function setBaseModelClass(string $baseModelClass): string
    {
        static::setStaticOption('baseModelClass', $baseModelClass);

        return static::class;
    }

    /**
     * Set URL alias.
     */
    public static function setUrlAlias(string $urlAlias): string
    {
        static::setStaticOption('urlAlias', $urlAlias);

        return static::class;
    }

    /**
     * Set display name. If either singular or plural version is left null, a human readable version of the class name will be generated.
     */
    public static function setDisplayName(?string $displayNamesingular = null, ?string $displayNamePlural = null): string
    {
        if ($displayNamesingular == null) {
            $displayNamesingular = str(class_basename(static::class))->kebab()->replace('-', ' ')->title()->singular()->toString();
        }

        if ($displayNamePlural == null) {
            $displayNamePlural = str($displayNamesingular)->plural()->toString();
        }

        static::setStaticOption('displayName.singular', $displayNamesingular);
        static::setStaticOption('displayName.plural', $displayNamePlural);

        return static::class;
    }

    /**
     * Set URL alias.
     */
    public static function setIcon(string $icon): string
    {
        static::setStaticOption('icon', $icon);

        return static::class;
    }

    /**
     * Set child navigation items.
     *
     * @param  Collection|array  $childNavigationItems
     */
    public static function setChildNavigationItems(...$childNavigationItems): void
    {
        $childNavigationItems = WRLAHelper::flattenArray($childNavigationItems);
        static::setStaticOption('navigation.children', $childNavigationItems);
    }

    /**
     * Set order by for browse page.
     */
    public static function setOrderBy(string $column = 'id', string $direction = 'desc'): void
    {
        static::setStaticOption('defaultOrderBy.column', $column);
        static::setStaticOption('defaultOrderBy.direction', $direction);
    }

    /**
     * Set pre query for browse page.
     * 
     * @param callable $preQuery A callable that takes a query builder and array filters as parameters and returns a modified query builder.
     */
    public static function setPreQuery(callable $preQuery): void
    {
        static::setStaticOption('browse.preQuery', $preQuery);
    }

    /**
     * Append pre query
     */
    public static function appendPreQuery(callable $preQuery): void
    {
        $existingPreQuery = static::getStaticOption(static::class, 'browse.preQuery');

        if (is_callable($existingPreQuery)) {
            // If preQuery is callable, create a new callable that calls both the existing and new preQuery
            $newPreQuery = function ($query, $filters) use ($existingPreQuery, $preQuery) {
                $query = $existingPreQuery($query, $filters);
                return $preQuery($query, $filters);
            };

            static::setStaticOption('browse.preQuery', $newPreQuery);

            return;
        }

        // If no existing preQuery, just set the new one
        static::setPreQuery($preQuery);
    }

    /**
     * Get pre query for browse page.
     */
    public static function processPreQuery(mixed $query, array $filters): mixed
    {
        $preQuery = static::getStaticOption(static::class, 'browse.preQuery');
        
        if (is_callable($preQuery)) {
            // If preQuery is callable, call it with the query builder
            return $preQuery($query, $filters);
        }

        // If preQuery is not callable, return the query as is
        return $query;
    }

    /**
     * Set browse filters.
     */
    public static function setBrowseFilters(...$filters)
    {
        $filters = WRLAHelper::flattenArray($filters);
        static::setStaticOption('browse.filters', $filters);
    }

    /**
     * Get dynamic browse filters.
     */
    public static function setDynamicBrowseFilters(array $defaultDynamicFilters = [])
    {
        static::setBrowseFilters();
        static::setStaticOption('browse.useDynamicFilters', true);
        static::setStaticOption('browse.defaultDynamicFilters', $defaultDynamicFilters);
    }

    /**
     * Remember filters, if true filters will be stored in local cookies.
     */
    public static function setRememberFilters(bool $rememberFilters)
    {
        static::setStaticOption('browse.rememberFilters.enabled', $rememberFilters);
    }

    /**
     * Set browse actions.
     */
    public static function setBrowseActions(... $browseActions)
    {
        $browseActions = WRLAHelper::flattenArray($browseActions);
        static::setStaticOption('browse.actions', $browseActions);
    }

    /**
     * Set browse additional rendering callable.
     * 
     * @param callable $preBrowseRender A callable that takes no parameters and returns html.
     */
    public static function setAdditionalRender(AdditionalRenderPosition $position, callable $preBrowseRender): void
    {
        static::setStaticOption("browse.additionalRendering.{$position->value}.callable", $preBrowseRender);
    }

    /**
     * Get the display name for the manageable model.
     */
    public static function getDisplayName(bool $plural = false): string
    {
        return static::getStaticOption(static::class, 'displayName.'.(! $plural ? 'singular' : 'plural'));
    }

    /**
     * Get the icon for the manageable model.
     */
    public static function getIcon(): string
    {
        return static::getStaticOption(static::class, 'icon');
    }

    /**
     * Get URL alias.
     */
    public static function getUrlAlias(): string
    {
        return static::getStaticOption(static::class, 'urlAlias');
    }

    /**
     * Get child navigation items.
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
    public static function getDefaultChildNavigationItems(): Collection
    {
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
            ),
        ]);
    }

    /**
     * Get browse actions
     */
    public static function getDefaultBrowseActions(): Collection
    {
        $browseActions = collect();

        // If has_access is false, return empty collection
        if (! static::getPermission(ManageableModelPermissions::ENABLED)) {
            return $browseActions;
        }

        // $manageableModel = static::make(); // This makes everything crash with bytes exhausted error

        // Check has create permission
        if (static::getPermission(ManageableModelPermissions::CREATE)) {
            $browseActions->put(-10, view(WRLAHelper::getViewPath('components.forms.button'), [
                'text' => 'Create '.static::getDisplayName(),
                'icon' => 'fa fa-plus',
                'color' => 'primary',
                'size' => 'small',
                'href' => route('wrla.manageable-models.create', ['modelUrlAlias' => static::getStaticOption(static::class, 'urlAlias')]),
            ]));
        }

        // At index 50 we put a forced gap to display any item after this on the right side
        $browseActions->put(50, <<<'HTML'
            <div class="ml-auto"></div>
        HTML);

        // Import Data
        if (static::getPermission(ManageableModelPermissions::CREATE)) {
            $browseActions->put(51, view(WRLAHelper::getViewPath('components.forms.button'), [
                'text' => 'Import Data',
                'icon' => 'fa fa-file-import',
                'color' => 'primary',
                'size' => 'small',
                'attributes' => new ComponentAttributeBag([
                    'onclick' => "window.loadLivewireModal(this, 'import-data-modal', {
                        manageableModelClass: '".str(static::class)->replace('\\', '\\\\')."'
                    });",
                ]),
            ]));
        }

        // Export as CSV
        $browseActions->put(52, view(WRLAHelper::getViewPath('components.forms.button'), [
            'text' => 'Export CSV',
            'icon' => 'fa fa-file-csv',
            'color' => 'primary',
            'size' => 'small',
            'attributes' => new ComponentAttributeBag([
                'wire:click' => 'exportAsCSVAction',
            ]),
        ]));

        return $browseActions;
    }

    /**
     * Get browse filters.
     */
    public static function getBrowseFilters(): Collection
    {
        return collect(static::getStaticOption(static::class, 'browse.filters'));
    }

    /**
     * Get browse actions.
     */
    public static function getBrowseActions(): Collection
    {
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
     * Get additional rendering callable.
     */
    public static function getAdditionalRender(AdditionalRenderPosition $position): ?callable
    {
        return static::getStaticOption(static::class, "browse.additionalRendering.{$position->value}.callable");
    }

    /**
     * Render additional rendering callable if set.
     */
    public static function renderAdditionalRender(AdditionalRenderPosition $position): string
    {
        $callable = static::getAdditionalRender($position);

        if (is_callable($callable)) {
            return (string) $callable();
        }

        return '';
    }

    /**
     * Get item actions, such as edit, delete.
     *
     * @param  mixed  $model
     */
    public function getDefaultInstanceActions(): Collection
    {
        // Initialise collection
        $instanceActions = collect();

        // If has_access is false, return empty collection
        if (! static::getPermission(ManageableModelPermissions::ENABLED)) {
            return $instanceActions;
        }

        // If create page, return empty collection
        if (WRLAHelper::getCurrentPageType() == PageType::CREATE) {
            return $instanceActions;
        }

        // Get model instance
        $model = $this->getModelInstance();

        // Get url alias and id
        $modelUrlAlias = static::getUrlAlias();
        $modelId = $model->id ?? null;

        // If soft deleted
        if (WRLAHelper::isSoftDeletable(static::getBaseModelClass()) && $model?->deleted_at != null) {
            // Restore
            $instanceActions->put('restore', InstanceAction::make($this, 'Restore', 'fa fa-undo', 'primary')
                ->enableOnCondition(static::getPermission(ManageableModelPermissions::RESTORE))
                ->setAdditionalAttributes([
                    'wire:click' => 'restoreModel('.$this->getModelInstance()->id.')',
                ])
            );

            // Permanent Delete
            $instanceActions->put('delete', InstanceAction::make($this, 'Delete', 'fa fa-trash', 'danger')
                ->enableOnCondition(static::getPermission(ManageableModelPermissions::DELETE))
                ->setAdditionalAttributes([
                    'title' => 'Permanent delete',
                    'x-on:click' => <<<JS
                        confirm('Are you sure you want to permanently delete this item?')
                            ? \$dispatch('deleteModel', { 'modelUrlAlias': '$modelUrlAlias', 'id': $modelId })
                            : event.stopImmediatePropagation();
                    JS,
                ])
            );
        }
        // If not soft deleted
        else {
            // Edit
            $instanceActions->put('edit', InstanceAction::make($this, 'Edit', 'fa fa-edit', 'primary')
                ->enableOnCondition(static::getPermission(ManageableModelPermissions::EDIT))
                ->setAction(route('wrla.manageable-models.edit', [
                    'modelUrlAlias' => $modelUrlAlias,
                    'id' => $modelId
                ]))
            );

            // Delete
            $instanceActions->put('delete', InstanceAction::make($this, 'Delete', 'fa fa-trash', 'danger')
                ->enableOnCondition(static::getPermission(ManageableModelPermissions::DELETE))
                ->setAdditionalAttributes([
                    'x-on:click' => <<<JS
                        confirm('Are you sure?')
                            ? \$dispatch('deleteModel', { 'modelUrlAlias': '$modelUrlAlias', 'id': $modelId })
                            : event.stopImmediatePropagation();
                    JS,
                ])
            );
        }

        return $instanceActions;
    }

    /**
     * Get default browse filters
     */
    public static function getDefaultBrowseFilters(): array
    {
        $defaultBrowseFilters = [
            static::getBrowseFilterSearch(),
            static::getBrowseFilterSoftDeleted()
        ];

        return $defaultBrowseFilters;
    }

    /**
     * Browse filter: Search
     */
    public static function getBrowseFilterSearch(): BrowseFilter
    {
        return Text::makeBrowseFilter('searchFilter', 'Search', 'fas fa-search text-slate-400')
                ->setAttributes([
                    'autofocus' => true,
                    'placeholder' => 'Search filter...',
                    'autocomplete' => "off"
                ])
                ->setOptions([
                    'mergeColumns' => []
                ])
                ->browseFilterApply(function(Builder $outerQuery, $table, $columns, $value) {
                    return $outerQuery->where(function ($query) use ($table, $columns, $value) {
                        $whereIndex = 0;

                        // Get all actual table columns (this is because we may have custom added columns from a preQuery)
                        $actualTableColumns = WRLAHelper::getTableColumns($table, (new (self::getBaseModelClass()))->getConnectionName());

                        foreach ($columns as $column => $label) {
                            // If column is int or begins with !, skip
                            if (is_int($column) || str_starts_with($column, '!')) {
                                continue;
                            }

                            // If column is relationship, then modify the column to be the related column
                            if ((WRLAHelper::isBrowseColumnRelationship($column))) {
                                // dump("Column is relationship: $column");
                                $relationshipParts = WRLAHelper::parseBrowseColumnRelationship($column);

                                $baseModelClass = self::getBaseModelClass();
                                $relationship = (new $baseModelClass)->{$relationshipParts[0]}();
                                if ($relationship?->getRelated() == null) {
                                    continue;
                                }
                                $relationshipTableName = $relationship->getRelated()->getTable();
                                $foreignColumn = $relationship->getForeignKeyName();

                                // If relationship connection is not empty, generate the SQL to inject it
                                if (! empty($relationshipConnection)) {
                                    $relationshipConnection = "`$relationshipConnection`.";
                                }

                                $whereIndex++;

                                // Safely escape value
                                $query->orWhereRelation($relationshipParts[0], "{$relationshipTableName}.{$relationshipParts[1]}", 'like', "%{$value}%");
                            }
                            // If table has this column, prepend table name
                            elseif(in_array($column, $actualTableColumns)) {
                                // dump("Column exists in table: $column");
                                // Force case-insensitive search using LOWER()
                                $column = "$table.$column";
                                $query->orWhereRaw("LOWER($column) LIKE ?", ['%' . strtolower($value) . '%']);
                            }
                            // Otherwise just use column name directly
                            else {
                                // dump("Column does not exist in table: $column");
                                $query->orHaving($column, 'like', "%{$value}%");
                            }
                        }
                    });
                });
    }

    /**
     * Browse filter: Soft deleted
     */
    public static function getBrowseFilterSoftDeleted(): ?BrowseFilter
    {
        if (WRLAHelper::isSoftDeletable(static::getBaseModelClass())) {
            return Select::makeBrowseFilter('softDeletedFilter')
                ->setLabel('Status', 'fas fa-heartbeat text-slate-400 !mr-1')
                ->setItems([
                    'not_trashed' => 'Active only',
                    'trashed' => 'Soft deleted only',
                    'all' => 'All',
                ])
                ->setOption('containerClass', 'w-1/6')
                ->validation('required|in:all,trashed,not_trashed')
                ->browseFilterApply(function (Builder $query, $table, $columns, $value) {
                    if ($value === 'not_trashed') {
                        return $query;
                    } elseif ($value === 'trashed') {
                        return $query->onlyTrashed();
                    } elseif ($value == 'all') {
                        return $query->withTrashed();
                    }

                    return $query;
                });
        }

        return null;
    }

    /**
     * Get instance actions and pass in the default instance actions.
     */
    final public function getInstanceActionsFinal(): Collection
    {
        // If ENABLED permission is false, return empty collection
        if (! static::getPermission(ManageableModelPermissions::ENABLED)) {
            return collect();
        }

        // If id is null, return empty collection
        if ($this->getModelInstance()->id == null) {
            return collect();
        }

        // Set currently active manageable model instance
        WRLAHelper::setCurrentActiveManageableModelInstance($this);

        return $this->getInstanceActions($this->getDefaultInstanceActions());
    }

    /**
     * Get browse columns (final) and make sure all values are BrowseColumn instances.
     */
    public function getBrowseColumnsFinal(): Collection
    {
        if (! static::getPermission(ManageableModelPermissions::ENABLED)) {
            return collect();
        }

        // If any of the values are strings, we convert into BrowseColumn instances
        return collect($this->getBrowseColumns())->map(function ($value, $key) {
            if ($value == null) {
                return null;
            }

            // Determine whether $value is already a BrowseColumn instance
            $valueIsBrowseColumn = $value instanceof BrowseColumnBase;

            // If value is not a BrowseColumn instance, then we convert it into a basic string BrowseColumn
            return $valueIsBrowseColumn ? $value : BrowseColumn::make($value);
        });
    }

    /**
     * Get manageable fields (final)
     */
    public function getManageableFieldsFinal(): array
    {
        // if (! static::getPermission(ManageableModelPermissions::ENABLED)) {
        //     return [];
        // }

        // Simply remove any null values from the manageable fields
        $manageableFields = once(fn () => array_filter($this->getManageableFields()));

        // Unpack any nested arrays within manageable fields
        $manageableFields = array_reduce($manageableFields, function ($carry, $item) {
            if (is_array($item)) {
                // Call ->beginGroup() on the first item
                $item[0]->beginGroup();
                // Call ->endGroup() on the last item
                end($item)->endGroup();

                return array_merge($carry, $item);
            }

            return array_merge($carry, [$item]);
        }, []);

        foreach ($manageableFields as $manageableField) {
            if ($manageableField->getType() == 'Wysiwyg') {
                $this->usesWysiwygEditor = true;
            }
        }

        return $manageableFields;
    }

    public static $instancelessManageableFields = [];

    /**
     * Get manageable field by name
     *
     * @param  string  $name
     */
    public function getManageableFieldByName(string $columnName): ?object
    {
        // If instanceless manageable fields are set for this class, return them
        if (array_key_exists(static::class, self::$instancelessManageableFields)) {
            $manageableFields = self::$instancelessManageableFields[static::class];
        }
        // Otherwise, set them
        else {
            self::$instancelessManageableFields[static::class] = $this->getManageableFieldsFinal();
            $manageableFields = self::$instancelessManageableFields[static::class];
        }

        // Loop through manageable fields and find the one with the matching name
        foreach ($manageableFields as $manageableField) {
            if ($manageableField->getAttribute('name') === $columnName) {
                return $manageableField;
            }
        }

        return null; // Return null if not found
    }

    /**
     * Get a json value with -> notation, eg: column->key1->key2
     *
     * @param  string  $key  The key in the json value.
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
        return data_get(json_decode((string) $modelInstance->{$column}, true), $dotNotation);
    }

    /**
     * Get value from model relationship, note this must also check whether uses -> notation for nested json
     *
     * @param  string  $key  The key, eg. relationship.column or relationship.column->key
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
        if ($relatedModel == null) {
            return null;
        }

        // If doesn't have -> notation then we can just return the relationship value
        if (! str_contains((string) $key, '->')) {
            return $relatedModel->{$key};
        }

        // If has -> notation then we need to get the json value from the relationship
        return $this->getInstanceJsonValue($key, $relatedModel);
    }

    /**
     * Get validation rules
     */
    public function getValidationRules(): Collection
    {
        $manageableFields = $this->getManageableFieldsFinal();

        $validationRules = collect();

        foreach ($manageableFields as $manageableField) {
            $validationRules->put($manageableField->getAttribute('name'), $manageableField->validationRules);
        }

        return $validationRules;
    }

    /**
     * Get form fields values array
     */
    public function getFormFieldsKeyValues(): array
    {
        $manageableFields = $this->getManageableFieldsFinal();

        $formFieldsValues = [];

        foreach ($manageableFields as $manageableField) {
            $formFieldsValues[$manageableField->getAttribute('name')] = $manageableField->getAttribute('value');
        }

        return $formFieldsValues;
    }

    /**
     * Run inline validation on manageable fields
     *
     * @return true|array If true then validation passed, if array (passed as [attribute.name => error]) then validation failed
     */
    public function runInlineValidation(Request $request): true|array
    {
        $manageableFields = $this->getManageableFieldsFinal();

        $messageBag = [];

        foreach ($manageableFields as $manageableField) {
            // If doesn't have inline validation then skip
            if (empty($manageableField->inlineValidationRules)) {
                continue;
            }

            // Get field name
            $fieldName = $manageableField->getAttribute('name');

            // Get input value
            $inputValue = $request->input($fieldName);

            // Run inline validation on the manageable field. If true then skip, if string message then fail
            $ifMessageThenFail = $manageableField->runInlineValidation($inputValue);

            // Now we pass back the attribute.name and message
            if ($ifMessageThenFail !== true) {
                $messageBag[$manageableField->getAttribute('name')] = $manageableField->getLabel().': '.$ifMessageThenFail;
            }
        }

        return empty($messageBag) ? true : $messageBag;
    }

    /**
     * Fill empty instance property values with default values from the table
     */
    public function fillEmptyInstanceAttributesWithDefaults(): void
    {
        // Get the table data for each column, allow for failure in case where this isn't a mysql database
        $tableData = static::getTableData();

        // Loop through (other than the specified ones and set all the default values)
        foreach ($tableData as $columnData) {
            $columnName = $columnData->Field;

            // If the column is 'id' or 'created_at' or 'updated_at' or 'deleted_at' then skip
            if (in_array($columnName, ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                continue;
            }

            // If the column is not set on the instance, then set the default value
            if (! $this->getModelInstance()->hasAttribute($columnName)) {
                $this->getModelInstance()->setAttribute($columnName, $columnData->Default);
            }
        }
    }

    /**
     * Get all columns
     * 
     * @return array
     */
    public static function getAllColumns(): array
    {
        // Get the table data for each column, allow for failure in case where this isn't a mysql database
        $tableData = static::getTableData();

        // Return the columns as an array
        return collect($tableData)->pluck('Field')->toArray();
    }

    /**
     * Store and get table data for the base model
     */
    public static function getTableData(): array
    {
        // If already set, return the table data
        $tableData = static::getStaticOption(static::class, 'tableData');
        if (! empty($tableData)) {
            return $tableData;
        }

        // Create dummy base model instance
        $modelInstance = new (static::getBaseModelClass());

        // Get table data
        try {
            $tableData = once(fn () => $modelInstance->getConnection()->select("SHOW COLUMNS FROM {$modelInstance->getTable()}")
            );
        } catch (\Exception) {
            return [];
        }

        // Store in static variable
        static::setStaticOption('tableData', $tableData);

        // Return
        return $tableData;
    }

    /**
     * Register instance action
     * @param callable $action
     * @return string
     */
    public function registerInstanceAction(callable $action): string
    {
        $actionKey = 'action'.count($this->registeredInstanceActions);
        $this->registeredInstanceActions[$actionKey] = $action;
        return $actionKey;
    }

    /**
     * Call a registered instance action by it's key
     * @param string $actionKey
     * @param array $parameters
     * @throws \Exception
     * @return string|RedirectResponse String shows message in browser alert message, RedirectResponse redirects to the specified URL
     */
    public function callInstanceAction(string $actionKey, array $parameters): mixed
    {
        if (! array_key_exists($actionKey, $this->registeredInstanceActions)) {
            throw new \Exception("Action not found: $actionKey");
        }

        return call_user_func($this->registeredInstanceActions[$actionKey], $this->getModelInstance(), $parameters);
    }

    /**
     * Create instance action button
     * 
     * @param null|callable|string $action Takes model instance, returns string message or RedirectResponse
     */
    public function instanceAction(string $text, ?string $icon = null, ?string $color = null, null|callable|string $action = null, null|bool|callable $enableOnCondition = null, ?array $additonalAttributes = null): InstanceAction
    {
        return InstanceAction::make($this, $text, $icon, $color, $action, $enableOnCondition, $additonalAttributes);
    }

    /**
     * Update the properties of the model instance based on the request and form data.
     *
     * @param  Request  $request  The HTTP request object.
     * @param  array  $formComponents  The array of form components.
     * @param  array  $formKeyValues  The array of form key-value pairs.+
     * @return bool|MessageBag Returns result of success, or a MessageBag of errors
     */
    public function updateModelInstanceProperties(Request $request, array $formComponents, array $formKeyValues): bool|MessageBag
    {
        // Perform any necessary actions before updating the model instance
        $request = $this->preUpdateModelInstance($request, $this->modelInstance);

        // Merge the request data into formKeyValues
        $formKeyValues = array_merge($formKeyValues, $request->all());

        // Check id request has any values that start with wrla_remove_ and apply to formKeyValues if so
        if (count($request->all()) > 0) {
            // Collect all keys that start with wrla_remove_ and have a value of true
            $removeKeys = collect($request->all())->filter(fn ($value, $key) => str_starts_with((string) $key, 'wrla_remove_') && $value === 'true');

            // If there are any keys to remove, set the special WRLA_KEY_REMOVE constant value and unset the original key
            if ($removeKeys->count() > 0) {
                $removeKeys->each(function ($value, $key) use (&$formKeyValues): void {
                    $keyWithoutRemovePrefix = ltrim($key, 'wrla_remove_');
                    $formKeyValues[$keyWithoutRemovePrefix] = WRLAHelper::WRLA_KEY_REMOVE;
                    unset($formKeyValues[$key]);
                });
            }
        }

        // Get the manageable fields for the model, and get form components as a collection
        $manageableFields = $this->getManageableFieldsFinal();
        $formComponents = collect($formComponents);

        // dd($manageableFields, $formComponents, $formKeyValues);

        // First do a loop through to check for any field names that use -> notation for nested json, if
        // we find any we put these last in the loop so that their values can be applied after everything else
        // $manageableFields = $manageableFields->sortBy(function ($manageableField) {
        //     return strpos($manageableField->getAttribute('name'), '->') !== false;
        // })->values()->toArray();

        // Iterate over each manageable field
        foreach ($manageableFields as $manageableField) {
            $fieldName = $manageableField->getAttribute('name');

            // If array key doesn't exist in form key values, we skip it
            if (! array_key_exists($fieldName, $formKeyValues)) {
                continue;
            }

            // Relationship setup
            $relationshipInstance = null;
            $isRelationshipField = $manageableField->isRelationshipField();

            // Is using nested JSON
            $isUsingNestedJson = $manageableField->isUsingNestedJson();

            // COME BACK TO DEBUG THIS, AS RELATIONSHIP JSON DOES NOT YET WORK
            // if(str_starts_with($fieldName, 'wrlaUserData__WRLA::REL::DOT__settings')) {
            //     dd(
            //         $fieldName,
            //         $this->getModelInstance()->hasAttribute($fieldName),
            //         $isRelationshipField,
            //     );
            // }

            // If doesn't exist on model instance and not a relationship, we just call apply submitted value final on it,
            // this is because the developer may need to run some logic seperate to the model instance
            if (! $this->getModelInstance()->hasAttribute($fieldName) && !$isRelationshipField && !$isUsingNestedJson) {
                $manageableField->applySubmittedValueFinal($request, $formKeyValues[$fieldName]);
                continue;
            }

            // If manageable field is relationship, we need to temporarily deal with the relationship instance instead
            if ($isRelationshipField) {
                $relationshipInstance = $manageableField->getRelationshipInstance();
                // dump($fieldName, $manageableField->getRelationshipFieldName(), $relationshipInstance->{$manageableField->getRelationshipFieldName()});
            }

            // Get the form component by name
            $formComponent = $formComponents->first(fn ($_formComponent) => $_formComponent->getAttribute('name') === $fieldName);

            // Check if the field name is based on a JSON column
            if ($isUsingNestedJson) {
                // If form key does not exist, then skip
                if (! array_key_exists($formComponent->getAttribute('name'), $formKeyValues)) {
                    continue;
                }

                [$fieldName, $jsonNotation] = WRLAHelper::parseJsonNotation($fieldName);
                $newValue = $formKeyValues[$formComponent->getAttribute('name')];
            }

            // Set field value to null
            $fieldValue = '';

            // Standard field value
            if (! $isUsingNestedJson) {
                // Apply the value to the form component and get the field value
                $fieldValue = $formComponent->applySubmittedValueFinal($request, $formKeyValues[$fieldName]);
            }
            // JSON notation
            else {
                // If is relation, get current field value from relationship instance
                if ($relationshipInstance != null) {
                    $fieldValue = json_decode((string) $relationshipInstance->{$manageableField->getRelationshipFieldName()});
                }
                // Otherwise, get the perhaps updated value from the model instance (we may be updating the same field with different JSON notation)
                else {
                    $fieldValue = json_decode((string) $this->modelInstance->{$fieldName});
                }

                // Apply the value to the form component and get the new value
                $newValue = $formComponent->applySubmittedValueFinal($request, $newValue);

                // If $newValue is valid JSON, we convert it to an array
                if (is_string($newValue) && WRLAHelper::isJson($newValue)) {
                    $newValue = json_decode($newValue, true);
                }

                // Set the new value using dot notation on the field value
                data_set($fieldValue, $jsonNotation, $newValue);

                // dd($fieldName, $jsonNotation, $fieldValue, $newValue);

                // Convert the field value to JSON
                if ($newValue instanceof MessageBag) {
                    return $newValue;
                } else {
                    $fieldValue = json_encode($fieldValue, JSON_UNESCAPED_SLASHES);
                }
            }

            // If field value an error bag something has failed, so return it instead of updating the model instance
            if ($fieldValue instanceof MessageBag) {
                return $fieldValue;
            } else {
                // If relationship instance is set
                if ($relationshipInstance != null) {
                    // Pre relationship field update hook
                    $this->preUpdateRelationshipInstanceField($this->modelInstance, $relationshipInstance, $manageableField->getRelationshipName(), $manageableField->getRelationshipFieldName(), $fieldValue);

                    // Update field and save the relationship instance
                    $relationshipInstance->{$manageableField->getRelationshipFieldName()} = $fieldValue;
                    $relationshipInstance->save();

                    // if(isset($jsonNotation) && str($jsonNotation)->contains('avatar')) {
                    //     dd($fieldName, $manageableField->getRelationshipFieldName(), $fieldValue, $relationshipInstance);
                    // }
                } else {
                    // Update the field value of the model instance
                    $this->modelInstance->{$fieldName} = $fieldValue;
                }
            }
        }

        return true;
    }

    /**
     * Pre update model instance hook. Note that this is called after validation but before the model is updated and saved.
     *
     * @param Request $request The HTTP request object.
     * @param mixed $model The model instance before changes are applied.
     */
    public function preUpdateModelInstance(Request $request, mixed $model): Request
    {
        // Override this method in your model to add custom logic before updating the model instance
        return $request;
    }

    /**
     * Pre update relationship instance field hook. Note that this is called after validation but before the relationship instance is updated and saved for each field it's associated with.
     *
     * @param  mixed  $modelInstance  The model instance.
     * @param  mixed  $relationshipInstance  The relationship instance (If it already exists)
     * @param  string  $relationshipName  The name of the relationship.
     * @param  string  $relationshipFieldName  The name of the field in the relationship.
     * @param  mixed  $fieldValue  The field value.
     */
    public function preUpdateRelationshipInstanceField(mixed &$modelInstance, mixed &$relationshipInstance, string $relationshipName, string $relationshipFieldName, mixed $fieldValue): void
    {
        // Override this method in your model to add custom logic before updating the relationship instance field
    }

    /**
     * Post update model instance hook. Note that this is called after validation and after the model is updated and saved.
     *
     * @param  Request  $request  The HTTP request object.
     * @param  mixed  $model  The model instance.
     */
    public function postUpdateModelInstance(Request $request, mixed $model): void
    {
        // Override this method in your model to add custom logic after updating the model instance
    }

    /**
     * Pre delete model instance hook
     */
    public function preDeleteModelInstance(Request $request, int $oldId, bool $soft): void
    {
        // Override this method in your model to add custom logic before deleting the model instance
    }

    /**
     * Post delete model instance hook
     */
    public function postDeleteModelInstance(Request $request, int $oldId, bool $soft): void
    {
        // Override this method in your model to add custom logic after deleting the model instance
    }

    /**
     * Is being created.
     */
    public function isBeingCreated(): bool
    {
        return $this->getModelInstance()->id == null;
    }

    /**
     * Get specific validation rule
     */
    public function getValidationRule($column): string
    {
        return $this->getValidationRules()->get($column);
    }

    /**
     * Note this must be called after getManageableFieldsFinal() as this sets the usesWysiwygEditor property
     */
    public function usesWysiwyg(): bool
    {
        return $this->usesWysiwygEditor;
    }

    /**
     * Get table columns
     */
    public static function getTableColumns(): array
    {
        $modelInstance = (new static)->getModelInstance();
        $table = str($modelInstance->getTable())->afterLast('.')->toString();
        $connection = $modelInstance->getConnectionName();

        return WRLAHelper::getTableColumns($table, $connection);
    }
}
