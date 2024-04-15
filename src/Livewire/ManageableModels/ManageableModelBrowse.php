<?php

namespace WebRegulate\LaravelAdministration\Livewire\ManageableModels;

use Livewire\Component;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;

class ManageableModelBrowse extends Component
{
    public $manageableModelClass;

    public function mount($manageableModelClass)
    {
        // If the manageable model reference is null, redirect to the dashboard
        if (is_null($manageableModelClass)) {
            return redirect()->route('wrla.dashboard')->with('error', "Manageable model `$manageableModelClass` not found.");
        }

        // Get the manageable model and base model class
        $this->manageableModelClass = $manageableModelClass;
        $modelClass = $manageableModelClass::getBaseModel();

        // If the model class does not exist, redirect to the dashboard
        if(!class_exists($modelClass)) {
            return redirect()->route('wrla.dashboard')->with('error', "Model `$modelClass` not found while loading manageable model `$manageableModelClass`.");
        }
    }

    public function render()
    {
        return view(WRLAHelper::getViewPath('livewire.manageable-models.browse'));
    }
}
