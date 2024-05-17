<?php

namespace WebRegulate\LaravelAdministration\Livewire\ManageableModels;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;
use WebRegulate\LaravelAdministration\Classes\WRLAPermissions;
use WebRegulate\LaravelAdministration\Classes\BrowseableColumn;

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


    /* Livewire Methods / Hooks
    --------------------------------------------------------------------------*/

    /**
     * Mount the component.
     *
     * @param string $manageableModelClass The class name of the manageable model.
     * @return \Illuminate\Http\RedirectResponse|null
     */
    public function mount($manageableModelClass)
    {
        // If the manageable model reference is null, redirect to the dashboard
        if (is_null($manageableModelClass)) {
            return redirect()->route('wrla.dashboard')->with('error', "Manageable model `$manageableModelClass` not found.");
        }

        // Get the manageable model and base model class
        $this->manageableModelClass = $manageableModelClass;
        $modelClass = $manageableModelClass::getBaseModelClass();

        // If the model class does not exist, redirect to the dashboard
        if(!class_exists($modelClass)) {
            return redirect()->route('wrla.dashboard')->with('error', "Model `$modelClass` not found while loading manageable model `$manageableModelClass`.");
        }

        // Build parent columns from manageable model
        $columns = $manageableModelClass::make()->getBrowseableColumns();
        $this->columns = collect($columns)->map(function($column) {
            return $column instanceof BrowseableColumn ? $column->label : $column;
        });

        // Get manageable model filter keys from collection
        $manageableModelFilters = $manageableModelClass::getBrowseFilters();
        foreach($manageableModelFilters as $key => $browseFilter) {
            $this->filters[$key] = $browseFilter->field->getAttribute('value');
        }

        // If ?delete is passed to the URL, delete the model
        if(request()->has('delete')) {
            $this->deleteModel(request()->input('delete'));
        }
    }

    /**
     * On search filter change.
     *
     * @return void
     */
    public function updatedSearch()
    {
        $manageableModelFilters = $this->manageableModelClass::getBrowseFilters();

        $validateArray = [];
        foreach($manageableModelFilters as $key => $browseFilter) {
            if(!empty($browseFilter->field->validationRules)) {
                $validateArray[$key] = $browseFilter->field->validationRules;
            }
        }

        $this->validate($validateArray);
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
        // Get any keys from columns that contain '::'
        return collect($this->columns)->filter(function($label, $column) {
            return strpos($column, '::') !== false;
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
        $tableName = (new $this->manageableModelClass::$baseModelClass)->getTable();
        
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
        $queryBuilder = $this->manageableModelClass::$baseModelClass::query();

        // Add selects for id, and all columns that don't have a relationship
        // $selectCols = [$tableName.'.id'];
        // $selectCols = array_merge($selectCols, array_diff(array_keys($this->columns->toArray()), $relationshipColumns->keys()->toArray()));
        // $queryBuilder = $queryBuilder->addSelect($selectCols);

        // // Append select deleted_at if $manageableModel::isSoftDeletable()
        // if($this->manageableModelClass::isSoftDeletable()) {
        //     $queryBuilder = $queryBuilder->addSelect($tableName.'.deleted_at');
        // }

        // We now just select all fields
        $queryBuilder = $queryBuilder->addSelect($tableName.'.*');

        // Relationship columns look like this column::relationship.display_column, so we need to split them
        // and add left joins and selects to the query
        if($relationshipColumns->count() > 0) {
            // Add left joins and selects
            foreach($relationshipColumns as $column => $label) {
                $parts = explode('::', $column);
                $relationship = explode('.', $parts[1]);
                $queryBuilder = $queryBuilder->leftJoin($relationship[0], $relationship[0] . '.id', '=', $parts[0]);
                $queryBuilder = $queryBuilder->addSelect($relationship[0] . '.' . $relationship[1] . ' as ' . $parts[0]);
            }
        }

        // TODO: If Json reference columns exist, add them to the query
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
            $queryBuilder = $browseFilter->apply($queryBuilder, $this->columns, $this->filters[$key]);
        }

        // For now just order by id DESC, but need to add post query and optional ordering etc to manageable models
        $queryBuilder = $queryBuilder->orderBy($tableName . '.id', 'DESC');

        return $queryBuilder->paginate(10);
    }

    /**
     * Delete a model.
     *
     * @param string $modelUrlAlias The URL alias of the model to delete.
     * @param int $id The ID of the model to delete.
     * @param int $permanent Whether to force delete the model.
     */
    public function deleteModel(int $id, int $permanent = 0)
    {
        // Get manageable model instance
        $manageableModel = new $this->manageableModelClass($id);

        // Check manage model has permission to delete
        if(!($manageableModel->permissions())->hasPermission(WRLAPermissions::DELETE)) {
            $this->errorMessage = 'You do not have permission to delete this model.';
            return;
        }

        // Check that model URL alias matches the manageable model class URL alias
        // if($modelUrlAlias != $this->manageableModelClass::getUrlAlias()) {
        //     return;
        // }

        // If permanent, force delete
        if($permanent == 1) {
            $model = $this->manageableModelClass::$baseModelClass::withTrashed()->find($id);
            $model->forceDelete();

        // Else, soft delete
        } else {
            $model = $this->manageableModelClass::$baseModelClass::find($id);
            $model->delete();
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

        $model = $this->manageableModelClass::$baseModelClass::withTrashed()->find($id);
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
