<?php

namespace WebRegulate\LaravelAdministration\Livewire\ManageableModels;

use Livewire\Component;
use WebRegulate\LaravelAdministration\Enums\PageType;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;
use WebRegulate\LaravelAdministration\Classes\ManageableModel;

/**
 * Class ManageableModelUpsert
 *
 * This class represents a Livewire component for upserting a manageable model.
 */
class ManageableModelUpsert extends Component
{
    /* Properties
    --------------------------------------------------------------------------*/

    /**
     * The class name of the manageable model.
     *
     * @var string
     */
    public string $manageableModelClass;

    /**
     * Upsert type
     *
     * @var PageType
     */
    public PageType $upsertType;

    /**
     * Model id, null if creating a new model.
     * 
     * @var ?int
     */
    public ?int $modelId = null;

    /* Livewire Methods / Hooks
    --------------------------------------------------------------------------*/

    /**
     * Mount the component.
     *
     * @param string $manageableModelClass The class name of the manageable model.
     * @param PageType $upsertType The type of upsert page.
     * @param ?int $modelId The id of the model to upsert, null if creating a new model.
     * @return \Illuminate\Http\RedirectResponse|null
     */
    public function mount(string $manageableModelClass, PageType $upsertType, ?int $modelId = null)
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

        // Set other properties
        $this->modelId = $modelId;
        $this->upsertType = $upsertType;
    }

    /**
     * Render the component.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function render()
    {
        $manageableModel = $this->manageableModelClass::make($this->modelId);

        return view(WRLAHelper::getViewPath('livewire.manageable-models.upsert'), [
            'manageableModel' => $manageableModel,
            'upsertType' => $this->upsertType,
            'usesWysiwyg' => $manageableModel->usesWysiwyg(),
        ]);
    }

    /* Methods
    --------------------------------------------------------------------------*/

    /**
     * Get manageable model instance
     * 
     * @return ManageableModel
     */
    public function getModelInstance(): ManageableModel {
        return new $this->manageableModelClass;
    }
}