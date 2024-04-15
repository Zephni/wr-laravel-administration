<?php

namespace WebRegulate\LaravelAdministration\Livewire\ManageableModels;

use Livewire\Component;
use WebRegulate\LaravelAdministration\Classes\ManageableModel;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;

class ManageableModelUpsert extends Component
{
    public $manageableModelClass;
    public $model;

    public function mount($manageableModelClass, $modelId = null)
    {
        // If the manageable model reference is null, redirect to the dashboard
        if (is_null($manageableModelClass)) {
            return redirect()->route('wrla.dashboard')->with('error', "Manageable model `$manageableModelClass` not found.");
        }

        // Get the base model class
        $modelClass = $manageableModelClass::$baseModel;

        // If the model ID is null, create a new model instance
        if (is_null($modelId)) {
            $this->model = new $modelClass();
        } else {
            // Find the model by its ID
            $this->model = $modelClass::find($modelId);

            // If the model is null, redirect to the dashboard
            if (is_null($this->model)) {
                return redirect()->route('wrla.dashboard')->with('error', "Model `$modelClass` with ID `$modelId` not found.");
            }
        }
    }

    public function render()
    {
        return view(WRLAHelper::getViewPath('livewire.manageable-models.upsert'));
    }
}
