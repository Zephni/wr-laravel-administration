<?php

namespace WebRegulate\LaravelAdministration\Livewire\ManageableModels;

use Livewire\Component;
use WebRegulate\LaravelAdministration\Classes\ManageableModel;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;

/**
 * Class ManageableModelUpsert
 *
 * This class is responsible for handling the upsert (create/update) functionality of manageable models.
 */
class ManageableModelUpsert extends Component
{
    /**
     * Manageable model instance.
     *
     * @var ManageableModel
     */
    private $manageableModel;

    /**
     * Mount the component.
     *
     * @param string $manageableModelClass The fully qualified class name of the manageable model.
     * @param mixed $modelId The ID of the model being upserted.
     * @return \Illuminate\Http\RedirectResponse|null
     */
    public function mount($manageableModelClass, $modelId = null)
    {
        // If the manageable model reference is null, redirect to the dashboard
        if (is_null($manageableModelClass)) {
            return redirect()->route('wrla.dashboard')->with('error', "Manageable model `$manageableModelClass` not found.");
        }

        // Get the manageable model and base model class
        $this->manageableModel = new $manageableModelClass();
        $modelClass = $manageableModelClass::getBaseModelClass();

        // If the model class does not exist, redirect to the dashboard
        if (!class_exists($modelClass)) {
            return redirect()->route('wrla.dashboard')->with('error', "Model `$modelClass` not found while loading manageable model `$manageableModelClass`.");
        }

        // If the model ID is null, create a new model instance
        if (is_null($modelId)) {
            $this->manageableModel->setModelInstance(new $modelClass());
        } else {
            // Find the model by its ID
            $this->manageableModel->setModelInstance($modelClass::find($modelId));

            // If the model is null, redirect to the dashboard
            if (is_null($this->manageableModel->modelInstance)) {
                return redirect()->route('wrla.dashboard')->with('error', "Model `$modelClass` with ID `$modelId` not found.");
            }
        }
    }

    /**
     * Render the component.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function render()
    {
        return view(WRLAHelper::getViewPath('livewire.manageable-models.upsert'), [
            'manageableModel' => $this->manageableModel,
        ]);
    }
}
