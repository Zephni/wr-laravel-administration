<?php

namespace WebRegulate\LaravelAdministration\Livewire\ManageableModels;

use Livewire\Component;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;

class ManageableModelBrowse extends Component
{
    public $modelUrlAlias;

    public function mount($modelUrlAlias)
    {
        $this->modelUrlAlias = $modelUrlAlias;
    }

    public function render()
    {
        return view(WRLAHelper::getViewPath('livewire.manageable-models.browse'));
    }
}
