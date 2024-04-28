<?php

namespace WebRegulate\LaravelAdministration\Livewire\ManageableModels;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Pagination\LengthAwarePaginator;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;

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
     * @var string
     */
    public $filters = [
        'search' => '',
        'showSoftDeleted' => false,
    ];


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

        // Build columns from manageable model
        $this->columns = $manageableModelClass::getBrowsableColumns();
    }

    /**
     * On search filter change.
     *
     * @return void
     */
    public function updatedSearch()
    {
        // Validate
        $this->validate([
            'search' => 'string|min:3|max:100',
        ]);
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
     * Browse the models.
     *
     * @return LengthAwarePaginator
     */
    protected function browseModels()
    {
        // Start query builder
        $queryBuilder = $this->manageableModelClass::$baseModelClass::query();

        // Search
        if($this->filters['search'] != '') {
            $queryBuilder = $queryBuilder->where(function($query) {
                foreach($this->columns as $column => $label) {
                    $query->orWhere($column, 'like', '%'.$this->filters['search'].'%');
                }
            });
        }

        // Soft deleted
        if($this->filters['showSoftDeleted']) {
            $queryBuilder = $queryBuilder->whereNotNull('deleted_at')->withTrashed();
        }

        return $queryBuilder->paginate(10);
    }

    /**
     * Delete a model.
     *
     * @param string $modelUrlAlias The URL alias of the model to delete.
     * @param int $id The ID of the model to delete.
     * @param int $permanent Whether to force delete the model.
     */
    public function deleteModel(string $modelUrlAlias, int $id, int $permanent = 0)
    {
        // Check that model URL alias matches the manageable model class URL alias
        if($modelUrlAlias != $this->manageableModelClass::getUrlAlias()) {
            return;
        }

        // If permanent, force delete
        if($permanent == 1) {
            $model = $this->manageableModelClass::$baseModelClass::withTrashed()->find($id);
            $model->forceDelete();

        // Else, soft delete
        } else {
            $model = $this->manageableModelClass::$baseModelClass::find($id);
            $model->delete();
        }
    }

    /**
     * Restore a model.
     *
     * @param string $modelUrlAlias The URL alias of the model to restore.
     * @param int $id The ID of the model to restore.
     */
    public function restoreModel(string $modelUrlAlias, int $id)
    {
        // Check that model URL alias matches the manageable model class URL alias
        if($modelUrlAlias != $this->manageableModelClass::getUrlAlias()) {
            return;
        }

        $model = $this->manageableModelClass::$baseModelClass::withTrashed()->find($id);
        $model->restore();
    }

    /**
     * Check if any filters are set
     *
     * @return bool
     */
    public function hasFilters()
    {
        return $this->filters['search'] != '' || $this->filters['showSoftDeleted'];
    }
}
