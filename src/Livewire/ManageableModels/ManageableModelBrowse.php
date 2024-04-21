<?php

namespace WebRegulate\LaravelAdministration\Livewire\ManageableModels;

use Livewire\Component;
use Livewire\WithPagination;
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
     * Search query.
     *
     * @var string
     */
    public $search = '';


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
            'search' => 'string|max:100',
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
        ]);
    }

    /* Methods
    --------------------------------------------------------------------------*/

    /**
     * Browse the models.
     */
    protected function browseModels()
    {
        if($this->search == '') {
            return $this->manageableModelClass::$baseModelClass::paginate(1);
        }
        else {
            return $this->manageableModelClass::$baseModelClass::where(function($query) {
                foreach($this->columns as $column => $label) {
                    $query->orWhere($column, 'like', "%$this->search%");
                }
            })->paginate(1);
        }
    }
}
