<?php

namespace WebRegulate\LaravelAdministration\Livewire\ManageableModels;

use Livewire\Component;
use WebRegulate\LaravelAdministration\Classes\ManageableModel;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;

class ManageableModelUpsert extends Component
{
    public $model;

    public function mount($modelUrlAlias, $modelId = null)
    {
        // Find the manageable model reference by its URL alias
        $manageableModelReference = ManageableModel::getByUrlAlias($modelUrlAlias);

        // If the manageable model reference is null, redirect to the dashboard
        if (is_null($manageableModelReference)) {
            return redirect()->route('wrla.dashboard')->with('error', "Manageable model `$modelUrlAlias` not found.");
        }

        // Get the model class
        $modelClass = $manageableModelReference::$baseModel;

        // If the model class is null, redirect to the dashboard
        if (is_null($modelClass)) {
            return redirect()->route('wrla.dashboard')->with('error', "Model class `$modelClass` not found from manageable model `$modelUrlAlias`.");
        }

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
