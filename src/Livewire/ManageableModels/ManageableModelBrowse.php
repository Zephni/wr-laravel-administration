<?php

namespace WebRegulate\LaravelAdministration\Livewire\ManageableModels;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\StreamedResponse;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;
use WebRegulate\LaravelAdministration\Classes\ManageableModel;
use WebRegulate\LaravelAdministration\Classes\CSVHelper;
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
     * Filters (array of BrowseFilter's). The manageable model's filters key => value pairs. key being the
     * filter key and value being the current value of the filter.
     *
     * @var array
     */
    public array $filters = [];

    /**
     * Dynamic filter inputs
     *
     * @var array
     */
    public array $dynamicFilterInputs = [];

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

    /**
     * Renders
     *
     * @var int
     */
    public int $renders = 0;

    /**
     * Listeners
     *
     * @var array
     */
    protected $listeners = [
        'filtersUpdatedOutside' => 'filtersUpdatedOutside',
        'deleteModel' => 'deleteModel',
    ];

    /* Livewire Methods / Hooks
    --------------------------------------------------------------------------*/

    /**
     * Filters updated outside
     *
     * @param array $dynamicFilterInputs
     * @param array $filters
     */
    public function filtersUpdatedOutside(array $dynamicFilterInputs): void
    {
        $this->dynamicFilterInputs = $dynamicFilterInputs;

        // foreach($dynamicFilterInputs as $item) {
        //     $this->filters[$item['field']] = $item['value'];
        // }
    }

    /**
     * Updates fields
     *
     * @param string $field
     * @return void
     */
    public function updatedFilters(string $field): void
    {
        $this->resetPage();
    }

    /**
     *
     */

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
        $manageableModelInstance = $this->getModelInstance();
        $modelClass = $manageableModelInstance::getBaseModelClass();

        // If the model class does not exist, redirect to the dashboard
        if(!class_exists($modelClass)) {
            return redirect()->route('wrla.dashboard')->with('error', "Model `$modelClass` not found while loading manageable model `$manageableModelClass`.");
        }

        // Run browse setup method
        $this->manageableModelClass::browseSetupFinal($this->filters);

        // Build parent columns from manageable model
        $columns = $manageableModelInstance->getBrowseColumns();
        $this->columns = collect($columns)->map(function($column) {
            return $column instanceof BrowseColumnBase ? $column->label : $column;
        });

        // Get manageable model filter keys from collection
        $manageableModelFilters = $manageableModelClass::getBrowseFilters();

        foreach($manageableModelFilters as $browseFilter) {
            $this->filters[$browseFilter->getKey()] = $browseFilter->getField()->getValue();
        }

        // Check the pre filters and override default browse filters if so
        if(!empty($preFilters)) {
            foreach($preFilters as $key => $value) {
                if(array_key_exists($key, $this->filters)) {
                    $this->filters[$key] = $value;
                }
            }
        }

        // Apply default order by and order direction
        $orderByData = $manageableModelClass::getDefaultOrderBy();
        $this->orderBy = $orderByData->get('column');
        $this->orderDirection = $orderByData->get('direction');
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
     * Export as CSV action
     *
     * @return StreamedResponse
     */
    public function exportAsCSVAction(): StreamedResponse
    {
        // Get current data set
        $models = collect($this->browseModels()->all());

        // Get all headings (array of all column names)
        $headings = array_keys($models->first()->toArray());

        // Get all row data
        $rowData = $models->sortBy('id')->values()->toArray();

        // Use CSVHelper to build CSV
        return CSVHelper::build(
            $this->manageableModelClass::getDisplayName(true) . '-' . date('Y-m-d H:i') . '.csv',
            $headings,
            $rowData
        );
    }

    /**
     * Render the component.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function render()
    {
        $this->renders++;
        $models = $this->browseModels();

        return view(WRLAHelper::getViewPath('livewire.manageable-models.browse'), [
            'models' => $models,
            'hasFilters' => $this->hasFilters(),
        ]);
    }

    /* Methods
    --------------------------------------------------------------------------*/

    /**
     * Get standard columns (non json reference or relationship columns)
     *
     * @return Collection
     */
    public function getStandardColumns()
    {
        return collect($this->columns)->filter(function($label, $column) {
            return !WRLAHelper::isBrowseColumnRelationship($column) && strpos($column, '->') === false;
        });
    }

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
     * @return LengthAwarePaginator
     */
    protected function browseModels(): LengthAwarePaginator
    {
        // get base model class and instance
        $baseModelClass = $this->manageableModelClass::getBaseModelClass();
        $baseModelInstance = new $baseModelClass;

        // Run browse setup method
        if($this->renders > 1) {
            $this->manageableModelClass::browseSetupFinal($this->filters);
        }

        // Get connection and table name
        $tableName = $baseModelInstance->getTable();

        // If table does not exist in database, redirect to dashboard with error
        if(!WRLAHelper::tableExists($baseModelInstance, $tableName)) {
            session()->flash('error', 'Table `' . $tableName . '` does not exist in the database.');
            $this->redirectRoute('wrla.dashboard');
            // We have to return an empty paginator so that the view does not error
            return new LengthAwarePaginator([], 0, 18);
        }

        // Get all types of columns
        // TODO: This could much more efficient if we filtered from a single collection of columns
        $standardColumns = $this->getStandardColumns();
        $relationshipColumns = $this->getRelationshipColumns();
        $jsonReferenceColumns = $this->getJsonReferenceColumns();
        $orderByIsRelationship = WRLAHelper::isBrowseColumnRelationship($this->orderBy);

        // Start eloquent query
        $eloquent = $baseModelClass::query();

        // Select any fields that aren't relationships or json references
        $eloquent = $eloquent->addSelect("$tableName.*");

        // Relationship named columns look like this relationship->remote_column, so we need to split them
        // and add left joins and selects to the query
        $joinsMade = [];
        if($relationshipColumns->count() > 0) {
            // We used to use localcolumn::relationship_table.remote_column, but now we use relationship_method->remote_column
            // So we can just use the relationship method to get the relationship
            foreach($relationshipColumns as $relationshipKey => $label) {
                // Get the relationship method and remote column
                [$relationshipMethod, $remoteColumn] = WRLAHelper::parseBrowseColumnRelationship($relationshipKey);

                // Get relation information
                $relation = $eloquent->getRelation($relationshipMethod);
                if($relation === null) continue;

                // With relationship
                $eloquent->with($relationshipMethod);

                // Get related data
                $related = $relation->getRelated();
                $relationTable = $related->getTable();

                // If join already made, skip
                if(in_array($relationTable, $joinsMade)) {
                    continue;
                }

                $eloquent = $eloquent->leftJoinRelation($relationshipMethod);

                // Add to joins made (And check for any joins added by the relationship's joins)
                $joinsMade[] = $relationTable;
                foreach($eloquent->getQuery()->joins as $join) {
                    if(in_array($join->table, $joinsMade)) continue;
                    $joinsMade[] = $join->table;
                }
            }
        }

        // If Json reference columns exist, add them to the query
        if($jsonReferenceColumns->count() > 0) {
            foreach($jsonReferenceColumns as $column => $label) {
                [$relationshipMethod, $remoteColumn] = WRLAHelper::parseBrowseColumnRelationship($column);
                $relation = $eloquent->getRelation($relationshipMethod);
                if($relation === null) continue;
                $related = $relation->getRelated();

                // If in relationship columns
                if($relationshipColumns->has($column)) {
                    $relationTable = $related->getTable();
                    $eloquent->addSelect("{$relationTable}.$remoteColumn as $column");
                    continue;
                }

                // Note that the column can be nested any number of levels deep, for example: data->profile->avatar
                // With query builder, json_extract is already automatically added, so we just make a nice alias for the value
                $eloquent = $eloquent->addSelect("{$column} as $column");
            }
        }

        // Now we loop through the filterable fields and apply them to the query
        $manageableModelFilters = [];

        if(empty($this->dynamicFilterInputs))
        {
            $manageableModelFilters = $this->manageableModelClass::getBrowseFilters($this->filters);

            foreach($manageableModelFilters as $browseFilter) {
                $key = $browseFilter->getKey();

                if(empty($this->dynamicFilterInputs)) {
                    if(empty($this->filters[$key])) {
                        continue;
                    }

                    $eloquent = $browseFilter->apply($eloquent, $tableName, $this->columns, $this->filters[$key]);
                }
            }
        }
        else
        {
            foreach($this->dynamicFilterInputs as $item) {
                $browseFilter = ManageableModelDynamicBrowseFilters::buildBrowseFilter($item);
                $browseFilter->field->setAttribute('value', $item['value'] ?? '');
                $manageableModelFilters[] = $browseFilter;

                $eloquent = $browseFilter->apply($eloquent, $tableName, $this->columns, $browseFilter->field->getValue());
            }
        }


        // Order by
        // If orderBy is standard column, order by that column
        if(!$orderByIsRelationship) {
            $eloquent = $eloquent->orderBy("$tableName.$this->orderBy", $this->orderDirection);
        // If orderBy is a relationship column, order by the relationship column
        } else {
            // Get relationship method and remote column
            [$relationshipMethod, $remoteColumn] = WRLAHelper::parseBrowseColumnRelationship($this->orderBy);

            // Get relation information
            $relation = $eloquent->getRelation($relationshipMethod);

            // TODO: This needs fixing as we cannot currently order by through style relationships eg. $this->relationship->belongsTo()
            if($relation !== null) {
                $related = $relation->getRelated();
                $relationTable = $related->getTable();

                // Apply join for relationship and order by relationship column (if not already joined)
                if(!in_array($relationTable, $joinsMade)) {
                    $eloquent = $eloquent->leftJoinRelation($relationshipMethod);
                    $joinsMade[] = $relationTable;
                }

                // Order by relationship column
                $eloquent = $eloquent->orderBy("$relationTable.$remoteColumn", $this->orderDirection);
            }
        }

        $this->debugMessage = $eloquent->toRawSql();

        $final = $eloquent->paginate(18);

        return $final;
    }

    /**
     * Delete a model.
     *
     * @param string $modelUrlAlias The URL alias of the model to delete.
     * @param int $id The ID of the model to delete.
     */
    public function deleteModel(string $modelUrlAlias, int $id)
    {
        // Get manageable model instance
        $manageableModel = new $this->{'manageableModelClass'}($id);

        // Check that model URL alias matches the manageable model class URL alias
        if($modelUrlAlias != $this->manageableModelClass::getUrlAlias()) {
            $this->errorMessage = 'Model URL alias does not match the manageable model class URL alias.';
            return;
        }

        // Delete the model and deconstruct the response
        [$success, $message] = WRLAHelper::deleteModel($manageableModel, $id);

        if($success) {
            $this->successMessage = $message;
            $this->errorMessage = null;
        } else {
            $this->errorMessage = $message;
            $this->successMessage = null;
        }
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
        // If manageable model uses dynamic browse filters, return true
        if($this->manageableModelClass::getStaticOption($this->manageableModelClass, 'browse.useDynamicFilters')) {
            return true;
        }

        // Loop through the filters and compare their values with the default values
        foreach($this->filters as $value) {
            // Return true If any filter value is not null
            if($value != null) {
                return true;
            }
        }

        // Return false if no filters are set
        return false;
    }
}
