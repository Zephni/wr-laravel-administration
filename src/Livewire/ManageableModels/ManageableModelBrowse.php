<?php

namespace WebRegulate\LaravelAdministration\Livewire\ManageableModels;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;
use WebRegulate\LaravelAdministration\Classes\ManageableModel;
use WebRegulate\LaravelAdministration\Enums\ManageableModelPermissions;
use WebRegulate\LaravelAdministration\Classes\BrowseColumns\BrowseColumnBase;

/**
 * Class ManageableModelBrowse
 *
 * This class represents a Livewire component for browsing manageable models.
 */
class ManageableModelBrowse extends Component
{
    use WithPagination;

    /* Properties
    --------------------------------------------------------------------------*/

    /**
     * The class name of the manageable model.
     *
     * @var string
     */
    public $manageableModelClass;

    /**
     * Columns to display (Collection of column names)
     *
     * @var \Illuminate\Support\Collection
     */
    public $columns = null;

    /**
     * Filters.
     *
     * @var array
     */
    public array $filters = [
        // 'search' => '',
        // 'showSoftDeleted' => false,
        // 'showAdminOnly' => false,
    ];

    /**
     * Order by.
     * 
     * @var string
     */
    public $orderBy = 'id';

    /**
     * Order direction.
     * 
     * @var string
     */
    public $orderDirection = 'desc';

    /**
     * Success message.
     * 
     * @var ?string
     */
    public $successMessage = null;

    /**
     * Error message.
     * 
     * @var ?string
     */
    public $errorMessage = null;

    /**
     * Debug message
     * 
     * @var ?string
     */
    public ?string $debugMessage = null;


    /* Livewire Methods / Hooks
    --------------------------------------------------------------------------*/

    /**
     * Mount the component.
     *
     * @param string $manageableModelClass The class name of the manageable model.
     * @param ?array $preFilters Pre filters passed from the AdminController::browse -> livewire-content view
     * @return \Illuminate\Http\RedirectResponse|null
     */
    public function mount(string $manageableModelClass, ?array $preFilters = null)
    {
        // If the manageable model reference is null, redirect to the dashboard
        if (is_null($manageableModelClass)) {
            return redirect()->route('wrla.dashboard')->with('error', "Manageable model `$manageableModelClass` not found.");
        }

        // Get the manageable model and base model class
        $this->manageableModelClass = $manageableModelClass;
        $manageableModelInstance = $this->getModelInstance()->withInstanceSetup();
        $modelClass = $manageableModelInstance::getBaseModelClass();

        // If the model class does not exist, redirect to the dashboard
        if(!class_exists($modelClass)) {
            return redirect()->route('wrla.dashboard')->with('error', "Model `$modelClass` not found while loading manageable model `$manageableModelClass`.");
        }

        // Build parent columns from manageable model
        $columns = $manageableModelInstance->getBrowseColumns();
        $this->columns = collect($columns)->map(function($column) {
            return $column instanceof BrowseColumnBase ? $column->label : $column;
        });

        // Get manageable model filter keys from collection
        $manageableModelFilters = $manageableModelClass::getBrowseFilters();
        foreach($manageableModelFilters as $key => $browseFilter) {
            $this->filters[$key] = $browseFilter->field->getAttribute('value');
        }

        // Check the pre filters and override default browse filters if so
        if(!empty($preFilters)) {
            foreach($preFilters as $key => $value) {
                if(array_key_exists($key, $this->filters)) {
                    $this->filters[$key] = $value;
                }
            }
        }

        // If ?delete is passed to the URL, delete the model
        if(request()->has('delete')) {
            $this->deleteModel(request()->input('delete'));
        }
    }

    /**
     * Get manageable model instance
     * 
     * @return ManageableModel
     */
    public function getModelInstance(): ManageableModel {
        return new $this->manageableModelClass;
    }

    /**
     * On search filter change.
     *
     * @return void
     */
    public function updatedSearch()
    {
        $manageableModelFilters = $this->getModelInstance()->getBrowseFilters();

        $validateArray = [];
        foreach($manageableModelFilters as $key => $browseFilter) {
            if(!empty($browseFilter->field->validationRules)) {
                $validateArray[$key] = $browseFilter->field->validationRules;
            }
        }

        $this->validate($validateArray);
    }

    /**
     * Order by
     * 
     * @param string $column
     * @param string $direction
     * @return void
     */
    public function reOrderAction(string $column, string $direction)
    {
        $this->orderBy = $column;
        $this->orderDirection = $direction;
        $this->debugMessage = "Order by $column $direction";
    }

    /**
     * Render the component.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function render()
    {
        $models = $this->browseModels();

        return view(WRLAHelper::getViewPath('livewire.manageable-models.browse'), [
            'models' => $models,
            'hasFilters' => $this->hasFilters(),
        ]);
    }

    /* Methods
    --------------------------------------------------------------------------*/

    /**
     * Get JSON reference columns
     * 
     * @return Collection
     */
    public function getJsonReferenceColumns()
    {
        return collect($this->columns)->filter(function($label, $column) {
            return strpos($column, '->') !== false;
        });
    }

    /**
     * Get relationship columns
     * 
     * @return Collection
     */
    public function getRelationshipColumns()
    {
        // Get any keys from columns that have a relationship
        return collect($this->columns)->filter(function($label, $column) {
            return WRLAHelper::isBrowseColumnRelationship($column);
        });
    }

    /**
     * Browse the models.
     *
     * @return LengthAwarePaginator | Collection
     */
    protected function browseModels()
    {        
        // Get table name
        $tableName = (new ($this->manageableModelClass::getStaticOption($this->manageableModelClass, 'baseModelClass')))->getTable();
        
        // If table does not exist in database, redirect to dashboard with error
        if(!WRLAHelper::tableExists($tableName)) {
            session()->flash('error', 'Table `' . $tableName . '` does not exist in the database.');
            $this->redirectRoute('wrla.dashboard');
            return collect([]); // We have to return a collection so that the view does not error
        }

        // Get Relationship and Json reference columns
        $relationshipColumns = $this->getRelationshipColumns();
        $jsonReferenceColumns = $this->getJsonReferenceColumns();

        // Start query builder
        $queryBuilder = $this->getModelInstance()::initialiseQueryBuilder();

        // We now just select all fields
        $queryBuilder = $queryBuilder->addSelect($tableName.'.*');

        // Relationship named columns look like this local_column::relationship_table.remote_column, so we need to split them
        // and add left joins and selects to the query
        if($relationshipColumns->count() > 0) {
            $tablesAlreadyJoined = [];

            // Add left joins and selects
            foreach($relationshipColumns as $column => $browseColumn) {
                $relationship = WRLAHelper::parseBrowseColumnRelationship($column);
                
                // If already joined just do the select part
                if(in_array($relationship['table'], $tablesAlreadyJoined)) {
                    $queryBuilder = $queryBuilder->addSelect("{$relationship['table']}.{$relationship['column']} as `{$relationship['table']}.{$relationship['column']}`");
                    continue;
                }

                $tablesAlreadyJoined[] = $relationship['table'];

                // If relationship is not the same table
                $queryBuilder = WRLAHelper::queryBuilderJoin(
                    $queryBuilder,
                    $relationship['table'],
                    $tableName.'.'.$relationship['local_column'], [
                        $relationship['table'] != $tableName
                            ? "{$relationship['table']}.id as `{$relationship['table']}.id`"
                            : "{$relationship['table']}_other.id as `{$relationship['table']}_other.id`",
                        
                        $relationship['table'] != $tableName
                            ? "{$relationship['table']}.{$relationship['column']} as `{$relationship['table']}.{$relationship['column']}`"
                            : "{$relationship['table']}_other.{$relationship['column']} as `{$relationship['table']}_other.{$relationship['column']}`"
                    ],
                    $relationship['table'] != $tableName ? null : $tableName.'_other'
                );
                
                // dd($queryBuilder->toRawSql(), $queryBuilder->get());

                // $queryBuilder = $queryBuilder->leftJoin($relationship[0], $relationship[0] . '.id', '=', $parts[0]);
                // $queryBuilder = $queryBuilder->addSelect($relationship[0] . '.' . $relationship[1] . ' as '.$relationship[0].'.' . $relationship[1]);
            }
        }

        // If Json reference columns exist, add them to the query
        if($jsonReferenceColumns->count() > 0) {
            foreach($jsonReferenceColumns as $column => $label) {
                // Note that the column can be nested any number of levels deep, for example: data->profile->avatar
                // With query builder, json_extract is already automatically added, so we just make a nice alias for the value
                $queryBuilder = $queryBuilder->addSelect($column . ' as ' . $column);
            }
        }

        // DD with current query string for debugging
        // dd($queryBuilder->toSql());

        // Now we loop through the filterable fields and apply them to the query
        $manageableModelFilters = $this->manageableModelClass::getBrowseFilters();

        foreach($manageableModelFilters as $key => $browseFilter) {
            $queryBuilder = $browseFilter->apply($queryBuilder, $tableName, $this->columns, $this->filters[$key]);
        }

        // For now just order by id DESC, but need to add post query and optional ordering etc to manageable models
        $queryBuilder = $queryBuilder->orderBy("$tableName.$this->orderBy", $this->orderDirection);

        $final = $queryBuilder->paginate(10);

        $this->debugMessage = $queryBuilder->toRawSql();

        return $final;
    }

    /**
     * Delete a model.
     *
     * @param string $modelUrlAlias The URL alias of the model to delete.
     * @param int $id The ID of the model to delete.
     */
    public function deleteModel(int $id)
    {
        // Get manageable model instance
        $manageableModel = new $this->manageableModelClass($id);

        // Check has delete permission
        if(!$this->manageableModelClass::getPermission(ManageableModelPermissions::DELETE->getString())) {
            $this->errorMessage = 'You do not have permission to delete this model.';
            return;
        }

        // Check that model URL alias matches the manageable model class URL alias
        // if($modelUrlAlias != $this->manageableModelClass::getUrlAlias()) {
        //     return;
        // }

        // Get base model class
        $baseModelClass = $this->manageableModelClass::getStaticOption($this->manageableModelClass, 'baseModelClass');

        // If model is not trashed already, find
        $model = $baseModelClass::find($id);

        // Set permanent check to false
        $permanent = 0;

        // If model found, soft delete
        if($model !== null) {
            $model = $baseModelClass::find($id);
            $model->delete();
        // Otherwise try finding with trashed and permanently delete
        } else {
            $model = $baseModelClass::withTrashed()->find($id);
            $model->forceDelete();
            $permanent = 1;
        }

        $this->successMessage = $this->manageableModelClass::getDisplayName()
            . ' #' . $id
            . ' '. ($permanent == 1 ? ' permanently deleted.' : ' deleted.');
    }

    /**
     * Restore a model.
     *
     * @param string $modelUrlAlias The URL alias of the model to restore.
     * @param int $id The ID of the model to restore.
     */
    public function restoreModel(int $id)
    {
        // Check that model URL alias matches the manageable model class URL alias
        // if($modelUrlAlias != $this->manageableModelClass::getUrlAlias()) {
        //     return;
        // }

        $baseModelClass = $this->manageableModelClass::getStaticOption($this->manageableModelClass, 'baseModelClass');
        $model = $baseModelClass::withTrashed()->find($id);
        $model->restore();
    }

    /**
     * Check if any filters are set.
     *
     * @return bool
     */
    public function hasFilters()
    {
        // Get the manageable model filters
        $manageableModelFilters = $this->manageableModelClass::getBrowseFilters();

        // Loop through the filters and compare their values with the default values
        foreach($this->filters as $key => $value) {
            // Return true If any filter value is different from the default value
            if($value != $manageableModelFilters[$key]) {
                return true;
            }
        }

        // Return false if no filters are set
        return false;
    }
}
